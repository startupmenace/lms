<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Schedule Live Class';
if (has_role('admin')) {
    $classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");
} else {
    $uid = get_user_id();
    $classes = db_get_all("SELECT c.* FROM classes c JOIN class_teachers ct ON c.id = ct.class_id WHERE c.is_active = 1 AND ct.teacher_id = ? ORDER BY c.name", [$uid]);
}
$subjects = db_get_all("SELECT * FROM subjects WHERE is_active = 1 ORDER BY name");
$teachers = db_get_all("SELECT id, full_name FROM users WHERE role IN ('admin','teacher') ORDER BY full_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : 'NULL';
    $subject_id = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : 'NULL';
    $teacher_id = (int)($_POST['teacher_id'] ?? get_user_id());
    $scheduled_at = sanitize($_POST['scheduled_at'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 60);
    $meeting_url = sanitize($_POST['meeting_url'] ?? '');

    if (empty($title) || empty($scheduled_at)) {
        set_flash('error', 'Please fill in required fields.');
    } else {
        $id = db_insert(
            "INSERT INTO live_classes (title, class_id, subject_id, teacher_id, scheduled_at, duration_minutes, meeting_url, status) VALUES (?, $class_id, $subject_id, ?, ?, ?, ?, 'scheduled')",
            [$title, $teacher_id, $scheduled_at, $duration, $meeting_url]
        );
        if ($id) {
            set_flash('success', 'Live class scheduled!');
            redirect('index.php');
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Schedule Live Class</h3>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Session Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., Chapter 6 - Algebra Review">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                    <select name="class_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <select name="subject_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">General</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date & Time <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="scheduled_at" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration (min)</label>
                    <input type="number" name="duration_minutes" value="60" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>
            <div class="bg-teal-50 border border-teal-200 rounded-lg p-3 text-xs text-teal-700 flex items-start gap-2">
                <i class="fas fa-video mt-0.5"></i>
                <div>
                    <strong>Built-in video call enabled.</strong> No external meeting URL needed — a secure 8x8 Jitsi room will be automatically created when the session starts. All participants can join directly from the classroom page.
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-calendar-check mr-2"></i> Schedule
                </button>
                <a href="index.php" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
