<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('parent');

$page_title = 'Fees';
$user_id = get_user_id();

$children = db_get_all("SELECT s.id, s.parent_name, c.name as class_name FROM student_parents sp JOIN students s ON sp.student_id = s.id LEFT JOIN classes c ON s.class_id = c.id WHERE sp.parent_user_id = ? AND s.is_active = 1 ORDER BY s.parent_name", [$user_id]);
$child_id = (int)($_GET['child_id'] ?? $children[0]['id'] ?? 0);

$selected = [];
foreach ($children as $c) { if ($c['id'] == $child_id) { $selected = $c; break; } }
if (empty($selected) && !empty($children)) $selected = $children[0];

$fees = $selected ? db_get_all("SELECT t.*, fs.name as structure_name FROM transactions t LEFT JOIN fee_structures fs ON t.fee_structure_id = fs.id WHERE t.student_id = ? ORDER BY t.created_at DESC", [$selected['id']]) : [];

$total_billed = array_sum(array_column($fees, 'total_amount'));
$total_paid = array_sum(array_column($fees, 'paid_amount'));
$total_due = $total_billed - $total_paid;
$overall_pct = $total_billed > 0 ? round($total_paid / $total_billed * 100) : 0;

include __DIR__ . '/../../includes/parent-header.php';
?>

<?php if (count($children) > 1): ?>
<div class="mb-6 flex items-center gap-3 flex-wrap">
    <span class="text-sm font-medium text-gray-600">Child:</span>
    <?php foreach ($children as $c): ?>
    <a href="?child_id=<?= $c['id'] ?>" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $c['id'] == $child_id ? 'bg-teal-600 text-white shadow' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' ?>">
        <?= sanitize($c['parent_name']) ?> (<?= sanitize($c['class_name'] ?? 'N/A') ?>)
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="max-w-5xl mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-6">Fee Status — <?= sanitize($selected['parent_name'] ?? '') ?></h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Total Billed</p>
            <p class="text-xl font-bold text-gray-900 mt-1"><?= format_currency($total_billed) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Total Paid</p>
            <p class="text-xl font-bold text-green-600 mt-1"><?= format_currency($total_paid) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Due Amount</p>
            <p class="text-xl font-bold <?= $total_due > 0 ? 'text-red-600' : 'text-green-600' ?> mt-1"><?= format_currency($total_due) ?></p>
        </div>
    </div>

    <?php if (empty($fees)): ?>
    <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
        <p class="text-gray-400">No fee records found.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Invoice</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Fee Type</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Date</th>
                    <th class="text-right py-3 px-4 font-medium text-gray-500">Amount</th>
                    <th class="text-right py-3 px-4 font-medium text-gray-500">Paid</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fees as $fee):
                    $pct = $fee['total_amount'] > 0 ? round($fee['paid_amount'] / $fee['total_amount'] * 100) : 0;
                ?>
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 text-gray-900">#<?= sanitize($fee['invoice_no']) ?></td>
                    <td class="py-3 px-4 text-gray-600"><?= sanitize($fee['structure_name'] ?? 'Fee') ?></td>
                    <td class="py-3 px-4 text-gray-600"><?= format_date($fee['created_at'] ?? $fee['due_date'] ?? '') ?></td>
                    <td class="py-3 px-4 text-right text-gray-900"><?= format_currency($fee['total_amount']) ?></td>
                    <td class="py-3 px-4 text-right text-gray-900"><?= format_currency($fee['paid_amount']) ?></td>
                    <td class="py-3 px-4 text-center">
                        <span class="text-xs px-2 py-1 rounded-full <?= $pct >= 100 ? 'bg-green-100 text-green-700' : ($pct > 0 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
                            <?= $pct >= 100 ? 'Paid' : ($pct > 0 ? $pct . '%' : 'Unpaid') ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/parent-footer.php'; ?>
