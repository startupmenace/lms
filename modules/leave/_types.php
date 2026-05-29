<?php
$leave_types = db_get_all("SELECT * FROM leave_types ORDER BY name");
?>

<div class="flex items-center justify-between mb-4">
    <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
        <i class="fas fa-tags text-teal-600"></i> Leave Types
    </h3>
    <button onclick="document.getElementById('add-type-form').classList.toggle('hidden')" class="bg-teal-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition">
        <i class="fas fa-plus mr-1"></i> Add Type
    </button>
</div>

<div id="add-type-form" class="hidden bg-gray-50 rounded-xl p-4 border border-gray-200 mb-4">
    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input type="hidden" name="action" value="add_type">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Name *</label>
            <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Max Days/Year</label>
            <input type="number" name="max_days" value="30" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Color</label>
            <input type="color" name="color" value="#0d9488" class="w-full h-[34px] border border-gray-300 rounded-lg cursor-pointer">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Description</label>
            <input type="text" name="description" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div class="sm:col-span-2 lg:col-span-4 flex items-center gap-2">
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-xs font-medium hover:bg-teal-700 transition">Save</button>
            <button type="button" onclick="this.closest('#add-type-form').classList.add('hidden')" class="px-4 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-200 transition">Cancel</button>
        </div>
    </form>
</div>

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/50">
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Type</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden md:table-cell">Description</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Max Days</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Active</th>
                <th class="text-right py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leave_types as $lt): ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="py-3 px-3">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full" style="background:<?= $lt['color'] ?>"></span>
                        <span class="font-medium text-gray-900"><?= sanitize($lt['name']) ?></span>
                    </div>
                </td>
                <td class="py-3 px-3 text-gray-600 text-xs hidden md:table-cell"><?= sanitize($lt['description'] ?? '—') ?></td>
                <td class="py-3 px-3 text-center font-medium"><?= $lt['max_days_per_year'] ?></td>
                <td class="py-3 px-3 text-center">
                    <?php if ($lt['is_active']): ?>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Active</span>
                    <?php else: ?>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="py-3 px-3 text-right">
                    <button onclick="document.getElementById('edit-<?= $lt['id'] ?>').classList.remove('hidden')" class="text-amber-600 hover:text-amber-800 text-xs mr-2"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this leave type?')">
                        <input type="hidden" name="action" value="delete_type">
                        <input type="hidden" name="id" value="<?= $lt['id'] ?>">
                        <button class="text-red-600 hover:text-red-800 text-xs"><i class="fas fa-trash"></i></button>
                    </form>

                    <div id="edit-<?= $lt['id'] ?>" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)this.classList.add('hidden')">
                        <div class="bg-white rounded-xl p-5 w-full max-w-md" onclick="event.stopPropagation()">
                            <h4 class="font-bold text-gray-900 mb-3">Edit Leave Type</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_type">
                                <input type="hidden" name="id" value="<?= $lt['id'] ?>">
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Name</label>
                                        <input type="text" name="name" value="<?= sanitize($lt['name']) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Description</label>
                                        <input type="text" name="description" value="<?= sanitize($lt['description'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Max Days/Year</label>
                                            <input type="number" name="max_days" value="<?= $lt['max_days_per_year'] ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Color</label>
                                            <input type="color" name="color" value="<?= $lt['color'] ?>" class="w-full h-[38px] border border-gray-300 rounded-lg cursor-pointer">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Active</label>
                                        <select name="is_active" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                            <option value="1" <?= $lt['is_active'] ? 'selected' : '' ?>>Yes</option>
                                            <option value="0" <?= !$lt['is_active'] ? 'selected' : '' ?>>No</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mt-4">
                                    <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">Update</button>
                                    <button type="button" onclick="this.closest('.hidden').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
