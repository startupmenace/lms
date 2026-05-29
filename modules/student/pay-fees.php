<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('student');

$page_title = 'Pay Fees';

$user_id = get_user_id();
$student = db_get_row("SELECT s.* FROM students s WHERE s.id = ? ORDER BY s.id LIMIT 1", [$user_id]);
$student_id = $student['id'] ?? 1;

$gateways = db_get_all("SELECT * FROM payment_gateway_config WHERE is_active = 1 ORDER BY gateway_name");

$invoice_no = $_GET['invoice'] ?? '';
$transaction = null;
if ($invoice_no) {
    $transaction = db_get_row("SELECT t.*, fs.name as structure_name FROM transactions t LEFT JOIN fee_structures fs ON t.fee_structure_id = fs.id WHERE t.invoice_no = ? AND t.student_id = ?", [$invoice_no, $student_id]);
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $inv = sanitize($_POST['invoice_no']);
    $method = sanitize($_POST['payment_method']);
    $trans_id = sanitize($_POST['transaction_id']);
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $note = sanitize($_POST['payment_note'] ?? '');

    $txn = db_get_row("SELECT * FROM transactions WHERE invoice_no = ? AND student_id = ?", [$inv, $student_id]);
    if (!$txn) {
        set_flash('error', 'Invalid invoice.');
        redirect('pay-fees.php');
    }

    $proof_file = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $target_dir = __DIR__ . '/../../uploads/payments/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $proof_file = upload_file($_FILES['payment_proof'], $target_dir, ['jpg', 'jpeg', 'png', 'pdf']);
    }

    $new_paid = $txn['paid_amount'] + $amount_paid;
    $new_due = $txn['total_amount'] - $new_paid;
    $new_status = $new_due <= 0 ? 'paid' : 'partial';

    db_query("UPDATE transactions SET 
        payment_method = ?, transaction_id = ?, payment_proof = ?, 
        paid_amount = ?, due_amount = ?, payment_status = ?,
        payment_note = ?, payment_date = CURDATE()
        WHERE invoice_no = ?",
        [$method, $trans_id, $proof_file, $new_paid, $new_due, $new_status, $note, $inv]);

    set_flash('success', 'Payment submitted for verification. We will confirm shortly.');
    redirect('fees.php');
}

include __DIR__ . '/../../includes/student-header.php';
?>

