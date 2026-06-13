<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('parent');

$page_title = 'My Profile';
$user_id = get_user_id();

$user = db_get_row("SELECT * FROM users WHERE id = ?", [$user_id]);

$children = db_get_all(
    "SELECT s.parent_name, c.name as class_name, sp.relationship
     FROM student_parents sp
     JOIN students s ON sp.student_id = s.id
     LEFT JOIN classes c ON s.class_id = c.id
     WHERE sp.parent_user_id = ?",
    [$user_id]);

include __DIR__ . '/../../includes/parent-header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-8 text-center">
            <div class="w-20 h-20 mx-auto bg-white/20 rounded-full flex items-center justify-center text-3xl font-bold text-white mb-2">
                <?= get_avatar($user['full_name'] ?? 'Parent') ?>
            </div>
            <h2 class="text-xl font-bold text-white"><?= sanitize($user['full_name'] ?? '') ?></h2>
            <p class="text-teal-100 text-sm">Parent</p>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 uppercase font-medium">Email</label>
                    <p class="text-sm text-gray-900 mt-1"><?= sanitize($user['email'] ?? '') ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 uppercase font-medium">Phone</label>
                    <p class="text-sm text-gray-900 mt-1"><?= sanitize($user['phone'] ?? '—') ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-4"><i class="fas fa-child text-teal-600 mr-2"></i>Linked Children</h3>
        <?php if (empty($children)): ?>
        <p class="text-sm text-gray-400 text-center py-6">No children linked to this account.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($children as $c): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-gray-900"><?= sanitize($c['parent_name']) ?></p>
                    <p class="text-xs text-gray-500"><?= sanitize($c['class_name'] ?? 'N/A') ?> | <?= ucfirst($c['relationship'] ?? 'Parent') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/parent-footer.php'; ?>
