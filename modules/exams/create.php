<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Schedule New Exam';
$classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");
$subjects = db_get_all("SELECT * FROM subjects WHERE is_active = 1 ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $class_id = (int)($_POST['class_id'] ?? 0);
    $exam_date = sanitize($_POST['exam_date'] ?? '');
    $start_time = sanitize($_POST['start_time'] ?? '');
    $end_time = sanitize($_POST['end_time'] ?? '');
    $subject_ids = $_POST['subject_ids'] ?? [];

    if (empty($title) || !$class_id || empty($exam_date) || empty($start_time) || empty($end_time)) {
        set_flash('error', 'Please fill in all required fields.');
    } else {
        $exam_id = db_insert(
            "INSERT INTO exams (title, class_id, exam_date, start_time, end_time, created_by) VALUES (?, ?, ?, ?, ?, ?)",
            [$title, $class_id, $exam_date, $start_time, $end_time, get_user_id()]
        );
        if ($exam_id) {
            foreach ($subject_ids as $sid) {
                $max_marks = (int)($_POST['max_marks_' . $sid] ?? 100);
                $pass_marks = (int)($_POST['pass_marks_' . $sid] ?? 33);
                db_insert("INSERT INTO exam_subjects (exam_id, subject_id, max_marks, pass_marks) VALUES (?, ?, ?, ?)",
                    [$exam_id, (int)$sid, $max_marks, $pass_marks]);
            }
            set_flash('success', 'Exam scheduled successfully!');
            redirect('index.php?class_id=' . $class_id);
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Schedule New Exam</h3>

        <?php if (has_flash('error')): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Exam Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., Mid-Term Examination">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                    <select name="class_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Exam Date <span class="text-red-500">*</span></label>
                    <input type="date" name="exam_date" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Time <span class="text-red-500">*</span></label>
                    <input type="time" name="start_time" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Time <span class="text-red-500">*</span></label>
                    <input type="time" name="end_time" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subjects</label>
                <div class="border border-gray-300 rounded-lg p-4 space-y-3">
                    <?php foreach ($subjects as $sub): ?>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="subject_ids[]" value="<?= $sub['id'] ?>" class="rounded text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-gray-700 w-32"><?= sanitize($sub['name']) ?></span>
                        <input type="number" name="max_marks_<?= $sub['id'] ?>" placeholder="Max Marks" value="100" class="w-24 border border-gray-200 rounded px-2 py-1 text-xs">
                        <input type="number" name="pass_marks_<?= $sub['id'] ?>" placeholder="Pass Marks" value="33" class="w-24 border border-gray-200 rounded px-2 py-1 text-xs">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-calendar-check mr-2"></i> Schedule Exam
                </button>
                <a href="index.php" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
