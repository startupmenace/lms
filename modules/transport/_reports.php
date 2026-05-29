<?php
$report_route_id = (int)($_GET['route_id'] ?? 0);
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 month'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$routes = db_get_all("SELECT id, name FROM transport_routes ORDER BY name");

$attendance_summary = [];
$route_summary = [];
$top_routes = [];

// Summary stats
$total_trips = db_get_row("SELECT COUNT(DISTINCT CONCAT(route_id, '-', date, '-', trip_type)) as c FROM transport_attendance WHERE date BETWEEN ? AND ?", [$date_from, $date_to])['c'];
$total_marks = db_get_row("SELECT COUNT(*) as c FROM transport_attendance WHERE date BETWEEN ? AND ?", [$date_from, $date_to])['c'];
$present_count = db_get_row("SELECT COUNT(*) as c FROM transport_attendance WHERE date BETWEEN ? AND ? AND status='present'", [$date_from, $date_to])['c'];
$absent_count = db_get_row("SELECT COUNT(*) as c FROM transport_attendance WHERE date BETWEEN ? AND ? AND status='absent'", [$date_from, $date_to])['c'];
$late_count = db_get_row("SELECT COUNT(*) as c FROM transport_attendance WHERE date BETWEEN ? AND ? AND status='late'", [$date_from, $date_to])['c'];
$att_rate = $total_marks > 0 ? round(($present_count / $total_marks) * 100, 1) : 0;

