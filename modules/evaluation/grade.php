<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$sub_id = (int)($_GET['id'] ?? 0);
$submission = db_get_row("SELECT ts.*, t.title as test_title, t.total_marks, t.instructions, s.parent_name as student_name, s.enrollment_id, s.class_id
    FROM test_submissions ts
    LEFT JOIN tests t ON ts.test_id = t.id
    LEFT JOIN students s ON ts.student_id = s.id
    WHERE ts.id = ?", [$sub_id]);

if (!$submission) {
    set_flash('error', 'Submission not found.');
    redirect('index.php');
}

$answers = json_decode($submission['answers'], true) ?? [];
$questions = db_get_all("SELECT * FROM questions WHERE test_id = ? ORDER BY sort_order, id", [$submission['test_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total_obtained = 0;
    $feedback = sanitize($_POST['feedback'] ?? '');
    $resubmit = isset($_POST['resubmit']) ? 'resubmitted' : 'evaluated';
    $marks = $_POST['marks'] ?? [];

    foreach ($marks as $qid => $mark) {
        $total_obtained += (float)$mark;
    }

    db_query("UPDATE test_submissions SET total_marks_obtained = ?, status = ?, evaluated_at = NOW(), evaluated_by = ?, feedback = ? WHERE id = ?",
        [$total_obtained, $resubmit, get_user_id(), $feedback, $sub_id]);

    set_flash('success', 'Evaluation saved successfully!');
    redirect('index.php');
}

$page_title = 'Grade: ' . sanitize($submission['test_title']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <?php if (has_flash('success')): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('success') ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900"><?= sanitize($submission['test_title']) ?></h2>
                <p class="text-sm text-gray-500">Student: <strong><?= sanitize($submission['student_name']) ?></strong> | <?= sanitize($submission['enrollment_id']) ?></p>
            </div>
            <span class="text-sm text-gray-500">Submitted: <?= format_date($submission['submitted_at'], 'd M Y h:i A') ?></span>
        </div>
    </div>

    <form method="POST">
        <div class="space-y-6">
            <?php foreach ($questions as $q): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-gray-400">Q<?= $q['id'] ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $q['type'] == 'mcq' ? 'bg-blue-100 text-blue-700' : 'bg-coral-100 text-coral-700' ?>"><?= strtoupper($q['type']) ?></span>
                        <span class="text-xs text-gray-500"><?= $q['marks'] ?> mark<?= $q['marks'] > 1 ? 's' : '' ?></span>
                    </div>
                </div>
                <p class="text-gray-900 mb-3"><?= sanitize($q['question_text']) ?></p>

                <?php if ($q['type'] == 'mcq'): ?>
                    <?php $options = json_decode($q['options'], true) ?? []; ?>
                    <div class="space-y-2 mb-3">
                        <?php foreach ($options as $i => $opt): ?>
                        <div class="flex items-center gap-2 p-2 rounded <?= $opt == $q['correct_answer'] ? 'bg-green-50 border border-green-200' : 'bg-gray-50' ?>">
                            <span class="text-sm"><?= $i + 1 ?>. <?= sanitize($opt) ?></span>
                            <?php if ($opt == $q['correct_answer']): ?>
                            <i class="fas fa-check text-green-500 text-xs"></i>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 rounded-lg p-4 mb-3">
                        <p class="text-sm text-gray-600 font-medium mb-1">Student's Answer:</p>
                        <p class="text-gray-900"><?= sanitize($answers['q_' . $q['id']] ?? 'No answer provided') ?></p>
                    </div>
                    <div class="bg-teal-50 rounded-lg p-4 mb-3">
                        <p class="text-sm text-teal-600 font-medium mb-1">Model Answer:</p>
                        <p class="text-gray-900"><?= sanitize($q['correct_answer'] ?? 'N/A') ?></p>
                    </div>
                <?php endif; ?>

                <div class="flex items-center gap-4 mt-3">
                    <div>
                        <label class="text-xs text-gray-500">Marks Awarded</label>
                        <input type="number" name="marks[<?= $q['id'] ?>]" min="0" max="<?= $q['marks'] ?>" value="0" step="0.5"
                               class="w-20 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <span class="text-xs text-gray-400">/ <?= $q['marks'] ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mt-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Feedback to Student</label>
                    <textarea name="feedback" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"><?= sanitize($submission['feedback'] ?? '') ?></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="resubmit" id="resubmit" value="1" class="rounded text-amber-600 focus:ring-amber-500">
                    <label for="resubmit" class="text-sm text-gray-700">Ask student to resubmit</label>
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-save mr-2"></i> Save Evaluation
                </button>
                <a href="index.php" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
