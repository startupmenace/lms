<?php
$user_id = get_user_id();
$applications = db_get_all("SELECT la.*, lt.name as leave_type_name, lt.color, u.full_name as reviewer_name
    FROM leave_applications la
    JOIN leave_types lt ON la.leave_type_id=lt.id
    LEFT JOIN users u ON la.reviewed_by=u.id
    WHERE la.user_id=?
    ORDER BY la.applied_at DESC", [$user_id]);

$balances = db_get_all("SELECT lt.*, COALESCE(la.used,0) as used_days
    FROM leave_types lt
    LEFT JOIN (SELECT leave_type_id, COALESCE(SUM(total_days),0) as used FROM leave_applications WHERE user_id=? AND status IN ('approved','pending') AND YEAR(from_date)=YEAR(CURDATE()) GROUP BY leave_type_id) la ON lt.id=la.leave_type_id
    WHERE lt.is_active=1 ORDER BY lt.name", [$user_id]);
?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
    <?php foreach ($balances as $b):
        $pct = $b['max_days_per_year'] > 0 ? round(($b['used_days'] / $b['max_days_per_year']) * 100) : 0;
    ?>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="w-3 h-3 rounded-full" style="background:<?= $b['color'] ?>"></span>
            <span class="font-semibold text-gray-900 text-sm"><?= sanitize($b['name']) ?></span>
        </div>
        <div class="text-2xl font-bold text-gray-900"><?= $b['max_days_per_year'] - $b['used_days'] ?></div>
        <div class="text-xs text-gray-500">of <?= $b['max_days_per_year'] ?> days remaining</div>
        <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
            <div class="h-1.5 rounded-full transition-all" style="width:<?= min(100,$pct) ?>%;background:<?= $b['color'] ?>"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-gray-900 text-sm">My Leave History</h3>
        <a href="?tab=apply" class="text-teal-600 hover:text-teal-800 text-xs font-medium"><i class="fas fa-plus mr-1"></i> Apply</a>
    </div>
    <?php if (empty($applications)): ?>
    <div class="text-center py-10 text-gray-400">
        <i class="fas fa-calendar-times text-3xl mb-3 block text-gray-300"></i>
        <p class="text-sm">No leave applications yet.</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50/50">
                    <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Type</th>
                    <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">Reason</th>
                    <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Dates</th>
                    <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Days</th>
                    <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Status</th>
                    <th class="text-right py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $a): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="py-3 px-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" style="background:<?= $a['color'] ?>"></span>
                            <span class="font-medium text-gray-900"><?= sanitize($a['leave_type_name']) ?></span>
                        </div>
                    </td>
                    <td class="py-3 px-3 text-gray-600 hidden sm:table-cell max-w-[200px] truncate"><?= sanitize($a['reason']) ?></td>
                    <td class="py-3 px-3 text-center text-xs text-gray-600">
                        <?= format_date($a['from_date']) ?> — <?= format_date($a['to_date']) ?>
                    </td>
                    <td class="py-3 px-3 text-center font-medium"><?= $a['total_days'] ?></td>
                    <td class="py-3 px-3 text-center">
                        <?php
                        $sc = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700','cancelled'=>'bg-gray-100 text-gray-600'];
                        ?>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $sc[$a['status']] ?? '' ?>"><?= ucfirst($a['status']) ?></span>
                    </td>
                    <td class="py-3 px-3 text-right">
                        <?php if ($a['status'] == 'pending'): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Cancel this leave?')">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button class="text-red-600 hover:text-red-800 text-xs font-medium"><i class="fas fa-times mr-0.5"></i> Cancel</button>
                        </form>
                        <?php else: ?>
                        <span class="text-xs text-gray-400"><?= $a['status'] == 'approved' ? '✓' : ($a['status'] == 'rejected' ? '✗' : '—') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
