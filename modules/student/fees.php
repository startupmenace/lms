<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('student');

$page_title = 'My Fee Details';

$user_id = get_user_id();
$student = db_get_row("SELECT s.* FROM students s WHERE s.id = ? ORDER BY s.id LIMIT 1", [$user_id]);
$student_id = $student['id'] ?? 1;

$transactions = db_get_all("SELECT t.*, fs.name as structure_name, fs.frequency FROM transactions t LEFT JOIN fee_structures fs ON t.fee_structure_id = fs.id WHERE t.student_id = ? ORDER BY t.created_at DESC", [$student_id]);

$total_billed = array_sum(array_column($transactions, 'total_amount'));
$total_paid = array_sum(array_column($transactions, 'paid_amount'));
$total_due = array_sum(array_column($transactions, 'due_amount'));
$overall_pct = $total_billed > 0 ? round($total_paid / $total_billed * 100) : 0;

include __DIR__ . '/../../includes/student-header.php';
?>

<div class="max-w-5xl mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Billed</p>
            <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1"><?= format_currency($total_billed) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Paid</p>
            <p class="text-xl sm:text-2xl font-bold text-green-600 mt-1"><?= format_currency($total_paid) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Due</p>
            <p class="text-xl sm:text-2xl font-bold text-red-600 mt-1"><?= format_currency($total_due) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Paid %</p>
            <p class="text-xl sm:text-2xl font-bold <?= $overall_pct >= 100 ? 'text-green-600' : ($overall_pct >= 50 ? 'text-amber-600' : 'text-red-600') ?> mt-1"><?= $overall_pct ?>%</p>
        </div>
    </div>

    <?php if ($total_billed > 0): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5 mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-700">Overall Payment Progress</span>
            <span class="text-sm font-bold <?= $overall_pct >= 100 ? 'text-green-600' : ($overall_pct >= 50 ? 'text-amber-600' : 'text-red-600') ?>"><?= $overall_pct ?>%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-700 ease-out <?= $overall_pct >= 100 ? 'bg-green-500' : ($overall_pct >= 50 ? 'bg-amber-500' : 'bg-red-500') ?>" style="width: <?= min($overall_pct, 100) ?>%"></div>
        </div>
        <p class="text-xs text-gray-400 mt-2"><?= format_currency($total_paid) ?> paid of <?= format_currency($total_billed) ?> billed</p>
    </div>
    <?php endif; ?>

    <h2 class="text-base sm:text-lg font-bold text-gray-900 mb-4">Fee Transactions</h2>

    <?php if (empty($transactions)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-wallet text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No fee records found.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto -mx-4 sm:-mx-0">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Invoice</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Fee Structure</th>
                        <th class="text-right py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Total</th>
                        <th class="text-right py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Paid</th>
                        <th class="text-right py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Due</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Progress</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Status</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): 
                        $pct = $t['total_amount'] > 0 ? round($t['paid_amount'] / $t['total_amount'] * 100) : 0;
                        $bar_color = $pct >= 100 ? 'bg-green-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-red-500');
                    ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-4 font-semibold text-gray-900">#<?= sanitize($t['invoice_no']) ?></td>
                        <td class="py-3 px-4 text-gray-800"><?= sanitize($t['structure_name'] ?? 'N/A') ?></td>
                        <td class="py-3 px-4 text-right font-medium text-gray-900"><?= format_currency($t['total_amount']) ?></td>
                        <td class="py-3 px-4 text-right font-semibold text-green-600"><?= format_currency($t['paid_amount']) ?></td>
                        <td class="py-3 px-4 text-right font-semibold text-red-600"><?= format_currency($t['due_amount']) ?></td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-[100px]">
                                    <div class="h-full rounded-full <?= $bar_color ?>" style="width: <?= min($pct, 100) ?>%"></div>
                                </div>
                                <span class="text-xs font-bold <?= $pct >= 100 ? 'text-green-600' : ($pct >= 50 ? 'text-amber-600' : 'text-red-600') ?>"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full <?= $t['payment_status'] == 'paid' ? 'bg-green-100 text-green-700' : ($t['payment_status'] == 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= ucfirst($t['payment_status']) ?></span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <?php if ($t['payment_status'] != 'paid'): ?>
                            <a href="pay-fees.php?invoice=<?= urlencode($t['invoice_no']) ?>" class="bg-teal-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-teal-700 transition inline-flex items-center gap-1">
                                <i class="fas fa-credit-card"></i> Pay Now
                            </a>
                            <?php else: ?>
                            <span class="text-xs font-semibold text-green-600"><i class="fas fa-check-circle"></i> Paid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/student-footer.php'; ?>
