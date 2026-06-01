<?php
$id = (int)($edit_id ?? ($_GET['id'] ?? 0));
$user = db_get_row("SELECT * FROM users WHERE id=?", [$id]);
if (!$user) {
    echo '<p class="text-gray-500 text-center py-8">User not found.</p>';
    return;
}
$roles = db_get_all("SELECT name FROM roles ORDER BY name");
?>

<div class="max-w-lg">
    <div class="flex items-center gap-2 mb-4">
        <a href="?tab=all" class="text-gray-400 hover:text-gray-600 transition"><i class="fas fa-arrow-left"></i></a>
        <h3 class="font-bold text-gray-900 text-sm">Edit: <?= sanitize($user['full_name']) ?></h3>
    </div>

    <form method="POST" class="space-y-4 bg-gray-50 rounded-xl border border-gray-200 p-5">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= $user['id'] ?>">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="full_name" value="<?= sanitize($user['full_name']) ?>" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" value="<?= sanitize($user['email']) ?>" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" value="<?= sanitize($user['phone'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Role</label>
                <select name="role"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= sanitize($r['name']) ?>" <?= $user['role']===$r['name']?'selected':'' ?>><?= ucfirst(sanitize($r['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
                <select name="is_active"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="1" <?= $user['is_active']?'selected':'' ?>>Active</option>
                    <option value="0" <?= !$user['is_active']?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">New Password</label>
                <input type="text" name="password" placeholder="Leave blank to keep current"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
        </div>

        <div class="flex items-center gap-2 pt-1">
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-xs font-medium hover:bg-teal-700 transition">
                <i class="fas fa-save mr-1"></i> Update User
            </button>
            <a href="?tab=all" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition">Cancel</a>
        </div>
    </form>
</div>