// Per-route summary
if ($report_route_id) {
    $attendance_summary = db_get_all("SELECT ta.date, ta.trip_type,
        COUNT(*) as total,
        SUM(CASE WHEN ta.status='present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN ta.status='absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN ta.status='late' THEN 1 ELSE 0 END) as late
        FROM transport_attendance ta
        WHERE ta.route_id=? AND ta.date BETWEEN ? AND ?
        GROUP BY ta.date, ta.trip_type
        ORDER BY ta.date DESC", [$report_route_id, $date_from, $date_to]);

    $route_name = db_get_row("SELECT name FROM transport_routes WHERE id=?", [$report_route_id])['name'] ?? 'Unknown';
    $route_summary = db_get_row("SELECT COUNT(*) as total,
        SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late
        FROM transport_attendance WHERE route_id=? AND date BETWEEN ? AND ?", [$report_route_id, $date_from, $date_to]);
} else {
    $top_routes = db_get_all("SELECT r.name,
        COUNT(*) as total,
        SUM(CASE WHEN ta.status='present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN ta.status='absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN ta.status='late' THEN 1 ELSE 0 END) as late
        FROM transport_attendance ta
        JOIN transport_routes r ON ta.route_id=r.id
        WHERE ta.date BETWEEN ? AND ?
        GROUP BY ta.route_id
        ORDER BY total DESC LIMIT 10", [$date_from, $date_to]);
}

// Student-level report for selected route
$student_report = [];
if ($report_route_id) {
    $student_report = db_get_all("SELECT u.name as student_name, s.enrollment_id,
        COUNT(*) as total_days,
        SUM(CASE WHEN ta.status='present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN ta.status='absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN ta.status='late' THEN 1 ELSE 0 END) as late_days
        FROM transport_attendance ta
        JOIN students s ON ta.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE ta.route_id=? AND ta.date BETWEEN ? AND ?
        GROUP BY ta.student_id
        ORDER BY u.name", [$report_route_id, $date_from, $date_to]);
}
?>

<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="reports">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Route</label>
                <select name="route_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">All Routes (Summary)</option>
                    <?php foreach ($routes as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $report_route_id == $r['id'] ? 'selected' : '' ?>><?= sanitize($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">From</label>
                <input type="date" name="date_from" value="<?= $date_from ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">To</label>
                <input type="date" name="date_to" value="<?= $date_to ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-filter mr-1"></i> Generate Report
            </button>
            <button type="button" onclick="window.print()" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-200 border border-gray-300 transition">
                <i class="fas fa-print mr-1"></i> Print
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900"><?= $total_trips ?></div>
            <div class="text-xs text-gray-500">Total Trips</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900"><?= $total_marks ?></div>
            <div class="text-xs text-gray-500">Total Records</div>
        </div>
        <div class="bg-white rounded-xl border border-green-100 p-4 text-center">
            <div class="text-2xl font-bold text-green-700"><?= $present_count ?></div>
            <div class="text-xs text-green-600">Present</div>
        </div>
        <div class="bg-white rounded-xl border border-amber-100 p-4 text-center">
            <div class="text-2xl font-bold text-amber-700"><?= $late_count ?></div>
            <div class="text-xs text-amber-600">Late</div>
        </div>
        <div class="bg-white rounded-xl border border-red-100 p-4 text-center">
            <div class="text-2xl font-bold text-red-700"><?= $absent_count ?></div>
            <div class="text-xs text-red-600">Absent</div>
        </div>
    </div>

    <!-- Attendance Rate Bar -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-bold text-gray-900 text-sm">Overall Attendance Rate</h3>
            <span class="text-2xl font-bold <?= $att_rate >= 90 ? 'text-green-600' : ($att_rate >= 75 ? 'text-amber-600' : 'text-red-600') ?>"><?= $att_rate ?>%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <?php $bar_color = $att_rate >= 90 ? 'bg-green-500' : ($att_rate >= 75 ? 'bg-amber-500' : 'bg-red-500'); ?>
            <div class="<?= $bar_color ?> h-3 rounded-full transition-all" style="width:<?= min(100, $att_rate) ?>%"></div>
        </div>
    </div>

    <?php if ($report_route_id && !empty($route_summary)): ?>
    <!-- Route Detail -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fas fa-route text-teal-600"></i> Route: <?= sanitize($route_name ?? '') ?>
        </h3>
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="text-center p-3 bg-green-50 rounded-lg">
                <div class="text-xl font-bold text-green-700"><?= $route_summary['present'] ?? 0 ?></div>
                <div class="text-xs text-green-600">Present</div>
            </div>
            <div class="text-center p-3 bg-amber-50 rounded-lg">
                <div class="text-xl font-bold text-amber-700"><?= $route_summary['late'] ?? 0 ?></div>
                <div class="text-xs text-amber-600">Late</div>
            </div>
            <div class="text-center p-3 bg-red-50 rounded-lg">
                <div class="text-xl font-bold text-red-700"><?= $route_summary['absent'] ?? 0 ?></div>
                <div class="text-xs text-red-600">Absent</div>
            </div>
        </div>

        <!-- Daily breakdown -->
        <?php if (!empty($attendance_summary)): ?>
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/50 sticky top-0">
                        <th class="text-left py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Date</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Trip</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Present</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Late</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Absent</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Total</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_summary as $day): ?>
                    <?php $rate = $day['total'] > 0 ? round(($day['present'] / $day['total']) * 100) : 0; ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-900"><?= format_date($day['date']) ?></td>
                        <td class="py-2 px-3 text-center capitalize text-xs text-gray-500"><?= $day['trip_type'] ?></td>
                        <td class="py-2 px-3 text-center text-green-700 font-medium"><?= $day['present'] ?></td>
                        <td class="py-2 px-3 text-center text-amber-700 font-medium"><?= $day['late'] ?></td>
                        <td class="py-2 px-3 text-center text-red-700 font-medium"><?= $day['absent'] ?></td>
                        <td class="py-2 px-3 text-center font-medium"><?= $day['total'] ?></td>
                        <td class="py-2 px-3 text-center">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $rate >= 90 ? 'bg-green-100 text-green-700' : ($rate >= 75 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= $rate ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Student-level report -->
    <?php if (!empty($student_report)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-sm">
            <i class="fas fa-user-graduate text-teal-600"></i> Student-wise Attendance
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/50">
                        <th class="text-left py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Student</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Total</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Present</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Late</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Absent</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($student_report as $sr):
                        $srate = $sr['total_days'] > 0 ? round(($sr['present_days'] / $sr['total_days']) * 100) : 0;
                    ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-900"><?= sanitize($sr['student_name']) ?></td>
                        <td class="py-2 px-3 text-center"><?= $sr['total_days'] ?></td>
                        <td class="py-2 px-3 text-center text-green-700"><?= $sr['present_days'] ?></td>
                        <td class="py-2 px-3 text-center text-amber-700"><?= $sr['late_days'] ?></td>
                        <td class="py-2 px-3 text-center text-red-700"><?= $sr['absent_days'] ?></td>
                        <td class="py-2 px-3 text-center">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $srate >= 90 ? 'bg-green-100 text-green-700' : ($srate >= 75 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= $srate ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif (!$report_route_id && !empty($top_routes)): ?>
    <!-- All Routes Summary -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-sm">
            <i class="fas fa-chart-bar text-teal-600"></i> Route-wise Attendance Summary
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/50">
                        <th class="text-left py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Route</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Total Records</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Present</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Late</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Absent</th>
                        <th class="text-center py-2 px-3 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_routes as $tr):
                        $tr_rate = $tr['total'] > 0 ? round(($tr['present'] / $tr['total']) * 100) : 0;
                    ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-900"><?= sanitize($tr['name']) ?></td>
                        <td class="py-2 px-3 text-center"><?= $tr['total'] ?></td>
                        <td class="py-2 px-3 text-center text-green-700 font-medium"><?= $tr['present'] ?></td>
                        <td class="py-2 px-3 text-center text-amber-700 font-medium"><?= $tr['late'] ?></td>
                        <td class="py-2 px-3 text-center text-red-700 font-medium"><?= $tr['absent'] ?></td>
                        <td class="py-2 px-3 text-center">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $tr_rate >= 90 ? 'bg-green-100 text-green-700' : ($tr_rate >= 75 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= $tr_rate ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif (!$report_route_id): ?>
    <div class="text-center py-12 text-gray-400">
        <i class="fas fa-chart-pie text-5xl mb-4 block text-gray-300"></i>
        <p class="text-sm">Select a route to view detailed reports, or view the route-wise summary above.</p>
    </div>
    <?php endif; ?>
</div>
