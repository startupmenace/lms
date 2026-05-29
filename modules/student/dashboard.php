<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('student');

$user_id = get_user_id();
$student = db_get_row("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.user_id = ? OR s.id = ?", [$user_id, $user_id]);

if (!$student) {
    $student = db_get_row("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id ORDER BY s.id LIMIT 1");
}

$page_title = 'Student Dashboard';

$student_id = $student['id'] ?? 0;
$attendance_stats = db_get_row("SELECT COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present FROM attendance WHERE student_id = ?", [$student_id]);
$total_days = $attendance_stats['total'] ?? 0;
$present_days = $attendance_stats['present'] ?? 0;
$attendance_pct = $total_days > 0 ? round(($present_days / $total_days) * 100, 1) : 0;

$upcoming_tests = db_get_all("SELECT t.*, s.name as subject_name FROM tests t LEFT JOIN subjects s ON t.subject_id = s.id WHERE t.class_id = ? AND t.is_active = 1 ORDER BY t.created_at DESC LIMIT 5", [$student['class_id'] ?? 0]);
$recent_submissions = db_get_all("SELECT ts.*, t.title as test_title, t.total_marks FROM test_submissions ts LEFT JOIN tests t ON ts.test_id = t.id WHERE ts.student_id = ? ORDER BY ts.submitted_at DESC LIMIT 5", [$student_id]);
$notices = db_get_all("SELECT * FROM notices WHERE (target_audience IN ('all','student') OR class_id = ?) AND is_active = 1 ORDER BY created_at DESC LIMIT 4", [$student['class_id'] ?? 0]);
$fees = db_get_all("SELECT t.*, fs.name as structure_name FROM transactions t LEFT JOIN fee_structures fs ON t.fee_structure_id = fs.id WHERE t.student_id = ? ORDER BY t.created_at DESC LIMIT 4", [$student_id]);

include __DIR__ . '/../../includes/student-header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card bg-white rounded-xl p-6 border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Attendance</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= $attendance_pct ?>%</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-calendar-check text-xl text-green-600"></i>
            </div>
        </div>
        <div class="mt-3 text-xs text-gray-500"><?= $present_days ?>/<?= $total_days ?> days present</div>
    </div>
    <div class="stat-card bg-white rounded-xl p-6 border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Tests Taken</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= count($recent_submissions) ?></p>
            </div>
            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-pen-clip text-xl text-amber-600"></i>
            </div>
        </div>
        <div class="mt-3 text-xs text-gray-500">Last 5 submissions shown</div>
    </div>
    <div class="stat-card bg-white rounded-xl p-6 border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Upcoming Tests</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= count($upcoming_tests) ?></p>
            </div>
            <div class="w-12 h-12 bg-coral-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-flask text-xl text-coral-600"></i>
            </div>
        </div>
    </div>
    <div class="stat-card bg-white rounded-xl p-6 border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Class</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= sanitize($student['class_name'] ?? 'N/A') ?></p>
            </div>
            <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-school text-xl text-teal-600"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900"><i class="fas fa-flask text-amber-600 mr-2"></i>Upcoming Tests</h3>
            <a href="tests.php" class="text-sm text-teal-600 hover:underline">View All</a>
        </div>
        <?php if (empty($upcoming_tests)): ?>
        <p class="text-sm text-gray-400 text-center py-6">No upcoming tests</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($upcoming_tests as $t): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-gray-900"><?= sanitize($t['title']) ?></p>
                    <p class="text-xs text-gray-500"><?= sanitize($t['subject_name'] ?? 'All Subjects') ?> | <?= $t['total_marks'] ?> marks | <?= $t['duration_minutes'] ?> min</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full <?= $t['difficulty'] == 'easy' ? 'bg-green-100 text-green-700' : ($t['difficulty'] == 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= ucfirst($t['difficulty']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900"><i class="fas fa-check-double text-green-600 mr-2"></i>Recent Scores</h3>
            <a href="tests.php" class="text-sm text-teal-600 hover:underline">View All</a>
        </div>
        <?php if (empty($recent_submissions)): ?>
        <p class="text-sm text-gray-400 text-center py-6">No results yet</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($recent_submissions as $sub): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-gray-900"><?= sanitize($sub['test_title']) ?></p>
                    <p class="text-xs text-gray-500"><?= format_date($sub['submitted_at'], 'd M Y') ?> | <?= $sub['total_marks_obtained'] !== null ? $sub['total_marks_obtained'] . '/' . $sub['total_marks'] : 'Pending' ?></p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full <?= $sub['status'] == 'evaluated' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>"><?= ucfirst($sub['status']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900"><i class="fas fa-bullhorn text-teal-600 mr-2"></i>Notices</h3>
            <a href="notices.php" class="text-sm text-teal-600 hover:underline">View All</a>
        </div>
        <?php if (empty($notices)): ?>
        <p class="text-sm text-gray-400 text-center py-6">No notices</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($notices as $n): ?>
            <div class="p-3 bg-amber-50 rounded-lg border border-amber-100">
                <p class="text-sm font-medium text-gray-900"><?= sanitize($n['title']) ?></p>
                <p class="text-xs text-gray-500 mt-1"><?= time_ago($n['created_at']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900"><i class="fas fa-wallet text-coral-600 mr-2"></i>Fee Status</h3>
            <a href="fees.php" class="text-sm text-teal-600 hover:underline">View All</a>
        </div>
        <?php
        $total_fee_billed = array_sum(array_column($fees, 'total_amount'));
        $total_fee_paid = array_sum(array_column($fees, 'paid_amount'));
        $fee_pct = $total_fee_billed > 0 ? round($total_fee_paid / $total_fee_billed * 100) : 0;
        ?>
        <?php if (empty($fees)): ?>
        <p class="text-sm text-gray-400 text-center py-6">No fee records</p>
        <?php else: ?>
        <?php if ($total_fee_billed > 0): ?>
        <div class="mb-4 p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between text-sm mb-1.5">
                <span class="text-gray-600 font-medium">Overall Payment</span>
                <span class="font-bold <?= $fee_pct >= 100 ? 'text-green-600' : ($fee_pct >= 50 ? 'text-amber-600' : 'text-red-600') ?>"><?= $fee_pct ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                <div class="h-full rounded-full <?= $fee_pct >= 100 ? 'bg-green-500' : ($fee_pct >= 50 ? 'bg-amber-500' : 'bg-red-500') ?>" style="width: <?= min($fee_pct, 100) ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
        <div class="space-y-2">
            <?php foreach ($fees as $fee):
                $pct = $fee['total_amount'] > 0 ? round($fee['paid_amount'] / $fee['total_amount'] * 100) : 0;
                $bar_color = $pct >= 100 ? 'bg-green-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-red-500');
            ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 truncate"><?= sanitize($fee['structure_name'] ?? 'Fee') ?></p>
                    <p class="text-xs text-gray-500">#<?= sanitize($fee['invoice_no']) ?></p>
                </div>
                <div class="flex items-center gap-3 ml-3">
                    <div class="w-16 bg-gray-200 rounded-full h-2 hidden sm:block">
                        <div class="h-full rounded-full <?= $bar_color ?>" style="width: <?= min($pct, 100) ?>%"></div>
                    </div>
                    <span class="text-xs font-bold <?= $pct >= 100 ? 'text-green-600' : ($pct >= 50 ? 'text-amber-600' : 'text-red-600') ?>"><?= $pct ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/student-footer.php'; ?>
