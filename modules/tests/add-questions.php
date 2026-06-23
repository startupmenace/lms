<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$test_id = (int)($_GET['id'] ?? 0);
$test = db_get_row("SELECT * FROM tests WHERE id = ?", [$test_id]);

if (!$test) {
    set_flash('error', 'Test not found.');
    redirect('index.php');
}

$page_title = 'Add Questions - ' . sanitize($test['title']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitize($_POST['type'] ?? 'mcq');
    $question_text = sanitize($_POST['question_text'] ?? '');
    $marks = (int)($_POST['marks'] ?? 1);
    $options = [];
    $correct_answer = '';

    if ($type === 'mcq') {
        for ($i = 1; $i <= 4; $i++) {
            $options[] = sanitize($_POST['option_' . $i] ?? '');
        }
        $correct_answer = sanitize($_POST['correct_answer'] ?? '');
    } else {
        $correct_answer = sanitize($_POST['model_answer'] ?? '');
    }

    if (empty($question_text)) {
        set_flash('error', 'Question text is required.');
    } else {
        db_insert(
            "INSERT INTO questions (test_id, type, question_text, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?, ?)",
            [$test_id, $type, $question_text, json_encode($options), $correct_answer, $marks]
        );
        db_query("UPDATE tests SET total_marks = (SELECT COALESCE(SUM(marks),0) FROM questions WHERE test_id = ?) WHERE id = ?", [$test_id, $test_id]);
        set_flash('success', 'Question added!');
        redirect('add-questions.php?id=' . $test_id);
    }
}

$questions = db_get_all("SELECT * FROM questions WHERE test_id = ? ORDER BY sort_order, id", [$test_id]);

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-5xl mx-auto">
    <?php if (has_flash('success')): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('success') ?></div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900"><?= sanitize($test['title']) ?></h2>
            <p class="text-sm text-gray-500">Adding questions (MCQ: <?= $test['mcq_count'] ?>, Subjective: <?= $test['subjective_count'] ?>)</p>
        </div>
        <a href="index.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">
            <i class="fas fa-check-circle mr-2"></i> Finish & Save
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Add New Question</h3>
            <?php if (has_flash('error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg mb-3 text-sm"><?= get_flash('error') ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Question Type</label>
                    <select name="type" id="qType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="toggleOptions()">
                        <option value="mcq">Multiple Choice (MCQ)</option>
                        <option value="subjective">Subjective</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Question Text</label>
                    <textarea name="question_text" rows="3" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"></textarea>
                </div>
                <div id="mcqOptions">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Options</label>
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="flex items-center gap-2 mb-2">
                        <input type="radio" name="correct_answer" value="<?= $i ?>" class="text-teal-600">
                        <input type="text" name="option_<?= $i ?>" placeholder="Option <?= $i ?>" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <?php endfor; ?>
                </div>
                <div id="subjectiveOptions" style="display:none;">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Model Answer (for reference)</label>
                    <textarea name="model_answer" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Marks</label>
                    <input type="number" name="marks" value="1" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition w-full">
                    <i class="fas fa-plus mr-2"></i>Add Question
                </button>
            </form>
        </div>

        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Questions (<?= count($questions) ?>)</h3>
            <?php if (empty($questions)): ?>
            <div class="bg-gray-50 rounded-lg p-6 text-center text-gray-400 text-sm">
                No questions added yet. Use the form to add questions.
            </div>
            <?php else: ?>
            <?php foreach ($questions as $i => $q): ?>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-medium text-gray-400">Q<?= $i + 1 ?></span>
                            <span class="text-xs px-2 py-0.5 rounded-full <?= $q['type'] == 'mcq' ? 'bg-blue-100 text-blue-700' : 'bg-coral-100 text-coral-700' ?>"><?= strtoupper($q['type']) ?></span>
                            <span class="text-xs text-gray-400"><?= $q['marks'] ?> mark<?= $q['marks'] > 1 ? 's' : '' ?></span>
                        </div>
                        <p class="text-sm text-gray-900"><?= sanitize($q['question_text']) ?></p>
                    </div>
                    <a href="delete-question.php?test_id=<?= $test_id ?>&qid=<?= $q['id'] ?>" class="text-red-400 hover:text-red-600 text-sm" data-confirm="Remove this question?"><i class="fas fa-times"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleOptions() {
    var type = document.getElementById('qType').value;
    document.getElementById('mcqOptions').style.display = type === 'mcq' ? 'block' : 'none';
    document.getElementById('subjectiveOptions').style.display = type === 'subjective' ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
