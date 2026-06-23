<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$id = (int)($_GET['id'] ?? 0);
$test = db_get_row("SELECT * FROM tests WHERE id = ?", [$id]);

if (!$test) {
    set_flash('error', 'Test not found.');
    redirect('index.php');
}

$questions = db_get_all("SELECT * FROM questions WHERE test_id = ? ORDER BY sort_order, id", [$id]);

$page_title = sanitize($test['title']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900"><?= sanitize($test['title']) ?></h2>
                <div class="flex items-center gap-3 mt-2 text-sm text-gray-500">
                    <span>Total Marks: <?= array_sum(array_column($questions, 'marks')) ?></span>
                    <span>Duration: <?= $test['duration_minutes'] ?> min</span>
                    <?php if (!empty($test['shuffle_questions'])): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs bg-purple-100 text-purple-700"><i class="fas fa-random mr-1"></i> Shuffled</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <span class="text-sm text-gray-500"><?= count($questions) ?> Questions</span>
            </div>
        </div>
        <?php if ($test['instructions']): ?>
        <div class="mt-4 p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
            <strong>Instructions:</strong> <?= nl2br(sanitize($test['instructions'])) ?>
        </div>
        <?php endif; ?>
    </div>

    <?php foreach ($questions as $i => $q): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="font-medium text-gray-900">Q<?= $i + 1 ?>. </span>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $q['type'] == 'mcq' ? 'bg-blue-100 text-blue-700' : 'bg-coral-100 text-coral-700' ?>"><?= strtoupper($q['type']) ?></span>
            <span class="text-xs text-gray-400">[<?= $q['marks'] ?> mark<?= $q['marks'] > 1 ? 's' : '' ?>]</span>
        </div>
        <p class="text-gray-900"><?= sanitize($q['question_text']) ?></p>
        <?php if ($q['type'] == 'mcq'): ?>
            <?php $options = json_decode($q['options'], true) ?? []; ?>
            <div class="mt-3 space-y-2">
                <?php foreach ($options as $oi => $opt): ?>
                <div class="flex items-center gap-2 p-2 rounded bg-gray-50 text-sm <?= ($oi + 1) == $q['correct_answer'] ? 'border border-green-200 bg-green-50' : '' ?>">
                    <span class="w-6 h-6 rounded-full border border-gray-300 flex items-center justify-center text-xs"><?= chr(65 + $oi) ?></span>
                    <?= sanitize($opt) ?>
                    <?php if (($oi + 1) == $q['correct_answer']): ?>
                    <i class="fas fa-check text-green-500 text-xs ml-auto"></i>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="flex gap-3">
        <a href="index.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Back to Tests</a>
        <a href="add-questions.php?id=<?= $id ?>" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition"><i class="fas fa-plus mr-2"></i>Add/Edit Questions</a>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
