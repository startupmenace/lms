<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('student');

$page_title = 'Notices';

$user_id = get_user_id();
$student = db_get_row("SELECT s.* FROM students s WHERE s.id = ? ORDER BY s.id LIMIT 1", [$user_id]);
$class_id = $student['class_id'] ?? null;

$notices = db_get_all("SELECT n.*, u.full_name as author FROM notices n LEFT JOIN users u ON n.created_by = u.id WHERE n.is_active = 1 AND (n.target_audience IN ('all','student') OR n.class_id = ?) ORDER BY n.created_at DESC", [$class_id ?: 0]);

include __DIR__ . '/../../includes/student-header.php';
?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-6">Notice Board</h2>

    <?php if (empty($notices)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-bullhorn text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No notices posted yet.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($notices as $n): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-sm transition">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700"><?= ucfirst($n['target_audience']) ?></span>
                <span class="text-xs text-gray-400"><?= time_ago($n['created_at']) ?></span>
            </div>
            <h3 class="font-semibold text-gray-900"><?= sanitize($n['title']) ?></h3>
            <p class="text-sm text-gray-600 mt-2"><?= nl2br(sanitize($n['content'])) ?></p>
            <p class="text-xs text-gray-400 mt-3">Posted by <?= sanitize($n['author'] ?? 'Admin') ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/student-footer.php'; ?>
