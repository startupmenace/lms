<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('exams');

$page_title = 'Exam Planner';

$classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");
$class_id = (int)($_GET['class_id'] ?? 0);

$exams = [];
if ($class_id) {
    $exams = db_get_all("SELECT e.*, GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ') as subjects 
        FROM exams e 
        LEFT JOIN exam_subjects es ON e.id = es.exam_id
        LEFT JOIN subjects s ON es.subject_id = s.id
        WHERE e.class_id = ? 
        GROUP BY e.id 
        ORDER BY e.exam_date", [$class_id]);
} else {
    $exams = db_get_all("SELECT e.*, c.name as class_name,
        GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ') as subjects 
        FROM exams e 
        LEFT JOIN classes c ON e.class_id = c.id
        LEFT JOIN exam_subjects es ON e.id = es.exam_id
        LEFT JOIN subjects s ON es.subject_id = s.id
        GROUP BY e.id 
        ORDER BY e.exam_date DESC LIMIT 20");
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div></div>
        <a href="create.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Schedule Exam
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex items-center gap-3">
            <select name="class_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                <option value="">All Classes</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $class_id == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (empty($exams)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-calendar-alt text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No exams scheduled yet.</p>
        <a href="create.php" class="text-teal-600 hover:underline text-sm mt-2 inline-block">Schedule your first exam</a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($exams as $exam): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-start justify-between mb-3">
                <h3 class="font-semibold text-gray-900"><?= sanitize($exam['title']) ?></h3>
                <span class="text-xs bg-teal-100 text-teal-700 px-2 py-1 rounded-full"><?= sanitize($exam['class_name'] ?? '') ?></span>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2 text-gray-500">
                    <i class="fas fa-calendar-day w-4 text-teal-400"></i> <?= format_date($exam['exam_date']) ?>
                </div>
                <div class="flex items-center gap-2 text-gray-500">
                    <i class="fas fa-clock w-4 text-teal-400"></i> <?= date('h:i A', strtotime($exam['start_time'])) ?> - <?= date('h:i A', strtotime($exam['end_time'])) ?>
                </div>
                <div class="flex items-center gap-2 text-gray-500">
                    <i class="fas fa-book w-4 text-teal-400"></i> <?= sanitize($exam['subjects'] ?? 'N/A') ?>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex gap-2">
                <a href="edit.php?id=<?= $exam['id'] ?>" class="text-amber-600 hover:text-amber-800 text-sm"><i class="fas fa-edit"></i></a>
                <a href="delete.php?id=<?= $exam['id'] ?>" class="text-red-600 hover:text-red-800 text-sm" data-confirm="Delete this exam?"><i class="fas fa-trash"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
