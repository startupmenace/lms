<?php
$students = db_get_all("SELECT s.*, u.full_name, u.email, u.phone,
    (SELECT COUNT(*) FROM attendance a WHERE a.student_id=s.id AND a.date=CURDATE() AND a.status='present') as today_present
    FROM students s JOIN users u ON s.user_id=u.id WHERE s.class_id=? AND s.is_active=1 ORDER BY u.full_name", [$class_id]);
$student_count = count($students);
?>
<div class="flex items-center justify-between mb-4">
    <div>
        <p class="text-sm text-gray-500"><?= $student_count ?> enrolled students</p>
    </div>
</div>

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-200">
                <th class="pb-2 pr-3 font-medium">Student</th>
                <th class="pb-2 pr-3 font-medium">Email</th>
                <th class="pb-2 pr-3 font-medium">Phone</th>
                <th class="pb-2 pr-3 font-medium">Enrollment ID</th>
                <th class="pb-2 text-center font-medium">Today</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
            <tr><td colspan="5" class="py-8 text-center text-gray-400 text-xs">No students in this class.</td></tr>
            <?php else: ?>
            <?php foreach ($students as $s): ?>
            <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                <td class="py-2.5 pr-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-400 to-coral-400 flex items-center justify-center text-white font-bold text-xs"><?= strtoupper(substr($s['full_name'], 0, 1)) ?></div>
                        <div>
                            <p class="font-medium text-gray-900 text-xs"><?= sanitize($s['full_name']) ?></p>
                            <p class="text-[10px] text-gray-400">ID: <?= $s['id'] ?></p>
                        </div>
                    </div>
                </td>
                <td class="py-2.5 pr-3 text-xs text-gray-500"><?= sanitize($s['email'] ?? '—') ?></td>
                <td class="py-2.5 pr-3 text-xs text-gray-500"><?= sanitize($s['phone'] ?? '—') ?></td>
                <td class="py-2.5 pr-3 text-xs text-gray-500"><?= sanitize($s['enrollment_id'] ?? '—') ?></td>
                <td class="py-2.5 text-center">
                    <?php if ($s['today_present'] > 0): ?>
                    <span class="inline-flex items-center gap-1 text-green-600 text-xs"><i class="fas fa-check-circle text-[10px]"></i> Present</span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 text-gray-300 text-xs"><i class="fas fa-minus-circle text-[10px]"></i> —</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
