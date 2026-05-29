<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Create Fee Structure';
$step = (int)($_GET['step'] ?? 1);
$classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = (int)($_POST['step'] ?? 1);

    if ($step == 2) {
        $_SESSION['fee_step1'] = [
            'class_ids' => $_POST['class_ids'] ?? []
        ];
        $step = 2;
    } elseif ($step == 3) {
        $_SESSION['fee_step2'] = [
            'name' => sanitize($_POST['name'] ?? ''),
            'prefix' => sanitize($_POST['prefix'] ?? ''),
            'receipt_start' => (int)($_POST['receipt_start'] ?? 1),
            'frequency' => sanitize($_POST['frequency'] ?? 'monthly')
        ];
        $step = 3;
    } elseif ($step == 4) {
        $fee_types = [];
        $categories = $_POST['category'] ?? [];
        $amounts = $_POST['amount'] ?? [];
        $taxes = $_POST['tax'] ?? [];
        for ($i = 0; $i < count($categories); $i++) {
            if (!empty($categories[$i]) && !empty($amounts[$i])) {
                $fee_types[] = [
                    'category' => sanitize($categories[$i]),
                    'amount' => (float)$amounts[$i],
                    'tax' => (float)($taxes[$i] ?? 0)
                ];
            }
        }

        $step1 = $_SESSION['fee_step1'] ?? [];
        $step2 = $_SESSION['fee_step2'] ?? [];

        if (empty($step2['name']) || empty($step1['class_ids']) || empty($fee_types)) {
            set_flash('error', 'Please fill in all required fields.');
        } else {
            $structure_id = db_insert(
                "INSERT INTO fee_structures (name, prefix, receipt_start, frequency, created_by) VALUES (?, ?, ?, ?, ?)",
                [$step2['name'], $step2['prefix'], $step2['receipt_start'], $step2['frequency'], get_user_id()]
            );
            if ($structure_id) {
                foreach ($step1['class_ids'] as $cid) {
                    db_insert("INSERT INTO fee_structure_classes (fee_structure_id, class_id) VALUES (?, ?)", [$structure_id, (int)$cid]);
                }
                foreach ($fee_types as $ft) {
                    db_insert("INSERT INTO fee_types (fee_structure_id, category, amount, tax_percent) VALUES (?, ?, ?, ?)",
                        [$structure_id, $ft['category'], $ft['amount'], $ft['tax']]);
                }
                unset($_SESSION['fee_step1'], $_SESSION['fee_step2']);
                set_flash('success', 'Fee structure created successfully!');
                redirect('index.php?tab=structures');
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-center mb-8">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold <?= $step >= 1 ? 'bg-teal-600 text-white' : 'bg-gray-200 text-gray-500' ?>">1</div>
                <span class="text-sm <?= $step >= 1 ? 'text-teal-600 font-medium' : 'text-gray-400' ?>">Select Classes</span>
            </div>
            <div class="w-12 h-0.5 <?= $step >= 2 ? 'bg-teal-600' : 'bg-gray-200' ?>"></div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold <?= $step >= 2 ? 'bg-teal-600 text-white' : 'bg-gray-200 text-gray-500' ?>">2</div>
                <span class="text-sm <?= $step >= 2 ? 'text-teal-600 font-medium' : 'text-gray-400' ?>">Structure Details</span>
            </div>
            <div class="w-12 h-0.5 <?= $step >= 3 ? 'bg-teal-600' : 'bg-gray-200' ?>"></div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold <?= $step >= 3 ? 'bg-teal-600 text-white' : 'bg-gray-200 text-gray-500' ?>">3</div>
                <span class="text-sm <?= $step >= 3 ? 'text-teal-600 font-medium' : 'text-gray-400' ?>">Fee Types</span>
            </div>
        </div>
    </div>

    <?php if (has_flash('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('error') ?></div>
    <?php endif; ?>

    <?php if ($step == 1): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Step 1: Select Classes</h3>
        <form method="POST">
            <input type="hidden" name="step" value="2">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                <?php foreach ($classes as $c): ?>
                <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                    <input type="checkbox" name="class_ids[]" value="<?= $c['id'] ?>" class="rounded text-teal-600 focus:ring-teal-500">
                    <span class="text-sm font-medium text-gray-700"><?= sanitize($c['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">Next: Structure Details <i class="fas fa-arrow-right ml-2"></i></button>
        </form>
    </div>

    <?php elseif ($step == 2): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Step 2: Structure Details</h3>
        <form method="POST">
            <input type="hidden" name="step" value="3">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Structure Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., Annual Fee 2025-26">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Prefix <span class="text-red-500">*</span></label>
                        <input type="text" name="prefix" required value="INV" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Start Number</label>
                        <input type="number" name="receipt_start" value="1" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                    <select name="frequency" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="half_yearly">Half Yearly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <a href="?step=1" class="bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition"><i class="fas fa-arrow-left mr-2"></i>Back</a>
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">Next: Fee Types <i class="fas fa-arrow-right ml-2"></i></button>
            </div>
        </form>
    </div>

    <?php elseif ($step == 3): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Step 3: Add Fee Types</h3>
        <form method="POST">
            <input type="hidden" name="step" value="4">

            <div id="feeTypesContainer">
                <div class="fee-type-row grid grid-cols-3 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                        <input type="text" name="category[]" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g., Tuition Fee">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Amount (KSh)</label>
                        <input type="number" name="amount[]" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tax %</label>
                        <input type="number" name="tax[]" step="0.01" value="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <button type="button" onclick="addFeeRow()" class="text-teal-600 text-sm hover:underline mb-4"><i class="fas fa-plus mr-1"></i> Add another fee type</button>

            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200">
                <a href="?step=2" class="bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition"><i class="fas fa-arrow-left mr-2"></i>Back</a>
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-check mr-2"></i> Create Fee Structure
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
let feeRowCount = 1;
function addFeeRow() {
    const container = document.getElementById('feeTypesContainer');
    const row = document.createElement('div');
    row.className = 'fee-type-row grid grid-cols-3 gap-3 mb-3';
    row.innerHTML = `
        <div><input type="text" name="category[]" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Category"></div>
        <div><input type="number" name="amount[]" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="0.00"></div>
        <div class="flex gap-2"><input type="number" name="tax[]" step="0.01" value="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"><button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button></div>
    `;
    container.appendChild(row);
    feeRowCount++;
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
