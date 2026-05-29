<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('student');

$page_title = 'My Attendance';

$user_id = get_user_id();
$student = db_get_row("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ? ORDER BY s.id LIMIT 1", [$user_id]);
$student_id = $student['id'] ?? 1;

$attendance = db_get_all("SELECT a.*, c.name as class_name FROM attendance a LEFT JOIN classes c ON a.class_id = c.id WHERE a.student_id = ? ORDER BY a.date DESC LIMIT 60", [$student_id]);

include __DIR__ . '/../../includes/student-header.php';
?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-6">My Attendance</h2>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Date</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attendance)): ?>
                <tr><td colspan="2" class="py-12 text-center text-gray-400">No attendance records found.</td></tr>
                <?php else: ?>
                <?php foreach ($attendance as $a): ?>
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 text-gray-900"><?= format_date($a['date']) ?></td>
                    <td class="py-3 px-4">
                        <span class="text-xs px-2 py-1 rounded-full <?= $a['status'] == 'present' ? 'bg-green-100 text-green-700' : ($a['status'] == 'absent' ? 'bg-red-100 text-red-700' : ($a['status'] == 'late' ? 'bg-amber-100 text-amber-700' : 'bg-coral-100 text-coral-700')) ?>">
                            <?= ucfirst($a['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/student-footer.php'; ?>
