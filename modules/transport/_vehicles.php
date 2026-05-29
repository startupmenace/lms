<?php
$edit_vehicle = null;
$show_form = isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit']);

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_vehicle = db_get_row("SELECT * FROM transport_vehicles WHERE id=?", [(int)$_GET['id']]);
    if (!$edit_vehicle) $show_form = false;
}

$vehicles = db_get_all("SELECT v.*, d.name as driver_name,
    (SELECT COUNT(*) FROM transport_routes WHERE vehicle_id=v.id AND status='active') as active_routes
    FROM transport_vehicles v
    LEFT JOIN transport_drivers d ON v.driver_id=d.id
    ORDER BY v.created_at DESC");
?>

<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-bold text-gray-900">Fleet Management</h2>
    <?php if (!$show_form): ?>
    <a href="?tab=vehicles&action=add" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
        <i class="fas fa-plus"></i> Add Vehicle
    </a>
    <?php endif; ?>
</div>

<?php if ($show_form): ?>
<div class="bg-gray-50 rounded-xl p-6 border border-gray-200 mb-6">
    <h3 class="font-bold text-gray-900 mb-4"><?= $edit_vehicle ? 'Edit Vehicle' : 'Register New Vehicle' ?></h3>
    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <input type="hidden" name="action" value="<?= $edit_vehicle ? 'edit_vehicle' : 'add_vehicle' ?>">
        <?php if ($edit_vehicle): ?><input type="hidden" name="id" value="<?= $edit_vehicle['id'] ?>"><?php endif; ?>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Vehicle Number *</label>
            <input type="text" name="vehicle_number" required value="<?= sanitize($edit_vehicle['vehicle_number'] ?? '') ?>" placeholder="e.g. KCA 123T" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Type</label>
            <select name="vehicle_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <?php foreach (['bus', 'van', 'car'] as $t): ?>
                <option value="<?= $t ?>" <?= ($edit_vehicle['vehicle_type'] ?? 'bus') == $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Seating Capacity *</label>
            <input type="number" name="capacity" required value="<?= (int)($edit_vehicle['capacity'] ?? 30) ?>" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Model</label>
            <input type="text" name="model" value="<?= sanitize($edit_vehicle['model'] ?? '') ?>" placeholder="e.g. Toyota Hiace" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Year</label>
            <input type="number" name="year" value="<?= $edit_vehicle['year'] ?? '' ?>" min="2000" max="2030" placeholder="e.g. 2022" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Fuel Type</label>
            <select name="fuel_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <?php foreach (['diesel', 'petrol', 'electric', 'cng'] as $f): ?>
                <option value="<?= $f ?>" <?= ($edit_vehicle['fuel_type'] ?? 'diesel') == $f ? 'selected' : '' ?>><?= ucfirst($f) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Insurance Expiry</label>
            <input type="date" name="insurance_expiry" value="<?= $edit_vehicle['insurance_expiry'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Last Maintenance</label>
            <input type="date" name="last_maintenance" value="<?= $edit_vehicle['last_maintenance'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <?php foreach (['active', 'maintenance', 'inactive'] as $s): ?>
                <option value="<?= $s ?>" <?= ($edit_vehicle['status'] ?? 'active') == $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2 lg:col-span-3">
            <label class="block text-xs font-bold text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"><?= sanitize($edit_vehicle['notes'] ?? '') ?></textarea>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="bg-teal-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-check mr-1"></i> <?= $edit_vehicle ? 'Update Vehicle' : 'Register Vehicle' ?>
            </button>
            <a href="?tab=vehicles" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-200 transition">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (empty($vehicles)): ?>
<div class="text-center py-12 text-gray-400">
    <i class="fas fa-truck text-5xl mb-4 block text-gray-300"></i>
    <p class="text-sm">No vehicles registered.</p>
</div>
<?php else: ?>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/50">
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Vehicle</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px] hidden sm:table-cell">Type / Model</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px] hidden md:table-cell">Capacity</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px] hidden lg:table-cell">Insurance</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Status</th>
                <th class="text-right py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehicles as $v):
                $insurance_color = '';
                if ($v['insurance_expiry']) {
                    $days = (strtotime($v['insurance_expiry']) - time()) / 86400;
                    $insurance_color = $days < 0 ? 'text-red-600' : ($days < 30 ? 'text-amber-600' : 'text-gray-600');
                }
            ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="py-3 px-3">
                    <span class="font-semibold text-gray-900"><?= sanitize($v['vehicle_number']) ?></span>
                    <?php if ($v['active_routes'] > 0): ?>
                    <span class="text-[10px] bg-teal-100 text-teal-700 px-1.5 py-0.5 rounded-full ml-1"><?= $v['active_routes'] ?> route(s)</span>
                    <?php endif; ?>
                </td>
                <td class="py-3 px-3 text-gray-600 hidden sm:table-cell">
                    <span class="capitalize"><?= $v['vehicle_type'] ?></span>
                    <?php if ($v['model']): ?>· <?= sanitize($v['model']) ?><?php endif; ?>
                </td>
                <td class="py-3 px-3 text-center text-gray-700 hidden md:table-cell"><?= $v['capacity'] ?> seats</td>
                <td class="py-3 px-3 text-center <?= $insurance_color ?> hidden lg:table-cell text-xs">
                    <?= $v['insurance_expiry'] ? format_date($v['insurance_expiry']) : '—' ?>
                </td>
                <td class="py-3 px-3 text-center">
                    <?php $sc = ['active' => 'bg-green-100 text-green-700', 'maintenance' => 'bg-amber-100 text-amber-700', 'inactive' => 'bg-gray-100 text-gray-600']; ?>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $sc[$v['status']] ?? '' ?>"><?= ucfirst($v['status']) ?></span>
                </td>
                <td class="py-3 px-3 text-right">
                    <a href="?tab=vehicles&action=edit&id=<?= $v['id'] ?>" class="text-amber-600 hover:text-amber-800 text-sm mr-2"><i class="fas fa-edit"></i></a>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this vehicle?')">
                        <input type="hidden" name="action" value="delete_vehicle">
                        <input type="hidden" name="id" value="<?= $v['id'] ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
