<?php
$applications = db_get_all("SELECT la.*, lt.name as leave_type_name, lt.color, u.full_name as staff_name, rv.full_name as reviewer_name
    FROM leave_applications la
    JOIN leave_types lt ON la.leave_type_id=lt.id
    JOIN users u ON la.user_id=u.id
    LEFT JOIN users rv ON la.reviewed_by=rv.id
    ORDER BY la.applied_at DESC");
?>

<div class="flex items-center justify-between mb-4">
    <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
        <i class="fas fa-users text-teal-600"></i> All Leave Applications
    </h3>
    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full font-medium"><?= count($applications) ?> total</span>
</div>

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/50">
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Staff</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">Type</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden md:table-cell">Reason</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Dates</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Days</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Status</th>
                <th class="text-right py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Review</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($applications as $a): ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="py-3 px-3">
                    <div class="font-medium text-gray-900"><?= sanitize($a['staff_name']) ?></div>
                </td>
                <td class="py-3 px-3 hidden sm:table-cell">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" style="background:<?= $a['color'] ?>"></span>
                        <span><?= sanitize($a['leave_type_name']) ?></span>
                    </div>
                </td>
                <td class="py-3 px-3 text-gray-600 hidden md:table-cell max-w-[200px] truncate"><?= sanitize($a['reason']) ?></td>
                <td class="py-3 px-3 text-center text-xs text-gray-600"><?= format_date($a['from_date']) ?> — <?= format_date($a['to_date']) ?></td>
                <td class="py-3 px-3 text-center font-medium"><?= $a['total_days'] ?></td>
                <td class="py-3 px-3 text-center">
                    <?php $sc = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700','cancelled'=>'bg-gray-100 text-gray-600']; ?>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $sc[$a['status']] ?? '' ?>"><?= ucfirst($a['status']) ?></span>
                </td>
                <td class="py-3 px-3 text-right">
                    <?php if ($a['status'] == 'pending'): ?>
                    <button onclick="document.getElementById('review-<?= $a['id'] ?>').classList.remove('hidden')" class="text-teal-600 hover:text-teal-800 text-xs font-medium">
                        <i class="fas fa-check-circle mr-0.5"></i> Review
                    </button>
                    <div id="review-<?= $a['id'] ?>" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)this.classList.add('hidden')">
                        <div class="bg-white rounded-xl p-5 w-full max-w-md" onclick="event.stopPropagation()">
                            <h4 class="font-bold text-gray-900 mb-3">Review Leave — <?= sanitize($a['staff_name']) ?></h4>
                            <div class="text-xs text-gray-600 mb-4 space-y-1">
                                <p><span class="font-medium">Type:</span> <?= sanitize($a['leave_type_name']) ?></p>
                                <p><span class="font-medium">Dates:</span> <?= format_date($a['from_date']) ?> — <?= format_date($a['to_date']) ?> (<?= $a['total_days'] ?> days)</p>
                                <p><span class="font-medium">Reason:</span> <?= sanitize($a['reason']) ?></p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="review">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <div class="mb-3">
                                    <textarea name="review_notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Review notes (optional)"></textarea>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit" name="status" value="approved" class="flex-1 bg-green-600 text-white px-3 py-2 rounded-lg text-xs font-medium hover:bg-green-700 transition">
                                        <i class="fas fa-check mr-1"></i> Approve
                                    </button>
                                    <button type="submit" name="status" value="rejected" class="flex-1 bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-medium hover:bg-red-700 transition">
                                        <i class="fas fa-times mr-1"></i> Reject
                                    </button>
                                    <button type="button" onclick="this.closest('.hidden')?.classList.add('hidden')" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition">Back</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($a['reviewer_name']): ?>
                    <span class="text-xs text-gray-400">by <?= sanitize($a['reviewer_name']) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
