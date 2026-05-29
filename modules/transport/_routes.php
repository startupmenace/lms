<?php
$edit_route = null;
$show_form = isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit']);

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_route = db_get_row("SELECT * FROM transport_routes WHERE id=?", [(int)$_GET['id']]);
    if ($edit_route) {
        $edit_route['stops'] = db_get_all("SELECT * FROM transport_route_stops WHERE route_id=? ORDER BY stop_order", [$edit_route['id']]);
    } else {
        $show_form = false;
    }
}

$vehicles = db_get_all("SELECT id, vehicle_number FROM transport_vehicles WHERE status='active' ORDER BY vehicle_number");
$drivers = db_get_all("SELECT id, name FROM transport_drivers WHERE status='active' ORDER BY name");
$routes = db_get_all("SELECT r.*, v.vehicle_number, v.capacity, d.name as driver_name, d.phone as driver_phone,
    (SELECT COUNT(*) FROM transport_route_students WHERE route_id=r.id AND status='active') as student_count,
    (SELECT COUNT(*) FROM transport_route_stops WHERE route_id=r.id) as stop_count
    FROM transport_routes r
    LEFT JOIN transport_vehicles v ON r.vehicle_id=v.id
    LEFT JOIN transport_drivers d ON r.driver_id=d.id
    ORDER BY r.name");

$view_route_map = null;
$view_route_stops = [];
if (isset($_GET['view_map']) && isset($_GET['id'])) {
    $view_route_map = db_get_row("SELECT r.*, v.vehicle_number, d.name as driver_name FROM transport_routes r
        LEFT JOIN transport_vehicles v ON r.vehicle_id=v.id
        LEFT JOIN transport_drivers d ON r.driver_id=d.id WHERE r.id=?", [(int)$_GET['id']]);
    if ($view_route_map) {
        $view_route_stops = db_get_all("SELECT * FROM transport_route_stops WHERE route_id=? ORDER BY stop_order", [$view_route_map['id']]);
    }
}
?>

<?php if ($view_route_map): ?>
<div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)window.location='?tab=routes'">
    <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
            <h3 class="font-bold text-gray-900"><i class="fas fa-map-marked-alt text-teal-600 mr-2"></i> <?= sanitize($view_route_map['name']) ?></h3>
            <a href="?tab=routes" class="text-gray-400 hover:text-gray-600 text-xl"><i class="fas fa-times"></i></a>
        </div>
        <div class="p-5">
            <div id="route-map" style="height:450px;border-radius:12px;border:1px solid #e5e7eb;"></div>
            <div class="mt-4 flex flex-wrap gap-2">
                <?php foreach ($view_route_stops as $i => $st): ?>
                <div class="flex items-center gap-1.5 text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5">
                    <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-[10px]"><?= $i+1 ?></span>
                    <span><?= sanitize($st['name']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const map = L.map('route-map').setView([-1.2921, 36.8219], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
    const stops = <?= json_encode($view_route_stops) ?>;
    const latlngs = [], bounds = [];
    stops.forEach(s => {
        if (s.latitude && s.longitude) {
            const m = L.marker([parseFloat(s.latitude), parseFloat(s.longitude)]).addTo(map);
            m.bindPopup(`<b>${s.name}</b>`);
            latlngs.push([parseFloat(s.latitude), parseFloat(s.longitude)]);
            bounds.push([parseFloat(s.latitude), parseFloat(s.longitude)]);
        }
    });
    if (latlngs.length > 1) { L.polyline(latlngs, {color:'#0d9488',weight:4,opacity:0.7}).addTo(map); map.fitBounds(bounds, {padding:[50,50]}); }
    else if (bounds.length === 1) map.setView(bounds[0], 14);
});
</script>
<?php endif; ?>

<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-bold text-gray-900">Route Management</h2>
    <?php if (!$show_form): ?>
    <a href="?tab=routes&action=add" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
        <i class="fas fa-plus"></i> Create Route
    </a>
    <?php endif; ?>
</div>

<?php if ($show_form): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="bg-gray-50 rounded-xl p-6 border border-gray-200 mb-6">
    <h3 class="font-bold text-gray-900 mb-4"><?= $edit_route ? 'Edit Route' : 'Create New Route' ?></h3>
    <form method="POST" id="route-form">
        <input type="hidden" name="action" value="<?= $edit_route ? 'edit_route' : 'add_route' ?>">
        <input type="hidden" name="stops" id="stops-input" value="">
        <?php if ($edit_route): ?><input type="hidden" name="id" value="<?= $edit_route['id'] ?>"><?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Route Name *</label>
                <input type="text" name="name" required value="<?= sanitize($edit_route['name'] ?? '') ?>" placeholder="e.g. Route A - Kasarani" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Vehicle</label>
                <select name="vehicle_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">— Select —</option>
                    <?php foreach ($vehicles as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= ($edit_route['vehicle_id'] ?? '') == $v['id'] ? 'selected' : '' ?>><?= sanitize($v['vehicle_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Driver</label>
                <select name="driver_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">— Select —</option>
                    <?php foreach ($drivers as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($edit_route['driver_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Fee (KSh)</label>
                <input type="number" step="0.01" name="fee_amount" value="<?= $edit_route['fee_amount'] ?? 0 ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Start Point</label>
                <input type="text" name="start_point" value="<?= sanitize($edit_route['start_point'] ?? '') ?>" placeholder="e.g. School Gate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">End Point</label>
                <input type="text" name="end_point" value="<?= sanitize($edit_route['end_point'] ?? '') ?>" placeholder="e.g. City Centre" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Departure</label>
                <input type="time" name="departure_time" value="<?= $edit_route['departure_time'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Arrival</label>
                <input type="time" name="arrival_time" value="<?= $edit_route['arrival_time'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <label class="block text-xs font-bold text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"><?= sanitize($edit_route['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="active" <?= ($edit_route['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($edit_route['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <!-- Stops + Map side-by-side -->
        <div class="border-t border-gray-200 pt-4 mt-2">
            <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-bold text-gray-700">Route Stops</label>
                <div class="flex items-center gap-2">
                    <span class="hidden lg:inline text-[10px] text-gray-400">Click <i class="fas fa-hand-pointer text-teal-500"></i> then the map</span>
                    <button type="button" id="add-stop" class="bg-teal-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition"><i class="fas fa-plus mr-1"></i> Add Stop</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                <!-- Stop list: 2/5 width -->
                <div class="lg:col-span-2 space-y-1.5">
                    <div id="stops-container" class="space-y-1.5 max-h-[380px] overflow-y-auto pr-1">
                        <?php if ($edit_route && !empty($edit_route['stops'])): ?>
                            <?php foreach ($edit_route['stops'] as $stop): ?>
                            <div class="stop-row flex items-center gap-1.5 bg-white rounded-lg border border-gray-200 px-2.5 py-2" data-idx="<?= $stop['stop_order'] - 1 ?>">
                                <span class="stop-order w-6 h-6 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-xs flex-shrink-0"><?= $stop['stop_order'] ?></span>
                                <input type="text" class="stop-name flex-1 min-w-0 border-0 border-b border-gray-200 px-1 py-0.5 text-xs focus:border-teal-500 outline-none bg-transparent" placeholder="Stop name" value="<?= sanitize($stop['name']) ?>">
                                <input type="text" class="stop-landmark w-16 border-0 border-b border-gray-200 px-1 py-0.5 text-xs focus:border-teal-500 outline-none bg-transparent hidden sm:inline-block" placeholder="Landmark" value="<?= sanitize($stop['landmark'] ?? '') ?>">
                                <input type="hidden" class="stop-lat" value="<?= $stop['latitude'] ?? '' ?>">
                                <input type="hidden" class="stop-lng" value="<?= $stop['longitude'] ?? '' ?>">
                                <button type="button" class="place-btn text-xs px-1.5 py-1 rounded font-medium flex-shrink-0 <?= ($stop['latitude'] && $stop['longitude']) ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700 hover:bg-amber-200' ?>">
                                    <?php if ($stop['latitude'] && $stop['longitude']): ?>
                                    <i class="fas fa-check"></i>
                                    <?php else: ?>
                                    <i class="fas fa-map-pin"></i>
                                    <?php endif; ?>
                                </button>
                                <button type="button" class="remove-stop text-red-400 hover:text-red-600 text-xs flex-shrink-0 px-1"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center py-8 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg" id="empty-stops-msg">
                            <i class="fas fa-map-marked-alt text-2xl mb-1 block text-gray-300"></i>
                            <p class="text-xs">No stops yet.</p>
                            <p class="text-[10px] mt-1">Tap <span class="text-teal-600 font-medium">Add Stop</span>, type a name, then click <i class="fas fa-map-pin text-amber-600"></i> <strong>Place</strong></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="fit-map-btn" class="text-[10px] text-teal-600 hover:text-teal-800 font-medium"><i class="fas fa-expand-arrows-alt mr-1"></i> Fit map to stops</button>
                </div>

                <!-- Map: 3/5 width -->
                <div class="lg:col-span-3 relative">
                    <div id="stops-map" style="height:400px;border-radius:10px;border:1px solid #d1d5db;cursor:crosshair;"></div>
                    <div id="place-hint" class="absolute top-2 left-2 z-[1000] bg-white/90 rounded-lg px-2.5 py-1.5 text-xs text-gray-700 shadow-sm border border-gray-200 flex items-center gap-2 pointer-events-none">
                        <i class="fas fa-mouse-pointer text-teal-600"></i>
                        <span>Click <strong class="text-amber-700">Place</strong> on a stop, then click the map</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="bg-teal-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-check mr-1"></i> <?= $edit_route ? 'Update Route' : 'Save Route' ?>
            </button>
            <a href="?tab=routes" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-200 transition">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('stops-container');
    const addBtn = document.getElementById('add-stop');
    const form = document.getElementById('route-form');
    const stopsInput = document.getElementById('stops-input');
    const emptyMsg = document.getElementById('empty-stops-msg');
    const fitBtn = document.getElementById('fit-map-btn');
    const hint = document.getElementById('place-hint');

    let activePlaceRow = null;

    // ---------- MAP ----------
    const map = L.map('stops-map', { zoomControl: true }).setView([-1.2921, 36.8219], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    let markers = [];
    let polyline = null;

    const COLORS = ['#0d9488','#2563eb','#d97706','#7c3aed','#dc2626','#0891b2','#059669','#ca8a04'];

    function rebuildMap() {
        markers.forEach(m => map.removeLayer(m));
        if (polyline) { map.removeLayer(polyline); polyline = null; }
        markers = [];

        const rows = container.querySelectorAll('.stop-row');
        const latlngs = [], bounds = [];

        rows.forEach((row, i) => {
            const name = row.querySelector('.stop-name').value.trim();
            const lat = parseFloat(row.querySelector('.stop-lat').value);
            const lng = parseFloat(row.querySelector('.stop-lng').value);
            const placeBtn = row.querySelector('.place-btn');

            if (name && !isNaN(lat) && !isNaN(lng)) {
                const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.bindPopup(`<b>${i+1}.</b> ${name}`);
                marker._row = row;

                marker.on('dragend', () => {
                    const p = marker.getLatLng();
                    row.querySelector('.stop-lat').value = p.lat.toFixed(6);
                    row.querySelector('.stop-lng').value = p.lng.toFixed(6);
                    rebuildMap();
                });

                marker.on('click', () => {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('ring-2', 'ring-teal-400');
                    setTimeout(() => row.classList.remove('ring-2', 'ring-teal-400'), 2000);
                });

                markers.push(marker);
                latlngs.push([lat, lng]);
                bounds.push([lat, lng]);
                placeBtn.className = 'place-btn text-xs px-1.5 py-1 rounded font-medium flex-shrink-0 bg-green-100 text-green-700';
                placeBtn.innerHTML = '<i class="fas fa-check"></i>';
            } else if (name) {
                placeBtn.className = 'place-btn text-xs px-1.5 py-1 rounded font-medium flex-shrink-0 bg-amber-100 text-amber-700 hover:bg-amber-200';
                placeBtn.innerHTML = '<i class="fas fa-map-pin"></i> Place';
            } else {
                placeBtn.className = 'place-btn text-xs px-1.5 py-1 rounded font-medium flex-shrink-0 bg-gray-100 text-gray-400';
                placeBtn.innerHTML = '<i class="fas fa-map-pin"></i>';
            }
        });

        if (latlngs.length > 1) {
            polyline = L.polyline(latlngs, { color: '#0d9488', weight: 4, opacity: 0.7, dashArray: '8,6' }).addTo(map);
        }

        const hasStops = rows.length > 0;
        if (emptyMsg) emptyMsg.style.display = hasStops ? 'none' : 'block';
    }

    // ---------- CLICK MAP -> place on active row ----------
    map.on('click', (e) => {
        // If a specific row is activated via Place button, use that
        if (activePlaceRow) {
            activePlaceRow.querySelector('.stop-lat').value = e.latlng.lat.toFixed(6);
            activePlaceRow.querySelector('.stop-lng').value = e.latlng.lng.toFixed(6);
            activePlaceRow.classList.remove('ring-2', 'ring-amber-400', 'bg-amber-50');
            activePlaceRow = null;
            hint.innerHTML = '<i class="fas fa-mouse-pointer text-teal-600"></i><span>✓ Placed! Click <strong class="text-amber-700">Place</strong> on another stop, or the map to continue</span>';
            rebuildMap();
            return;
        }

        // Otherwise, find first unplaced row that has a name
        const rows = container.querySelectorAll('.stop-row');
        for (const row of rows) {
            const lat = row.querySelector('.stop-lat').value;
            const name = row.querySelector('.stop-name').value.trim();
            if (!lat && name) {
                row.querySelector('.stop-lat').value = e.latlng.lat.toFixed(6);
                row.querySelector('.stop-lng').value = e.latlng.lng.toFixed(6);
                rebuildMap();
                return;
            }
        }

        // If all rows already placed, place on the last row
        if (rows.length > 0) {
            const last = rows[rows.length - 1];
            const name = last.querySelector('.stop-name').value.trim();
            if (name) {
                last.querySelector('.stop-lat').value = e.latlng.lat.toFixed(6);
                last.querySelector('.stop-lng').value = e.latlng.lng.toFixed(6);
                rebuildMap();
            } else {
                hint.innerHTML = '<i class="fas fa-exclamation-circle text-amber-600"></i><span>Type a stop name first, then click the map</span>';
                setTimeout(() => {
                    hint.innerHTML = '<i class="fas fa-mouse-pointer text-teal-600"></i><span>Click <strong class="text-amber-700">Place</strong> on a stop, then click the map</span>';
                }, 2000);
            }
        }
    });

    // ---------- PLACE BUTTON (activates a specific row) ----------
    container.addEventListener('click', (e) => {
        const btn = e.target.closest('.place-btn');
        if (!btn) return;
        const row = btn.closest('.stop-row');
        const lat = row.querySelector('.stop-lat').value;
        const name = row.querySelector('.stop-name').value.trim();

        if (!name) {
            row.querySelector('.stop-name').focus();
            row.querySelector('.stop-name').classList.add('ring-2', 'ring-red-300');
            setTimeout(() => row.querySelector('.stop-name').classList.remove('ring-2', 'ring-red-300'), 1500);
            return;
        }

        if (lat) {
            // Already placed — scroll to map marker
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('ring-2', 'ring-teal-400');
            setTimeout(() => row.classList.remove('ring-2', 'ring-teal-400'), 2000);
            return;
        }

        // Activate place mode for this row
        if (activePlaceRow) {
            activePlaceRow.classList.remove('ring-2', 'ring-amber-400', 'bg-amber-50');
        }
        activePlaceRow = row;
        row.classList.add('ring-2', 'ring-amber-400', 'bg-amber-50');
        hint.innerHTML = '<i class="fas fa-hand-pointer text-amber-600"></i><span>Now <strong>click the map</strong> to place <strong class="text-amber-700">' + name + '</strong></span>';
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    // ---------- REMOVE STOP ----------
    container.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-stop');
        if (!btn) return;
        if (container.querySelectorAll('.stop-row').length <= 1) return;
        const row = btn.closest('.stop-row');
        if (activePlaceRow === row) {
            activePlaceRow.classList.remove('ring-2', 'ring-amber-400', 'bg-amber-50');
            activePlaceRow = null;
        }
        row.remove();
        renumberAll();
        rebuildMap();
    });

    // ---------- ADD STOP ----------
    addBtn.addEventListener('click', () => {
        const div = document.createElement('div');
        div.className = 'stop-row flex items-center gap-1.5 bg-white rounded-lg border border-gray-200 px-2.5 py-2';
        div.innerHTML = `
            <span class="stop-order w-6 h-6 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-xs flex-shrink-0">1</span>
            <input type="text" class="stop-name flex-1 min-w-0 border-0 border-b border-gray-200 px-1 py-0.5 text-xs focus:border-teal-500 outline-none bg-transparent" placeholder="Stop name">
            <input type="text" class="stop-landmark w-16 border-0 border-b border-gray-200 px-1 py-0.5 text-xs focus:border-teal-500 outline-none bg-transparent hidden sm:inline-block" placeholder="Landmark">
            <input type="hidden" class="stop-lat" value="">
            <input type="hidden" class="stop-lng" value="">
            <button type="button" class="place-btn text-xs px-1.5 py-1 rounded font-medium flex-shrink-0 bg-gray-100 text-gray-400"><i class="fas fa-map-pin"></i></button>
            <button type="button" class="remove-stop text-red-400 hover:text-red-600 text-xs flex-shrink-0 px-1"><i class="fas fa-times"></i></button>`;
        if (emptyMsg) container.insertBefore(div, emptyMsg);
        else container.appendChild(div);
        div.querySelector('.stop-name').focus();
        renumberAll();
        rebuildMap();
    });

    // ---------- RENUMBER ----------
    function renumberAll() {
        const rows = container.querySelectorAll('.stop-row');
        rows.forEach((row, i) => {
            row.dataset.idx = i;
            const order = row.querySelector('.stop-order');
            order.textContent = i + 1;
            const c = COLORS[i % COLORS.length];
            order.style.background = c + '22';
            order.style.color = c;
        });
    }

    // ---------- FIT MAP ----------
    fitBtn.addEventListener('click', () => {
        if (markers.length === 0) return;
        map.fitBounds(L.featureGroup(markers).getBounds().pad(0.1));
    });

    // ---------- SUBMIT ----------
    form.addEventListener('submit', () => {
        const stops = [];
        container.querySelectorAll('.stop-row').forEach(row => {
            const name = row.querySelector('.stop-name').value.trim();
            if (name) {
                stops.push({
                    name: name,
                    landmark: row.querySelector('.stop-landmark').value.trim(),
                    latitude: row.querySelector('.stop-lat').value,
                    longitude: row.querySelector('.stop-lng').value,
                });
            }
        });
        stopsInput.value = JSON.stringify(stops);
    });

    // ---------- INPUT EVENTS ----------
    container.addEventListener('input', (e) => {
        if (e.target.classList.contains('stop-name')) rebuildMap();
    });

    // ---------- INIT ----------
    renumberAll();
    rebuildMap();
});
</script>
<?php endif; ?>

<?php if (empty($routes)): ?>
<div class="text-center py-12 text-gray-400">
    <i class="fas fa-road text-5xl mb-4 block text-gray-300"></i>
    <p class="text-sm">No routes created yet.</p>
</div>
<?php else: ?>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/50">
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Route</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px] hidden md:table-cell">Vehicle</th>
                <th class="text-left py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px] hidden md:table-cell">Driver</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Stops</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Students</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px] hidden lg:table-cell">Times</th>
                <th class="text-right py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Fee</th>
                <th class="text-center py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Status</th>
                <th class="text-right py-3 px-3 font-bold text-gray-700 uppercase tracking-wider text-[10px]">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($routes as $r): ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="py-3 px-3">
                    <div class="font-semibold text-gray-900"><?= sanitize($r['name']) ?></div>
                    <div class="text-xs text-gray-400"><?= sanitize($r['start_point'] ?? '') ?> → <?= sanitize($r['end_point'] ?? '') ?></div>
                </td>
                <td class="py-3 px-3 text-gray-600 hidden md:table-cell"><?= sanitize($r['vehicle_number'] ?? '—') ?></td>
                <td class="py-3 px-3 hidden md:table-cell">
                    <div class="text-gray-600"><?= sanitize($r['driver_name'] ?? '—') ?></div>
                    <?php if ($r['driver_phone']): ?><div class="text-xs text-gray-400"><?= sanitize($r['driver_phone']) ?></div><?php endif; ?>
                </td>
                <td class="py-3 px-3 text-center"><?= $r['stop_count'] ?></td>
                <td class="py-3 px-3 text-center"><?= $r['student_count'] ?></td>
                <td class="py-3 px-3 text-center text-xs text-gray-500 hidden lg:table-cell">
                    <?php if ($r['departure_time'] || $r['arrival_time']): ?>
                    <div><?= $r['departure_time'] ? date('h:i A', strtotime($r['departure_time'])) : '—' ?></div>
                    <div class="text-gray-300">to</div>
                    <div><?= $r['arrival_time'] ? date('h:i A', strtotime($r['arrival_time'])) : '—' ?></div>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="py-3 px-3 text-right font-medium"><?= format_currency($r['fee_amount']) ?></td>
                <td class="py-3 px-3 text-center">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $r['status'] == 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>"><?= ucfirst($r['status']) ?></span>
                </td>
                <td class="py-3 px-3 text-right">
                    <a href="?tab=routes&view_map&id=<?= $r['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm mr-1.5" title="View on map"><i class="fas fa-map-marked-alt"></i></a>
                    <a href="?tab=routes&action=edit&id=<?= $r['id'] ?>" class="text-amber-600 hover:text-amber-800 text-sm mr-1.5"><i class="fas fa-edit"></i></a>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this route and all associated data?')">
                        <input type="hidden" name="action" value="delete_route">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
