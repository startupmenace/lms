<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('dashboard');

$page_title = 'Dashboard';

$user_id = get_user_id();
$teacher_name = get_user_name();
$is_admin = has_role('admin');

$total_students = db_get_row("SELECT COUNT(*) as count FROM students")['count'] ?? 0;
$total_classes = db_get_row("SELECT COUNT(*) as count FROM classes")['count'] ?? 0;
$today_attendance = db_get_row("SELECT COUNT(*) as present FROM attendance WHERE date = CURDATE() AND status = 'present'")['present'] ?? 0;
$total_exams = db_get_row("SELECT COUNT(*) as count FROM exams WHERE exam_date >= CURDATE()")['count'] ?? 0;

$my_tests_count = db_get_row("SELECT COUNT(*) as count FROM tests WHERE created_by = ?", [$user_id])['count'] ?? 0;
$pending_count = db_get_row("SELECT COUNT(*) as count FROM test_submissions ts JOIN tests t ON ts.test_id = t.id WHERE ts.status = 'pending' AND t.created_by = ?", [$user_id])['count'] ?? 0;
$upcoming_live = db_get_row("SELECT COUNT(*) as count FROM live_classes WHERE teacher_id = ? AND scheduled_at >= NOW()", [$user_id])['count'] ?? 0;

$recent_students = db_get_all("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id ORDER BY s.created_at DESC LIMIT 5");

