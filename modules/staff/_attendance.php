<?php
$teachers = db_get_all("SELECT u.id, u.full_name, sd.employee_id, u.role FROM users u LEFT JOIN staff_details sd ON u.id=sd.user_id WHERE u.role IN ('admin','teacher') AND u.is_active=1 ORDER BY u.full_name");

$date = $_GET['date'] ?? date('Y-m-d');
$attendance_tab = $_GET['att_tab'] ?? 'mark';

// GET existing attendance for the selected date
$existing = [];
if ($attendance_tab === 'mark') {
    $rows = db_get_all("SELECT * FROM staff_attendance WHERE date=? ORDER BY user_id", [$date]);
    foreach ($rows as $r) $existing[$r['user_id']] = $r;
}

// Reports
$month = $_GET['month'] ?? date('Y-m');
?>
<div class="flex items-center justify-between mb-4">
    <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
        <i class="fas fa-clipboard-check text-teal-600"></i> Staff Attendance
    </h3>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
    <div class="flex border-b border-gray-200 overflow-x-auto">
        <a href="?tab=attendance&att_tab=mark" class="px-4 py-2.5 text-xs font-medium whitespace-nowrap <?= $attendance_tab=='mark'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
            <i class="fas fa-pen mr-1"></i> Mark Attendance
        </a>
        <a href="?tab=attendance&att_tab=report" class="px-4 py-2.5 text-xs font-medium whitespace-nowrap <?= $attendance_tab=='report'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
            <i class="fas fa-chart-bar mr-1"></i> Reports
        </a>
    </div>

    <div class="p-4">
        <?php if ($attendance_tab === 'mark'): ?>
        <form method="GET" class="flex items-end gap-3 mb-4">
            <input type="hidden" name="tab" value="attendance">
            <input type="hidden" name="att_tab" value="mark">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="<?= $date ?>" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="save_attendance">
            <input type="hidden" name="date" value="<?= $date ?>">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50/50">
                            <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Staff</th>
                            <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Status</th>
                            <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">Check In</th>
                            <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">Check Out</th>
                            <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden md:table-cell">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $t): 
                            $att = $existing[$t['id']] ?? null;
                        ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <input type="hidden" name="user_id[]" value="<?= $t['id'] ?>">
                            <td class="py-2 px-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-[10px] font-bold flex-shrink-0"><?= get_avatar($t['full_name']) ?></div>
                                    <div>
                                        <div class="text-xs font-medium text-gray-900"><?= sanitize($t['full_name']) ?></div>
                                        <div class="text-[9px] text-gray-400"><?= sanitize($t['employee_id'] ?: '—') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-2 px-2 text-center">
                                <select name="status[<?= $t['id'] ?>]" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                                    <?php foreach (['present','absent','late','half-day','leave'] as $s): ?>
                                    <option value="<?= $s ?>" <?= ($att['status']??'present')==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="py-2 px-2 text-center hidden sm:table-cell">
                                <input type="time" name="check_in[<?= $t['id'] ?>]" value="<?= $att['check_in'] ?? '' ?>" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none w-24">
                            </td>
                            <td class="py-2 px-2 text-center hidden sm:table-cell">
                                <input type="time" name="check_out[<?= $t['id'] ?>]" value="<?= $att['check_out'] ?? '' ?>" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none w-24">
                            </td>
                            <td class="py-2 px-2 hidden md:table-cell">
                                <input type="text" name="remarks[<?= $t['id'] ?>]" value="<?= sanitize($att['remarks'] ?? '') ?>" placeholder="Optional" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex items-center gap-2">
                <button type="submit" class="bg-teal-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-save mr-1"></i> Save Attendance
                </button>
                <button type="button" onclick="setAll('present')" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">All Present</button>
                <button type="button" onclick="setAll('absent')" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">All Absent</button>
            </div>
        </form>
        <?php else: ?>
        <!-- Reports -->
        <form method="GET" class="flex items-end gap-3 mb-4">
            <input type="hidden" name="tab" value="attendance">
            <input type="hidden" name="att_tab" value="report">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Month</label>
                <input type="month" name="month" value="<?= $month ?>" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Staff</label>
                <select name="staff_id" class="border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                    <option value="">All Staff</option>
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($_GET['staff_id']??'')==$t['id']?'selected':'' ?>><?= sanitize($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php
        $y = substr($month, 0, 4);
        $m = substr($month, 5, 2);
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$m, (int)$y);

        $staff_filter = (int)($_GET['staff_id'] ?? 0);
        $where = "WHERE MONTH(sa.date)=? AND YEAR(sa.date)=?";
        $params = [$m, $y];
        if ($staff_filter) { $where .= " AND sa.user_id=?"; $params[] = $staff_filter; }

        $report_data = db_get_all("SELECT sa.*, u.full_name, sd.employee_id
            FROM staff_attendance sa
            JOIN users u ON sa.user_id=u.id
            LEFT JOIN staff_details sd ON u.id=sd.user_id
            $where
            ORDER BY u.full_name, sa.date", $params);
        $grouped = [];
        foreach ($report_data as $r) {
            $uid = $r['user_id'];
            if (!isset($grouped[$uid])) $grouped[$uid] = ['name' => $r['full_name'], 'emp_id' => $r['employee_id'], 'records' => [], 'summary' => ['present'=>0,'absent'=>0,'late'=>0,'half-day'=>0,'leave'=>0]];
            $grouped[$uid]['records'][$r['date']] = $r;
            $grouped[$uid]['summary'][$r['status']]++;
        }
        ?>

        <?php if (empty($grouped)): ?>
        <div class="text-center py-10 text-gray-400">
            <i class="fas fa-calendar text-3xl mb-3 block text-gray-300"></i>
            <p class="text-sm">No attendance records for <?= date('F Y', strtotime($month . '-01')) ?>.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/50">
                        <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider sticky left-0 bg-gray-50 z-10">Staff</th>
                        <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                        <th class="text-center py-2 px-1 font-bold text-gray-700 uppercase text-[9px] tracking-wider min-w-[24px] <?= date('w', strtotime("$y-$m-$d"))==0?'text-red-500':'' ?>"><?= $d ?></th>
                        <?php endfor; ?>
                        <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider bg-gray-50">P</th>
                        <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider bg-gray-50">A</th>
                        <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider bg-gray-50">L</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grouped as $uid => $g): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-1.5 px-2 font-medium text-gray-900 sticky left-0 bg-white z-10">
                            <?= sanitize($g['name']) ?>
                            <span class="text-[9px] text-gray-400 block"><?= sanitize($g['emp_id'] ?? '') ?></span>
                        </td>
                        <?php for ($d = 1; $d <= $days_in_month; $d++): 
                            $ds = sprintf("%04d-%02d-%02d", $y, $m, $d);
                            $att = $g['records'][$ds] ?? null;
                            $is_weekend = date('w', strtotime($ds)) == 0;
                        ?>
                        <td class="text-center py-1.5 px-1 <?= $is_weekend?'bg-red-50':'' ?>">
                            <?php if ($att): ?>
                            <span class="inline-block w-5 h-5 rounded-full text-[9px] font-bold flex items-center justify-center mx-auto
                                <?= $att['status']=='present'?'bg-green-100 text-green-700':($att['status']=='absent'?'bg-red-100 text-red-700':($att['status']=='late'?'bg-amber-100 text-amber-700':($att['status']=='half-day'?'bg-blue-100 text-blue-700':'bg-gray-100 text-gray-600'))) ?>">
                                <?= strtoupper(substr($att['status'], 0, 1)) ?>
                            </span>
                            <?php elseif (!$is_weekend): ?>
                            <span class="text-gray-300">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endfor; ?>
                        <td class="text-center py-1.5 px-2 font-bold text-green-700 bg-green-50/50"><?= $g['summary']['present'] ?></td>
                        <td class="text-center py-1.5 px-2 font-bold text-red-700 bg-red-50/50"><?= $g['summary']['absent'] ?></td>
                        <td class="text-center py-1.5 px-2 font-bold text-amber-700 bg-amber-50/50"><?= $g['summary']['late'] + $g['summary']['half-day'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function setAll(status) {
    document.querySelectorAll('select[name^="status["]').forEach(s => s.value = status);
}
</script>
