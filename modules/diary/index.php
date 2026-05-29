<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher', 'student');

$page_title = 'My Diary';
$tab = $_GET['tab'] ?? 'entries';
$teachers = db_get_all("SELECT id, full_name FROM users WHERE role IN ('admin','teacher') AND is_active=1 ORDER BY full_name");

$editing_entry = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $entry_date = $_POST['entry_date'];
        $category = $_POST['category'] ?? 'general';
        $is_private = (int)($_POST['is_private'] ?? 0);
        $shared_with = !empty($_POST['shared_with']) ? (int)$_POST['shared_with'] : null;

        if ($action === 'create') {
            if ($shared_with) {
                db_insert("INSERT INTO diary_entries (user_id,title,content,entry_date,category,is_private,shared_with,shared_at) VALUES (?,?,?,?,?,?,?,NOW())", [
                    get_user_id(), $title, $content, $entry_date, $category, $is_private, $shared_with
                ]);
            } else {
                db_insert("INSERT INTO diary_entries (user_id,title,content,entry_date,category,is_private) VALUES (?,?,?,?,?,?)", [
                    get_user_id(), $title, $content, $entry_date, $category, $is_private
                ]);
            }
            set_flash('success', 'Diary entry created');
        } else {
            $id = (int)$_POST['id'];
            $entry = db_get_row("SELECT * FROM diary_entries WHERE id=? AND (user_id=? OR ? IN ('admin','teacher'))", [$id, get_user_id(), get_user_role()]);
            if ($entry) {
                if ($shared_with) {
                    db_query("UPDATE diary_entries SET title=?, content=?, entry_date=?, category=?, is_private=?, shared_with=?, shared_at=NOW() WHERE id=?", [
                        $title, $content, $entry_date, $category, $is_private, $shared_with, $id
                    ]);
                } else {
                    db_query("UPDATE diary_entries SET title=?, content=?, entry_date=?, category=?, is_private=?, shared_with=NULL, shared_at=NULL WHERE id=?", [
                        $title, $content, $entry_date, $category, $is_private, $id
                    ]);
                }
                set_flash('success', 'Diary entry updated');
            }
        }
        redirect('?tab=entries');
    }

    if ($action === 'add_feedback') {
        $id = (int)$_POST['id'];
        $feedback = trim($_POST['teacher_feedback'] ?? '');
        $entry = db_get_row("SELECT * FROM diary_entries WHERE id=? AND shared_with=?", [$id, get_user_id()]);
        if ($entry) {
            db_query("UPDATE diary_entries SET teacher_feedback=? WHERE id=?", [$feedback, $id]);
            set_flash('success', 'Feedback saved');
        }
        redirect('?tab=shared');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $entry = db_get_row("SELECT * FROM diary_entries WHERE id=? AND user_id=?", [$id, get_user_id()]);
        if ($entry || has_role('admin')) {
            db_query("DELETE FROM diary_entries WHERE id=?", [$id]);
            set_flash('success', 'Entry deleted');
        }
        redirect('?tab=entries');
    }
}

if (isset($_GET['edit']) && isset($_GET['id'])) {
    $editing_entry = db_get_row("SELECT * FROM diary_entries WHERE id=? AND (user_id=? OR ? IN ('admin','teacher'))", [(int)$_GET['id'], get_user_id(), get_user_role()]);
}

