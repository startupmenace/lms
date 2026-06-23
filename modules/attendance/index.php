<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Attendance Management';
$tab = $_GET['tab'] ?? 'mark';

if (has_role('admin')) {
    $classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");
} else {
    $classes = db_get_all("SELECT c.* FROM classes c JOIN class_teachers ct ON c.id = ct.class_id WHERE ct.teacher_id = ? AND ct.role = 'class_teacher' AND c.is_active = 1 GROUP BY c.id ORDER BY c.name", [get_user_id()]);
}
$class_id = (int)($_GET['class_id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');
$students = [];

if ($class_id) {
    $students = db_get_all("SELECT s.*, a.status as attendance_status, a.remark as absent_reason
        FROM students s
        LEFT JOIN attendance a ON a.student_id = s.id AND a.date = ?
        WHERE s.class_id = ? ORDER BY s.parent_name", [$date, $class_id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    require_module_access('attendance');
    if (!has_role('admin')) {
        $is_ct = db_get_row("SELECT id FROM class_teachers WHERE class_id = ? AND teacher_id = ? AND role = 'class_teacher'", [$class_id, get_user_id()]);
        if (!$is_ct) {
            set_flash('error', 'Only class teachers can mark attendance.');
            redirect("index.php?tab=mark");
        }
    }
    foreach ($_POST['status'] as $student_id => $status) {
        $existing = db_get_row("SELECT id FROM attendance WHERE student_id = ? AND date = ?", [(int)$student_id, $date]);
        $reason = $_POST['absent_reason'][$student_id] ?? null;
        if ($existing) {
            db_query("UPDATE attendance SET status = ?, remark = ?, marked_by = ? WHERE id = ?", [$status, $reason ?: null, get_user_id(), $existing['id']]);
        } else {
            db_insert("INSERT INTO attendance (student_id, class_id, date, status, remark, marked_by) VALUES (?, ?, ?, ?, ?, ?)",
                [(int)$student_id, $class_id, $date, $status, $reason ?: null, get_user_id()]);
        }
    }
    set_flash('success', 'Attendance saved for ' . format_date($date));
    redirect("index.php?tab=mark&class_id=$class_id&date=$date");
}

$total_students = db_get_row("SELECT COUNT(*) as count FROM students WHERE class_id = ?", [$class_id])['count'] ?? 0;
$today_stats = null;
if ($class_id) {
    $today_stats = db_get_row("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status='leave' THEN 1 ELSE 0 END) as `leave`
        FROM attendance WHERE class_id = ? AND date = ?", [$class_id, $date]);
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <?php if (has_flash('success')): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('success') ?></div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900"><i class="fas fa-calendar-check text-teal-600 mr-2"></i>Attendance</h1>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200">
            <a href="?tab=mark<?= $class_id ? "&class_id=$class_id&date=$date" : '' ?>" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='mark'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-pen mr-2"></i>Mark Attendance
            </a>
            <a href="?tab=report<?= $class_id ? "&class_id=$class_id" : '' ?>" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='report'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-chart-bar mr-2"></i>Report &amp; Overview
            </a>
        </div>
    </div>

<?php if ($tab === 'mark'): ?>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="tab" value="mark">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $class_id == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="<?= $date ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
            </div>
            <?php if ($class_id): ?>
            <div class="flex gap-2 ml-auto">
                <a href="?tab=mark&class_id=<?= $class_id ?>&date=<?= date('Y-m-d', strtotime($date . ' -1 day')) ?>" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200"><i class="fas fa-chevron-left"></i></a>
                <a href="?tab=mark&class_id=<?= $class_id ?>&date=<?= date('Y-m-d') ?>" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200">Today</a>
                <a href="?tab=mark&class_id=<?= $class_id ?>&date=<?= date('Y-m-d', strtotime($date . ' +1 day')) ?>" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200"><i class="fas fa-chevron-right"></i></a>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($class_id && $students): ?>
    <?php if ($today_stats && $today_stats['total'] > 0): ?>
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600"><?= $today_stats['present'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Present</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-600"><?= $today_stats['absent'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Absent</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600"><?= $today_stats['late'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Late</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600"><?= $today_stats['leave'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Leave</p>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-4 font-medium text-gray-500">#</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Student</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-500 w-16">Present</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-500 w-16">Absent</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-500 w-16">Late</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-500 w-16">Leave</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Reason (if not present)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($students as $s): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-500"><?= $i++ ?></td>
                        <td class="py-3 px-4 font-medium text-gray-900"><?= sanitize($s['parent_name'] ?? 'N/A') ?></td>
                        <?php $status = $s['attendance_status'] ?? 'present'; ?>
                        <td class="py-3 px-4 text-center">
                            <input type="radio" name="status[<?= $s['id'] ?>]" value="present" <?= $status == 'present' ? 'checked' : '' ?> class="text-green-600 focus:ring-green-500" onchange="toggleReason(this, <?= $s['id'] ?>)">
                        </td>
                        <td class="py-3 px-4 text-center">
                            <input type="radio" name="status[<?= $s['id'] ?>]" value="absent" <?= $status == 'absent' ? 'checked' : '' ?> class="text-red-600 focus:ring-red-500" onchange="toggleReason(this, <?= $s['id'] ?>)">
                        </td>
                        <td class="py-3 px-4 text-center">
                            <input type="radio" name="status[<?= $s['id'] ?>]" value="late" <?= $status == 'late' ? 'checked' : '' ?> class="text-amber-600 focus:ring-amber-500" onchange="toggleReason(this, <?= $s['id'] ?>)">
                        </td>
                        <td class="py-3 px-4 text-center">
                            <input type="radio" name="status[<?= $s['id'] ?>]" value="leave" <?= $status == 'leave' ? 'checked' : '' ?> class="text-blue-600 focus:ring-blue-500" onchange="toggleReason(this, <?= $s['id'] ?>)">
                        </td>
                        <td class="py-3 px-4">
                            <input type="text" name="absent_reason[<?= $s['id'] ?>]" value="<?= sanitize($s['absent_reason'] ?? '') ?>" placeholder="Sick, traffic, etc." class="w-full border border-gray-200 rounded-lg px-2 py-1 text-xs focus:ring-2 focus:ring-teal-500 outline-none absent-reason-<?= $s['id'] ?>" <?= $status === 'present' ? 'style="opacity:0.4"' : '' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <button type="submit" name="save_attendance" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                <i class="fas fa-save mr-2"></i>Save Attendance
            </button>
        </div>
    </form>

    <?php elseif ($class_id): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-users text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No students found in this class.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-calendar-check text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">Select a class and date to mark attendance.</p>
    </div>
    <?php endif; ?>

<?php elseif ($tab === 'report'): ?>

    <?php
    $range = $_GET['range'] ?? 'month';
    $report_class_id = $class_id ?: ((int)($_GET['report_class_id'] ?? 0));
    $from_date = $_GET['from'] ?? date('Y-m-d', strtotime($range === 'week' ? '-7 days' : '-30 days'));
    $to_date = $_GET['to'] ?? date('Y-m-d');
    $student_filter = (int)($_GET['student_id'] ?? 0);

    $report_students = [];
    if ($report_class_id) {
        $where = "WHERE a.class_id = ? AND a.date BETWEEN ? AND ?";
        $params = [$report_class_id, $from_date, $to_date];
        if ($student_filter) {
            $where .= " AND a.student_id = ?";
            $params[] = $student_filter;
        }
        $report_students = db_get_all("SELECT 
            a.student_id, s.parent_name, s.enrollment_id,
            COUNT(*) as total_days,
            SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN a.status='leave' THEN 1 ELSE 0 END) as `leave`
            FROM attendance a 
            JOIN students s ON a.student_id = s.id
            $where
            GROUP BY a.student_id
            ORDER BY s.parent_name", $params);
    }

    $all_students = $report_class_id ? db_get_all("SELECT id, parent_name FROM students WHERE class_id=? ORDER BY parent_name", [$report_class_id]) : [];

    $range_presets = [
        'week' => 'Last 7 Days',
        'month' => 'Last 30 Days',
        'term' => 'This Term',
    ];

    $class_att_stats = [];
    if ($report_class_id) {
        $class_att_stats = db_get_row("SELECT 
            COUNT(DISTINCT a.student_id) as total_students,
            COUNT(*) as total_records,
            SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN a.status='leave' THEN 1 ELSE 0 END) as `leave`
            FROM attendance a WHERE a.class_id=? AND a.date BETWEEN ? AND ?",
            [$report_class_id, $from_date, $to_date]);
    }
    ?>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="tab" value="report">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="report_class_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $report_class_id == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <input type="date" name="from" value="<?= $from_date ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input type="date" name="to" value="<?= $to_date ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <?php if ($report_class_id): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                <select name="student_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">All Students</option>
                    <?php foreach ($all_students as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= $student_filter == $st['id'] ? 'selected' : '' ?>><?= sanitize($st['parent_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-teal-700 transition"><i class="fas fa-search mr-1"></i>Filter</button>
            </div>
            <div class="flex gap-1 ml-auto">
                <?php foreach ($range_presets as $k => $label): ?>
                <a href="?tab=report&report_class_id=<?= $report_class_id ?>&range=<?= $k ?>" class="px-3 py-2 rounded-lg text-xs font-medium <?= $range==$k ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>
        </form>
    </div>

    <?php if ($report_class_id && $class_att_stats && $class_att_stats['total_records'] > 0): ?>
    <?php $total_recs = max($class_att_stats['total_records'], 1); ?>
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-2xl font-bold text-gray-900"><?= $class_att_stats['total_students'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Students tracked</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-2xl font-bold text-green-600"><?= round(($class_att_stats['present'] ?? 0) / $total_recs * 100) ?>%</p>
            <p class="text-xs text-gray-500">Present rate</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-2xl font-bold text-red-600"><?= round(($class_att_stats['absent'] ?? 0) / $total_recs * 100) ?>%</p>
            <p class="text-xs text-gray-500">Absent rate</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-2xl font-bold text-amber-600"><?= round(($class_att_stats['late'] ?? 0) / $total_recs * 100) ?>%</p>
            <p class="text-xs text-gray-500">Late rate</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Student</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Days</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Present</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Absent</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Late</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Leave</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Attendance %</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_students as $r):
                    $pct = $r['total_days'] > 0 ? round($r['present'] / $r['total_days'] * 100) : 0;
                    $color = $pct >= 90 ? 'text-green-600' : ($pct >= 75 ? 'text-amber-600' : 'text-red-600');
                ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium text-gray-900"><?= sanitize($r['parent_name']) ?></td>
                    <td class="py-3 px-4 text-center text-gray-700"><?= $r['total_days'] ?></td>
                    <td class="py-3 px-4 text-center text-green-700 font-medium"><?= $r['present'] ?></td>
                    <td class="py-3 px-4 text-center text-red-700 font-medium"><?= $r['absent'] ?></td>
                    <td class="py-3 px-4 text-center text-amber-700 font-medium"><?= $r['late'] ?></td>
                    <td class="py-3 px-4 text-center text-blue-700 font-medium"><?= $r['leave'] ?></td>
                    <td class="py-3 px-4 text-center font-bold <?= $color ?>"><?= $pct ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php elseif ($report_class_id): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-chart-bar text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No attendance records in this period.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-chart-bar text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">Select a class and date range to see the report.</p>
    </div>
    <?php endif; ?>

<?php endif; ?>
</div>

<script>
function toggleReason(el, sid) {
    const input = document.querySelector('.absent-reason-' + sid);
    if (input) {
        input.style.opacity = el.value === 'present' ? '0.4' : '1';
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
