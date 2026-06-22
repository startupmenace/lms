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
    $discount_amt = max(0, (float)($_POST['discount_amount'] ?? 0));
    $total = (float)$tx['total_amount'];
    $paid = (float)$tx['paid_amount'];
    $max_discount = $total - $paid;
    if ($discount_amt > $max_discount) $discount_amt = $max_discount;

    $due = $total - $discount_amt - $paid;

    db_query("UPDATE transactions SET discount = ?, due_amount = ? WHERE id = ?", [$discount_amt, $due, $id]);

    if ($due <= 0 && $paid > 0) {
        db_query("UPDATE transactions SET payment_status = 'paid' WHERE id = ?", [$id]);
    } elseif ($paid > 0 && $due > 0) {
        db_query("UPDATE transactions SET payment_status = 'partial' WHERE id = ?", [$id]);
    }

    set_flash('success', 'Discount of ' . format_currency($discount_amt) . ' applied to invoice #' . $tx['invoice_no'] . '.');
    redirect('index.php?tab=transactions');
}

$page_title = 'Apply Discount';
include __DIR__ . '/../../includes/header.php';

$total = (float)$tx['total_amount'];
$current_discount = (float)$tx['discount'];
$paid = (float)$tx['paid_amount'];
$due = $total - $current_discount - $paid;
$max_allowed = $total - $paid;
$current_pct = $total > 0 ? round($current_discount / $total * 100, 1) : 0;

$json_total = json_encode($total);
$json_paid = json_encode($paid);
$json_max = json_encode($max_allowed);
$js_sym = json_encode('KSh');
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900"><i class="fas fa-tag text-teal-600 mr-2"></i>Apply Discount</h1>
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
            <div class="flex justify-between text-sm"><span class="text-gray-600">Already Paid</span><span class="font-bold text-green-600"><?= format_currency($paid) ?></span></div>
            <?php if ($current_discount > 0): ?>
            <div class="flex justify-between text-sm"><span class="text-gray-600">Current Discount</span><span class="font-bold text-orange-600"><?= format_currency($current_discount) ?></span></div>
            <?php endif; ?>
            <div class="flex justify-between text-sm pt-2 border-t border-dashed border-gray-300">
                <span class="font-medium text-gray-700">New Due</span>
                <span class="font-bold text-lg" id="displayDue"><?= format_currency($due) ?></span>
            </div>
        </div>

        <?php if (has_flash('error')): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <form method="POST" id="discountForm" autocomplete="off">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount (%)</label>
                    <input type="text" inputmode="decimal" id="pctInput" placeholder="e.g. 10" autocomplete="off"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Amount <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">KSh</span>
                        <input type="text" inputmode="decimal" name="discount_amount" id="amtInput" required autocomplete="new-password"
                            class="w-full border border-gray-300 rounded-lg pl-12 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"
                            placeholder="Enter amount" value="">
                    </div>
                </div>
            </div>

            <div id="previewBox" class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800 mb-4 hidden">
                <i class="fas fa-lightbulb mr-1"></i> New due after discount: <strong id="previewDue">—</strong>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
                    <i class="fas fa-check"></i> Apply Discount
                </button>
                <a href="index.php?tab=transactions" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    'use strict';
    var total = <?= $json_total ?>;
    var paid = <?= $json_paid ?>;
    var maxAmt = <?= $json_max ?>;
    var sym = <?= $js_sym ?>;

    var pctEl = document.getElementById('pctInput');
    var amtEl = document.getElementById('amtInput');
    var previewEl = document.getElementById('previewBox');
    var previewDue = document.getElementById('previewDue');
    var displayDue = document.getElementById('displayDue');

    function liveUpdate() {
        var pct = parseFloat(pctEl.value) || 0;
        var amt = parseFloat(amtEl.value) || 0;

        if (document.activeElement === pctEl && pct > 0) {
            amt = total * pct / 100;
            if (amt > maxAmt) { amt = maxAmt; }
            amtEl.value = amt.toFixed(2);
        } else if (document.activeElement === amtEl && amt > 0) {
            pct = total > 0 ? (amt / total * 100) : 0;
            if (pct > 100) { pct = 100; amt = total; }
            if (amt > maxAmt) { amt = maxAmt; pct = total > 0 ? (amt / total * 100) : 0; }
            pctEl.value = pct.toFixed(1);
        }

        amt = parseFloat(amtEl.value) || 0;
        var newDue = total - paid - amt;
        if (newDue < 0) newDue = 0;

        var fmt = sym + ' ' + newDue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        previewDue.textContent = fmt;
        displayDue.textContent = fmt;
        previewDue.className = newDue > 0 ? 'font-bold text-red-600' : 'font-bold text-green-600';
        displayDue.className = 'font-bold text-lg ' + (newDue > 0 ? 'text-red-600' : 'text-green-600');
        previewEl.classList.toggle('hidden', amt <= 0);
    }

    pctEl.addEventListener('input', liveUpdate);
    amtEl.addEventListener('input', liveUpdate);

    pctEl.value = '';
    amtEl.value = '';
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
