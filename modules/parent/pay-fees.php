<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('parent');

$page_title = 'Pay Fees';
$user_id = get_user_id();

$children = db_get_all("SELECT s.id, s.parent_name, c.name as class_name FROM student_parents sp JOIN students s ON sp.student_id = s.id LEFT JOIN classes c ON s.class_id = c.id WHERE sp.parent_user_id = ? AND s.is_active = 1 ORDER BY s.parent_name", [$user_id]);
$child_id = (int)($_GET['child_id'] ?? $children[0]['id'] ?? 0);

$gateways = db_get_all("SELECT * FROM payment_gateway_config WHERE is_active = 1 ORDER BY gateway_name");

$invoice_no = $_GET['invoice'] ?? '';
$transaction = null;
if ($invoice_no && $child_id) {
    $transaction = db_get_row("SELECT t.*, fs.name as structure_name FROM transactions t LEFT JOIN fee_structures fs ON t.fee_structure_id = fs.id WHERE t.invoice_no = ? AND t.student_id = ?", [$invoice_no, $child_id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $inv = sanitize($_POST['invoice_no']);
    $cid = (int)$_POST['child_id'];
    $method = sanitize($_POST['payment_method']);
    $trans_id = sanitize($_POST['transaction_id']);
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $note = sanitize($_POST['payment_note'] ?? '');

    $txn = db_get_row("SELECT * FROM transactions WHERE invoice_no = ? AND student_id = ?", [$inv, $cid]);
    if (!$txn) {
        set_flash('error', 'Invalid invoice.');
        redirect('pay-fees.php?invoice=' . urlencode($inv) . '&child_id=' . $cid);
    }

    $proof_file = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $target_dir = ensure_upload_dir('payments');
        $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array($ext, $allowed)) {
            $proof_file = 'proof_' . $cid . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_dir . '/' . $proof_file);
        }
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
    redirect('fees.php?child_id=' . $cid);
}

