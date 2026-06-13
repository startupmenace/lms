<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('parent');

$page_title = 'Homework';
$user_id = get_user_id();

$children = db_get_all("SELECT s.id, s.parent_name, c.name as class_name FROM student_parents sp JOIN students s ON sp.student_id = s.id LEFT JOIN classes c ON s.class_id = c.id WHERE sp.parent_user_id = ? AND s.is_active = 1 ORDER BY s.parent_name", [$user_id]);
$child_id = (int)($_GET['child_id'] ?? $children[0]['id'] ?? 0);

$selected = [];
foreach ($children as $c) { if ($c['id'] == $child_id) { $selected = $c; break; } }
if (empty($selected) && !empty($children)) $selected = $children[0];

$homeworks = $selected ? db_get_all("SELECT h.*, s.name as subject_name, u.full_name as teacher_name FROM homework h LEFT JOIN subjects s ON h.subject_id = s.id LEFT JOIN users u ON h.teacher_id = u.id WHERE h.class_id = ? ORDER BY h.created_at DESC", [$selected['class_id'] ?? 0]) : [];

include __DIR__ . '/../../includes/parent-header.php';
?>

<?php if (count($children) > 1): ?>
<div class="mb-6 flex items-center gap-3 flex-wrap">
    <span class="text-sm font-medium text-gray-600">Child:</span>
    <?php foreach ($children as $c): ?>
    <a href="?child_id=<?= $c['id'] ?>" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $c['id'] == $child_id ? 'bg-teal-600 text-white shadow' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' ?>">
        <?= sanitize($c['parent_name']) ?> (<?= sanitize($c['class_name'] ?? 'N/A') ?>)
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="max-w-5xl mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-6">Homework — <?= sanitize($selected['parent_name'] ?? '') ?></h2>

    <?php if (empty($homeworks)): ?>
    <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
        <p class="text-gray-400">No homework assigned yet.</p>
    </div>
    <?php else: ?>
    <div class="grid gap-4">
        <?php foreach ($homeworks as $h): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <h4 class="font-semibold text-gray-900"><?= sanitize($h['title']) ?></h4>
                    <div class="flex flex-wrap gap-3 mt-1.5 text-xs text-gray-500">
                        <span><i class="fas fa-book mr-1"></i><?= sanitize($h['subject_name'] ?? 'General') ?></span>
                        <span><i class="fas fa-user mr-1"></i><?= sanitize($h['teacher_name'] ?? 'Teacher') ?></span>
                        <?php if ($h['due_date']): ?>
                        <span class="<?= strtotime($h['due_date']) < time() ? 'text-red-500 font-medium' : '' ?>">
                            <i class="fas fa-calendar-alt mr-1"></i>Due: <?= format_date($h['due_date']) ?>
                        </span>
                        <?php endif; ?>
                        <span><i class="fas fa-upload mr-1"></i><?= ucfirst($h['submission_type'] ?? 'digital') ?></span>
                    </div>
                    <?php if ($h['description']): ?>
                    <p class="text-sm text-gray-600 mt-3"><?= nl2br(sanitize($h['description'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/parent-footer.php'; ?>
