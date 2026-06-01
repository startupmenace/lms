<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('live-class');

$page_title = 'Live Class Attendance Report';

$live_id = (int)($_GET['id'] ?? 0);
$tab = $_GET['tab'] ?? 'overview';

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">

    <?php if ($live_id > 0): ?>
    <?php
    $live = db_get_row("SELECT lc.*, u.full_name as teacher_name, c.name as class_name FROM live_classes lc LEFT JOIN users u ON lc.teacher_id = u.id LEFT JOIN classes c ON lc.class_id = c.id WHERE lc.id = ?", [$live_id]);
    if (!$live) { echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">Live class not found.</div>'; include __DIR__ . '/../../includes/footer.php'; exit; }

    $attendance = db_get_all("SELECT lca.*, u.full_name, u.email, u.role FROM live_class_attendance lca LEFT JOIN users u ON lca.user_id = u.id WHERE lca.live_class_id = ? ORDER BY lca.joined_at ASC", [$live_id]);
    $total_joined = count($attendance);
    $currently_active = count(array_filter($attendance, function($a) { return $a['status'] === 'active'; }));
    $avg_duration = $total_joined > 0 ? round(array_sum(array_column($attendance, 'duration_seconds')) / $total_joined) : 0;

    $students_in_class = 0;
    if ($live['class_id']) {
        $students_in_class = db_get_row("SELECT COUNT(*) as count FROM students WHERE class_id = ?", [$live['class_id']])['count'] ?? 0;
    }
    $attendance_rate = $students_in_class > 0 ? round($total_joined / $students_in_class * 100, 1) : 0;
    ?>
    <div class="mb-6">
        <a href="attendance-report.php" class="text-sm text-teal-600 hover:underline mb-3 inline-block">&larr; All Sessions</a>
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 rounded-2xl p-5 sm:p-7 text-white">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold"><?= sanitize($live['title']) ?></h1>
                    <p class="text-teal-100 text-sm mt-1">
                        <?= sanitize($live['teacher_name']) ?> &middot; <?= sanitize($live['class_name'] ?? 'All Classes') ?> &middot; <?= format_date($live['scheduled_at'], 'd M Y h:i A') ?>
                    </p>
                </div>
                <span class="text-xs bg-white/20 rounded-full px-3 py-1.5 font-semibold inline-flex items-center gap-1.5 self-start">
                    <span class="w-2 h-2 rounded-full <?= $live['status'] == 'live' ? 'bg-red-400 animate-pulse' : 'bg-green-400' ?>"></span> <?= ucfirst($live['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-5 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Joined</p>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1"><?= $total_joined ?></p>
            <p class="text-xs text-gray-400 mt-1">participants</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Still Active</p>
            <p class="text-2xl sm:text-3xl font-bold text-amber-600 mt-1"><?= $currently_active ?></p>
            <p class="text-xs text-gray-400 mt-1">in session</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Avg. Duration</p>
            <p class="text-2xl sm:text-3xl font-bold text-teal-600 mt-1"><?= $avg_duration > 0 ? gmdate('H:i', $avg_duration) : '—' ?></p>
            <p class="text-xs text-gray-400 mt-1">hh:mm per person</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Attendance Rate</p>
            <p class="text-2xl sm:text-3xl font-bold <?= $attendance_rate >= 80 ? 'text-green-600' : ($attendance_rate >= 50 ? 'text-amber-600' : 'text-red-600') ?> mt-1"><?= $attendance_rate ?>%</p>
            <p class="text-xs text-gray-400 mt-1"><?= $total_joined ?>/<?= $students_in_class ?> enrolled</p>
        </div>
    </div>

    <?php if ($total_joined > 0): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base sm:text-lg font-bold text-gray-900">
                <span class="flex items-center gap-2">
                    <i class="fas fa-users text-teal-600"></i> Attendance Details
                </span>
            </h2>
            <button onclick="exportTable()" class="text-xs font-semibold text-teal-600 hover:text-teal-700 border border-teal-200 px-3 py-1.5 rounded-lg hover:bg-teal-50 transition focus:outline-none focus:ring-2 focus:ring-teal-400">
                <i class="fas fa-download mr-1"></i> Export CSV
            </button>
        </div>
        <div class="overflow-x-auto -mx-4 sm:-mx-0">
            <table id="attendance-table" class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200 bg-gray-50/50">
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">#</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Name</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden sm:table-cell">Email</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Role</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Joined At</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden md:table-cell">Left At</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Duration</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $i => $a):
                        $duration_str = '—';
                        if ($a['duration_seconds'] > 0) {
                            $h = floor($a['duration_seconds'] / 3600);
                            $m = floor(($a['duration_seconds'] % 3600) / 60);
                            $s = $a['duration_seconds'] % 60;
                            $duration_str = ($h > 0 ? $h . 'h ' : '') . $m . 'm ' . $s . 's';
                        } elseif ($a['status'] === 'active') {
                            $elapsed = time() - strtotime($a['joined_at']);
                            $h = floor($elapsed / 3600);
                            $m = floor(($elapsed % 3600) / 60);
                            $duration_str = ($h > 0 ? $h . 'h ' : '') . $m . 'm (live)';
                        }
                    ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-gray-500 font-medium"><?= $i + 1 ?></td>
                        <td class="py-3 px-4 font-semibold text-gray-900"><?= sanitize($a['full_name'] ?? 'Unknown') ?></td>
                        <td class="py-3 px-4 text-gray-600 hidden sm:table-cell"><?= sanitize($a['email'] ?? '—') ?></td>
                        <td class="py-3 px-4">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $a['role'] == 'teacher' ? 'bg-purple-100 text-purple-700' : ($a['role'] == 'admin' ? 'bg-teal-100 text-teal-700' : 'bg-blue-100 text-blue-700') ?>">
                                <?= ucfirst($a['role'] ?? 'student') ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-700"><?= date('h:i:s A', strtotime($a['joined_at'])) ?></td>
                        <td class="py-3 px-4 text-gray-500 hidden md:table-cell"><?= $a['left_at'] ? date('h:i:s A', strtotime($a['left_at'])) : '—' ?></td>
                        <td class="py-3 px-4 font-medium <?= $a['status'] === 'active' ? 'text-amber-600' : 'text-gray-700' ?>"><?= $duration_str ?></td>
                        <td class="py-3 px-4 text-center">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full <?= $a['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                <?= $a['status'] === 'active' ? 'Live' : 'Left' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function exportTable() {
        var table = document.getElementById('attendance-table');
        if (!table) return;
        var rows = table.querySelectorAll('tr');
        var csv = [];
        rows.forEach(function(row) {
            var cols = row.querySelectorAll('th, td');
            var rowData = [];
            cols.forEach(function(col) {
                var text = col.textContent.trim().replace(/,/g, ' ').replace(/\s+/g, ' ');
                rowData.push('"' + text + '"');
            });
            csv.push(rowData.join(','));
        });
        var blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'attendance-<?= $live_id ?>.csv';
        a.click();
    }
    </script>

    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-user-clock text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500 text-base">No attendance records for this session yet.</p>
        <p class="text-gray-400 text-sm mt-2">Attendance is recorded automatically when users join the live class room.</p>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <?php
    $sessions = db_get_all("SELECT lc.*, u.full_name as teacher_name, c.name as class_name,
        (SELECT COUNT(*) FROM live_class_attendance WHERE live_class_id = lc.id) as total_attended,
        (SELECT COUNT(*) FROM live_class_attendance WHERE live_class_id = lc.id AND status = 'active') as still_active
        FROM live_classes lc
        LEFT JOIN users u ON lc.teacher_id = u.id
        LEFT JOIN classes c ON lc.class_id = c.id
        ORDER BY lc.scheduled_at DESC");
    ?>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Live Class Attendance Reports</h1>
            <p class="text-gray-500 text-sm mt-1">View detailed attendance for each live session</p>
        </div>
    </div>

    <?php if (empty($sessions)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fas fa-video text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No live classes have been scheduled yet.</p>
        <a href="schedule.php" class="inline-flex items-center gap-2 mt-4 bg-teal-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">Schedule a Live Class</a>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto -mx-4 sm:-mx-0">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200 bg-gray-50/50">
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Session</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden sm:table-cell">Teacher</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden md:table-cell">Class</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Date</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Attended</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Active</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $s): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-4">
                            <a href="attendance-report.php?id=<?= $s['id'] ?>" class="font-semibold text-gray-900 hover:text-teal-600"><?= sanitize($s['title']) ?></a>
                        </td>
                        <td class="py-3 px-4 text-gray-700 hidden sm:table-cell"><?= sanitize($s['teacher_name']) ?></td>
                        <td class="py-3 px-4 text-gray-700 hidden md:table-cell"><?= sanitize($s['class_name'] ?? 'All') ?></td>
                        <td class="py-3 px-4 text-gray-700"><?= format_date($s['scheduled_at'], 'd M Y') ?></td>
                        <td class="py-3 px-4 text-center">
                            <span class="font-bold text-gray-900"><?= $s['total_attended'] ?></span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <?php if ($s['still_active'] > 0): ?>
                            <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full"><?= $s['still_active'] ?> live</span>
                            <?php else: ?>
                            <span class="text-xs text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="attendance-report.php?id=<?= $s['id'] ?>" class="inline-flex items-center gap-1.5 text-teal-600 hover:text-teal-700 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-teal-400 rounded-lg px-2 py-1">
                                View Report <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
