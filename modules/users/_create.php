<?php
$roles = db_get_all("SELECT name FROM roles ORDER BY name");
?>

<div class="max-w-lg">
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="create">

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Full Name *</label>
            <input type="text" name="full_name" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"
                   placeholder="e.g. John Doe">
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Email *</label>
            <input type="email" name="email" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"
                   placeholder="e.g. john@school.com">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"
                       placeholder="0712 345 678">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Role *</label>
                <select name="role" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">Select role...</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= sanitize($r['name']) ?>"><?= ucfirst(sanitize($r['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Password</label>
            <input type="text" name="password" value="password"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            <p class="text-xs text-gray-400 mt-1">Default: <code>password</code>. User can change it in their profile.</p>
        </div>

        <div class="flex items-center gap-2 pt-2">
            <button type="submit" class="bg-teal-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-user-plus mr-1"></i> Create User
            </button>
            <a href="?tab=all" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">Cancel</a>
        </div>
    </form>
</div>
