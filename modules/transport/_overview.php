<?php
$total_vehicles = db_get_row("SELECT COUNT(*) as c FROM transport_vehicles")['c'];
$active_vehicles = db_get_row("SELECT COUNT(*) as c FROM transport_vehicles WHERE status='active'")['c'];
$maintenance_vehicles = db_get_row("SELECT COUNT(*) as c FROM transport_vehicles WHERE status='maintenance'")['c'];
$total_drivers = db_get_row("SELECT COUNT(*) as c FROM transport_drivers")['c'];
$active_drivers = db_get_row("SELECT COUNT(*) as c FROM transport_drivers WHERE status='active'")['c'];
$total_routes = db_get_row("SELECT COUNT(*) as c FROM transport_routes")['c'];
$active_routes = db_get_row("SELECT COUNT(*) as c FROM transport_routes WHERE status='active'")['c'];
$total_assigned = db_get_row("SELECT COUNT(*) as c FROM transport_route_students WHERE status='active'")['c'];
$total_students = db_get_row("SELECT COUNT(*) as c FROM students WHERE is_active=1")['c'];

$today = date('Y-m-d');
$today_present = db_get_row("SELECT COUNT(*) as c FROM transport_attendance WHERE date=? AND status='present'", [$today])['c'];
$today_absent = db_get_row("SELECT COUNT(*) as c FROM transport_attendance WHERE date=? AND status='absent'", [$today])['c'];
$today_late = db_get_row("SELECT COUNT(*) as c FROM transport_attendance WHERE date=? AND status='late'", [$today])['c'];

$expiring_insurance = db_get_all("SELECT vehicle_number,insurance_expiry FROM transport_vehicles WHERE insurance_expiry IS NOT NULL AND insurance_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY insurance_expiry");
$expiring_licenses = db_get_all("SELECT name,license_number,license_expiry FROM transport_drivers WHERE license_expiry IS NOT NULL AND license_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY license_expiry");

$recent_routes = db_get_all("SELECT r.*, v.vehicle_number, d.name as driver_name,
    (SELECT COUNT(*) FROM transport_route_students WHERE route_id=r.id AND status='active') as student_count
    FROM transport_routes r
    LEFT JOIN transport_vehicles v ON r.vehicle_id=v.id
    LEFT JOIN transport_drivers d ON r.driver_id=d.id
    WHERE r.status='active' ORDER BY r.name");
?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl p-5 text-white">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-white/80">Vehicles</span>
            <i class="fas fa-truck text-2xl text-white/50"></i>
        </div>
        <div class="text-3xl font-bold"><?= $total_vehicles ?></div>
        <div class="text-xs text-white/70 mt-1"><?= $active_vehicles ?> active · <?= $maintenance_vehicles ?> in maintenance</div>
    </div>
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-white/80">Drivers</span>
            <i class="fas fa-id-card text-2xl text-white/50"></i>
        </div>
        <div class="text-3xl font-bold"><?= $total_drivers ?></div>
        <div class="text-xs text-white/70 mt-1"><?= $active_drivers ?> active</div>
    </div>
    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-5 text-white">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-white/80">Routes</span>
            <i class="fas fa-route text-2xl text-white/50"></i>
        </div>
        <div class="text-3xl font-bold"><?= $total_routes ?></div>
        <div class="text-xs text-white/70 mt-1"><?= $active_routes ?> active</div>
    </div>
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-5 text-white">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-white/80">Students</span>
            <i class="fas fa-user-graduate text-2xl text-white/50"></i>
        </div>
        <div class="text-3xl font-bold"><?= $total_assigned ?></div>
        <div class="text-xs text-white/70 mt-1">of <?= $total_students ?> total students</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-route text-teal-600"></i> Active Routes
            </h3>
            <?php if (empty($recent_routes)): ?>
            <div class="text-center py-8 text-gray-400">
                <i class="fas fa-bus text-4xl mb-3 block text-gray-300"></i>
                <p class="text-sm">No routes configured yet.</p>
                <a href="?tab=routes" class="text-teal-600 hover:underline text-sm mt-2 inline-block">Create your first route</a>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_routes as $r): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-600">
                            <i class="fas fa-bus"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 text-sm"><?= sanitize($r['name']) ?></div>
                            <div class="text-xs text-gray-500">
                                <?= sanitize($r['vehicle_number'] ?? 'No vehicle') ?> ·
                                <?= sanitize($r['driver_name'] ?? 'No driver') ?> ·
                                <?= $r['student_count'] ?> students
                            </div>
                        </div>
                    </div>
                    <div class="text-right text-xs text-gray-500">
                        <?php if ($r['departure_time']): ?>
                        <div><i class="far fa-clock"></i> <?= date('h:i A', strtotime($r['departure_time'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-6">
        <!-- Today's Attendance Summary -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-900 mb-3 text-sm flex items-center gap-2">
                <i class="fas fa-clipboard-check text-teal-600"></i> Today's Attendance
            </h3>
            <?php if ($today_present + $today_absent + $today_late > 0): ?>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> Present</span>
                    <span class="font-bold text-green-700"><?= $today_present ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Late</span>
                    <span class="font-bold text-amber-700"><?= $today_late ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Absent</span>
                    <span class="font-bold text-red-700"><?= $today_absent ?></span>
                </div>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400 text-center py-4">No attendance marked today</p>
            <?php endif; ?>
        </div>

        <!-- Upcoming Expiries -->
        <?php if (!empty($expiring_insurance) || !empty($expiring_licenses)): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-900 mb-3 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-amber-500"></i> Upcoming Expiries
            </h3>
            <div class="space-y-2 text-sm">
                <?php foreach ($expiring_insurance as $e): ?>
                <div class="flex items-center justify-between text-amber-700 bg-amber-50 px-3 py-2 rounded-lg">
                    <span><i class="fas fa-shield-alt mr-1"></i> <?= sanitize($e['vehicle_number']) ?></span>
                    <span class="text-xs"><?= format_date($e['insurance_expiry']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php foreach ($expiring_licenses as $l): ?>
                <div class="flex items-center justify-between text-red-700 bg-red-50 px-3 py-2 rounded-lg">
                    <span><i class="fas fa-id-card mr-1"></i> <?= sanitize($l['name']) ?></span>
                    <span class="text-xs"><?= format_date($l['license_expiry']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