<div class="max-w-3xl mx-auto">
    <?php if (has_flash('success')): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <i class="fas fa-check-circle"></i> <?= get_flash('success') ?>
    </div>
    <?php endif; ?>
    <?php if (has_flash('error')): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i> <?= get_flash('error') ?>
    </div>
    <?php endif; ?>

    <?php if (!$invoice_no): ?>
    <!-- Step 1: Select an invoice to pay -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Select Fee Item to Pay</h2>
        <?php
        $pending = db_get_all("SELECT t.*, fs.name as structure_name FROM transactions t LEFT JOIN fee_structures fs ON t.fee_structure_id = fs.id WHERE t.student_id = ? AND t.payment_status IN ('pending','partial') AND t.due_amount > 0 ORDER BY t.created_at DESC", [$student_id]);
        ?>
        <?php if (empty($pending)): ?>
        <div class="text-center py-8 text-gray-400">
            <i class="fas fa-check-circle text-4xl text-green-400 mb-3 block"></i>
            <p>No pending payments. All fees are up to date!</p>
            <a href="fees.php" class="text-teal-600 hover:underline text-sm mt-2 inline-block">View Fee Details</a>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($pending as $p): ?>
            <a href="pay-fees.php?invoice=<?= urlencode($p['invoice_no']) ?>" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-teal-300 hover:bg-teal-50/30 transition group">
                <div>
                    <p class="font-medium text-gray-900">#<?= sanitize($p['invoice_no']) ?> — <?= sanitize($p['structure_name'] ?? 'Fee') ?></p>
                    <p class="text-sm text-gray-500 mt-0.5">Due: <span class="font-semibold text-red-600"><?= format_currency($p['due_amount']) ?></span></p>
                </div>
                <div class="text-teal-600 group-hover:translate-x-1 transition">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- Step 2: Payment form -->
    <div class="mb-4">
        <a href="pay-fees.php" class="text-sm text-gray-500 hover:text-teal-600 transition"><i class="fas fa-arrow-left mr-1"></i> Back to fee items</a>
    </div>

    <?php if (!$transaction): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
        <i class="fas fa-exclamation-triangle text-4xl text-amber-400 mb-3 block"></i>
        <p class="text-gray-500">Transaction not found.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-900">Payment for #<?= sanitize($transaction['invoice_no']) ?></h2>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Fee Structure:</span> <span class="font-medium"><?= sanitize($transaction['structure_name'] ?? 'N/A') ?></span></div>
            <div><span class="text-gray-500">Total Amount:</span> <span class="font-medium"><?= format_currency($transaction['total_amount']) ?></span></div>
            <div><span class="text-gray-500">Already Paid:</span> <span class="font-medium text-green-600"><?= format_currency($transaction['paid_amount']) ?></span></div>
            <div><span class="text-gray-500">Amount Due:</span> <span class="font-medium text-red-600"><?= format_currency($transaction['due_amount']) ?></span></div>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="invoice_no" value="<?= sanitize($transaction['invoice_no']) ?>">

        <!-- Payment Method Selection -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="font-semibold text-gray-900">Choose Payment Method</h3>
            </div>
            <div class="p-6 space-y-4">
                <?php if (empty($gateways)): ?>
                <div class="bg-amber-50 text-amber-700 p-4 rounded-lg text-sm">No payment gateways are active yet. Please contact the school.</div>
                <?php else: ?>
                <?php foreach ($gateways as $gw):
                    $cfg = json_decode($gw['config_data'], true);
                    $icon = $gw['gateway_name'] === 'mpesa' ? 'fa-mobile-alt' : 'fa-university';
                    $color = $gw['gateway_name'] === 'mpesa' ? 'green' : 'blue';
                ?>
                <label class="flex items-start gap-4 p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-teal-300 transition has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50/30">
                    <input type="radio" name="payment_method" value="<?= sanitize($gw['gateway_name']) ?>" required class="mt-1 text-teal-600 focus:ring-teal-500">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-<?= $color ?>-100 rounded-lg flex items-center justify-center">
                                <i class="fas <?= $icon ?> text-sm text-<?= $color ?>-600"></i>
                            </div>
                            <span class="font-medium text-gray-900"><?= sanitize($gw['label']) ?></span>
                        </div>
                        <?php if ($gw['gateway_name'] === 'mpesa'): ?>
                        <div class="mt-3 text-sm text-gray-600 space-y-1 bg-gray-50 rounded-lg p-3">
                            <?php if (!empty($cfg['paybill'])): ?><p><strong>Paybill:</strong> <?= sanitize($cfg['paybill']) ?></p><?php endif; ?>
                            <?php if (!empty($cfg['till'])): ?><p><strong>Till:</strong> <?= sanitize($cfg['till']) ?></p><?php endif; ?>
                            <?php if (!empty($cfg['account_format'])): ?><p><strong>Account:</strong> <?= sanitize($cfg['account_format']) ?></p><?php endif; ?>
                        </div>
                        <?php elseif ($gw['gateway_name'] === 'bank_transfer'): ?>
                        <div class="mt-3 text-sm text-gray-600 space-y-1 bg-gray-50 rounded-lg p-3">
                            <?php if (!empty($cfg['bank_name'])): ?><p><strong>Bank:</strong> <?= sanitize($cfg['bank_name']) ?></p><?php endif; ?>
                            <?php if (!empty($cfg['account_name'])): ?><p><strong>Account Name:</strong> <?= sanitize($cfg['account_name']) ?></p><?php endif; ?>
                            <?php if (!empty($cfg['account_number'])): ?><p><strong>Account No:</strong> <?= sanitize($cfg['account_number']) ?></p><?php endif; ?>
                            <?php if (!empty($cfg['branch'])): ?><p><strong>Branch:</strong> <?= sanitize($cfg['branch']) ?></p><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($gw['instructions'])): ?>
                        <p class="text-xs text-gray-500 mt-2"><i class="fas fa-info-circle mr-1"></i><?= sanitize($gw['instructions']) ?></p>
                        <?php endif; ?>
                    </div>
                </label>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="font-semibold text-gray-900">Payment Details</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Amount Paying (KSh)</label>
                    <input type="number" name="amount_paid" step="0.01" min="1" max="<?= $transaction['due_amount'] ?>" required value="<?= $transaction['due_amount'] ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <p class="text-xs text-gray-400 mt-1">Max: <?= format_currency($transaction['due_amount']) ?></p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Transaction / Reference Number</label>
                    <input type="text" name="transaction_id" required placeholder="M-Pesa confirmation code or bank reference" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Upload Receipt / Screenshot (optional)</label>
                    <input type="file" name="payment_proof" accept="image/*,.pdf" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                    <p class="text-xs text-gray-400 mt-1">Upload M-Pesa SMS screenshot or bank receipt (JPG, PNG, PDF)</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Note (optional)</label>
                    <textarea name="payment_note" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Any additional info..."></textarea>
                </div>
            </div>
        </div>

        <button type="submit" name="submit_payment" class="w-full bg-teal-600 text-white py-3.5 rounded-xl font-semibold hover:bg-teal-700 transition flex items-center justify-center gap-2 text-base">
            <i class="fas fa-paper-plane"></i> Submit Payment for Verification
        </button>
        <p class="text-xs text-gray-400 text-center">Your payment will be verified by the school administration.</p>
    </form>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/student-footer.php'; ?>
