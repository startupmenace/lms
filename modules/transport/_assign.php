<?php
$selected_route_id = (int)($_GET['route_id'] ?? 0);
$class_filter = (int)($_GET['class_id'] ?? 0);
$session_filter = $_GET['session'] ?? date('Y');

$routes = db_get_all("SELECT r.*, v.vehicle_number, v.capacity,
    (SELECT COUNT(*) FROM transport_route_students WHERE route_id=r.id AND status='active') as assigned_count
    FROM transport_routes r
    LEFT JOIN transport_vehicles v ON r.vehicle_id=v.id
    WHERE r.status='active' ORDER BY r.name");

$classes = db_get_all("SELECT * FROM classes WHERE is_active=1 ORDER BY name");

$assigned_student_ids = [];
$route_capacity = 0;
if ($selected_route_id) {
    $route_info = db_get_row("SELECT v.capacity, (SELECT COUNT(*) FROM transport_route_students WHERE route_id=? AND status='active') as assigned FROM transport_routes r LEFT JOIN transport_vehicles v ON r.vehicle_id=v.id WHERE r.id=?", [$selected_route_id, $selected_route_id]);
    $route_capacity = $route_info['capacity'] ?? 0;
    $assigned_rows = db_get_all("SELECT student_id FROM transport_route_students WHERE route_id=? AND status='active'", [$selected_route_id]);
    $assigned_student_ids = array_column($assigned_rows, 'student_id');
}

$students = [];
if ($class_filter) {
    $students = db_get_all("SELECT s.id, s.enrollment_id, s.parent_phone, u.name as student_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.class_id = ? AND s.is_active = 1
        ORDER BY u.name", [$class_filter]);
}

$route_stops = [];
if ($selected_route_id) {
    $route_stops = db_get_all("SELECT * FROM transport_route_stops WHERE route_id=? ORDER BY stop_order", [$selected_route_id]);
}

$assignments = [];
if ($selected_route_id) {
    $assignments = db_get_all("SELECT rs.*, u.name as student_name, s.enrollment_id, s.parent_phone, c.name as class_name, rs2.name as stop_name
        FROM transport_route_students rs
        JOIN students s ON rs.student_id = s.id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN transport_route_stops rs2 ON rs.stop_id = rs2.id
        WHERE rs.route_id = ? AND rs.status='active'
        ORDER BY u.name", [$selected_route_id]);
}
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-sm">
                <i class="fas fa-user-plus text-teal-600"></i> Assign Student
            </h3>

            <form method="GET" class="mb-4 space-y-3">
                <input type="hidden" name="tab" value="assign">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Route</label>
                    <select name="route_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                        <option value="">— Select Route —</option>
                        <?php foreach ($routes as $r): ?>
                        <?php
                            $fill = $r['capacity'] > 0 ? round(($r['assigned_count'] / $r['capacity']) * 100) : 0;
                        ?>
                        <option value="<?= $r['id'] ?>" <?= $selected_route_id == $r['id'] ? 'selected' : '' ?>>
                            <?= sanitize($r['name']) ?> (<?= $r['assigned_count'] ?>/<?= $r['capacity'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($selected_route_id && $route_capacity > 0): ?>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                        <span>Capacity usage</span>
                        <span class="font-semibold"><?= count($assigned_student_ids) ?> / <?= $route_capacity ?></span>
                    </div>
                    <?php $pct = min(100, round((count($assigned_student_ids) / $route_capacity) * 100)); ?>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-teal-500 h-2 rounded-full transition-all" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Class</label>
                    <select name="class_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $class_filter == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if ($selected_route_id && $students): ?>
            <form method="POST">
                <input type="hidden" name="action" value="assign_student">
                <input type="hidden" name="route_id" value="<?= $selected_route_id ?>">
                <input type="hidden" name="session" value="<?= sanitize($session_filter) ?>">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Student</label>
                        <select name="student_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="">— Select —</option>
                            <?php foreach ($students as $s): ?>
                            <?php if (!in_array($s['id'], $assigned_student_ids)): ?>
                            <option value="<?= $s['id'] ?>"><?= sanitize($s['student_name']) ?> (<?= sanitize($s['enrollment_id']) ?>)</option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <?php
                            $available = array_filter($students, fn($s) => !in_array($s['id'], $assigned_student_ids));
                        ?>
                        <p class="text-xs text-gray-400 mt-1"><?= count($available) ?> unassigned students</p>
                    </div>
                    <?php if ($route_stops): ?>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Pickup/Drop Stop</label>
                        <select name="stop_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="">— General —</option>
                            <?php foreach ($route_stops as $st): ?>
                            <option value="<?= $st['id'] ?>"><?= sanitize($st['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Fee (KSh)</label>
                        <input type="number" step="0.01" name="fee_amount" value="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <button type="submit" class="w-full bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                        <i class="fas fa-plus mr-1"></i> Assign
                    </button>
                </div>
            </form>
            <?php elseif ($selected_route_id): ?>
            <p class="text-sm text-gray-500 text-center py-4">No unassigned students available.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <i class="fas fa-list text-teal-600"></i> Assigned Students
                </h3>
                <?php if ($selected_route_id): ?>
                <span class="text-xs bg-teal-100 text-teal-700 px-2 py-1 rounded-full font-semibold"><?= count($assignments) ?> students</span>
                <?php endif; ?>
            </div>

            <?php if (empty($assignments)): ?>
            <div class="text-center py-10 text-gray-400">
                <i class="fas fa-users text-4xl mb-3 block text-gray-300"></i>
                <p class="text-sm"><?= $selected_route_id ? 'No students assigned yet.' : 'Select a route above.' ?></p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50/50">
                            <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Student</th>
                            <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">Class</th>
                            <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden lg:table-cell">Parent Phone</th>
                            <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden md:table-cell">Stop</th>
                            <th class="text-right py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Fee</th>
                            <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="py-2 px-2">
                                <span class="font-medium text-gray-900"><?= sanitize($a['student_name']) ?></span>
                                <span class="text-xs text-gray-400 ml-1"><?= sanitize($a['enrollment_id']) ?></span>
                            </td>
                            <td class="py-2 px-2 text-gray-600 hidden sm:table-cell"><?= sanitize($a['class_name'] ?? '—') ?></td>
                            <td class="py-2 px-2 text-gray-600 hidden lg:table-cell text-xs"><?= sanitize($a['parent_phone'] ?? '—') ?></td>
                            <td class="py-2 px-2 text-gray-600 hidden md:table-cell"><?= sanitize($a['stop_name'] ?? '—') ?></td>
                            <td class="py-2 px-2 text-right font-medium"><?= format_currency($a['fee_amount']) ?></td>
                            <td class="py-2 px-2 text-center">
                                <form method="POST" class="inline" onsubmit="return confirm('Remove this student from route?')">
                                    <input type="hidden" name="action" value="unassign_student">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium"><i class="fas fa-user-minus mr-0.5"></i> Remove</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
