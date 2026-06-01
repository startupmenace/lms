<?php
$search = sanitize($_GET['search'] ?? '');
$role_filter = sanitize($_GET['role'] ?? '');
$status_filter = $_GET['status'] ?? '';

$where = "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($role_filter) {
    $where .= " AND u.role = ?";
    $params[] = $role_filter;
}
if ($status_filter === 'active') {
    $where .= " AND u.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where .= " AND u.is_active = 0";
}

$users = db_get_all("SELECT u.*, r.description as role_description FROM users u LEFT JOIN roles r ON u.role = r.name $where ORDER BY u.is_active DESC, u.full_name", $params);

$roles = db_get_all("SELECT name FROM roles ORDER BY name");
?>

<div class="flex flex-col sm:flex-row gap-3 mb-4">
    <form method="GET" class="flex-1 flex gap-2">
        <input type="hidden" name="tab" value="all">
        <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search by name, email or phone..." class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        <select name="role" class="border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
            <option value="">All Roles</option>
            <?php foreach ($roles as $r): ?>
            <option value="<?= sanitize($r['name']) ?>" <?= $role_filter===$r['name']?'selected':'' ?>><?= ucfirst(sanitize($r['name'])) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="active" <?= $status_filter==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $status_filter==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
        <button type="submit" class="bg-teal-600 text-white px-3 py-2 rounded-lg text-xs font-medium hover:bg-teal-700 transition"><i class="fas fa-search"></i></button>
    </form>
</div>

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/50">
                <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Name</th>
                <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">Email</th>
                <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden md:table-cell">Phone</th>
                <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Role</th>
                <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Status</th>
                <th class="text-right py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="py-2 px-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-[10px] font-bold flex-shrink-0"><?= get_avatar($u['full_name']) ?></div>
                        <span class="text-xs font-medium text-gray-900"><?= sanitize($u['full_name']) ?></span>
                    </div>
                </td>
                <td class="py-2 px-2 text-xs text-gray-600 hidden sm:table-cell"><?= sanitize($u['email']) ?></td>
                <td class="py-2 px-2 text-xs text-gray-600 hidden md:table-cell"><?= sanitize($u['phone'] ?? '—') ?></td>
                <td class="py-2 px-2 text-center">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                        <?= $u['role']==='admin'?'bg-purple-100 text-purple-700':($u['role']==='teacher'?'bg-blue-100 text-blue-700':($u['role']==='student'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-700')) ?>">
                        <?= ucfirst(sanitize($u['role'])) ?>
                    </span>
                </td>
                <td class="py-2 px-2 text-center">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $u['is_active']?'bg-green-100 text-green-700':'bg-gray-100 text-gray-600' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span>
                </td>
                <td class="py-2 px-2 text-right">
                    <a href="?tab=edit&id=<?= $u['id'] ?>" class="text-amber-600 hover:text-amber-800 text-xs mr-1.5" title="Edit"><i class="fas fa-edit"></i></a>
                    <?php if ($u['id'] !== get_user_id()): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Toggle status for <?= sanitize($u['full_name']) ?>?')">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button class="text-<?= $u['is_active']?'red':'green' ?>-600 hover:text-<?= $u['is_active']?'red':'green' ?>-800 text-xs mr-1.5" title="<?= $u['is_active']?'Deactivate':'Activate' ?>">
                            <i class="fas fa-<?= $u['is_active']?'ban':'check-circle' ?>"></i>
                        </button>
                    </form>
                    <form method="POST" class="inline" onsubmit="return confirm('Permanently delete <?= sanitize($u['full_name']) ?>? This cannot be undone.')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button class="text-red-600 hover:text-red-800 text-xs" title="Delete"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr><td colspan="6" class="text-center py-10 text-gray-400 text-sm">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
