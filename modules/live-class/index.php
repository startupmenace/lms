<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$page_title = 'Live Class';

$live_classes = db_get_all("SELECT lc.*, u.full_name as teacher_name, c.name as class_name
    FROM live_classes lc
    LEFT JOIN users u ON lc.teacher_id = u.id
    LEFT JOIN classes c ON lc.class_id = c.id
    ORDER BY lc.scheduled_at DESC LIMIT 10");

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Live Classes</h2>
        <div class="flex items-center gap-3">
            <a href="attendance-report.php" class="text-teal-600 border border-teal-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-50 transition flex items-center gap-2">
                <i class="fas fa-clipboard-list"></i> Attendance Report
            </a>
            <?php if (has_role('admin') || has_role('teacher')): ?>
            <a href="schedule.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
                <i class="fas fa-plus"></i> Schedule Live Class
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($live_classes)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-video text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No live classes scheduled yet.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($live_classes as $lc): ?>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition">
            <div class="bg-gradient-to-r from-teal-500 to-coral-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs px-2 py-1 rounded-full bg-white/20 text-white">
                        <?= $lc['status'] == 'live' ? '🔴 Live Now' : ucfirst($lc['status']) ?>
                    </span>
                    <i class="fas fa-video text-white/60"></i>
                </div>
                <h3 class="text-white font-semibold mt-2"><?= sanitize($lc['title']) ?></h3>
            </div>
            <div class="p-4 space-y-2 text-sm">
                <div class="flex items-center gap-2 text-gray-500">
                    <i class="fas fa-chalkboard-teacher w-4 text-teal-400"></i> <?= sanitize($lc['teacher_name']) ?>
                </div>
                <div class="flex items-center gap-2 text-gray-500">
                    <i class="fas fa-users w-4 text-teal-400"></i> <?= sanitize($lc['class_name'] ?? 'All Classes') ?>
                </div>
                <div class="flex items-center gap-2 text-gray-500">
                    <i class="fas fa-clock w-4 text-teal-400"></i> <?= format_date($lc['scheduled_at'], 'd M Y h:i A') ?>
                </div>
                <div class="flex items-center gap-2 text-gray-500">
                    <i class="fas fa-hourglass-half w-4 text-teal-400"></i> <?= $lc['duration_minutes'] ?> minutes
                </div>
            </div>
            <div class="p-4 pt-0">
                <?php if ($lc['status'] == 'live' || $lc['status'] == 'scheduled'): ?>
                <a href="room.php?id=<?= $lc['id'] ?>" class="block w-full bg-teal-600 text-white text-center py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-sign-in-alt mr-2"></i> Join Class
                </a>
                <?php elseif ($lc['recording_url']): ?>
                <a href="<?= sanitize($lc['recording_url']) ?>" class="block w-full bg-gray-100 text-gray-700 text-center py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    <i class="fas fa-play mr-2"></i> View Recording
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