$entries = [];
$shared_entries = [];
if ($tab === 'entries') {
    if (has_role('admin')) {
        $entries = db_get_all("SELECT de.*, u.full_name as author_name FROM diary_entries de JOIN users u ON de.user_id=u.id ORDER BY de.entry_date DESC, de.created_at DESC LIMIT 100");
    } else {
        $entries = db_get_all("SELECT * FROM diary_entries WHERE user_id=? ORDER BY entry_date DESC, created_at DESC LIMIT 100", [get_user_id()]);
    }
} elseif ($tab === 'shared' && !has_role('student')) {
    $shared_entries = db_get_all("SELECT de.*, u.full_name as author_name, u2.full_name as teacher_name
        FROM diary_entries de
        JOIN users u ON de.user_id=u.id
        LEFT JOIN users u2 ON de.shared_with=u2.id
        WHERE de.shared_with=? OR (de.shared_with IS NOT NULL AND ?='admin')
        ORDER BY de.shared_at DESC LIMIT 50", [get_user_id(), get_user_role()]);
}

$header_file = has_role('student') ? __DIR__ . '/../../includes/student-header.php' : __DIR__ . '/../../includes/header.php';
$footer_file = has_role('student') ? __DIR__ . '/../../includes/student-footer.php' : __DIR__ . '/../../includes/footer.php';
include $header_file;
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                <i class="fas fa-book text-teal-600 mr-2"></i> <?= has_role('student') ? 'Student' : 'Staff' ?> Diary
            </h1>
            <p class="text-gray-500 text-sm mt-1">Record daily notes <?= has_role('student') ? 'and share them with your teacher' : 'and review student submissions' ?></p>
        </div>
        <?php if ($tab !== 'write'): ?>
        <a href="?tab=write" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
            <i class="fas fa-plus"></i> New Entry
        </a>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="?tab=entries" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='entries'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-list mr-2"></i>My Entries
            </a>
            <a href="?tab=write" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='write'||$editing_entry?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-pen mr-2"></i><?= $editing_entry ? 'Edit Entry' : 'Write Entry' ?>
            </a>
            <?php if (!has_role('student')): ?>
            <a href="?tab=shared" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='shared'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-share-alt mr-2"></i>Shared with Me
                <?php $shared_count = db_get_row("SELECT COUNT(*) as c FROM diary_entries WHERE shared_with=? AND teacher_feedback IS NULL", [get_user_id()])['c'] ?? 0; ?>
                <?php if ($shared_count > 0): ?><span class="ml-1 bg-teal-100 text-teal-700 text-[10px] px-1.5 py-0.5 rounded-full font-bold"><?= $shared_count ?></span><?php endif; ?>
            </a>
            <?php endif; ?>
        </div>
        <div class="p-5">
            <?php if ($tab === 'write' || $editing_entry): ?>
            <!-- Write/Edit Form -->
            <div class="max-w-2xl mx-auto">
                <form method="POST">
                    <input type="hidden" name="action" value="<?= $editing_entry ? 'update' : 'create' ?>">
                    <?php if ($editing_entry): ?><input type="hidden" name="id" value="<?= $editing_entry['id'] ?>"><?php endif; ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Title *</label>
                            <input type="text" name="title" required value="<?= sanitize($editing_entry['title'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Date *</label>
                            <input type="date" name="entry_date" required value="<?= $editing_entry['entry_date'] ?? date('Y-m-d') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Category</label>
                            <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                <?php $cats = ['general'=>'General','lesson'=>'Lesson Note','reflection'=>'Reflection','meeting'=>'Meeting Notes','observation'=>'Observation','planning'=>'Planning', 'homework'=>'Homework', 'journal'=>'Journal']; ?>
                                <?php foreach ($cats as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($editing_entry['category'] ?? 'general') == $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Content *</label>
                            <textarea name="content" required rows="8" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"><?= sanitize($editing_entry['content'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_private" value="1" <?= ($editing_entry['is_private'] ?? 0) ? 'checked' : '' ?> class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="text-xs text-gray-600">Private (only visible to you <?= has_role('student') ? 'and admin' : '' ?>)</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Share with Teacher</label>
                            <select name="shared_with" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                <option value="">— Don't share —</option>
                                <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= ($editing_entry['shared_with']??'')==$t['id']?'selected':'' ?>><?= sanitize($t['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-[10px] text-gray-400 mt-0.5">Teachers can view and give feedback on shared entries</p>
                        </div>
                        <?php if ($editing_entry && $editing_entry['teacher_feedback']): ?>
                        <div class="sm:col-span-2 bg-teal-50 rounded-lg p-3">
                            <p class="text-xs font-bold text-teal-800 mb-1"><i class="fas fa-comment-dots mr-1"></i> Teacher Feedback</p>
                            <p class="text-xs text-teal-700"><?= nl2br(sanitize($editing_entry['teacher_feedback'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="bg-teal-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                        <i class="fas fa-save mr-1"></i> <?= $editing_entry ? 'Update Entry' : 'Save Entry' ?>
                    </button>
                    <?php if ($editing_entry): ?>
                    <a href="?tab=entries" class="ml-2 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-200 transition">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php elseif ($tab === 'shared' && !has_role('student')): ?>
            <!-- Shared with Me -->
            <h3 class="font-bold text-gray-900 text-sm mb-4 flex items-center gap-2">
                <i class="fas fa-share-alt text-teal-600"></i> Entries Shared with Me
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium"><?= count($shared_entries) ?></span>
            </h3>
            <?php if (empty($shared_entries)): ?>
            <div class="text-center py-10 text-gray-400">
                <i class="fas fa-inbox text-3xl mb-3 block text-gray-300"></i>
                <p class="text-sm">No entries have been shared with you yet.</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($shared_entries as $e): ?>
                <div class="border border-gray-200 rounded-xl p-4 <?= $e['teacher_feedback'] ? 'border-l-4 border-l-teal-500' : 'border-l-4 border-l-amber-400' ?>">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full 
                                    <?php $cc = ['general'=>'bg-gray-100 text-gray-700','lesson'=>'bg-blue-100 text-blue-700','reflection'=>'bg-purple-100 text-purple-700','meeting'=>'bg-amber-100 text-amber-700','observation'=>'bg-green-100 text-green-700','planning'=>'bg-teal-100 text-teal-700','homework'=>'bg-pink-100 text-pink-700','journal'=>'bg-indigo-100 text-indigo-700']; ?>
                                    <?= $cc[$e['category']] ?? 'bg-gray-100' ?>">
                                    <?= ucfirst($e['category']) ?>
                                </span>
                                <span class="text-[10px] text-gray-400">by <strong><?= sanitize($e['author_name']) ?></strong></span>
                                <span class="text-[10px] text-gray-400"><?= format_date($e['entry_date']) ?></span>
                            </div>
                            <h4 class="font-semibold text-gray-900 text-sm mt-1"><?= sanitize($e['title']) ?></h4>
                        </div>
                        <?php if ($e['is_private']): ?>
                        <span class="text-amber-600 text-xs"><i class="fas fa-lock"></i></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-600 mb-3"><?= nl2br(sanitize($e['content'])) ?></p>

                    <?php if ($e['teacher_feedback']): ?>
                    <div class="bg-teal-50 rounded-lg p-3 text-xs text-teal-800 mb-3">
                        <strong class="text-teal-900"><i class="fas fa-comment-dots mr-1"></i> Your Feedback:</strong>
                        <p class="mt-1"><?= nl2br(sanitize($e['teacher_feedback'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!$e['teacher_feedback']): ?>
                    <form method="POST" class="border-t border-gray-100 pt-3 mt-2">
                        <input type="hidden" name="action" value="add_feedback">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Add Feedback / Correction</label>
                        <textarea name="teacher_feedback" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Write your feedback or corrections..."></textarea>
                        <button type="submit" class="mt-1 bg-teal-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition">
                            <i class="fas fa-paper-plane mr-1"></i> Send Feedback
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- My Entries -->
            <?php if (empty($entries)): ?>
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-book-open text-4xl mb-3 block text-gray-300"></i>
                <p class="text-sm">No diary entries yet.</p>
                <a href="?tab=write" class="text-teal-600 hover:underline text-sm mt-2 inline-block">Write your first entry</a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($entries as $e): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition card-hover <?= $e['shared_with'] ? 'border-l-2 border-l-teal-400' : '' ?> <?= $e['teacher_feedback'] ? 'ring-1 ring-teal-300' : '' ?>">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full 
                                <?php $cc = ['general'=>'bg-gray-100 text-gray-700','lesson'=>'bg-blue-100 text-blue-700','reflection'=>'bg-purple-100 text-purple-700','meeting'=>'bg-amber-100 text-amber-700','observation'=>'bg-green-100 text-green-700','planning'=>'bg-teal-100 text-teal-700','homework'=>'bg-pink-100 text-pink-700','journal'=>'bg-indigo-100 text-indigo-700']; ?>
                                <?= $cc[$e['category']] ?? 'bg-gray-100' ?>">
                                <?= ucfirst($e['category']) ?>
                            </span>
                            <?php if ($e['is_private']): ?>
                            <span class="text-xs text-amber-600 ml-1"><i class="fas fa-lock"></i></span>
                            <?php endif; ?>
                            <?php if ($e['shared_with']): ?>
                            <span class="text-xs text-teal-600 ml-1"><i class="fas fa-share-alt"></i></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs text-gray-400"><?= format_date($e['entry_date']) ?></span>
                    </div>
                    <h4 class="font-semibold text-gray-900 text-sm mb-1"><?= sanitize($e['title']) ?></h4>
                    <p class="text-xs text-gray-600 line-clamp-3"><?= nl2br(sanitize(mb_substr($e['content'], 0, 200))) ?><?= mb_strlen($e['content']) > 200 ? '...' : '' ?></p>
                    <?php if (has_role('admin') && isset($e['author_name'])): ?>
                    <p class="text-[10px] text-gray-400 mt-2">by <?= sanitize($e['author_name']) ?></p>
                    <?php endif; ?>
                    <?php if ($e['teacher_feedback']): ?>
                    <div class="mt-2 bg-teal-50 rounded p-2 text-[10px] text-teal-700">
                        <i class="fas fa-comment-dots mr-0.5"></i> Teacher gave feedback
                    </div>
                    <?php endif; ?>
                    <div class="mt-3 pt-2 border-t border-gray-100 flex items-center gap-2 text-xs">
                        <a href="?edit&id=<?= $e['id'] ?>" class="text-amber-600 hover:text-amber-800"><i class="fas fa-edit mr-0.5"></i> Edit</a>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this entry?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                            <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash mr-0.5"></i> Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include $footer_file; ?>
