<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('parent');

$page_title = 'Apply Leave';
$user_id = get_user_id();

$children = db_get_all("SELECT s.id, s.parent_name, c.name as class_name FROM student_parents sp JOIN students s ON sp.student_id = s.id LEFT JOIN classes c ON s.class_id = c.id WHERE sp.parent_user_id = ? AND s.is_active = 1 ORDER BY s.parent_name", [$user_id]);
$child_id = (int)($_POST['child_id'] ?? $_GET['child_id'] ?? $children[0]['id'] ?? 0);

$selected = [];
foreach ($children as $c) { if ($c['id'] == $child_id) { $selected = $c; break; } }
if (empty($selected) && !empty($children)) $selected = $children[0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_leave'])) {
    $child_id = (int)($_POST['child_id'] ?? 0);
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    $reason = sanitize($_POST['reason'] ?? '');

    if ($child_id && $from_date && $to_date && $reason) {
        $days = max(1, (strtotime($to_date) - strtotime($from_date)) / 86400 + 1);
        $existing_id = db_insert("INSERT INTO leave_applications (user_id, student_id, leave_type_id, reason, from_date, to_date, total_days, status, applied_at) VALUES (?, ?, 1, ?, ?, ?, ?, 'pending', NOW())",
            [$user_id, $child_id, $reason, $from_date, $to_date, $days]);
        set_flash('success', 'Leave application submitted successfully.');
        redirect('leave.php');
    } else {
        set_flash('error', 'Please fill in all required fields.');
    }
}

$applications = $selected ? db_get_all("SELECT * FROM leave_applications WHERE student_id = ? AND user_id = ? ORDER BY applied_at DESC LIMIT 20", [$selected['id'], $user_id]) : [];

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

<div class="max-w-4xl mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-6">Apply Leave — <?= sanitize($selected['parent_name'] ?? '') ?></h2>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
        <form method="post" class="space-y-4">
            <input type="hidden" name="child_id" value="<?= $selected['id'] ?? 0 ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="from_date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="to_date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <textarea name="reason" rows="3" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none" placeholder="Reason for leave..."></textarea>
            </div>
            <button type="submit" name="save_leave" class="bg-teal-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-paper-plane mr-2"></i>Submit Application
            </button>
        </form>
    </div>

    <?php if ($applications): ?>
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Leave History</h3>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left py-3 px-4 font-medium text-gray-500">From</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">To</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Reason</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $a): ?>
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 text-gray-900"><?= format_date($a['from_date']) ?></td>
                    <td class="py-3 px-4 text-gray-900"><?= format_date($a['to_date']) ?></td>
                    <td class="py-3 px-4 text-gray-600 max-w-xs truncate"><?= sanitize($a['reason']) ?></td>
                    <td class="py-3 px-4 text-center">
                        <span class="text-xs px-2 py-1 rounded-full <?= $a['status'] == 'approved' ? 'bg-green-100 text-green-700' : ($a['status'] == 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') ?>">
                            <?= ucfirst($a['status']) ?>
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
