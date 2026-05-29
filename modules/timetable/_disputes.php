<?php
if (has_role('admin')) {
    $disputes = db_get_all("SELECT td.*, u.full_name as teacher_name, te.day_of_week, te.period_id, te.room,
        tp.name as period_name, tp.start_time, tp.end_time, s.name as subject_name, c.name as class_name
        FROM timetable_disputes td
        JOIN timetable_entries te ON td.entry_id=te.id
        JOIN timetable_periods tp ON te.period_id=tp.id
        LEFT JOIN subjects s ON te.subject_id=s.id
        LEFT JOIN classes c ON te.class_id=c.id
        JOIN users u ON td.teacher_id=u.id
        ORDER BY td.created_at DESC");
} else {
    $disputes = db_get_all("SELECT td.*, te.day_of_week, te.period_id, te.room,
        tp.name as period_name, tp.start_time, tp.end_time, s.name as subject_name, c.name as class_name
        FROM timetable_disputes td
        JOIN timetable_entries te ON td.entry_id=te.id
        JOIN timetable_periods tp ON te.period_id=tp.id
        LEFT JOIN subjects s ON te.subject_id=s.id
        LEFT JOIN classes c ON te.class_id=c.id
        WHERE td.teacher_id=?
        ORDER BY td.created_at DESC", [get_user_id()]);
}
$day_short = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
?>
<div class="flex items-center justify-between mb-4">
    <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
        <i class="fas fa-exclamation-triangle text-teal-600"></i> Timetable Disputes
        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium"><?= count($disputes) ?></span>
    </h3>
    <?php if (!has_role('admin')): ?>
    <a href="?tab=view" class="text-teal-600 hover:text-teal-800 text-xs font-medium"><i class="fas fa-arrow-left mr-1"></i> Back to Timetable</a>
    <?php endif; ?>
</div>

<?php if (empty($disputes)): ?>
<div class="text-center py-12 text-gray-400">
    <i class="fas fa-check-circle text-4xl mb-3 block text-green-300"></i>
    <p class="text-sm text-gray-500">No disputes raised. Everything looks good!</p>
</div>
<?php else: ?>
<div class="space-y-3">
    <?php foreach ($disputes as $d): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-4 <?= $d['status']=='pending' ? 'border-l-4 border-l-amber-500' : ($d['status']=='resolved' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-gray-400') ?>">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full 
                        <?= $d['status']=='pending'?'bg-amber-100 text-amber-700':($d['status']=='resolved'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-600') ?>">
                        <?= ucfirst($d['status']) ?>
                    </span>
                    <?php if (has_role('admin')): ?>
                    <span class="text-xs text-gray-500">by <strong><?= sanitize($d['teacher_name']) ?></strong></span>
                    <?php endif; ?>
                    <span class="text-xs text-gray-400"><?= format_date($d['created_at'], 'd M Y, h:i A') ?></span>
                </div>
                <div class="text-sm font-medium text-gray-900 mb-1">
                    <?= sanitize($d['subject_name'] ?? 'Unknown') ?> · <?= sanitize($d['class_name'] ?? '') ?>
                </div>
                <div class="text-xs text-gray-500 mb-2">
                    <?= $day_short[$d['day_of_week']] ?> · <?= sanitize($d['period_name']) ?> (<?= date('h:i A', strtotime($d['start_time'])) ?> — <?= date('h:i A', strtotime($d['end_time'])) ?>)
                    <?php if ($d['room']): ?> · Room <?= sanitize($d['room']) ?><?php endif; ?>
                </div>
                <div class="bg-gray-50 rounded-lg p-2.5 text-xs text-gray-700">
                    <strong class="text-gray-900">Reason:</strong> <?= sanitize($d['reason']) ?>
                </div>
                <?php if ($d['resolution_notes']): ?>
                <div class="mt-2 bg-teal-50 rounded-lg p-2.5 text-xs text-teal-800">
                    <strong>Resolution:</strong> <?= sanitize($d['resolution_notes']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (has_role('admin') && $d['status'] == 'pending'): ?>
            <div class="flex-shrink-0">
                <button onclick="document.getElementById('resolve-<?= $d['id'] ?>').classList.remove('hidden')" class="bg-teal-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-check mr-1"></i> Resolve
                </button>
            </div>
            <div id="resolve-<?= $d['id'] ?>" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)this.classList.add('hidden')">
                <div class="bg-white rounded-xl p-5 w-full max-w-sm" onclick="event.stopPropagation()">
                    <h4 class="font-bold text-gray-900 text-sm mb-2">Resolve Dispute</h4>
                    <p class="text-xs text-gray-500 mb-3"><?= sanitize($d['teacher_name']) ?> reported: <?= sanitize($d['reason']) ?></p>
                    <form method="POST">
                        <input type="hidden" name="action" value="resolve_dispute">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <textarea name="resolution_notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Resolution notes..."></textarea>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="submit" name="status" value="resolved" class="flex-1 bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-green-700 transition"><i class="fas fa-check mr-1"></i> Resolve</button>
                            <button type="submit" name="status" value="dismissed" class="flex-1 bg-gray-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-gray-600 transition"><i class="fas fa-times mr-1"></i> Dismiss</button>
                            <button type="button" onclick="this.closest('.hidden').classList.add('hidden')" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
