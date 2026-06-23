<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('student');

$user_id = get_user_id();
$test_id = (int)($_GET['id'] ?? 0);
$test = db_get_row("SELECT t.*, s.name as subject_name FROM tests t LEFT JOIN subjects s ON t.subject_id = s.id WHERE t.id = ? AND t.is_active = 1", [$test_id]);

if (!$test) {
    set_flash('error', 'Test not found or unavailable.');
    redirect('tests.php');
}

$student = db_get_row("SELECT s.* FROM students s WHERE s.user_id = ?", [$user_id]);
if (!$student) {
    $student = db_get_row("SELECT s.* FROM students s ORDER BY s.id LIMIT 1");
}
$student_id = $student['id'] ?? 0;

// Check if already submitted
$existing = db_get_row("SELECT id, status FROM test_submissions WHERE test_id = ? AND student_id = ?", [$test_id, $student_id]);
if ($existing && $existing['status'] !== 'resubmitted') {
    set_flash('error', 'You have already submitted this test.');
    redirect('tests.php');
}

// Remove previous submission if resubmitted
if ($existing && $existing['status'] === 'resubmitted') {
    db_query("DELETE FROM test_submissions WHERE id = ?", [$existing['id']]);
}

$questions = db_get_all("SELECT * FROM questions WHERE test_id = ? ORDER BY sort_order, id", [$test_id]);
$question_order = null;
if (!empty($test['shuffle_questions'])) {
    $ids = array_column($questions, 'id');
    shuffle($ids);
    $question_order = json_encode($ids);
    $ordered = [];
    foreach ($ids as $qid) {
        foreach ($questions as $q) {
            if ($q['id'] == $qid) { $ordered[] = $q; break; }
        }
    }
    $questions = $ordered;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = [];
    $total_obtained = 0;

    foreach ($questions as $q) {
        $answer = $_POST['q_' . $q['id']] ?? '';
        $answers['q_' . $q['id']] = $answer;
        if ($q['type'] === 'mcq' && (int)$answer === (int)$q['correct_answer']) {
            $answers['mark_' . $q['id']] = (float)$q['marks'];
            $total_obtained += (float)$q['marks'];
        }
    }

    db_insert("INSERT INTO test_submissions (test_id, student_id, answers, question_order, total_marks_obtained, status, submitted_at) VALUES (?,?,?,?,?,'pending',NOW())", [
        $test_id, $student_id, json_encode($answers), $question_order, $total_obtained
    ]);

    set_flash('success', 'Test submitted successfully!');
    redirect('tests.php');
}

$page_title = sanitize($test['title']);

include __DIR__ . '/../../includes/student-header.php';
?>

<div class="max-w-4xl mx-auto">
    <form method="POST" id="test-form">
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900"><?= sanitize($test['title']) ?></h2>
                    <div class="flex items-center gap-3 mt-2 text-sm text-gray-500">
                        <span><i class="fas fa-book mr-1"></i> <?= sanitize($test['subject_name'] ?? 'All Subjects') ?></span>
                        <span><i class="fas fa-clock mr-1"></i> <?= $test['duration_minutes'] ?> min</span>
                        <span><i class="fas fa-star mr-1"></i> <?= $test['total_marks'] ?> marks</span>
                        <span class="px-2 py-0.5 rounded-full text-xs <?= $test['difficulty'] == 'easy' ? 'bg-green-100 text-green-700' : ($test['difficulty'] == 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= ucfirst($test['difficulty']) ?></span>
                    </div>
                </div>
                <div class="text-right">
                    <div id="timer" class="text-lg font-bold text-teal-600"><?= $test['duration_minutes'] ?>:00</div>
                    <div class="text-xs text-gray-400">remaining</div>
                </div>
            </div>
            <?php if ($test['instructions']): ?>
            <div class="mt-4 p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
                <strong>Instructions:</strong> <?= nl2br(sanitize($test['instructions'])) ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="space-y-4">
            <?php foreach ($questions as $i => $q): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="font-medium text-gray-900">Q<?= $i + 1 ?>. </span>
                    <span class="text-xs px-2 py-0.5 rounded-full <?= $q['type'] == 'mcq' ? 'bg-blue-100 text-blue-700' : 'bg-coral-100 text-coral-700' ?>"><?= strtoupper($q['type']) ?></span>
                    <span class="text-xs text-gray-400">[<?= $q['marks'] ?> mark<?= $q['marks'] > 1 ? 's' : '' ?>]</span>
                </div>
                <p class="text-gray-900 mb-3"><?= sanitize($q['question_text']) ?></p>

                <?php if ($q['type'] == 'mcq'): ?>
                    <?php $options = json_decode($q['options'], true) ?? []; ?>
                    <div class="space-y-2">
                        <?php foreach ($options as $oi => $opt): ?>
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50">
                            <input type="radio" name="q_<?= $q['id'] ?>" value="<?= $oi + 1 ?>" class="w-4 h-4 text-teal-600 focus:ring-teal-500">
                            <span class="text-sm text-gray-800"><?= chr(65 + $oi) ?>. <?= sanitize($opt) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <textarea name="q_<?= $q['id'] ?>" rows="3" placeholder="Type your answer here..." class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"></textarea>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="bg-teal-600 text-white px-8 py-3 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-paper-plane mr-2"></i> Submit Test
            </button>
            <a href="tests.php" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
        </div>
    </form>
</div>

<script>
(function() {
    var totalMinutes = <?= $test['duration_minutes'] ?>;
    var timerEl = document.getElementById('timer');
    var formEl = document.getElementById('test-form');
    var endTime = new Date().getTime() + totalMinutes * 60000;

    function updateTimer() {
        var now = new Date().getTime();
        var diff = endTime - now;
        if (diff <= 0) {
            timerEl.textContent = '0:00';
            timerEl.classList.add('text-red-600');
            if (formEl) formEl.submit();
            return;
        }
        var m = Math.floor(diff / 60000);
        var s = Math.floor((diff % 60000) / 1000);
        timerEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        if (diff < 120000) timerEl.classList.add('text-red-600');
    }

    updateTimer();
    setInterval(updateTimer, 1000);
})();
</script>

<?php include __DIR__ . '/../../includes/student-footer.php'; ?>
