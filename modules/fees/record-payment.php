<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
$tx = db_get_row("SELECT t.*, s.parent_name, s.enrollment_id, c.name as class_name
    FROM transactions t
    LEFT JOIN students s ON t.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE t.id = ?", [$id]);

if (!$tx) {
    set_flash('error', 'Transaction not found.');
    redirect('index.php?tab=transactions');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = max(0, (float)($_POST['amount'] ?? 0));
    $method = sanitize($_POST['method'] ?? '');
    $ref = sanitize($_POST['transaction_id'] ?? '');
    $payment_date = sanitize($_POST['payment_date'] ?? date('Y-m-d'));
    $note = sanitize($_POST['note'] ?? '');

    $total = (float)$tx['total_amount'];
    $discount = (float)$tx['discount'];
    $current_paid = (float)$tx['paid_amount'];
    $applicable = $total - $discount;
    $remaining = $applicable - $current_paid;

    if ($amount <= 0) {
        set_flash('error', 'Payment amount must be greater than 0.');
        redirect('record-payment.php?id=' . $id);
    }

    $new_paid = $current_paid + $amount;
    if ($new_paid > $applicable) {
        set_flash('error', 'Total payment (' . format_currency($new_paid) . ') exceeds applicable amount (' . format_currency($applicable) . ').');
        redirect('record-payment.php?id=' . $id);
    }

    $new_due = $applicable - $new_paid;
    if ($new_due <= 0) {
        $new_status = 'paid';
    } elseif ($new_paid > 0) {
        $new_status = 'partial';
    } else {
        $new_status = 'pending';
    }

    db_query("UPDATE transactions SET paid_amount = ?, due_amount = ?, payment_status = ?, payment_method = ?, transaction_id = ?, payment_date = ?, payment_note = ? WHERE id = ?",
        [$new_paid, $new_due, $new_status, $method, $ref, $payment_date, $note, $id]);

    db_insert("INSERT INTO payment_records (transaction_id, amount, method, transaction_id_ref, payment_date, note, recorded_by, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
        [$id, $amount, $method, $ref, $payment_date, $note, get_user_id()]);

    set_flash('success', 'Payment of ' . format_currency($amount) . ' recorded against invoice #' . $tx['invoice_no'] . '. Status: ' . ucfirst($new_status));
    redirect('index.php?tab=transactions');
}

$page_title = 'Record Payment';
include __DIR__ . '/../../includes/header.php';

$total = (float)$tx['total_amount'];
$discount = (float)$tx['discount'];
$current_paid = (float)$tx['paid_amount'];
$applicable = $total - $discount;
$remaining = $applicable - $current_paid;
$payment_methods = ['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'mpesa' => 'M-Pesa', 'other' => 'Other'];
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900"><i class="fas fa-hand-holding-usd text-teal-600 mr-2"></i>Record Payment</h1>
        <a href="index.php?tab=transactions" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition"><i class="fas fa-arrow-left mr-2"></i>Back</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="font-semibold text-gray-900 mb-4">Invoice #<?= sanitize($tx['invoice_no']) ?></h3>
        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div><span class="text-gray-500">Student:</span> <span class="font-medium text-gray-900"><?= sanitize($tx['parent_name'] ?? 'N/A') ?></span></div>
            <div><span class="text-gray-500">Enrollment:</span> <span class="font-medium text-gray-900"><?= sanitize($tx['enrollment_id'] ?? 'N/A') ?></span></div>
            <div><span class="text-gray-500">Class:</span> <span class="font-medium text-gray-900"><?= sanitize($tx['class_name'] ?? 'N/A') ?></span></div>
            <div><span class="text-gray-500">Term:</span> <span class="font-medium text-gray-900"><?= sanitize($tx['term'] ?? 'N/A') ?> (<?= sanitize($tx['session_year'] ?? '') ?>)</span></div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 space-y-2 mb-6">
            <div class="flex justify-between text-sm"><span class="text-gray-600">Total Amount</span><span class="font-bold text-gray-900"><?= format_currency($total) ?></span></div>
            <div class="flex justify-between text-sm"><span class="text-gray-600">Discount</span><span class="font-bold text-orange-600"><?= format_currency($discount) ?></span></div>
            <div class="flex justify-between text-sm"><span class="text-gray-600">Applicable</span><span class="font-bold text-gray-900"><?= format_currency($applicable) ?></span></div>
            <div class="flex justify-between text-sm"><span class="text-gray-600">Already Paid</span><span class="font-bold text-green-600"><?= format_currency($current_paid) ?></span></div>
            <div class="flex justify-between text-sm pt-2 border-t border-dashed border-gray-300"><span class="font-medium text-gray-700">Remaining</span><span class="font-bold <?= $remaining > 0 ? 'text-red-600' : 'text-green-600' ?>"><?= format_currency($remaining) ?></span></div>
        </div>

        <?php if ($remaining <= 0): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
            <i class="fas fa-check-circle mr-1"></i> This invoice is fully paid. No further payment needed.
        </div>
        <?php endif; ?>

        <?php if (has_flash('error')): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <?php if ($remaining > 0): ?>
        <form method="POST">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium"><?= CURRENCY_SYMBOL ?? 'KSh' ?></span>
                        <input type="number" name="amount" step="0.01" min="0.01" max="<?= $remaining ?>" required
                            class="w-full border border-gray-300 rounded-lg pl-12 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"
                            placeholder="0.00">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Max: <?= format_currency($remaining) ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                    <select name="method" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">Select method</option>
                        <?php foreach ($payment_methods as $val => $label): ?>
                        <option value="<?= $val ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction ID / Reference</label>
                    <input type="text" name="transaction_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Optional">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                    <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                <textarea name="note" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Optional note"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
                    <i class="fas fa-check"></i> Record Payment
                </button>
                <a href="index.php?tab=transactions" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($current_paid > 0): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-3">Payment History</h3>
        <?php
        $payments = db_get_all("SELECT pp.*, u.full_name as recorded_by_name
            FROM payment_records pp
            LEFT JOIN users u ON pp.recorded_by = u.id
            WHERE pp.transaction_id = ?
            ORDER BY pp.payment_date DESC, pp.recorded_at DESC", [$id]);
        ?>
        <?php if (empty($payments)): ?>
        <p class="text-sm text-gray-400 text-center py-4">No individual payment records. Payments were recorded in bulk.</p>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-2 px-2 font-medium text-gray-500">Date</th>
                    <th class="text-right py-2 px-2 font-medium text-gray-500">Amount</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-500">Method</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-500">Ref</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-500">Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                <tr class="border-b border-gray-100">
                    <td class="py-2 px-2"><?= format_date($p['payment_date']) ?></td>
                    <td class="py-2 px-2 text-right font-medium text-green-600"><?= format_currency($p['amount']) ?></td>
                    <td class="py-2 px-2"><span class="text-xs bg-gray-100 px-2 py-0.5 rounded"><?= ucfirst(str_replace('_', ' ', $p['method'])) ?></span></td>
                    <td class="py-2 px-2 text-xs text-gray-600"><?= sanitize($p['transaction_id_ref'] ?? '—') ?></td>
                    <td class="py-2 px-2 text-xs text-gray-600"><?= sanitize($p['recorded_by_name'] ?? 'N/A') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
