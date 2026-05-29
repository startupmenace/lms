<?php
$edit_driver = null;
$show_form = isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit']);

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_driver = db_get_row("SELECT * FROM transport_drivers WHERE id=?", [(int)$_GET['id']]);
    if (!$edit_driver) $show_form = false;
}

$drivers = db_get_all("SELECT d.*,
    (SELECT COUNT(*) FROM transport_routes WHERE driver_id=d.id AND status='active') as active_routes
    FROM transport_drivers d ORDER BY d.created_at DESC");
?>

<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-bold text-gray-900">Driver Management</h2>
    <?php if (!$show_form): ?>
    <a href="?tab=drivers&action=add" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
        <i class="fas fa-plus"></i> Add Driver
    </a>
    <?php endif; ?>
</div>

<?php if ($show_form): ?>
<div class="bg-gray-50 rounded-xl p-6 border border-gray-200 mb-6">
    <h3 class="font-bold text-gray-900 mb-4"><?= $edit_driver ? 'Edit Driver' : 'Register New Driver' ?></h3>
    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <input type="hidden" name="action" value="<?= $edit_driver ? 'edit_driver' : 'add_driver' ?>">
        <?php if ($edit_driver): ?><input type="hidden" name="id" value="<?= $edit_driver['id'] ?>"><?php endif; ?>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Full Name *</label>
            <input type="text" name="name" required value="<?= sanitize($edit_driver['name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Phone</label>
            <input type="text" name="phone" value="<?= sanitize($edit_driver['phone'] ?? '') ?>" placeholder="+254 7XX XXX XXX" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="<?= sanitize($edit_driver['email'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">License Number</label>
            <input type="text" name="license_number" value="<?= sanitize($edit_driver['license_number'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">License Expiry</label>
            <input type="date" name="license_expiry" value="<?= $edit_driver['license_expiry'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Date of Birth</label>
            <input type="date" name="date_of_birth" value="<?= $edit_driver['date_of_birth'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div class="sm:col-span-2 lg:col-span-3">
            <label class="block text-xs font-bold text-gray-700 mb-1">Address</label>
            <textarea name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"><?= sanitize($edit_driver['address'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Emergency Contact Name</label>
            <input type="text" name="emergency_contact_name" value="<?= sanitize($edit_driver['emergency_contact_name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Emergency Contact Phone</label>
            <input type="text" name="emergency_contact_phone" value="<?= sanitize($edit_driver['emergency_contact_phone'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <option value="active" <?= ($edit_driver['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($edit_driver['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="sm:col-span-2 lg:col-span-3">
            <label class="block text-xs font-bold text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"><?= sanitize($edit_driver['notes'] ?? '') ?></textarea>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="bg-teal-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-check mr-1"></i> <?= $edit_driver ? 'Update Driver' : 'Register Driver' ?>
            </button>
            <a href="?tab=drivers" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-200 transition">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (empty($drivers)): ?>
<div class="text-center py-12 text-gray-400">
    <i class="fas fa-id-card text-5xl mb-4 block text-gray-300"></i>
    <p class="text-sm">No drivers registered.</p>
</div>
<?php else: ?>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/50">
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Driver</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px] hidden sm:table-cell">Contact</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px] hidden md:table-cell">License</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px] hidden lg:table-cell">License Expiry</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Status</th>
                <th class="text-right py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($drivers as $d):
                $lic_color = '';
                if ($d['license_expiry']) {
                    $days = (strtotime($d['license_expiry']) - time()) / 86400;
                    $lic_color = $days < 0 ? 'text-red-600' : ($days < 30 ? 'text-amber-600' : '');
                }
            ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="py-3 px-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-xs font-bold text-teal-700 flex-shrink-0">
                            <?= get_avatar($d['name']) ?>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900"><?= sanitize($d['name']) ?></div>
                            <?php if ($d['active_routes'] > 0): ?>
                            <div class="text-[10px] text-gray-400"><?= $d['active_routes'] ?> active route(s)</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="py-3 px-3 text-gray-600 hidden sm:table-cell">
                    <div><?= sanitize($d['phone'] ?? '—') ?></div>
                    <div class="text-xs text-gray-400"><?= sanitize($d['email'] ?? '') ?></div>
                </td>
                <td class="py-3 px-3 text-gray-600 hidden md:table-cell"><?= sanitize($d['license_number'] ?? '—') ?></td>
                <td class="py-3 px-3 text-center text-xs <?= $lic_color ?> hidden lg:table-cell"><?= $d['license_expiry'] ? format_date($d['license_expiry']) : '—' ?></td>
                <td class="py-3 px-3 text-center">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $d['status'] == 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>"><?= ucfirst($d['status']) ?></span>
                </td>
                <td class="py-3 px-3 text-right">
                    <a href="?tab=drivers&action=edit&id=<?= $d['id'] ?>" class="text-amber-600 hover:text-amber-800 text-sm mr-2"><i class="fas fa-edit"></i></a>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this driver record?')">
                        <input type="hidden" name="action" value="delete_driver">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
