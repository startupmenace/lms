<?php
$class = db_get_row("SELECT c.*,
    (SELECT COUNT(*) FROM students WHERE class_id=c.id) as student_count,
    (SELECT u.full_name FROM class_teachers ct JOIN users u ON ct.teacher_id=u.id WHERE ct.class_id=c.id AND ct.role='class_teacher' LIMIT 1) as class_teacher_name
    FROM classes c WHERE c.id=?", [$class_id]);
if (!$class) {
    echo '<p class="text-gray-500 text-center py-8">Class not found.</p>';
    return;
}

$sub_tabs = [
    'overview' => ['Overview', 'fa-info-circle'],
    'students' => ['Students', 'fa-user-graduate'],
    'attendance' => ['Attendance', 'fa-calendar-check'],
    'teachers' => ['Teachers', 'fa-chalkboard-teacher'],
    'subjects' => ['Subjects', 'fa-book'],
    'homework' => ['Homework', 'fa-tasks'],
    'resources' => ['Resources', 'fa-link'],
];
?>
<div class="mb-4">
    <a href="?action=list" class="text-gray-400 hover:text-gray-600 text-sm transition"><i class="fas fa-arrow-left mr-1"></i> All Classes</a>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-coral-500 flex items-center justify-center text-white font-bold text-lg">
                <?= strtoupper(substr($class['name'], 0, 2)) ?>
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-900"><?= sanitize($class['name']) ?></h1>
                <p class="text-xs text-gray-500">
                    <?= sanitize($class['category']) ?>
                    <?= $class['section'] ? ' · Section ' . sanitize($class['section']) : '' ?>
                    · <?= $class['student_count'] ?> students
                    · Class Teacher: <strong><?= sanitize($class['class_teacher_name'] ?? 'Not assigned') ?></strong>
                </p>
            </div>
        </div>
    </div>

    <div class="flex border-b border-gray-200 overflow-x-auto">
        <?php foreach ($sub_tabs as $key => $info): ?>
        <a href="?id=<?= $class_id ?>&tab=<?= $key ?>" class="flex items-center gap-1.5 px-4 py-2.5 text-xs font-medium whitespace-nowrap <?= $tab===$key?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
            <i class="fas <?= $info[1] ?>"></i> <?= $info[0] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="p-5">
        <?php
        $tab_file = __DIR__ . '/_' . $tab . '.php';
        if (file_exists($tab_file)) {
            include $tab_file;
        } else {
            include __DIR__ . '/_overview.php';
        }
        ?>
    </div>
</div>
