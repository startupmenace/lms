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
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D', strtotime("-$i days"));
    $present = db_get_row("SELECT COUNT(*) as count FROM attendance WHERE date = ? AND status = 'present'", [$d])['count'] ?? 0;
    $absent = db_get_row("SELECT COUNT(*) as count FROM attendance WHERE date = ? AND status = 'absent'", [$d])['count'] ?? 0;
    $chart_present[] = $present;
    $chart_absent[] = $absent;
}

$hour = (int)date('H');
if ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else $greeting = 'Good evening';

include __DIR__ . '/../../includes/header.php';
?>

<div class="bg-gradient-to-r from-teal-600 to-teal-500 rounded-2xl p-5 sm:p-7 mb-6 sm:mb-8 text-white">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold"><?= $greeting ?>, <?= sanitize($teacher_name) ?>!</h1>
            <p class="text-teal-100 text-sm sm:text-base mt-1"><?= date('l, j F Y') ?> · Here's your school overview</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= BASE_URL ?>/modules/students/create.php" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-white/50">
                <i class="fas fa-plus-circle"></i> Add Student
            </a>
            <a href="<?= BASE_URL ?>/modules/attendance/index.php" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-white/50">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
    <div class="lg:col-span-2 space-y-4 sm:space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base sm:text-lg font-bold text-gray-900">Attendance Overview</h2>
                <a href="<?= BASE_URL ?>/modules/attendance/index.php" class="text-sm font-semibold text-teal-600 hover:text-teal-700 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-400 rounded-lg px-2 py-1">View Details</a>
            </div>
            <canvas id="attendanceChart" height="200" aria-label="Attendance chart showing present and absent counts for the past week" role="img"></canvas>
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
                <a href="<?= BASE_URL ?>/modules/students/create.php" class="flex items-center gap-3 p-3 rounded-xl bg-teal-50 hover:bg-teal-100 border border-teal-100 hover:border-teal-200 transition group focus:outline-none focus:ring-2 focus:ring-teal-400">
                    <div class="w-9 h-9 rounded-lg bg-teal-100 flex items-center justify-center group-hover:scale-110 transition"><i class="fas fa-user-plus text-teal-600"></i></div>
                    <div><p class="text-sm font-semibold text-gray-900">Add Student</p><p class="text-xs text-gray-500">New enrollment</p></div>
                </a>
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
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Present',
                data: <?= json_encode($chart_present) ?>,
                borderColor: '#0d9488',
                backgroundColor: 'rgba(13, 148, 136, 0.08)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#0d9488',
                pointRadius: 4,
                pointHoverRadius: 6
            }, {
                label: 'Absent',
                data: <?= json_encode($chart_absent) ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.08)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ef4444',
                pointRadius: 4,
                pointHoverRadius: 6
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
                x: { grid: { display: false }, ticks: { font: { size: 11, weight: 'bold' } } }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
