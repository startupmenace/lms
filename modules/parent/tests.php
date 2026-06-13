<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('parent');

$page_title = 'Test Results';
$user_id = get_user_id();

$children = db_get_all("SELECT s.id, s.parent_name, c.name as class_name FROM student_parents sp JOIN students s ON sp.student_id = s.id LEFT JOIN classes c ON s.class_id = c.id WHERE sp.parent_user_id = ? AND s.is_active = 1 ORDER BY s.parent_name", [$user_id]);
$child_id = (int)($_GET['child_id'] ?? $children[0]['id'] ?? 0);

$selected = [];
foreach ($children as $c) { if ($c['id'] == $child_id) { $selected = $c; break; } }
if (empty($selected) && !empty($children)) $selected = $children[0];

$submissions = $selected ? db_get_all("SELECT ts.*, t.title as test_title, t.total_marks, t.duration_minutes, s.name as subject_name FROM test_submissions ts LEFT JOIN tests t ON ts.test_id = t.id LEFT JOIN subjects s ON t.subject_id = s.id WHERE ts.student_id = ? ORDER BY ts.submitted_at DESC LIMIT 50", [$selected['id']]) : [];

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
    <h2 class="text-xl font-semibold text-gray-900 mb-6">Test Results — <?= sanitize($selected['parent_name'] ?? '') ?></h2>

    <?php if (empty($submissions)): ?>
    <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
        <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-flask text-2xl text-gray-400"></i>
        </div>
        <p class="text-gray-400">No test results yet.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Test</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Subject</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Date</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Score</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $sub): ?>
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 font-medium text-gray-900"><?= sanitize($sub['test_title']) ?></td>
                    <td class="py-3 px-4 text-gray-600"><?= sanitize($sub['subject_name'] ?? 'All') ?></td>
                    <td class="py-3 px-4 text-gray-600"><?= format_date($sub['submitted_at'], 'd M Y') ?></td>
                    <td class="py-3 px-4 text-center font-bold <?= $sub['total_marks_obtained'] !== null ? ($sub['total_marks_obtained'] / max($sub['total_marks'], 1) >= 0.5 ? 'text-green-600' : 'text-red-600') : 'text-gray-400' ?>">
                        <?= $sub['total_marks_obtained'] !== null ? $sub['total_marks_obtained'] . '/' . $sub['total_marks'] : '—' ?>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="text-xs px-2 py-1 rounded-full <?= $sub['status'] == 'evaluated' ? 'bg-green-100 text-green-700' : ($sub['status'] == 'resubmitted' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') ?>">
                            <?= ucfirst($sub['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/parent-footer.php'; ?>
