<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('student');

$page_title = 'My Tests';

$user_id = get_user_id();
$student = db_get_row("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.user_id = ?", [$user_id]);
if (!$student) {
    $student = db_get_row("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id ORDER BY s.id LIMIT 1");
}
$student_id = $student['id'] ?? 0;
$class_id = $student['class_id'] ?? 0;

$tests = db_get_all("SELECT t.*, s.name as subject_name, 
    (SELECT ts.status FROM test_submissions ts WHERE ts.test_id = t.id AND ts.student_id = ?) as submission_status,
    (SELECT ts.total_marks_obtained FROM test_submissions ts WHERE ts.test_id = t.id AND ts.student_id = ?) as obtained_marks
    FROM tests t LEFT JOIN subjects s ON t.subject_id = s.id WHERE t.class_id = ? AND t.is_active = 1 ORDER BY t.created_at DESC", [$student_id, $student_id, $class_id]);

include __DIR__ . '/../../includes/student-header.php';
?>

<div class="max-w-5xl mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-6">My Tests</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if (empty($tests)): ?>
        <div class="col-span-full bg-white rounded-xl border border-gray-200 p-12 text-center">
            <i class="fas fa-flask text-5xl text-gray-300 mb-4 block"></i>
            <p class="text-gray-500">No tests available for your class.</p>
        </div>
        <?php else: ?>
        <?php foreach ($tests as $t): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-start justify-between mb-2">
                <h3 class="font-semibold text-gray-900"><?= sanitize($t['title']) ?></h3>
                <span class="text-xs px-2 py-1 rounded-full <?= $t['difficulty'] == 'easy' ? 'bg-green-100 text-green-700' : ($t['difficulty'] == 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= ucfirst($t['difficulty']) ?></span>
            </div>
            <div class="text-xs text-gray-500 space-y-1">
                <div><i class="fas fa-book w-4 mr-1"></i> <?= sanitize($t['subject_name'] ?? 'All Subjects') ?></div>
                <div><i class="fas fa-clock w-4 mr-1"></i> <?= $t['duration_minutes'] ?> minutes | <?= $t['total_marks'] ?> marks<?= $t['shuffle_questions'] ? ' | <i class="fas fa-random text-purple-500 mr-1"></i> Shuffled' : '' ?></div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                <?php if ($t['submission_status']): ?>
                    <span class="text-xs px-2 py-1 rounded-full <?= $t['submission_status'] == 'evaluated' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                        <?= ucfirst($t['submission_status']) ?>
                    </span>
                    <?php if ($t['obtained_marks'] !== null): ?>
                    <span class="text-sm font-medium"><?= $t['obtained_marks'] ?>/<?= $t['total_marks'] ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="take-test.php?id=<?= $t['id'] ?>" class="text-teal-600 text-sm font-medium hover:underline"><i class="fas fa-play mr-1"></i> Start Test</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/student-footer.php'; ?>