if ($is_admin) {
    $fee_summary = db_get_row("SELECT
        COALESCE(SUM(total_amount),0) as total_billed,
        COALESCE(SUM(discount),0) as total_discount,
        COALESCE(SUM(paid_amount),0) as total_paid,
        COALESCE(SUM(total_amount - discount - paid_amount),0) as total_due
        FROM transactions") ?? ['total_billed'=>0,'total_discount'=>0,'total_paid'=>0,'total_due'=>0];

    $fee_summary['total_applicable'] = $fee_summary['total_billed'] - $fee_summary['total_discount'];
    $fee_summary['collection_pct'] = $fee_summary['total_applicable'] > 0
        ? round($fee_summary['total_paid'] / $fee_summary['total_applicable'] * 100) : 0;

    $fee_by_class = db_get_all("SELECT c.id, c.name,
        COALESCE(SUM(t.total_amount),0) as billed,
        COALESCE(SUM(t.discount),0) as discount,
        COALESCE(SUM(t.paid_amount),0) as paid,
        COUNT(DISTINCT t.student_id) as paying_students
        FROM classes c
        LEFT JOIN students s ON s.class_id = c.id AND s.is_active = 1
        LEFT JOIN transactions t ON t.student_id = s.id
        GROUP BY c.id, c.name ORDER BY c.name");

    $payment_status_counts = db_get_row("SELECT
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) as partial_count,
        SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count
        FROM transactions") ?? ['paid_count'=>0,'partial_count'=>0,'pending_count'=>0];

    $birthday_month = $_GET['birthday_month'] ?? date('m');
    $birthday_students = db_get_all("SELECT s.*, c.name as class_name
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE MONTH(s.date_of_birth) = ? AND s.is_active = 1
        ORDER BY DAY(s.date_of_birth)", [$birthday_month]);

    $my_classes = db_get_all("SELECT c.*,
        (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count,
        COALESCE(ROUND((SELECT COUNT(*) FROM attendance WHERE class_id = c.id AND status = 'present') * 100.0 / NULLIF((SELECT COUNT(*) FROM attendance WHERE class_id = c.id), 0), 1), 0) as attendance_rate
        FROM classes c ORDER BY c.name");
} else {
    $my_classes = db_get_all("SELECT c.*,
        (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count,
        COALESCE(ROUND((SELECT COUNT(*) FROM attendance WHERE class_id = c.id AND status = 'present') * 100.0 / NULLIF((SELECT COUNT(*) FROM attendance WHERE class_id = c.id), 0), 1), 0) as attendance_rate
        FROM classes c WHERE c.id IN (
            SELECT DISTINCT class_id FROM attendance WHERE marked_by = ?
            UNION
            SELECT DISTINCT class_id FROM tests WHERE created_by = ?
            UNION
            SELECT DISTINCT class_id FROM exams WHERE created_by = ?
            UNION
            SELECT DISTINCT class_id FROM live_classes WHERE teacher_id = ?
        ) ORDER BY c.name", [$user_id, $user_id, $user_id, $user_id]);
}

$today = date('Y-m-d');
$today_exams = db_get_all("SELECT e.*, c.name as class_name FROM exams e LEFT JOIN classes c ON e.class_id = c.id WHERE e.exam_date = ? ORDER BY e.start_time", [$today]);
$today_live = db_get_all("SELECT l.*, c.name as class_name FROM live_classes l LEFT JOIN classes c ON l.class_id = c.id WHERE DATE(l.scheduled_at) = ? AND l.teacher_id = ? ORDER BY l.scheduled_at", [$today, $user_id]);

$pending_evaluations = db_get_all("SELECT ts.*, t.title as test_title, s.parent_name as student_name, c.name as class_name FROM test_submissions ts JOIN tests t ON ts.test_id = t.id JOIN students s ON ts.student_id = s.id JOIN classes c ON s.class_id = c.id WHERE ts.status = 'pending' AND t.created_by = ? ORDER BY ts.submitted_at DESC LIMIT 8", [$user_id]);

$chart_labels = [];
$chart_present = [];
$chart_absent = [];
foreach ($my_classes as $c) {
    $chart_labels[] = $c['name'];
    $stats = db_get_row("SELECT
        COALESCE(SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END), 0) as present_count,
        COALESCE(SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END), 0) as absent_count
        FROM attendance WHERE class_id = ?", [$c['id']]);
    $chart_present[] = (int)$stats['present_count'];
    $chart_absent[] = (int)$stats['absent_count'];
}

$hour = (int)date('H');
if ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else $greeting = 'Good evening';

// Check-in status for staff attendance
$checkin_today = db_get_row("SELECT check_in, check_out FROM staff_attendance WHERE user_id=? AND date=?", [$user_id, date('Y-m-d')]);

// Compute overall grade from last 30 days
$grade_stats = db_get_row("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present
    FROM staff_attendance
    WHERE user_id=? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)", [$user_id]);

if ($grade_stats && $grade_stats['total'] > 0) {
    $rate = round($grade_stats['present'] * 100 / $grade_stats['total']);
    if ($rate >= 90) {
        $grade = ['code' => 'EE', 'label' => 'Exceeding Expectation', 'class' => 'bg-green-100 text-green-800'];
    } elseif ($rate >= 75) {
        $grade = ['code' => 'ME', 'label' => 'Meeting Expectation', 'class' => 'bg-amber-100 text-amber-800'];
    } else {
        $grade = ['code' => 'BE', 'label' => 'Below Expectation', 'class' => 'bg-red-100 text-red-800'];
    }
} else {
    $grade = null;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="bg-gradient-to-r from-teal-600 to-teal-500 rounded-2xl p-5 sm:p-7 mb-6 sm:mb-8 text-white">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold"><?= $greeting ?>, <?= sanitize($teacher_name) ?>!</h1>
            <p class="text-teal-100 text-sm sm:text-base mt-1"><?= date('l, j F Y') ?> · Here's your school overview</p>
        </div>
        <div class="flex gap-2">
            <?php if (has_role('admin')): ?>
            <a href="<?= BASE_URL ?>/modules/students/create.php" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-white/50">
                <i class="fas fa-plus-circle"></i> Add Student
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/modules/attendance/index.php" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-white/50">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5 mb-6 sm:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-teal-100 flex items-center justify-center">
                <i class="fas fa-fingerprint text-2xl text-teal-600"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Staff Attendance</h3>
                <p class="text-lg font-bold text-gray-900 mt-0.5" id="checkin-status">Loading...</p>
                <p class="text-xs text-gray-500 mt-0.5" id="checkin-times">--</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($grade): ?>
            <span class="text-sm font-bold px-3 py-1.5 rounded-full <?= $grade['class'] ?>" title="<?= sanitize($grade['label']) ?>"><?= $grade['code'] ?></span>
            <?php endif; ?>
            <button id="checkin-btn" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-teal-400 bg-gray-300 text-gray-500 cursor-not-allowed" disabled>
                <span id="checkin-btn-text">Loading...</span>
            </button>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6 sm:mb-8">
    <div class="stat-card bg-white rounded-xl p-4 sm:p-5 border border-gray-200 focus-within:ring-2 focus-within:ring-teal-400">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wide">Students</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1"><?= number_format($total_students) ?></p>
            </div>
            <div class="w-11 h-11 sm:w-12 sm:h-12 bg-teal-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-graduate text-lg sm:text-xl text-teal-600"></i>
            </div>
        </div>
        <div class="mt-2 text-xs font-medium text-green-600"><i class="fas fa-arrow-up mr-1"></i> Active enrollment</div>
    </div>
    <div class="stat-card bg-white rounded-xl p-4 sm:p-5 border border-gray-200 focus-within:ring-2 focus-within:ring-teal-400">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wide">My Tests</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1"><?= $my_tests_count ?></p>
            </div>
            <div class="w-11 h-11 sm:w-12 sm:h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-flask text-lg sm:text-xl text-amber-600"></i>
            </div>
        </div>
        <div class="mt-2 text-xs font-medium text-gray-500"><?= $pending_count ?> pending grading</div>
    </div>
    <div class="stat-card bg-white rounded-xl p-4 sm:p-5 border border-gray-200 focus-within:ring-2 focus-within:ring-teal-400">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wide">Attendance</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1"><?= number_format($today_attendance) ?></p>
            </div>
            <div class="w-11 h-11 sm:w-12 sm:h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-calendar-check text-lg sm:text-xl text-green-600"></i>
            </div>
        </div>
        <div class="mt-2 text-xs font-medium text-green-600"><i class="fas fa-check-circle mr-1"></i> Marked today</div>
    </div>
    <div class="stat-card bg-white rounded-xl p-4 sm:p-5 border border-gray-200 focus-within:ring-2 focus-within:ring-teal-400">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wide">Live Classes</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1"><?= $upcoming_live ?></p>
            </div>
            <div class="w-11 h-11 sm:w-12 sm:h-12 bg-rose-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-video text-lg sm:text-xl text-rose-600"></i>
            </div>
        </div>
        <div class="mt-2 text-xs font-medium text-rose-600"><i class="fas fa-calendar mr-1"></i> Upcoming</div>
    </div>
</div>

<?php if ($is_admin): ?>
<div class="mb-6 sm:mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base sm:text-lg font-bold text-gray-900">
            <span class="flex items-center gap-2"><i class="fas fa-coins text-teal-600"></i> Fee Overview</span>
        </h2>
        <a href="<?= BASE_URL ?>/modules/fees/index.php" class="text-sm font-semibold text-teal-600 hover:text-teal-700 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-400 rounded-lg px-2 py-1">Manage Fees</a>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Billed</p>
            <p class="text-lg sm:text-xl font-bold text-gray-900 mt-1"><?= format_currency($fee_summary['total_billed']) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Discount</p>
            <p class="text-lg sm:text-xl font-bold text-orange-600 mt-1"><?= format_currency($fee_summary['total_discount']) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Collected</p>
            <p class="text-lg sm:text-xl font-bold text-green-600 mt-1"><?= format_currency($fee_summary['total_paid']) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Due</p>
            <p class="text-lg sm:text-xl font-bold text-red-600 mt-1"><?= format_currency($fee_summary['total_due']) ?></p>
        </div>
    </div>
    <?php if ($fee_summary['total_applicable'] > 0): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-sm font-medium text-gray-700">Overall Collection Progress</span>
            <span class="text-sm font-bold <?= $fee_summary['collection_pct'] >= 100 ? 'text-green-600' : ($fee_summary['collection_pct'] >= 50 ? 'text-amber-600' : 'text-red-600') ?>"><?= $fee_summary['collection_pct'] ?>%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div class="h-full rounded-full <?= $fee_summary['collection_pct'] >= 100 ? 'bg-green-500' : ($fee_summary['collection_pct'] >= 50 ? 'bg-amber-500' : 'bg-red-500') ?>" style="width: <?= min($fee_summary['collection_pct'], 100) ?>%"></div>
        </div>
    </div>
    <?php endif; ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
            <h3 class="text-sm font-bold text-gray-900 mb-3">Fee Collection by Class</h3>
            <canvas id="feeByClassChart" height="220"></canvas>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
            <h3 class="text-sm font-bold text-gray-900 mb-3">Payment Status</h3>
            <canvas id="feeStatusPieChart" height="200"></canvas>
        </div>
    </div>
</div>

<div class="mb-6 sm:mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base sm:text-lg font-bold text-gray-900">
            <span class="flex items-center gap-2"><i class="fas fa-cake-candles text-pink-500"></i> Student Birthdays</span>
        </h2>
        <form method="GET" class="flex items-center gap-2">
            <select name="birthday_month" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= sprintf('%02d', $m) ?>" <?= $birthday_month == sprintf('%02d', $m) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>
    <?php if (empty($birthday_students)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6 text-center">
        <i class="fas fa-calendar text-3xl text-gray-300 mb-2 block"></i>
        <p class="text-gray-500 text-sm">No birthdays this month</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <?php foreach ($birthday_students as $b): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-3 text-center hover:shadow-md hover:border-pink-200 transition">
            <div class="w-10 h-10 mx-auto rounded-full bg-pink-100 flex items-center justify-center mb-2">
                <i class="fas fa-cake-candles text-pink-500"></i>
            </div>
            <p class="text-sm font-bold text-gray-900 truncate"><?= sanitize($b['parent_name'] ?? 'N/A') ?></p>
            <p class="text-xs text-gray-500"><?= sanitize($b['class_name'] ?? '') ?></p>
            <p class="text-xs font-semibold text-pink-600 mt-1"><?= date('j M', strtotime($b['date_of_birth'])) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
    <div class="lg:col-span-2 space-y-4 sm:space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base sm:text-lg font-bold text-gray-900">Attendance Overview</h2>
                <a href="<?= BASE_URL ?>/modules/attendance/index.php" class="text-sm font-semibold text-teal-600 hover:text-teal-700 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-400 rounded-lg px-2 py-1">View Details</a>
            </div>
            <canvas id="attendanceChart" height="200" aria-label="Attendance chart showing present and absent counts per class" role="img"></canvas>
        </div>

        <?php if (!empty($pending_evaluations)): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base sm:text-lg font-bold text-gray-900">
                    <span class="flex items-center gap-2">
                        Pending Evaluations
                        <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2 py-0.5 rounded-full"><?= $pending_count ?></span>
                    </span>
                </h2>
                <a href="<?= BASE_URL ?>/modules/evaluation/index.php" class="text-sm font-semibold text-teal-600 hover:text-teal-700 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-400 rounded-lg px-2 py-1">Grade Now</a>
            </div>
            <div class="space-y-2">
                <?php foreach ($pending_evaluations as $sub): ?>
                <a href="<?= BASE_URL ?>/modules/evaluation/grade.php?id=<?= $sub['id'] ?>" class="flex items-center justify-between p-3 rounded-xl hover:bg-amber-50 border border-gray-100 hover:border-amber-200 transition group focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-pen text-amber-600 text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate"><?= sanitize($sub['test_title']) ?></p>
                            <p class="text-xs text-gray-500"><?= sanitize($sub['student_name']) ?> · <?= sanitize($sub['class_name']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs text-gray-400"><?= time_ago($sub['submitted_at']) ?></span>
                        <i class="fas fa-chevron-right text-xs text-gray-300 group-hover:text-amber-500 transition"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="space-y-4 sm:space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
            <h2 class="text-base sm:text-lg font-bold text-gray-900 mb-4">Quick Actions</h2>
            <div class="space-y-2">
                <a href="<?= BASE_URL ?>/modules/attendance/index.php" class="flex items-center gap-3 p-3 rounded-xl bg-green-50 hover:bg-green-100 border border-green-100 hover:border-green-200 transition group focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="w-9 h-9 rounded-lg bg-green-100 flex items-center justify-center group-hover:scale-110 transition"><i class="fas fa-clipboard-check text-green-600"></i></div>
                    <div><p class="text-sm font-semibold text-gray-900">Mark Attendance</p><p class="text-xs text-gray-500">Today's class</p></div>
                </a>
                <a href="<?= BASE_URL ?>/modules/exams/create.php" class="flex items-center gap-3 p-3 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-100 hover:border-amber-200 transition group focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center group-hover:scale-110 transition"><i class="fas fa-calendar-plus text-amber-600"></i></div>
                    <div><p class="text-sm font-semibold text-gray-900">Schedule Exam</p><p class="text-xs text-gray-500">Plan assessment</p></div>
                </a>
                <a href="<?= BASE_URL ?>/modules/tests/create.php" class="flex items-center gap-3 p-3 rounded-xl bg-purple-50 hover:bg-purple-100 border border-purple-100 hover:border-purple-200 transition group focus:outline-none focus:ring-2 focus:ring-purple-400">
                    <div class="w-9 h-9 rounded-lg bg-purple-100 flex items-center justify-center group-hover:scale-110 transition"><i class="fas fa-flask text-purple-600"></i></div>
                    <div><p class="text-sm font-semibold text-gray-900">Create Test</p><p class="text-xs text-gray-500">MCQ &amp; subjective</p></div>
                </a>
                <a href="<?= BASE_URL ?>/modules/live-class/index.php" class="flex items-center gap-3 p-3 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-100 hover:border-rose-200 transition group focus:outline-none focus:ring-2 focus:ring-rose-400">
                    <div class="w-9 h-9 rounded-lg bg-rose-100 flex items-center justify-center group-hover:scale-110 transition"><i class="fas fa-video text-rose-600"></i></div>
                    <div><p class="text-sm font-semibold text-gray-900">Live Class</p><p class="text-xs text-gray-500">Start session</p></div>
                </a>
                <?php if (has_role('admin')): ?>
                <a href="<?= BASE_URL ?>/modules/students/create.php" class="flex items-center gap-3 p-3 rounded-xl bg-teal-50 hover:bg-teal-100 border border-teal-100 hover:border-teal-200 transition group focus:outline-none focus:ring-2 focus:ring-teal-400">
                    <div class="w-9 h-9 rounded-lg bg-teal-100 flex items-center justify-center group-hover:scale-110 transition"><i class="fas fa-user-plus text-teal-600"></i></div>
                    <div><p class="text-sm font-semibold text-gray-900">Add Student</p><p class="text-xs text-gray-500">New enrollment</p></div>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($today_exams) || !empty($today_live)): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
            <h2 class="text-base sm:text-lg font-bold text-gray-900 mb-4">
                <span class="flex items-center gap-2">
                    <i class="fas fa-calendar-day text-teal-600"></i> Today's Schedule
                </span>
            </h2>
            <div class="space-y-3">
                <?php foreach ($today_exams as $exam): ?>
                <div class="flex items-center gap-3 p-3 rounded-xl bg-amber-50 border border-amber-100">
                    <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0"><i class="fas fa-pen-clip text-amber-600"></i></div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate"><?= sanitize($exam['title']) ?></p>
                        <p class="text-xs text-gray-500"><?= sanitize($exam['class_name']) ?> · <?= date('h:i A', strtotime($exam['start_time'])) ?> - <?= date('h:i A', strtotime($exam['end_time'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php foreach ($today_live as $live): ?>
                <div class="flex items-center gap-3 p-3 rounded-xl bg-rose-50 border border-rose-100">
                    <div class="w-9 h-9 rounded-lg bg-rose-100 flex items-center justify-center flex-shrink-0"><i class="fas fa-video text-rose-600"></i></div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate"><?= sanitize($live['title']) ?></p>
                        <p class="text-xs text-gray-500"><?= sanitize($live['class_name']) ?> · <?= date('h:i A', strtotime($live['scheduled_at'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($my_classes)): ?>
<div class="mb-6 sm:mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base sm:text-lg font-bold text-gray-900">
            <span class="flex items-center gap-2">
                <i class="fas fa-school text-teal-600"></i> My Classes
            </span>
        </h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-5">
        <?php foreach ($my_classes as $class): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5 hover:shadow-md hover:border-teal-200 transition group focus-within:ring-2 focus-within:ring-teal-400">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center group-hover:scale-110 transition">
                    <i class="fas fa-users text-teal-600"></i>
                </div>
                <span class="text-xs font-semibold <?= ($class['attendance_rate'] ?? 0) >= 80 ? 'text-green-600 bg-green-50' : 'text-amber-600 bg-amber-50' ?> px-2.5 py-1 rounded-full"><?= ($class['attendance_rate'] ?? 0) ?>% attendance</span>
            </div>
            <h3 class="text-base font-bold text-gray-900"><?= sanitize($class['name']) ?></h3>
            <?php if (!empty($class['section'])): ?>
            <p class="text-xs text-gray-500">Section <?= sanitize($class['section']) ?></p>
            <?php endif; ?>
            <p class="text-sm text-gray-600 mt-2"><span class="font-bold text-gray-900"><?= $class['student_count'] ?? 0 ?></span> students</p>
            <div class="mt-3 flex gap-2">
                <a href="<?= BASE_URL ?>/modules/attendance/index.php?class_id=<?= $class['id'] ?>" class="text-xs font-semibold text-teal-600 hover:text-teal-700 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-400 rounded-lg px-2 py-1" aria-label="Mark attendance for <?= sanitize($class['name']) ?>">Attendance</a>
                <a href="<?= BASE_URL ?>/modules/tests/create.php?class_id=<?= $class['id'] ?>" class="text-xs font-semibold text-purple-600 hover:text-purple-700 hover:underline focus:outline-none focus:ring-2 focus:ring-purple-400 rounded-lg px-2 py-1" aria-label="Create test for <?= sanitize($class['name']) ?>">Create Test</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base sm:text-lg font-bold text-gray-900">
            <span class="flex items-center gap-2">
                <i class="fas fa-user-graduate text-teal-600"></i> Recent Students
            </span>
        </h2>
        <a href="<?= BASE_URL ?>/modules/students/index.php" class="text-sm font-semibold text-teal-600 hover:text-teal-700 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-400 rounded-lg px-2 py-1 whitespace-nowrap">View All</a>
    </div>
    <div class="overflow-x-auto -mx-4 sm:-mx-0">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b-2 border-gray-200">
                    <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Enrollment</th>
                    <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Student Name</th>
                    <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Class</th>
                    <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden sm:table-cell">Parent</th>
                    <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden md:table-cell">Phone</th>
                    <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden md:table-cell">Admission</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_students)): ?>
                <tr><td colspan="6" class="py-10 text-center text-gray-400 text-base">No students enrolled yet</td></tr>
                <?php else: ?>
                <?php foreach ($recent_students as $s): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="py-3.5 px-4 text-gray-900 font-medium text-xs sm:text-sm"><?= sanitize($s['enrollment_id']) ?></td>
                    <td class="py-3.5 px-4">
                        <a href="<?= BASE_URL ?>/modules/students/view.php?id=<?= $s['id'] ?>" class="text-teal-600 hover:text-teal-700 font-semibold hover:underline focus:outline-none focus:ring-2 focus:ring-teal-400 rounded-lg"><?= sanitize($s['parent_name'] ?? 'N/A') ?></a>
                    </td>
                    <td class="py-3.5 px-4 text-gray-800 font-medium"><?= sanitize($s['class_name'] ?? 'N/A') ?></td>
                    <td class="py-3.5 px-4 text-gray-700 hidden sm:table-cell"><?= sanitize($s['parent_name'] ?? 'N/A') ?></td>
                    <td class="py-3.5 px-4 text-gray-700 hidden md:table-cell"><?= sanitize($s['parent_phone'] ?? 'N/A') ?></td>
                    <td class="py-3.5 px-4 text-gray-700 hidden md:table-cell"><?= $s['admission_date'] ? format_date($s['admission_date']) : 'N/A' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('attendanceChart');
    if (!ctx) return;
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Present',
                data: <?= json_encode($chart_present) ?>,
                backgroundColor: 'rgba(13, 148, 136, 0.7)',
                borderColor: '#0d9488',
                borderWidth: 1,
                borderRadius: 4
            }, {
                label: 'Absent',
                data: <?= json_encode($chart_absent) ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderColor: '#ef4444',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: true, position: 'top', labels: { usePointStyle: true, padding: 16, font: { size: 12, weight: 'bold' } } }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 } } },
                x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' }, maxRotation: 45 } }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });
});
</script>

<?php if ($is_admin): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var feeCtx = document.getElementById('feeByClassChart');
    if (feeCtx) {
        var classes = <?= json_encode(array_column($fee_by_class, 'name')) ?>;
        var billed = <?= json_encode(array_map(function($c){return (float)$c['billed'];}, $fee_by_class)) ?>;
        var paid = <?= json_encode(array_map(function($c){return (float)$c['paid'];}, $fee_by_class)) ?>;
        new Chart(feeCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: classes,
                datasets: [{
                    label: 'Billed',
                    data: billed,
                    backgroundColor: 'rgba(13, 148, 136, 0.6)',
                    borderColor: '#0d9488',
                    borderWidth: 1,
                    borderRadius: 4
                }, {
                    label: 'Collected',
                    data: paid,
                    backgroundColor: 'rgba(34, 197, 94, 0.6)',
                    borderColor: '#22c55e',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: true, position: 'top', labels: { usePointStyle: true, padding: 12, font: { size: 11, weight: 'bold' } } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' } } }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    }
    var pieCtx = document.getElementById('feeStatusPieChart');
    if (pieCtx) {
        new Chart(pieCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Partial', 'Pending'],
                datasets: [{
                    data: [<?= (int)$payment_status_counts['paid_count'] ?>, <?= (int)$payment_status_counts['partial_count'] ?>, <?= (int)$payment_status_counts['pending_count'] ?>],
                    backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 10, font: { size: 11, weight: 'bold' } } }
                },
                cutout: '60%'
            }
        });
    }
});
</script>
<?php endif; ?>
<script>
(function() {
    var btn = document.getElementById('checkin-btn');
    var statusEl = document.getElementById('checkin-status');
    var timesEl = document.getElementById('checkin-times');
    var btnText = document.getElementById('checkin-btn-text');
    var ajaxUrl = '<?= BASE_URL ?>/modules/dashboard/ajax-checkin.php';

    function formatTime(t) {
        if (!t) return '--';
        var parts = t.split(':');
        var d = new Date();
        d.setHours(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2] || 0));
        return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    function updateUI(status, checkIn, checkOut) {
        if (status === 'checked_in') {
            statusEl.textContent = 'Checked In';
            statusEl.className = 'text-lg font-bold text-green-700 mt-0.5';
            timesEl.textContent = 'In: ' + formatTime(checkIn);
            btn.innerHTML = '<span>Check Out</span>';
            btn.className = 'inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-teal-400 bg-orange-500 text-white hover:bg-orange-600';
            btn.dataset.action = 'checkout';
            btn.disabled = false;
        } else if (status === 'checked_out') {
            statusEl.textContent = 'Checked Out';
            statusEl.className = 'text-lg font-bold text-gray-500 mt-0.5';
            timesEl.textContent = 'In: ' + formatTime(checkIn) + ' \u00b7 Out: ' + formatTime(checkOut);
            btn.innerHTML = '<span>Completed</span>';
            btn.className = 'inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-teal-400 bg-gray-300 text-gray-500 cursor-not-allowed';
            btn.disabled = true;
        } else {
            statusEl.textContent = 'Not Checked In';
            statusEl.className = 'text-lg font-bold text-gray-400 mt-0.5';
            timesEl.textContent = 'Start your day';
            btn.innerHTML = '<span>Check In</span>';
            btn.className = 'inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-teal-400 bg-teal-600 text-white hover:bg-teal-700';
            btn.dataset.action = 'checkin';
            btn.disabled = false;
        }
    }

    function fetchStatus() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.status) updateUI(res.status, res.check_in, res.check_out);
            } catch(e) { statusEl.textContent = 'Error loading'; }
        };
        xhr.onerror = function() { statusEl.textContent = 'Offline'; };
        xhr.send('action=status');
    }

    btn.addEventListener('click', function() {
        if (btn.disabled) return;
        var action = btn.dataset.action || 'checkin';
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.ok) {
                    fetchStatus();
                } else {
                    alert(res.msg);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch(e) {
                alert('Server error. Please try again.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        };
        xhr.onerror = function() {
            alert('Network error. Please try again.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        };
        xhr.send('action=' + action);
    });

    fetchStatus();
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
