<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Attendance Management';

$classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");
$class_id = (int)($_GET['class_id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');
$students = [];

if ($class_id) {
    $students = db_get_all("SELECT s.*, 
        (SELECT status FROM attendance WHERE student_id = s.id AND date = ?) as attendance_status
        FROM students s WHERE s.class_id = ? ORDER BY s.parent_name", [$date, $class_id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    require_role('admin', 'teacher');
    foreach ($_POST['status'] as $student_id => $status) {
        $existing = db_get_row("SELECT id FROM attendance WHERE student_id = ? AND date = ?", [(int)$student_id, $date]);
        if ($existing) {
            db_query("UPDATE attendance SET status = ?, marked_by = ? WHERE id = ?", [$status, get_user_id(), $existing['id']]);
        } else {
            db_insert("INSERT INTO attendance (student_id, class_id, date, status, marked_by) VALUES (?, ?, ?, ?, ?)",
                [(int)$student_id, $class_id, $date, $status, get_user_id()]);
        }
    }
    set_flash('success', 'Attendance saved for ' . format_date($date));
    redirect("index.php?class_id=$class_id&date=$date");
}

$total_students = db_get_row("SELECT COUNT(*) as count FROM students WHERE class_id = ?", [$class_id])['count'] ?? 0;
$today_stats = null;
if ($class_id) {
    $today_stats = db_get_row("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status='leave' THEN 1 ELSE 0 END) as `leave`
        FROM attendance WHERE class_id = ? AND date = ?", [$class_id, $date]);
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <?php if (has_flash('success')): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('success') ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $class_id == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="<?= $date ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
            </div>
            <?php if ($class_id): ?>
            <div class="flex gap-2 ml-auto">
                <a href="?class_id=<?= $class_id ?>&date=<?= date('Y-m-d', strtotime($date . ' -1 day')) ?>" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200"><i class="fas fa-chevron-left"></i></a>
                <a href="?class_id=<?= $class_id ?>&date=<?= date('Y-m-d') ?>" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200">Today</a>
                <a href="?class_id=<?= $class_id ?>&date=<?= date('Y-m-d', strtotime($date . ' +1 day')) ?>" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200"><i class="fas fa-chevron-right"></i></a>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($class_id && $students): ?>
    <?php if ($today_stats && $today_stats['total'] > 0): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600"><?= $today_stats['present'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Present</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-600"><?= $today_stats['absent'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Absent</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600"><?= $today_stats['late'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Late</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-coral-600"><?= $today_stats['leave'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Leave</p>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-4 font-medium text-gray-500">#</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Student Name</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Enrollment ID</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-500">Present</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-500">Absent</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-500">Late</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-500">Leave</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($students as $s): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-500"><?= $i++ ?></td>
                        <td class="py-3 px-4 font-medium text-gray-900"><?= sanitize($s['parent_name'] ?? 'N/A') ?></td>
                        <td class="py-3 px-4 text-gray-600"><?= sanitize($s['enrollment_id']) ?></td>
                        <?php $status = $s['attendance_status'] ?? 'present'; ?>
                        <td class="py-3 px-4 text-center">
                            <input type="radio" name="status[<?= $s['id'] ?>]" value="present" <?= $status == 'present' ? 'checked' : '' ?> class="text-green-600 focus:ring-green-500">
                        </td>
                        <td class="py-3 px-4 text-center">
                            <input type="radio" name="status[<?= $s['id'] ?>]" value="absent" <?= $status == 'absent' ? 'checked' : '' ?> class="text-red-600 focus:ring-red-500">
                        </td>
                        <td class="py-3 px-4 text-center">
                            <input type="radio" name="status[<?= $s['id'] ?>]" value="late" <?= $status == 'late' ? 'checked' : '' ?> class="text-amber-600 focus:ring-amber-500">
                        </td>
                        <td class="py-3 px-4 text-center">
                            <input type="radio" name="status[<?= $s['id'] ?>]" value="leave" <?= $status == 'leave' ? 'checked' : '' ?> class="text-coral-600 focus:ring-coral-500">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <button type="submit" name="save_attendance" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-save mr-2"></i>Save Attendance
            </button>
        </div>
    </form>
    <?php elseif ($class_id): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-users text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No students found in this class.</p>
        <a href="<?= BASE_URL ?>/modules/students/create.php" class="text-teal-600 hover:underline text-sm mt-2 inline-block">Add students</a>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-calendar-check text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">Select a class and date to mark attendance.</p>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
