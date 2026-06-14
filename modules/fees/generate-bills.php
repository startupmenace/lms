<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Generate Bills';
$classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");
$structures = db_get_all("SELECT * FROM fee_structures WHERE is_active = 1 ORDER BY name");

$selected_class = (int)($_GET['class_id'] ?? 0);
$selected_structure = (int)($_GET['structure_id'] ?? 0);
$selected_term = $_GET['term'] ?? '';
$selected_session = $_GET['session'] ?? date('Y') . '-' . (date('Y') + 1);
$preview = $_GET['preview'] ?? 0;

$fee_types = [];
$students = [];

if ($selected_class && $selected_structure && $preview) {
    $fee_types = db_get_all("SELECT * FROM fee_types WHERE fee_structure_id = ? ORDER BY is_optional, category", [$selected_structure]);
    $structure = db_get_row("SELECT * FROM fee_structures WHERE id = ?", [$selected_structure]);
    $students = db_get_all("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.class_id = ? AND s.is_active = 1 ORDER BY s.parent_name", [$selected_class]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $class_id = (int)$_POST['class_id'];
    $structure_id = (int)$_POST['structure_id'];
    $term = sanitize($_POST['term']);
    $session = sanitize($_POST['session']);
    $structure = db_get_row("SELECT * FROM fee_structures WHERE id = ?", [$structure_id]);
    $base_types = db_get_all("SELECT * FROM fee_types WHERE fee_structure_id = ? AND is_optional = 0", [$structure_id]);

    $base_total = array_sum(array_column($base_types, 'amount'));
    $base_tax = 0;
    foreach ($base_types as $bt) {
        $base_tax += $bt['amount'] * ($bt['tax_percent'] / 100);
    }
    $base_total += $base_tax;

    $student_ids = $_POST['student_id'] ?? [];
    $extras_data = $_POST['extras'] ?? [];
    $extras_labels = $_POST['extras_label'] ?? [];
    $extras_amounts = $_POST['extras_amount'] ?? [];

    $created = 0;
    foreach ($student_ids as $sid) {
        $sid = (int)$sid;
        $existing = db_get_row("SELECT id FROM transactions WHERE student_id = ? AND fee_structure_id = ? AND term = ? AND session_year = ?", [$sid, $structure_id, $term, $session]);
        if ($existing) continue;

        $extras = [];
        $extra_total = 0;
        if (isset($extras_data[$sid])) {
            foreach ($extras_data[$sid] as $i => $val) {
                if (!empty($extras_labels[$sid][$i]) && !empty($extras_amounts[$sid][$i])) {
                    $label = sanitize($extras_labels[$sid][$i]);
                    $amount = (float)$extras_amounts[$sid][$i];
                    $extras[] = ['label' => $label, 'amount' => $amount];
                    $extra_total += $amount;
                }
            }
        }

        $line_items = [];
        foreach ($base_types as $bt) {
            $line_items[] = [
                'category' => $bt['category'],
                'amount' => (float)$bt['amount'],
                'tax_percent' => (float)$bt['tax_percent'],
                'type' => 'base'
            ];
        }
        foreach ($extras as $ex) {
            $line_items[] = [
                'category' => $ex['label'],
                'amount' => $ex['amount'],
                'tax_percent' => 0,
                'type' => 'extra'
            ];
        }

        $total = $base_total + $extra_total;
        $prefix = $structure['prefix'] ?? 'INV';
        $receipt_num = $structure['receipt_start'] + $sid;
        $invoice_no = $prefix . '-' . $receipt_num . '-' . time();

        db_insert(
            "INSERT INTO transactions (student_id, fee_structure_id, term, session_year, invoice_no, total_amount, line_items, paid_amount, due_amount, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, 'pending', NOW())",
            [$sid, $structure_id, $term, $session, $invoice_no, $total, json_encode($line_items), $total]
        );
        $created++;
    }

    set_flash('success', "$created bill(s) generated successfully.");
    redirect('index.php?tab=collection');
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900"><i class="fas fa-file-invoice text-teal-600 mr-2"></i>Generate Bills</h1>
        <a href="index.php?tab=collection" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition"><i class="fas fa-arrow-left mr-2"></i>Back</a>
    </div>

    <?php if (has_flash('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('error') ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Class <span class="text-red-500">*</span></label>
                <select name="class_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selected_class == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?> (<?= db_get_row("SELECT COUNT(*) as c FROM students WHERE class_id=? AND is_active=1", [$c['id']])['c'] ?> students)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fee Structure <span class="text-red-500">*</span></label>
                <select name="structure_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                    <option value="">Select Structure</option>
                    <?php foreach ($structures as $s):
                        $linked = db_get_row("SELECT COUNT(*) as c FROM fee_structure_classes WHERE fee_structure_id=? AND class_id=?", [$s['id'], $selected_class]);
                        if ($selected_class && !$linked['c']) continue;
                    ?>
                    <option value="<?= $s['id'] ?>" <?= $selected_structure == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['name']) ?> (<?= ucfirst($s['frequency']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Term</label>
                <select name="term" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="Term 1" <?= $selected_term == 'Term 1' ? 'selected' : '' ?>>Term 1</option>
                    <option value="Term 2" <?= $selected_term == 'Term 2' ? 'selected' : '' ?>>Term 2</option>
                    <option value="Term 3" <?= $selected_term == 'Term 3' ? 'selected' : '' ?>>Term 3</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Session</label>
                <input type="text" name="session" value="<?= sanitize($selected_session) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div class="flex items-end">
                <input type="hidden" name="preview" value="1">
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition w-full"><i class="fas fa-search mr-1"></i> Preview</button>
            </div>
        </form>
    </div>

    <?php if ($preview && $selected_class && $selected_structure): ?>
    <?php
    $structure = db_get_row("SELECT * FROM fee_structures WHERE id = ?", [$selected_structure]);
    $base_types = array_filter($fee_types, fn($ft) => !$ft['is_optional']);
    $optional_types = array_filter($fee_types, fn($ft) => $ft['is_optional']);
    $base_total = 0;
    foreach ($base_types as $bt) {
        $base_total += $bt['amount'] + ($bt['amount'] * $bt['tax_percent'] / 100);
    }
    ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">
                Preview: <?= sanitize($structure['name']) ?> — <?= sanitize($selected_term) ?> <?= sanitize($selected_session) ?>
                <span class="text-sm font-normal text-gray-500 ml-2">(<?= count($students) ?> students)</span>
            </h3>
            <div class="text-xs text-gray-500 mt-1">
                Base fee per student: <strong><?= format_currency($base_total) ?></strong>
                <?php if (!empty($optional_types)): ?>
                | Optional extras available: <?= implode(', ', array_map(fn($ot) => sanitize($ot['category']) . ' (' . format_currency($ot['amount']) . ')', $optional_types)) ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($students)): ?>
        <div class="p-12 text-center text-gray-400">No active students in this class.</div>
        <?php else: ?>
        <form method="POST" id="billForm">
            <input type="hidden" name="class_id" value="<?= $selected_class ?>">
            <input type="hidden" name="structure_id" value="<?= $selected_structure ?>">
            <input type="hidden" name="term" value="<?= sanitize($selected_term) ?>">
            <input type="hidden" name="session" value="<?= sanitize($selected_session) ?>">
            <input type="hidden" name="generate" value="1">

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-medium text-gray-500 w-10"><input type="checkbox" checked disabled></th>
                            <th class="text-left py-3 px-4 font-medium text-gray-500">Student</th>
                            <th class="text-right py-3 px-4 font-medium text-gray-500">Base Fee</th>
                            <th class="py-3 px-4 font-medium text-gray-500" colspan="2">Extra Charges</th>
                            <th class="text-right py-3 px-4 font-medium text-gray-500">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s):
                            $existing_bill = db_get_row("SELECT id FROM transactions WHERE student_id = ? AND fee_structure_id = ? AND term = ? AND session_year = ?", [$s['id'], $selected_structure, $selected_term, $selected_session]);
                        ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 <?= $existing_bill ? 'bg-gray-50 opacity-60' : '' ?>">
                            <td class="py-3 px-4">
                                <?php if ($existing_bill): ?>
                                <i class="fas fa-check-circle text-green-500" title="Already billed"></i>
                                <?php else: ?>
                                <input type="checkbox" name="student_id[]" value="<?= $s['id'] ?>" checked>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <?php if (!empty($s['profile_image'])): ?>
                                        <img src="<?= upload_url($s['profile_image'], 'students') ?>" class="w-7 h-7 rounded-full object-cover">
                                    <?php else: ?>
                                        <span class="w-7 h-7 rounded-full bg-teal-100 flex items-center justify-center text-xs font-bold text-teal-700"><?= get_avatar($s['parent_name']) ?></span>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-medium text-gray-900"><?= sanitize($s['parent_name']) ?></p>
                                        <p class="text-xs text-gray-400"><?= sanitize($s['enrollment_id']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right font-medium"><?= format_currency($base_total) ?></td>
                            <td class="py-3 px-4" colspan="2">
                                <?php if ($existing_bill): ?>
                                <span class="text-xs text-green-600"><i class="fas fa-check-circle"></i> Already billed</span>
                                <?php else: ?>
                                <div class="extras-row flex gap-2 items-center">
                                    <input type="text" name="extras_label[<?= $s['id'] ?>][]" placeholder="e.g. Transport" class="w-28 border border-gray-200 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-teal-500 outline-none">
                                    <input type="number" name="extras_amount[<?= $s['id'] ?>][]" placeholder="Amount" step="0.01" min="0" class="w-20 border border-gray-200 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-teal-500 outline-none">
                                    <button type="button" onclick="addExtra(this)" class="text-teal-600 hover:text-teal-800 text-xs"><i class="fas fa-plus"></i></button>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-right font-semibold"><?= format_currency($existing_bill ? 0 : $base_total) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    <strong><?= count($students) ?></strong> students |
                    Base fee: <strong><?= format_currency($base_total) ?></strong>
                </p>
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
                    <i class="fas fa-file-invoice"></i> Generate Bills
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function addExtra(btn) {
    const row = btn.closest('.extras-row');
    const clone = row.cloneNode(true);
    clone.querySelectorAll('input').forEach(inp => inp.value = '');
    row.parentNode.appendChild(clone);
    btn.remove();
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
