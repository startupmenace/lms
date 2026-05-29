<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Manage Holidays';

$edit_id = (int)($_GET['edit'] ?? 0);
$delete_id = (int)($_GET['delete'] ?? 0);

if ($delete_id) {
    db_query("DELETE FROM holidays WHERE id = ?", [$delete_id]);
    set_flash('success', 'Holiday deleted.');
    redirect('manage.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id = (int)($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $date = sanitize($_POST['date'] ?? '');
    $type = sanitize($_POST['type'] ?? 'public');
    $recurring = isset($_POST['is_recurring']) ? 1 : 0;

    if ($title && $date) {
        if ($id > 0) {
            db_query("UPDATE holidays SET title=?, description=?, date=?, type=?, is_recurring=? WHERE id=?",
                [$title, $description, $date, $type, $recurring, $id]);
            set_flash('success', 'Holiday updated.');
        } else {
            db_insert("INSERT INTO holidays (title, description, date, type, is_recurring, created_by) VALUES (?, ?, ?, ?, ?, ?)",
                [$title, $description, $date, $type, $recurring, get_user_id()]);
            set_flash('success', 'Holiday added.');
        }
        redirect('manage.php');
    }
    set_flash('error', 'Title and date are required.');
    redirect('manage.php' . ($id > 0 ? '?edit=' . $id : ''));
}

$edit_holiday = null;
if ($edit_id > 0) {
    $edit_holiday = db_get_row("SELECT * FROM holidays WHERE id = ?", [$edit_id]);
}

$holidays = db_get_all("SELECT h.*, u.full_name as created_by_name FROM holidays h LEFT JOIN users u ON h.created_by = u.id ORDER BY h.date DESC");

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Manage Holidays</h1>
            <p class="text-gray-500 text-sm mt-1">Add, edit or remove holidays and events</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php" class="text-teal-600 border border-teal-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-50 transition flex items-center gap-2">
                <i class="fas fa-calendar-alt"></i> Calendar View
            </a>
        </div>
    </div>

    <?php if (has_flash('success')): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm"><?= get_flash('success') ?></div>
    <?php endif; ?>
    <?php if (has_flash('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm"><?= get_flash('error') ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 p-5 sticky top-6">
                <h2 class="font-bold text-gray-900 mb-4"><?= $edit_holiday ? 'Edit Holiday' : 'Add New Holiday' ?></h2>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $edit_holiday['id'] ?? 0 ?>">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required value="<?= sanitize($edit_holiday['title'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                            <input type="date" name="date" required value="<?= $edit_holiday['date'] ?? date('Y-m-d') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400">
                                <option value="public" <?= ($edit_holiday['type'] ?? '') == 'public' ? 'selected' : '' ?>>Public Holiday</option>
                                <option value="school" <?= ($edit_holiday['type'] ?? '') == 'school' ? 'selected' : '' ?>>School Holiday</option>
                                <option value="event" <?= ($edit_holiday['type'] ?? '') == 'event' ? 'selected' : '' ?>>Event / Special Day</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                            <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400"><?= sanitize($edit_holiday['description'] ?? '') ?></textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_recurring" id="recurring" value="1" <?= ($edit_holiday['is_recurring'] ?? 0) ? 'checked' : '' ?> class="rounded border-gray-300 text-teal-600 focus:ring-teal-400">
                            <label for="recurring" class="text-sm text-gray-600">Recurs every year</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="submit" name="save" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex-1">
                                <i class="fas fa-save mr-1"></i> <?= $edit_holiday ? 'Update' : 'Save' ?>
                            </button>
                            <?php if ($edit_holiday): ?>
                            <a href="manage.php" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto -mx-4 sm:-mx-0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-gray-200 bg-gray-50/50">
                                <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Date</th>
                                <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Title</th>
                                <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden md:table-cell">Type</th>
                                <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden sm:table-cell">Recurring</th>
                                <th class="text-right py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $type_badges = [
                                'public' => 'bg-red-100 text-red-700',
                                'school' => 'bg-amber-100 text-amber-700',
                                'event' => 'bg-blue-100 text-blue-700',
                            ];
                            ?>
                            <?php if (empty($holidays)): ?>
                            <tr><td colspan="5" class="py-12 text-center text-gray-400">No holidays added yet.</td></tr>
                            <?php else: ?>
                            <?php foreach ($holidays as $h): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                <td class="py-3 px-4 font-medium text-gray-900"><?= format_date($h['date'], 'd M Y') ?></td>
                                <td class="py-3 px-4">
                                    <span class="font-semibold text-gray-900"><?= sanitize($h['title']) ?></span>
                                    <?php if ($h['description']): ?>
                                    <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs"><?= sanitize($h['description']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 hidden md:table-cell">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $type_badges[$h['type']] ?? 'bg-gray-100 text-gray-600' ?>">
                                        <?= ucfirst($h['type']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 hidden sm:table-cell">
                                    <?php if ($h['is_recurring']): ?>
                                    <span class="text-xs text-teal-600"><i class="fas fa-sync-alt mr-1"></i> Yearly</span>
                                    <?php else: ?>
                                    <span class="text-xs text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="manage.php?edit=<?= $h['id'] ?>" class="text-teal-600 hover:text-teal-700 text-xs font-semibold mr-3 focus:outline-none focus:ring-2 focus:ring-teal-400 rounded px-1"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="manage.php?delete=<?= $h['id'] ?>" onclick="return confirm('Delete this holiday?')" class="text-red-600 hover:text-red-700 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-red-400 rounded px-1"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
