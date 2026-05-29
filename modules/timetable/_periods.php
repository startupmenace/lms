<?php
$periods = db_get_all("SELECT * FROM timetable_periods ORDER BY sort_order");
?>

<div class="flex items-center justify-between mb-4">
    <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2"><i class="fas fa-clock text-teal-600"></i> Time Periods</h3>
    <button onclick="document.getElementById('add-form').classList.toggle('hidden')" class="bg-teal-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition">
        <i class="fas fa-plus mr-1"></i> Add Period
    </button>
</div>

<div id="add-form" class="hidden bg-gray-50 rounded-xl p-4 border border-gray-200 mb-4">
    <form method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <input type="hidden" name="action" value="add_period">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Name *</label>
            <input type="text" name="name" required placeholder="e.g. Period 1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Start *</label>
            <input type="time" name="start_time" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">End *</label>
            <input type="time" name="end_time" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Order</label>
            <input type="number" name="sort_order" value="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div class="sm:col-span-4 flex items-center gap-2">
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-xs font-medium hover:bg-teal-700 transition">Save</button>
            <button type="button" onclick="this.closest('#add-form').classList.add('hidden')" class="px-4 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-200 transition">Cancel</button>
        </div>
    </form>
</div>

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/50">
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Order</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Name</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">Start</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">End</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Active</th>
                <th class="text-right py-3 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($periods as $p): ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="py-3 px-3 text-gray-500 text-xs"><?= $p['sort_order'] ?></td>
                <td class="py-3 px-3 font-medium text-gray-900"><?= sanitize($p['name']) ?></td>
                <td class="py-3 px-3 text-gray-600 text-xs hidden sm:table-cell"><?= date('h:i A', strtotime($p['start_time'])) ?></td>
                <td class="py-3 px-3 text-gray-600 text-xs hidden sm:table-cell"><?= date('h:i A', strtotime($p['end_time'])) ?></td>
                <td class="py-3 px-3 text-center">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $p['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>"><?= $p['is_active'] ? 'Yes' : 'No' ?></span>
                </td>
                <td class="py-3 px-3 text-right">
                    <button onclick="document.getElementById('edit-<?= $p['id'] ?>').classList.remove('hidden')" class="text-amber-600 hover:text-amber-800 text-xs mr-2"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this period?')">
                        <input type="hidden" name="action" value="delete_period">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="text-red-600 hover:text-red-800 text-xs"><i class="fas fa-trash"></i></button>
                    </form>

                    <div id="edit-<?= $p['id'] ?>" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)this.classList.add('hidden')">
                        <div class="bg-white rounded-xl p-5 w-full max-w-md" onclick="event.stopPropagation()">
                            <h4 class="font-bold text-gray-900 mb-3">Edit Period</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_period">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="col-span-2">
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Name</label>
                                        <input type="text" name="name" value="<?= sanitize($p['name']) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Start</label>
                                        <input type="time" name="start_time" value="<?= $p['start_time'] ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">End</label>
                                        <input type="time" name="end_time" value="<?= $p['end_time'] ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Order</label>
                                        <input type="number" name="sort_order" value="<?= $p['sort_order'] ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Active</label>
                                        <select name="is_active" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                            <option value="1" <?= $p['is_active']?'selected':'' ?>>Yes</option>
                                            <option value="0" <?= !$p['is_active']?'selected':'' ?>>No</option>
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
