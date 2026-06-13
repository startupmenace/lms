<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('noticeboard');

$page_title = 'Notice Board';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_notice'])) {
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $target = sanitize($_POST['target_audience'] ?? 'all');
    if (!empty($title) && !empty($content)) {
        $notice_id = db_insert("INSERT INTO notices (title, content, target_audience, created_by) VALUES (?, ?, ?, ?)",
            [$title, $content, $target, get_user_id()]);
        // Create notifications for target users
        $user_where = "role IN ('admin','teacher')";
        if ($target === 'student') $user_where = "role='student'";
        elseif ($target === 'teacher') $user_where = "role IN ('admin','teacher')";
        elseif ($target === 'all') $user_where = "1=1";
        $target_users = db_get_all("SELECT id FROM users WHERE is_active=1 AND $user_where");
        $link = BASE_URL . '/modules/noticeboard/index.php';
        foreach ($target_users as $tu) {
            db_insert("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?,?,?,?,?)",
                [$tu['id'], 'notice', $title, substr($content, 0, 200), $link]);
        }
        set_flash('success', 'Notice published!');
        redirect('index.php');
    }
}

$notices = db_get_all("SELECT n.*, u.full_name as author FROM notices n LEFT JOIN users u ON n.created_by = u.id WHERE n.is_active = 1 ORDER BY n.created_at DESC");

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Notice Board</h2>
        <button onclick="document.getElementById('noticeForm').classList.toggle('hidden')" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
            <i class="fas fa-plus"></i> New Notice
        </button>
    </div>

    <?php if (has_flash('success')): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('success') ?></div>
    <?php endif; ?>

    <div id="noticeForm" class="hidden bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="font-semibold text-gray-900 mb-4">Post New Notice</h3>
        <form method="POST" class="space-y-4">
            <div>
                <input type="text" name="title" required placeholder="Notice Title" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <textarea name="content" required rows="4" placeholder="Write your notice..." class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"></textarea>
            </div>
            <div class="flex items-center gap-4">
                <select name="target_audience" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="all">Everyone</option>
                    <option value="teacher">Teachers Only</option>
                    <option value="student">Students Only</option>
                </select>
                <button type="submit" name="add_notice" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-bullhorn mr-2"></i> Publish Notice
                </button>
            </div>
        </form>
    </div>

    <?php if (empty($notices)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-bullhorn text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No notices posted yet.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($notices as $n): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-sm transition">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-teal-100 text-teal-700"><?= ucfirst($n['target_audience']) ?></span>
                        <span class="text-xs text-gray-400"><?= time_ago($n['created_at']) ?></span>
                    </div>
                    <h3 class="font-semibold text-gray-900"><?= sanitize($n['title']) ?></h3>
                    <p class="text-sm text-gray-600 mt-2"><?= nl2br(sanitize($n['content'])) ?></p>
                    <p class="text-xs text-gray-400 mt-3">Posted by <?= sanitize($n['author'] ?? 'Admin') ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>


<?php include __DIR__ . '/../../includes/footer.php'; ?>
 