include __DIR__ . '/../../includes/parent-header.php';
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

    <?php if (count($children) > 1): ?>
    <div class="mb-6 flex items-center gap-3 flex-wrap">
        <span class="text-sm font-medium text-gray-600">Child:</span>
        <?php foreach ($children as $c): ?>
        <a href="?child_id=<?= $c['id'] ?>" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $c['id'] == $child_id ? 'bg-teal-600 text-white shadow' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' ?>">
            <?= sanitize($c['parent_name']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$invoice_no): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Select Fee Item to Pay</h2>
        <?php
        $pending = db_get_all("SELECT t.*, fs.name as structure_name FROM transactions t LEFT JOIN fee_structures fs ON t.fee_structure_id = fs.id WHERE t.student_id = ? AND t.payment_status IN ('pending','partial') AND t.due_amount > 0 ORDER BY t.created_at DESC", [$child_id]);
        ?>
        <?php if (empty($pending)): ?>
        <div class="text-center py-8 text-gray-400">
            <i class="fas fa-check-circle text-4xl text-green-400 mb-3 block"></i>
            <p>No pending payments. All fees are up to date!</p>
            <a href="fees.php?child_id=<?= $child_id ?>" class="text-teal-600 hover:underline text-sm mt-2 inline-block">View Fee Details</a>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($pending as $p): ?>
            <a href="pay-fees.php?invoice=<?= urlencode($p['invoice_no']) ?>&child_id=<?= $child_id ?>" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-teal-300 hover:bg-teal-50/30 transition group">
                <div>
                    <p class="font-medium text-gray-900">#<?= sanitize($p['invoice_no']) ?> — <?= sanitize($p['structure_name'] ?? 'Fee') ?></p>
                    <p class="text-sm text-gray-500 mt-0.5">Due: <span class="font-semibold text-red-600"><?= format_currency($p['due_amount']) ?></span></p>
                </div>
                <div class="text-teal-600 group-hover:translate-x-1 transition"><i class="fas fa-arrow-right"></i></div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif ($transaction): ?>
    <div class="mb-4">
        <a href="pay-fees.php?child_id=<?= $child_id ?>" class="text-sm text-gray-500 hover:text-teal-600 transition"><i class="fas fa-arrow-left mr-1"></i> Back to fee items</a>
    </div>

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
        <input type="hidden" name="child_id" value="<?= $child_id ?>">

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
                        <button type="button" onclick="showMpesaInstant()"
                                class="mt-3 w-full bg-green-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition flex items-center justify-center gap-2">
                            <i class="fas fa-bolt"></i> Pay Instantly via M-Pesa
                        </button>
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

    <!-- M-Pesa Instant Pay Modal -->
    <div id="mpesa-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target===this)closeMpesaModal()">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900"><i class="fas fa-bolt text-green-600 mr-2"></i>Instant M-Pesa Payment</h3>
                <button type="button" onclick="closeMpesaModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <p class="text-sm text-gray-500 mb-4">Enter your M-Pesa phone number to receive an STK push prompt.</p>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Amount (KSh)</label>
                    <input type="number" id="mpesa-amount" readonly value="<?= $transaction['due_amount'] ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-gray-50 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">M-Pesa Phone Number</label>
                    <input type="tel" id="mpesa-phone" placeholder="0712345678" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                    <p class="text-xs text-gray-400 mt-1">The phone must be registered for M-Pesa.</p>
                </div>
                <div id="mpesa-status" class="hidden text-sm rounded-lg p-3"></div>
                <button type="button" id="mpesa-pay-btn" onclick="triggerMpesaStk()"
                        class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-mobile-alt"></i> Send STK Push
                </button>
            </div>
        </div>
    </div>

    <script>
    function showMpesaInstant() {
        document.getElementById('mpesa-modal').classList.remove('hidden');
        document.getElementById('mpesa-phone').focus();
    }
    function closeMpesaModal() {
        document.getElementById('mpesa-modal').classList.add('hidden');
        document.getElementById('mpesa-status').classList.add('hidden');
    }
    function triggerMpesaStk() {
        const btn = document.getElementById('mpesa-pay-btn');
        const status = document.getElementById('mpesa-status');
        const phone = document.getElementById('mpesa-phone').value.trim();
        const amount = document.getElementById('mpesa-amount').value;

        if (!phone) { alert('Please enter your M-Pesa phone number'); return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending STK Push...';
        status.className = 'hidden';

        fetch('mpesa-stk.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'invoice_no=<?= urlencode($transaction['invoice_no']) ?>&child_id=<?= $child_id ?>&amount=' + encodeURIComponent(amount) + '&phone=' + encodeURIComponent(phone)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                status.className = 'bg-blue-50 border border-blue-200 text-blue-700 rounded-lg p-3 text-sm';
                status.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> STK push sent! Waiting for you to complete payment on your phone...';
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Waiting for confirmation...';
                status.classList.remove('hidden');
                pollMpesaStatus(d.CheckoutRequestID, d.invoice_no, d.child_id, d.amount);
            } else {
                status.className = 'bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm';
                status.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> ' + (d.error || 'Payment failed');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-mobile-alt"></i> Try Again';
                status.classList.remove('hidden');
            }
        })
        .catch(e => {
            status.className = 'bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm';
            status.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Network error. Please try again.';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-mobile-alt"></i> Try Again';
            status.classList.remove('hidden');
        });
    }

    function pollMpesaStatus(checkoutId, invoiceNo, childId, amount) {
        const maxAttempts = 20;
        let attempts = 0;
        const status = document.getElementById('mpesa-status');
        const btn = document.getElementById('mpesa-pay-btn');

        function poll() {
            attempts++;
            if (attempts > maxAttempts) {
                status.className = 'bg-amber-50 border border-amber-200 text-amber-700 rounded-lg p-3 text-sm';
                status.innerHTML = '<i class="fas fa-clock mr-1"></i> Payment not received yet. Check your phone or try again.';
                btn.innerHTML = '<i class="fas fa-mobile-alt"></i> Try Again';
                btn.disabled = false;
                status.classList.remove('hidden');
                return;
            }

            fetch('mpesa-query.php?checkout_id=' + encodeURIComponent(checkoutId) +
                  '&invoice_no=' + encodeURIComponent(invoiceNo) +
                  '&child_id=' + encodeURIComponent(childId) +
                  '&amount=' + encodeURIComponent(amount))
            .then(r => r.json())
            .then(d => {
                if (d.status === 'completed') {
                    status.className = 'bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 text-sm';
                    status.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Payment of KSh ' + (d.amount || amount) + ' received!';
                    btn.innerHTML = '<i class="fas fa-check"></i> Payment Complete';
                    setTimeout(() => { closeMpesaModal(); window.location.href = 'fees.php?child_id=' + childId; }, 2000);
                } else if (d.status === 'cancelled') {
                    status.className = 'bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm';
                    status.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Payment cancelled on your phone.';
                    btn.innerHTML = '<i class="fas fa-mobile-alt"></i> Try Again';
                    btn.disabled = false;
                    status.classList.remove('hidden');
                } else if (d.status === 'failed') {
                    status.className = 'bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm';
                    status.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Payment failed: ' + (d.resultDesc || 'Unknown error');
                    btn.innerHTML = '<i class="fas fa-mobile-alt"></i> Try Again';
                    btn.disabled = false;
                    status.classList.remove('hidden');
                } else {
                    setTimeout(poll, 3000);
                }
            })
            .catch(e => {
                setTimeout(poll, 3000);
            });
        }

        poll();
    }
    </script>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
        <i class="fas fa-exclamation-triangle text-4xl text-amber-400 mb-3 block"></i>
        <p class="text-gray-500">Transaction not found.</p>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/parent-footer.php'; ?>
