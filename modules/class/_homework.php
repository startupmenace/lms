<?php
$homeworks = db_get_all("SELECT h.*, s.name as subject_name, u.full_name as teacher_name,
    (SELECT COUNT(*) FROM homework_submissions WHERE homework_id=h.id) as submission_count
    FROM homework h
    LEFT JOIN subjects s ON h.subject_id=s.id
    LEFT JOIN users u ON h.teacher_id=u.id
    WHERE h.class_id=? ORDER BY h.created_at DESC", [$class_id]);
$students = db_get_all("SELECT s.id, u.full_name FROM students s JOIN users u ON s.user_id=u.id WHERE s.class_id=? AND s.is_active=1 ORDER BY u.full_name", [$class_id]);
$teacher_subjects = db_get_all("SELECT s.id, s.name FROM subjects s JOIN class_subjects cs ON s.id=cs.subject_id WHERE cs.class_id=? ORDER BY s.name", [$class_id]);
?>
<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500"><?= count($homeworks) ?> homework assignment(s)</p>
</div>

<form method="post" class="p-4 bg-gray-50 rounded-xl border border-gray-200 mb-6">
    <input type="hidden" name="action" value="create_homework">
    <input type="hidden" name="class_id" value="<?= $class_id ?>">
    <h4 class="font-bold text-gray-900 text-xs mb-3">New Homework</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
        <div>
            <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">Title</label>
            <input type="text" name="title" required class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
        </div>
        <div>
            <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">Subject</label>
            <select name="subject_id" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
                <option value="">General</option>
                <?php foreach ($teacher_subjects as $s): ?>
                <option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">Due Date</label>
            <input type="date" name="due_date" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
        </div>
        <div>
            <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">Submission Type</label>
            <select name="submission_type" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
                <option value="digital">Digital</option>
                <option value="physical">Physical</option>
                <option value="both">Both</option>
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">Description</label>
        <textarea name="description" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs"></textarea>
    </div>
    <div class="flex justify-end">
        <button type="submit" class="bg-teal-600 text-white px-6 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition">Create Homework</button>
    </div>
</form>

<div class="space-y-3">
    <?php if (empty($homeworks)): ?>
    <p class="text-gray-400 text-xs text-center py-8">No homework assigned yet.</p>
    <?php else: ?>
    <?php foreach ($homeworks as $h): ?>
    <div class="border border-gray-200 rounded-xl p-4">
        <div class="flex items-start justify-between mb-2">
            <div>
                <h4 class="font-bold text-gray-900 text-sm"><?= sanitize($h['title']) ?></h4>
                <p class="text-[10px] text-gray-400">
                    <?= sanitize($h['subject_name'] ?? 'General') ?> · 
                    by <?= sanitize($h['teacher_name'] ?? 'Unknown') ?> · 
                    <?= $h['due_date'] ? 'Due: ' . format_date($h['due_date']) : 'No due date' ?> · 
                    <?= $h['submission_count'] ?> submission(s)
                </p>
            </div>
            <span class="text-[10px] text-gray-400"><?= $h['submission_type'] ?></span>
        </div>
        <?php if ($h['description']): ?>
        <p class="text-xs text-gray-600 mb-3"><?= nl2br(sanitize($h['description'])) ?></p>
        <?php endif; ?>
        <details class="text-xs">
            <summary class="text-teal-600 cursor-pointer font-medium">Mark Submissions</summary>
            <div class="mt-2 space-y-1">
                <?php foreach ($students as $s):
                    $sub = db_get_row("SELECT * FROM homework_submissions WHERE homework_id=? AND student_id=?", [$h['id'], $s['id']]);
                ?>
                <form method="post" class="flex items-center justify-between py-1 border-b border-gray-50">
                    <input type="hidden" name="action" value="submit_homework">
                    <input type="hidden" name="homework_id" value="<?= $h['id'] ?>">
                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="class_id" value="<?= $class_id ?>">
                    <span class="text-xs text-gray-600"><?= sanitize($s['full_name']) ?></span>
                    <div class="flex items-center gap-2">
                        <?php if ($sub): ?>
                        <span class="text-green-600 text-[10px]"><i class="fas fa-check"></i> Submitted</span>
                        <?php else: ?>
                        <span class="text-gray-300 text-[10px]">Not submitted</span>
                        <button type="submit" class="text-teal-600 hover:text-teal-800 text-[10px] font-medium">Mark as submitted</button>
                        <?php endif; ?>
                    </div>
                </form>
                <?php endforeach; ?>
            </div>
        </details>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
