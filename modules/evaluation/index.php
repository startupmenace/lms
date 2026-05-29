<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Evaluation & Grading';

$submissions = db_get_all("SELECT ts.*, t.title as test_title, t.total_marks, s.parent_name as student_name, s.enrollment_id
    FROM test_submissions ts
    LEFT JOIN tests t ON ts.test_id = t.id
    LEFT JOIN students s ON ts.student_id = s.id
    ORDER BY ts.submitted_at DESC LIMIT 50");

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Evaluation</h2>
    </div>

    <?php if (empty($submissions)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-check-double text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No submissions to evaluate yet.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Test</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Student</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Submitted</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Status</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Score</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $sub): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium text-gray-900"><?= sanitize($sub['test_title']) ?></td>
                    <td class="py-3 px-4"><?= sanitize($sub['student_name']) ?></td>
                    <td class="py-3 px-4 text-gray-500"><?= format_date($sub['submitted_at'], 'd M Y h:i A') ?></td>
                    <td class="py-3 px-4 text-center">
                        <span class="text-xs px-2 py-1 rounded-full <?= $sub['status'] == 'evaluated' ? 'bg-green-100 text-green-700' : ($sub['status'] == 'resubmitted' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') ?>">
                            <?= ucfirst($sub['status']) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <?= $sub['total_marks_obtained'] !== null ? $sub['total_marks_obtained'] . '/' . $sub['total_marks'] : '-' ?>
                    </td>
                    <td class="py-3 px-4">
                        <a href="grade.php?id=<?= $sub['id'] ?>" class="text-teal-600 hover:text-teal-800 text-sm font-medium">
                            <i class="fas fa-check-circle mr-1"></i> Grade
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
