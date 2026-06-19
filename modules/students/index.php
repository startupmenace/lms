<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('students');

$page_title = 'Manage Students';

$classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");
$class_filter = $_GET['class_id'] ?? '';
$search = $_GET['search'] ?? '';

$stream_filter = $_GET['stream'] ?? '';
$streams = $class_filter ? db_get_all("SELECT DISTINCT section FROM classes WHERE id=? AND section IS NOT NULL AND section!='' ORDER BY section", [(int)$class_filter]) : [];

$where = "WHERE 1=1";
$params = [];
if ($class_filter) {
    $where .= " AND s.class_id = ?";
    $params[] = (int)$class_filter;
}
if ($stream_filter) {
    $where .= " AND c.section = ?";
    $params[] = $stream_filter;
}
if ($search) {
    $where .= " AND (s.enrollment_id LIKE ? OR s.parent_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$students = db_get_all("SELECT s.*, c.name as class_name, c.section as class_section FROM students s LEFT JOIN classes c ON s.class_id = c.id $where ORDER BY c.name, c.section, s.parent_name", $params);

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm text-gray-500"><?= count($students) ?> total students</p>
    </div>
    <a href="create.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
        <i class="fas fa-plus"></i> Add New Student
    </a>
    <a href="import.php" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition flex items-center gap-2">
        <i class="fas fa-file-csv"></i> Import CSV
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search by ID or name..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <select name="class_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
            <option value="">All Classes</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $class_filter == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?><?= $c['section'] ? ' (' . sanitize($c['section']) . ')' : '' ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($streams)): ?>
        <select name="stream" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            <option value="">All Streams</option>
            <?php foreach ($streams as $st): ?>
            <option value="<?= sanitize($st['section']) ?>" <?= $stream_filter == $st['section'] ? 'selected' : '' ?>><?= sanitize($st['section']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
            <i class="fas fa-search"></i> Filter
        </button>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
                <th class="text-left py-3 px-4 font-medium text-gray-500">Enrollment</th>
                <th class="text-left py-3 px-4 font-medium text-gray-500">Student Name</th>
                <th class="text-left py-3 px-4 font-medium text-gray-500">Class</th>
                <th class="text-left py-3 px-4 font-medium text-gray-500">Parent Name</th>
                <th class="text-left py-3 px-4 font-medium text-gray-500">Phone</th>
                <th class="text-left py-3 px-4 font-medium text-gray-500">Admission Date</th>
                <th class="text-left py-3 px-4 font-medium text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
            <tr><td colspan="7" class="py-12 text-center text-gray-400">
                <i class="fas fa-user-graduate text-4xl mb-3 block text-gray-300"></i>
                No students found. <a href="create.php" class="text-teal-600 hover:underline">Add your first student</a>
            </td></tr>
            <?php else: ?>
            <?php foreach ($students as $s): ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-teal-600"><?= sanitize($s['enrollment_id']) ?></td>
                <td class="py-3 px-4 flex items-center gap-2">
                    <?php if (!empty($s['profile_image'])): ?>
                        <img src="<?= upload_url($s['profile_image'], 'students') ?>" class="w-7 h-7 rounded-full object-cover">
                    <?php else: ?>
                        <span class="w-7 h-7 rounded-full bg-teal-100 flex items-center justify-center text-xs font-bold text-teal-700"><?= get_avatar($s['parent_name'] ?? 'S') ?></span>
                    <?php endif; ?>
                    <?= sanitize($s['parent_name'] ?? 'N/A') ?>
                </td>
                <td class="py-3 px-4"><span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded"><?= sanitize($s['class_name'] ?? 'N/A') ?><?= !empty($s['class_section']) ? ' · ' . sanitize($s['class_section']) : '' ?></span></td>
                <td class="py-3 px-4"><?= sanitize($s['parent_name'] ?? 'N/A') ?></td>
                <td class="py-3 px-4"><?= sanitize($s['parent_phone'] ?? 'N/A') ?></td>
                <td class="py-3 px-4"><?= $s['admission_date'] ? format_date($s['admission_date']) : 'N/A' ?></td>
                <td class="py-3 px-4">
                    <a href="view.php?id=<?= $s['id'] ?>" class="text-teal-600 hover:text-teal-800 mr-3" title="View"><i class="fas fa-eye"></i></a>
                    <a href="edit.php?id=<?= $s['id'] ?>" class="text-amber-600 hover:text-amber-800 mr-3" title="Edit"><i class="fas fa-edit"></i></a>
                    <a href="delete.php?id=<?= $s['id'] ?>" class="text-red-600 hover:text-red-800" title="Delete" data-confirm="Delete this student?"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
