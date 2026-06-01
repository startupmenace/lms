<?php
$today = date('Y-m-d');
$today_att = db_get_row("SELECT COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present FROM attendance WHERE class_id=? AND date=?", [$class_id, $today]);
$att_pct = $today_att['total'] > 0 ? round(($today_att['present'] / $today_att['total']) * 100) : 0;

$week_att = db_get_all("SELECT date, COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present FROM attendance WHERE class_id=? AND date >= DATE_SUB(?, INTERVAL 7 DAY) GROUP BY date ORDER BY date", [$class_id, $today]);
$upcoming_hw = db_get_all("SELECT h.*, s.name as subject_name FROM homework h LEFT JOIN subjects s ON h.subject_id=s.id WHERE h.class_id=? AND (h.due_date >= ? OR h.due_date IS NULL) AND h.is_active=1 ORDER BY h.due_date ASC LIMIT 5", [$class_id, $today]);
$teacher_count = db_get_row("SELECT COUNT(*) as count FROM class_teachers WHERE class_id=?", [$class_id])['count'];
$subject_count = db_get_row("SELECT COUNT(*) as count FROM class_subjects WHERE class_id=?", [$class_id])['count'];
$resource_count = db_get_row("SELECT COUNT(*) as count FROM class_resources WHERE class_id=?", [$class_id])['count'];
$homework_count = db_get_row("SELECT COUNT(*) as count FROM homework WHERE class_id=? AND is_active=1", [$class_id])['count'];
?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
        <p class="text-2xl font-bold text-green-700"><?= $att_pct ?>%</p>
        <p class="text-xs text-green-600">Today's Attendance</p>
        <p class="text-[10px] text-green-500"><?= $today_att['present'] ?? 0 ?>/<?= $today_att['total'] ?? 0 ?> present</p>
    </div>
    <div class="bg-blue-50 rounded-xl border border-blue-200 p-4 text-center">
        <p class="text-2xl font-bold text-blue-700"><?= $teacher_count ?></p>
        <p class="text-xs text-blue-600">Teachers</p>
        <p class="text-[10px] text-blue-500"><?= $subject_count ?> subjects</p>
    </div>
    <div class="bg-amber-50 rounded-xl border border-amber-200 p-4 text-center">
        <p class="text-2xl font-bold text-amber-700"><?= $homework_count ?></p>
        <p class="text-xs text-amber-600">Homework</p>
        <p class="text-[10px] text-amber-500"><?= count($upcoming_hw) ?> upcoming</p>
    </div>
    <div class="bg-purple-50 rounded-xl border border-purple-200 p-4 text-center">
        <p class="text-2xl font-bold text-purple-700"><?= $resource_count ?></p>
        <p class="text-xs text-purple-600">Resources</p>
        <p class="text-[10px] text-purple-500">Shared links</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="font-bold text-gray-900 text-sm">Attendance (Last 7 Days)</h3>
        </div>
        <div class="p-4">
            <?php if (empty($week_att)): ?>
            <p class="text-gray-400 text-xs text-center py-4">No attendance records this week.</p>
            <?php else: ?>
            <div class="flex items-end gap-2 h-24">
                <?php foreach ($week_att as $w): 
                    $pct = $w['total'] > 0 ? ($w['present'] / $w['total']) * 100 : 0;
                ?>
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full bg-gray-100 rounded-t relative" style="height:80px">
                        <div class="absolute bottom-0 w-full bg-teal-500 rounded-t transition-all" style="height:<?= $pct ?>%"></div>
                    </div>
                    <span class="text-[9px] text-gray-500"><?= date('D', strtotime($w['date'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="font-bold text-gray-900 text-sm">Upcoming Homework</h3>
        </div>
        <div class="p-4 space-y-2">
            <?php if (empty($upcoming_hw)): ?>
            <p class="text-gray-400 text-xs text-center py-4">No homework assigned.</p>
            <?php else: ?>
            <?php foreach ($upcoming_hw as $h): ?>
            <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                <div>
                    <p class="text-xs font-medium text-gray-900"><?= sanitize($h['title']) ?></p>
                    <p class="text-[10px] text-gray-400"><?= sanitize($h['subject_name'] ?? 'General') ?> · <?= $h['submission_type'] ?></p>
                </div>
                <span class="text-[10px] text-gray-500"><?= $h['due_date'] ? format_date($h['due_date']) : 'No due date' ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
