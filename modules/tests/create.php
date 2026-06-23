<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Create New Test';
$classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");
$subjects = db_get_all("SELECT * FROM subjects WHERE is_active = 1 ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $class_id = (int)($_POST['class_id'] ?? 0);
    $subject_id = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : 'NULL';
    $total_marks = (int)($_POST['total_marks'] ?? 100);
    $difficulty = sanitize($_POST['difficulty'] ?? 'medium');
    $mcq_count = (int)($_POST['mcq_count'] ?? 0);
    $subjective_count = (int)($_POST['subjective_count'] ?? 0);
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 60);
    $instructions = sanitize($_POST['instructions'] ?? '');

    if (empty($title) || !$class_id) {
        set_flash('error', 'Please fill in required fields.');
    } else {
        $shuffle = isset($_POST['shuffle_questions']) ? 1 : 0;
        $test_id = db_insert(
            "INSERT INTO tests (title, class_id, subject_id, total_marks, difficulty, mcq_count, subjective_count, duration_minutes, instructions, shuffle_questions, created_by) VALUES (?, ?, $subject_id, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$title, $class_id, $total_marks, $difficulty, $mcq_count, $subjective_count, $duration_minutes, $instructions, $shuffle, get_user_id()]
        );
        if ($test_id) {
            set_flash('success', 'Test created! Now add questions.');
            redirect('add-questions.php?id=' . $test_id);
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Create New Test</h3>

        <?php if (has_flash('error')): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Test Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., Chapter 5 - Algebra Quiz">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <select name="subject_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Marks</label>
                    <input type="number" name="total_marks" value="100" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" value="60" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" style="display: none;">Difficulty</label>
                    <select name="difficulty" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="easy">Easy</option>
                        <option value="medium" selected>Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Question Count</label>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500">MCQ Questions</label>
                        <input type="number" name="mcq_count" value="5" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Subjective Questions</label>
                        <input type="number" name="subjective_count" value="3" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                <textarea name="instructions" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Instructions for students..."></textarea>
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" name="shuffle_questions" id="shuffle_questions" value="1" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                <label for="shuffle_questions" class="text-sm text-gray-700">Shuffle questions for each student <span class="text-xs text-gray-400">(each student sees questions in random order)</span></label>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-flask mr-2"></i> Create Test & Add Questions
                </button>
                <a href="index.php" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
