<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('parent');

$page_title = 'Notices';
$user_id = get_user_id();

$children = db_get_all("SELECT s.id, s.class_id FROM student_parents sp JOIN students s ON sp.student_id = s.id WHERE sp.parent_user_id = ? AND s.is_active = 1", [$user_id]);

$class_ids = [-1];
foreach ($children as $c) $class_ids[] = (int)$c['class_id'];
$placeholders = implode(',', array_fill(0, count($class_ids), '?'));
$params = array_merge(['all', 'student'], $class_ids);

$notices = db_get_all("SELECT * FROM notices WHERE target_audience IN (?, ?) AND (class_id IS NULL OR class_id IN ($placeholders)) AND is_active = 1 ORDER BY created_at DESC", $params);

include __DIR__ . '/../../includes/parent-header.php';
?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-6">School Notices</h2>

    <?php if (empty($notices)): ?>
    <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
        <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-bullhorn text-2xl text-gray-400"></i>
        </div>
        <p class="text-gray-400">No notices available.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($notices as $n): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h4 class="font-semibold text-gray-900"><?= sanitize($n['title']) ?></h4>
                    <p class="text-sm text-gray-600 mt-2"><?= nl2br(sanitize($n['content'] ?? '')) ?></p>
                    <p class="text-xs text-gray-400 mt-3"><i class="far fa-clock mr-1"></i><?= time_ago($n['created_at']) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/parent-footer.php'; ?>
