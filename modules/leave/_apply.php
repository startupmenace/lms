<?php
$leave_types = db_get_all("SELECT * FROM leave_types WHERE is_active=1 ORDER BY name");
$balance_info = [];
foreach ($leave_types as $lt) {
    $used = db_get_row("SELECT COALESCE(SUM(total_days),0) as used FROM leave_applications WHERE user_id=? AND leave_type_id=? AND status IN ('approved','pending') AND YEAR(from_date)=YEAR(CURDATE())", [get_user_id(), $lt['id']])['used'];
    $balance_info[$lt['id']] = [
        'remaining' => $lt['max_days_per_year'] - $used,
        'total' => $lt['max_days_per_year']
    ];
}
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fas fa-paper-plane text-teal-600"></i> New Leave Application
        </h3>
        <form method="POST" id="leave-form">
            <input type="hidden" name="action" value="apply">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Leave Type *</label>
                    <select name="leave_type_id" id="leave-type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">— Select Type —</option>
                        <?php foreach ($leave_types as $lt): ?>
                        <option value="<?= $lt['id'] ?>" data-remaining="<?= $balance_info[$lt['id']]['remaining'] ?>" data-total="<?= $lt['max_days_per_year'] ?>">
                            <?= sanitize($lt['name']) ?> (<?= $balance_info[$lt['id']]['remaining'] ?> days left)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Total Days</label>
                    <div id="days-display" class="px-3 py-2 text-sm text-gray-500 bg-gray-50 rounded-lg border border-gray-200">Select dates first</div>
                    <input type="hidden" name="total_days" id="total-days-input">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">From Date *</label>
                    <input type="date" name="from_date" id="from-date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">To Date *</label>
                    <input type="date" name="to_date" id="to-date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 mb-1">Reason *</label>
                <textarea name="reason" required rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Please provide a detailed reason for your leave request..."></textarea>
            </div>

            <!-- Balance info -->
            <div id="balance-info" class="hidden mb-4 bg-teal-50 border border-teal-200 rounded-lg p-3 text-sm text-teal-800">
                <i class="fas fa-info-circle mr-1"></i> <span id="balance-text"></span>
            </div>

            <button type="submit" class="bg-teal-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-paper-plane mr-1"></i> Submit Application
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const from = document.getElementById('from-date');
    const to = document.getElementById('to-date');
    const display = document.getElementById('days-display');
    const hidden = document.getElementById('total-days-input');
    const type = document.getElementById('leave-type');
    const balanceInfo = document.getElementById('balance-info');
    const balanceText = document.getElementById('balance-text');

    function calcDays() {
        if (!from.value || !to.value) { display.textContent = 'Select dates first'; hidden.value = ''; balanceInfo.classList.add('hidden'); return; }
        const f = new Date(from.value), t = new Date(to.value);
        if (t < f) { display.textContent = 'End must be after start'; hidden.value = ''; balanceInfo.classList.add('hidden'); return; }
        const days = Math.floor((t - f) / 86400000) + 1;
        display.textContent = days + ' day' + (days > 1 ? 's' : '');
        hidden.value = days;

        const opt = type.options[type.selectedIndex];
        if (opt && opt.dataset.remaining !== undefined) {
            const remaining = parseInt(opt.dataset.remaining);
            if (days > remaining) {
                balanceInfo.classList.remove('hidden');
                balanceText.textContent = 'You only have ' + remaining + ' day(s) remaining for ' + opt.text.split(' (')[0] + '. You need ' + (days - remaining) + ' more.';
                balanceInfo.className = 'mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800';
            } else {
                balanceInfo.classList.remove('hidden');
                balanceText.textContent = 'You have ' + remaining + ' day(s) remaining for ' + opt.text.split(' (')[0] + '. This leave will leave you with ' + (remaining - days) + '.';
                balanceInfo.className = 'mb-4 bg-teal-50 border border-teal-200 rounded-lg p-3 text-sm text-teal-800';
            }
        }
    }

    from.addEventListener('change', calcDays);
    to.addEventListener('change', calcDays);
    type.addEventListener('change', calcDays);
});
</script>
