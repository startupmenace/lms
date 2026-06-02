<?php
$att_date = $_GET['date'] ?? date('Y-m-d');
$att_route_id = (int)($_GET['route_id'] ?? 0);
$trip_type = $_GET['trip_type'] ?? 'pickup';

$routes = db_get_all("SELECT id, name FROM transport_routes WHERE status='active' ORDER BY name");

$students_on_route = [];
$attendance_records = [];

if ($att_route_id) {
    $students_on_route = db_get_all("SELECT rs.id as rs_id, rs.student_id, rs.stop_id, rs.fee_amount,
            u.full_name as student_name, s.enrollment_id, rs2.name as stop_name
        FROM transport_route_students rs
        JOIN students s ON rs.student_id = s.id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN transport_route_stops rs2 ON rs.stop_id = rs2.id
        WHERE rs.route_id = ? AND rs.status='active'
        ORDER BY u.full_name", [$att_route_id]);

    $attendance_records = db_get_all("SELECT student_id, status, remarks FROM transport_attendance WHERE route_id=? AND date=? AND trip_type=?", [$att_route_id, $att_date, $trip_type]);
    $attendance_records = array_column($attendance_records, null, 'student_id');
}

$recent_attendance = db_get_all("SELECT ta.*, u.full_name as student_name, r.name as route_name
    FROM transport_attendance ta
    JOIN students s ON ta.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN transport_routes r ON ta.route_id = r.id
    ORDER BY ta.created_at DESC LIMIT 50");
?>

<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="attendance">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Route</label>
                <select name="route_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                    <option value="">— Select —</option>
                    <?php foreach ($routes as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $att_route_id == $r['id'] ? 'selected' : '' ?>><?= sanitize($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="<?= $att_date ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Trip</label>
                <select name="trip_type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="pickup" <?= $trip_type == 'pickup' ? 'selected' : '' ?>>Pickup (Morning)</option>
                    <option value="drop" <?= $trip_type == 'drop' ? 'selected' : '' ?>>Drop (Afternoon)</option>
                </select>
            </div>
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-search mr-1"></i> Load
            </button>
            <?php if ($att_route_id && !empty($students_on_route)): ?>
            <a href="?tab=reports&route_id=<?= $att_route_id ?>&date_from=<?= date('Y-m-d', strtotime('-1 month')) ?>&date_to=<?= $att_date ?>" class="px-4 py-2 rounded-lg text-sm font-medium text-teal-600 hover:bg-teal-50 border border-teal-200 transition">
                <i class="fas fa-chart-bar mr-1"></i> View Report
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Attendance Form -->
    <?php if ($att_route_id && !empty($students_on_route)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <i class="fas fa-clipboard-check text-teal-600"></i>
                Mark Attendance — <?= format_date($att_date) ?> (<?= ucfirst($trip_type) ?>)
            </h3>
            <span class="text-xs text-gray-400"><?= count($students_on_route) ?> student(s)</span>
        </div>

        <form method="POST" id="attendance-form">
            <input type="hidden" name="action" value="mark_attendance">
            <input type="hidden" name="route_id" value="<?= $att_route_id ?>">
            <input type="hidden" name="date" value="<?= $att_date ?>">
            <input type="hidden" name="trip_type" value="<?= $trip_type ?>">

            <div class="flex items-center gap-2 mb-4 text-xs">
                <span class="text-gray-500 font-medium">Bulk mark:</span>
                <button type="button" class="bulk-mark px-3 py-1 rounded bg-green-100 text-green-700 hover:bg-green-200 transition font-medium" data-status="present">All Present</button>
                <button type="button" class="bulk-mark px-3 py-1 rounded bg-amber-100 text-amber-700 hover:bg-amber-200 transition font-medium" data-status="late">All Late</button>
                <button type="button" class="bulk-mark px-3 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200 transition font-medium" data-status="absent">All Absent</button>
            </div>

            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50/50 sticky top-0">
                            <th class="text-left py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">#</th>
                            <th class="text-left py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Student</th>
                            <th class="text-left py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">Stop</th>
                            <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Present</th>
                            <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Late</th>
                            <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Absent</th>
                            <th class="text-left py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden lg:table-cell">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students_on_route as $i => $s):
                            $prev = $attendance_records[$s['student_id']] ?? null;
                            $prev_status = $prev['status'] ?? 'present';
                            $prev_remarks = $prev['remarks'] ?? '';
                        ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="py-2 px-3 text-gray-400 text-xs w-8"><?= $i + 1 ?></td>
                            <td class="py-2 px-3">
                                <span class="font-medium text-gray-900 text-sm"><?= sanitize($s['student_name']) ?></span>
                                <span class="text-xs text-gray-400 ml-1"><?= sanitize($s['enrollment_id']) ?></span>
                            </td>
                            <td class="py-2 px-3 text-gray-600 text-xs hidden sm:table-cell"><?= sanitize($s['stop_name'] ?? '—') ?></td>
                            <td class="py-2 px-3 text-center">
                                <input type="radio" name="students[<?= $s['student_id'] ?>]" value="present" <?= $prev_status == 'present' ? 'checked' : '' ?> class="text-green-500 focus:ring-green-500">
                            </td>
                            <td class="py-2 px-3 text-center">
                                <input type="radio" name="students[<?= $s['student_id'] ?>]" value="late" <?= $prev_status == 'late' ? 'checked' : '' ?> class="text-amber-500 focus:ring-amber-500">
                            </td>
                            <td class="py-2 px-3 text-center">
                                <input type="radio" name="students[<?= $s['student_id'] ?>]" value="absent" <?= $prev_status == 'absent' ? 'checked' : '' ?> class="text-red-500 focus:ring-red-500">
                            </td>
                            <td class="py-2 px-3 hidden lg:table-cell">
                                <input type="text" name="remarks[<?= $s['student_id'] ?>]" value="<?= sanitize($prev_remarks) ?>" placeholder="Note" class="w-full border-0 border-b border-gray-200 px-1 py-1 text-xs focus:border-teal-500 outline-none bg-transparent">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                <button type="submit" class="bg-teal-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-save mr-1"></i> Save Attendance
                </button>
                <div class="text-xs text-gray-400 flex items-center gap-3">
                    <span><i class="fas fa-check-circle text-green-500 mr-1"></i> <span id="count-present">0</span></span>
                    <span><i class="fas fa-clock text-amber-500 mr-1"></i> <span id="count-late">0</span></span>
                    <span><i class="fas fa-times-circle text-red-500 mr-1"></i> <span id="count-absent">0</span></span>
                </div>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.bulk-mark').forEach(btn => {
            btn.addEventListener('click', function() {
                var status = this.dataset.status;
                document.querySelectorAll('input[type="radio"]').forEach(r => {
                    if (r.value === status) r.checked = true;
                });
                updateCounts();
            });
        });

        function updateCounts() {
            var p = 0, l = 0, a = 0;
            document.querySelectorAll('input[type="radio"]:checked').forEach(r => {
                if (r.value === 'present') p++;
                else if (r.value === 'late') l++;
                else if (r.value === 'absent') a++;
            });
            document.getElementById('count-present').textContent = p;
            document.getElementById('count-late').textContent = l;
            document.getElementById('count-absent').textContent = a;
        }

        document.querySelectorAll('input[type="radio"]').forEach(r => r.addEventListener('change', updateCounts));
        updateCounts();
    });
    </script>

    <?php elseif ($att_route_id): ?>
    <div class="bg-white rounded-xl p-10 text-center border border-gray-200">
        <i class="fas fa-users text-4xl text-gray-300 mb-3 block"></i>
        <p class="text-gray-500 text-sm mb-2">No students assigned to this route.</p>
        <a href="?tab=assign&route_id=<?= $att_route_id ?>" class="text-teal-600 hover:underline text-sm">Assign students first</a>
    </div>
    <?php endif; ?>

    <!-- Recent Attendance -->
    <?php if (!empty($recent_attendance)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <i class="fas fa-history text-teal-600"></i> Recent Attendance (Last 50)
            </h3>
        </div>
        <div class="overflow-x-auto max-h-72 overflow-y-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/50 sticky top-0">
                        <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Date</th>
                        <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Route</th>
                        <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Student</th>
                        <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Trip</th>
                        <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Status</th>
                        <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden lg:table-cell">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_attendance as $a): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-2 px-2 text-xs text-gray-600"><?= format_date($a['date'], 'd M Y') ?></td>
                        <td class="py-2 px-2 text-xs text-gray-600"><?= sanitize($a['route_name']) ?></td>
                        <td class="py-2 px-2 font-medium text-gray-900 text-xs"><?= sanitize($a['student_name']) ?></td>
                        <td class="py-2 px-2 text-center text-xs capitalize text-gray-500"><?= $a['trip_type'] ?></td>
                        <td class="py-2 px-2 text-center">
                            <?php $sc = ['present' => 'bg-green-100 text-green-700', 'absent' => 'bg-red-100 text-red-700', 'late' => 'bg-amber-100 text-amber-700']; ?>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full <?= $sc[$a['status']] ?? '' ?>"><?= ucfirst($a['status']) ?></span>
                        </td>
                        <td class="py-2 px-2 text-xs text-gray-500 hidden lg:table-cell"><?= sanitize($a['remarks'] ?: '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
