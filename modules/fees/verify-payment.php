<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('fees');

$page_title = 'Verify Payments';

// Confirm payment
if (isset($_GET['confirm']) && isset($_GET['id'])) {
    $txn_id = (int)$_GET['id'];
    $txn = db_get_row("SELECT * FROM transactions WHERE id = ?", [$txn_id]);
    if ($txn && $txn['payment_method'] && !$txn['verified_by']) {
        db_query("UPDATE transactions SET verified_by = ?, verified_at = NOW() WHERE id = ?", [get_user_id(), $txn_id]);
        set_flash('success', 'Payment #' . $txn['invoice_no'] . ' verified successfully.');
    }
    redirect('verify-payment.php');
}

// Reject payment
if (isset($_GET['reject']) && isset($_GET['id'])) {
    $txn_id = (int)$_GET['id'];
    $txn = db_get_row("SELECT * FROM transactions WHERE id = ?", [$txn_id]);
    if ($txn && $txn['payment_method'] && !$txn['verified_by']) {
        // Reset - remove the payment method, proof, and revert amounts
        $old_paid = $txn['paid_amount'];
        $new_due = $txn['total_amount'];
        $new_paid = 0;
        $new_status = 'pending';
        // If there were previous verified payments, keep those
        $prev_verified = db_get_row("SELECT COALESCE(SUM(paid_amount),0) as total FROM transactions WHERE id != ? AND student_id = ? AND verified_by IS NOT NULL", [$txn_id, $txn['student_id']]);
        if ($prev_verified && $prev_verified['total'] > 0) {
            $new_paid = $prev_verified['total'];
            $new_due = $txn['total_amount'] - $new_paid;
            $new_status = $new_due <= 0 ? 'paid' : ($new_paid > 0 ? 'partial' : 'pending');
        }
        db_query("UPDATE transactions SET payment_method = NULL, transaction_id = NULL, payment_proof = NULL, paid_amount = ?, due_amount = ?, payment_status = ?, verified_by = NULL, verified_at = NULL, payment_note = NULL WHERE id = ?", [$new_paid, $new_due, $new_status, $txn_id]);
        set_flash('success', 'Payment #' . $txn['invoice_no'] . ' rejected. Transaction reverted.');
    }
    redirect('verify-payment.php');
}

$pending = db_get_all("SELECT t.*, s.parent_name, s.enrollment_id, c.name as class_name,
    u.full_name as verified_name
    FROM transactions t 
    LEFT JOIN students s ON t.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON t.verified_by = u.id
    WHERE t.payment_method IS NOT NULL
    ORDER BY t.verified_at DESC, t.created_at DESC");

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <?php if (has_flash('success')): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <i class="fas fa-check-circle"></i> <?= get_flash('success') ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Payment Verification Queue</h3>
            <span class="text-xs bg-teal-100 text-teal-700 px-2 py-1 rounded-full"><?= count($pending) ?> submissions</span>
        </div>

        <?php if (empty($pending)): ?>
        <div class="p-12 text-center text-gray-400">
            <i class="fas fa-check-double text-5xl text-green-300 mb-4 block"></i>
            <p class="text-lg font-medium text-gray-500">All payments verified</p>
            <p class="text-sm mt-1">No pending payment verifications.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/50">
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Invoice</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Student</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Class</th>
                        <th class="text-right py-3 px-4 font-medium text-gray-500">Amount Paid</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Method</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Transaction ID</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Receipt</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-500">Verified By</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $p): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium text-gray-900">#<?= sanitize($p['invoice_no']) ?></td>
                        <td class="py-3 px-4"><?= sanitize($p['parent_name'] ?? 'N/A') ?></td>
                        <td class="py-3 px-4"><?= sanitize($p['class_name'] ?? 'N/A') ?></td>
                        <td class="py-3 px-4 text-right font-medium text-green-600"><?= format_currency($p['paid_amount']) ?></td>
                        <td class="py-3 px-4">
                            <?php if ($p['payment_method'] === 'mpesa'): ?>
                            <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs"><i class="fas fa-mobile-alt"></i> M-Pesa</span>
                            <?php elseif ($p['payment_method'] === 'bank_transfer'): ?>
                            <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs"><i class="fas fa-university"></i> Bank</span>
                            <?php else: ?>
                            <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-xs text-gray-600 max-w-[120px] truncate" title="<?= sanitize($p['transaction_id']) ?>"><?= sanitize($p['transaction_id'] ?? '—') ?></td>
                        <td class="py-3 px-4">
                            <?php if ($p['payment_proof']): ?>
                            <a href="<?= BASE_URL ?>/uploads/payments/<?= urlencode($p['payment_proof']) ?>" target="_blank" class="text-teal-600 hover:underline text-xs flex items-center gap-1">
                                <i class="fas fa-file"></i> View
                            </a>
                            <?php else: ?>
                            <span class="text-gray-400 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <?php if ($p['verified_by']): ?>
                            <span class="text-xs text-green-600"><i class="fas fa-check-circle"></i> <?= sanitize($p['verified_name'] ?? 'Verified') ?></span>
                            <?php else: ?>
                            <span class="text-xs text-amber-600"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <?php if (!$p['verified_by']): ?>
                            <div class="flex items-center justify-center gap-2">
                                <a href="?confirm=1&id=<?= $p['id'] ?>" onclick="return confirm('Confirm this payment?')" class="bg-green-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-green-600 transition"><i class="fas fa-check mr-1"></i>Confirm</a>
                                <a href="?reject=1&id=<?= $p['id'] ?>" onclick="return confirm('Reject this payment? This will revert the transaction.')" class="bg-red-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-red-600 transition"><i class="fas fa-times mr-1"></i>Reject</a>
                            </div>
                            <?php else: ?>
                            <span class="text-xs text-gray-400">Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($p['payment_note']): ?>
                    <tr class="bg-gray-50/50">
                        <td colspan="9" class="py-2 px-4 text-xs text-gray-500"><i class="fas fa-sticky-note mr-1"></i>Note: <?= sanitize($p['payment_note']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
