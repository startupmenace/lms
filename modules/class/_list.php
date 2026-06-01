<?php
$classes = db_get_all("SELECT c.*,
    (SELECT COUNT(*) FROM students WHERE class_id=c.id) as student_count,
    (SELECT u.full_name FROM class_teachers ct JOIN users u ON ct.teacher_id=u.id WHERE ct.class_id=c.id AND ct.role='class_teacher' LIMIT 1) as class_teacher_name
    FROM classes c WHERE c.is_active=1 ORDER BY c.name");
?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
            <i class="fas fa-school text-teal-600 mr-2"></i> Classes
        </h1>
        <p class="text-gray-500 text-sm mt-1"><?= count($classes) ?> active classes</p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($classes as $c): ?>
    <a href="?id=<?= $c['id'] ?>" class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-teal-300 hover:shadow-md transition group">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-coral-500 flex items-center justify-center text-white font-bold text-sm">
                <?= strtoupper(substr($c['name'], 0, 2)) ?>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 group-hover:text-teal-700 transition"><?= sanitize($c['name']) ?></h3>
                <p class="text-xs text-gray-400"><?= sanitize($c['category']) ?><?= $c['section'] ? ' · ' . sanitize($c['section']) : '' ?></p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 text-center">
            <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-lg font-bold text-gray-900"><?= $c['student_count'] ?></p>
                <p class="text-[10px] text-gray-500">Students</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs font-medium text-gray-600 truncate"><?= sanitize($c['class_teacher_name'] ?? '—') ?></p>
                <p class="text-[10px] text-gray-500">Class Teacher</p>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
