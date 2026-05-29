<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Test Management';
$tests = db_get_all("SELECT t.*, c.name as class_name, s.name as subject_name,
    (SELECT COUNT(*) FROM questions WHERE test_id = t.id) as question_count
    FROM tests t
    LEFT JOIN classes c ON t.class_id = c.id
    LEFT JOIN subjects s ON t.subject_id = s.id
    ORDER BY t.created_at DESC");

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500"><?= count($tests) ?> total tests</p>
        <a href="create.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Create New Test
        </a>
    </div>

    <?php if (empty($tests)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-flask text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No tests created yet.</p>
        <a href="create.php" class="text-teal-600 hover:underline text-sm mt-2 inline-block">Create your first test</a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($tests as $t): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-start justify-between mb-2">
                <h3 class="font-semibold text-gray-900"><?= sanitize($t['title']) ?></h3>
                <span class="text-xs px-2 py-1 rounded-full <?= $t['difficulty'] == 'easy' ? 'bg-green-100 text-green-700' : ($t['difficulty'] == 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
                    <?= ucfirst($t['difficulty']) ?>
                </span>
            </div>
            <div class="text-sm text-gray-500 space-y-1">
                <div><i class="fas fa-school w-4 text-gray-400 mr-1"></i> <?= sanitize($t['class_name'] ?? 'N/A') ?></div>
                <div><i class="fas fa-book w-4 text-gray-400 mr-1"></i> <?= sanitize($t['subject_name'] ?? 'All Subjects') ?></div>
                <div><i class="fas fa-list w-4 text-gray-400 mr-1"></i> <?= $t['question_count'] ?> Questions | <?= $t['total_marks'] ?> Marks</div>
                <div><i class="fas fa-clock w-4 text-gray-400 mr-1"></i> <?= $t['duration_minutes'] ?> min</div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex gap-3">
                <a href="view.php?id=<?= $t['id'] ?>" class="text-teal-600 hover:text-teal-800 text-sm"><i class="fas fa-eye"></i></a>
                <a href="edit.php?id=<?= $t['id'] ?>" class="text-amber-600 hover:text-amber-800 text-sm"><i class="fas fa-edit"></i></a>
                <a href="delete.php?id=<?= $t['id'] ?>" class="text-red-600 hover:text-red-800 text-sm" data-confirm="Delete this test?"><i class="fas fa-trash"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
