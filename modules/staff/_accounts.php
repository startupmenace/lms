<?php
if (!has_role('admin')) {
    echo '<p class="text-gray-500 text-center py-8">Access restricted to admins only.</p>';
    return;
}

$staff = db_get_all("SELECT id, full_name, email, phone, role, is_active, created_at FROM users WHERE role IN ('admin','teacher') ORDER BY is_active DESC, full_name");

$edit_account = null;
if (isset($_GET['edit_id'])) {
    $edit_account = db_get_row("SELECT id, full_name, email, phone, role, is_active FROM users WHERE id=? AND role IN ('admin','teacher')", [(int)$_GET['edit_id']]);
}
?>
<div class="flex items-center justify-between mb-4">
    <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
        <i class="fas fa-user-cog text-teal-600"></i> Staff Accounts
        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium"><?= count($staff) ?></span>
    </h3>
    <button type="button" onclick="document.getElementById('add-form').classList.toggle('hidden');document.getElementById('edit-form').classList.add('hidden')" class="bg-teal-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition">
        <i class="fas fa-plus mr-1"></i> New Account
    </button>
</div>

<!-- Add Form -->
<div id="add-form" class="<?= $edit_account ? 'hidden' : 'hidden' ?> bg-gray-50 rounded-xl border border-gray-200 p-4 mb-4">
    <h4 class="font-bold text-gray-900 text-xs mb-3 flex items-center gap-2"><i class="fas fa-plus-circle text-teal-600"></i> Create New Staff Account</h4>
    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input type="hidden" name="action" value="add_account">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Full Name *</label>
            <input type="text" name="full_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Email *</label>
            <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Role</label>
            <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                <option value="teacher">Teacher</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Phone</label>
            <input type="text" name="phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Password</label>
            <input type="text" name="password" value="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            <p class="text-[9px] text-gray-400 mt-0.5">Default: <code>password</code></p>
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-xs font-medium hover:bg-teal-700 transition"><i class="fas fa-check mr-1"></i> Create</button>
            <button type="button" onclick="this.closest('#add-form').classList.add('hidden')" class="ml-2 px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition">Cancel</button>
        </div>
    </form>
</div>

<!-- Edit Form -->
<?php if ($edit_account): ?>
<div id="edit-form" class="bg-amber-50 rounded-xl border border-amber-200 p-4 mb-4">
    <h4 class="font-bold text-gray-900 text-xs mb-3 flex items-center gap-2"><i class="fas fa-edit text-amber-600"></i> Edit: <?= sanitize($edit_account['full_name']) ?></h4>
    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input type="hidden" name="action" value="edit_account">
        <input type="hidden" name="id" value="<?= $edit_account['id'] ?>">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Full Name *</label>
            <input type="text" name="full_name" value="<?= sanitize($edit_account['full_name']) ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Email *</label>
            <input type="email" name="email" value="<?= sanitize($edit_account['email']) ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Role</label>
            <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                <option value="teacher" <?= $edit_account['role']=='teacher'?'selected':'' ?>>Teacher</option>
                <option value="admin" <?= $edit_account['role']=='admin'?'selected':'' ?>>Admin</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Phone</label>
            <input type="text" name="phone" value="<?= sanitize($edit_account['phone'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Active</label>
            <select name="is_active" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                <option value="1" <?= $edit_account['is_active']?'selected':'' ?>>Active</option>
                <option value="0" <?= !$edit_account['is_active']?'selected':'' ?>>Inactive</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">New Password</label>
            <input type="text" name="password" placeholder="Leave blank to keep current" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-amber-600 text-white px-4 py-2 rounded-lg text-xs font-medium hover:bg-amber-700 transition"><i class="fas fa-save mr-1"></i> Update</button>
            <a href="?tab=accounts" class="ml-2 px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Staff List Table -->
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
            <?php foreach ($staff as $s): ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="py-2 px-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-[10px] font-bold flex-shrink-0"><?= get_avatar($s['full_name']) ?></div>
                        <span class="text-xs font-medium text-gray-900"><?= sanitize($s['full_name']) ?></span>
                    </div>
                </td>
                <td class="py-2 px-2 text-xs text-gray-600 hidden sm:table-cell"><?= sanitize($s['email']) ?></td>
                <td class="py-2 px-2 text-xs text-gray-600 hidden md:table-cell"><?= sanitize($s['phone'] ?? '—') ?></td>
                <td class="py-2 px-2 text-center">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $s['role']=='admin'?'bg-purple-100 text-purple-700':'bg-blue-100 text-blue-700' ?>"><?= ucfirst($s['role']) ?></span>
                </td>
                <td class="py-2 px-2 text-center">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $s['is_active']?'bg-green-100 text-green-700':'bg-gray-100 text-gray-600' ?>"><?= $s['is_active']?'Active':'Inactive' ?></span>
                </td>
                <td class="py-2 px-2 text-right">
                    <a href="?tab=accounts&edit_id=<?= $s['id'] ?>" class="text-amber-600 hover:text-amber-800 text-xs mr-1.5"><i class="fas fa-edit"></i></a>
                    <?php if ($s['id'] !== get_user_id()): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Deactivate <?= sanitize($s['full_name']) ?>?')">
                        <input type="hidden" name="action" value="delete_account">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button class="text-red-600 hover:text-red-800 text-xs"><i class="fas fa-ban"></i></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
