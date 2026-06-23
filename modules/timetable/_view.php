<?php
$classes = db_get_all("SELECT * FROM classes WHERE is_active=1 ORDER BY name");
$teachers = db_get_all("SELECT id, full_name FROM users WHERE role IN ('admin','teacher') AND is_active=1 ORDER BY full_name");
$subjects = db_get_all("SELECT * FROM subjects WHERE is_active=1 ORDER BY name");
$periods = db_get_all("SELECT * FROM timetable_periods WHERE is_active=1 ORDER BY sort_order");

$filter_class = (int)($_GET['class_id'] ?? 0);
$filter_teacher = (int)($_GET['teacher_id'] ?? 0);
$academic_year = $_GET['year'] ?? date('Y');

$days_to_show = [1,2,3,4,5,6,0]; // Mon-Sun

// Build entry map: day_of_week + period_id => entry
$entries = [];
if ($filter_class || $filter_teacher) {
    $where = "WHERE e.academic_year=? AND e.is_active=1";
    $params = [$academic_year];
    if ($filter_class) { $where .= " AND e.class_id=?"; $params[] = $filter_class; }
    if ($filter_teacher) { $where .= " AND e.teacher_id=?"; $params[] = $filter_teacher; }

    $rows = db_get_all("SELECT e.*, s.name as subject_name, s.code as subject_code, u.full_name as teacher_name,
        c.name as class_name
        FROM timetable_entries e
        LEFT JOIN subjects s ON e.subject_id=s.id
        LEFT JOIN users u ON e.teacher_id=u.id
        LEFT JOIN classes c ON e.class_id=c.id
        $where
        ORDER BY e.day_of_week, e.period_id", $params);

    foreach ($rows as $r) {
        $entries[$r['day_of_week'] . '-' . $r['period_id']] = $r;
    }
}

// Dispute map for teachers
$my_disputes = [];
if (!has_role('admin')) {
    $dispute_rows = db_get_all("SELECT entry_id, status FROM timetable_disputes WHERE teacher_id=? AND status='pending'", [get_user_id()]);
    foreach ($dispute_rows as $d) $my_disputes[$d['entry_id']] = $d['status'];
}
?>

<div class="mb-4">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="view">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Class</label>
            <select name="class_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                <option value="">All Classes</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filter_class==$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Teacher</label>
            <select name="teacher_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                <option value="">All Teachers</option>
                <?php foreach ($teachers as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $filter_teacher==$t['id']?'selected':'' ?>><?= sanitize($t['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Year</label>
            <select name="year" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $academic_year==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="button" onclick="window.print()" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 border border-gray-300 transition">
            <i class="fas fa-print mr-1"></i> Print
        </button>
    </form>
</div>

<?php if (!$filter_class && !$filter_teacher): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700 mb-4 flex items-center gap-2">
    <i class="fas fa-info-circle"></i> Select a class or teacher above to view the timetable.
</div>
<?php elseif (empty($periods)): ?>
<div class="text-center py-12 text-gray-400">
    <i class="fas fa-clock text-4xl mb-3 block text-gray-300"></i>
    <p class="text-sm">No periods defined yet. Ask an admin to set up periods first.</p>
</div>
<?php else: ?>
<div class="overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th class="sticky left-0 bg-white z-10 border-r border-b border-gray-200 px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider w-24 min-w-[90px]">Period</th>
                <?php foreach ($days_to_show as $d): ?>
                <th class="border-b border-gray-200 px-2 py-2 text-center text-xs font-bold <?= $d==0||$d==6?'text-red-500':'text-gray-700' ?> min-w-[120px]">
                    <?= $day_short[$d] ?>
                    <?php if ($d==0): ?><span class="text-[9px] text-red-400 block">Sun</span><?php endif; ?>
                    <?php if ($d==6): ?><span class="text-[9px] text-red-400 block">Sat</span><?php endif; ?>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($periods as $p): ?>
            <tr class="hover:bg-gray-50/50 transition">
                <td class="sticky left-0 bg-white z-10 border-r border-b border-gray-100 px-2 py-2 text-xs font-semibold text-gray-600 whitespace-nowrap">
                    <div><?= sanitize($p['name']) ?></div>
                    <div class="text-[9px] text-gray-400 font-normal"><?= date('h:i A', strtotime($p['start_time'])) ?> — <?= date('h:i A', strtotime($p['end_time'])) ?></div>
                </td>
                <?php foreach ($days_to_show as $d):
                    $key = $d . '-' . $p['id'];
                    $entry = $entries[$key] ?? null;
                ?>
                <td class="border-b border-gray-100 px-1.5 py-1.5 align-top <?= $d==0||$d==6?'bg-red-50/30':'' ?>">
                    <?php if ($entry): ?>
                    <div class="bg-white rounded-lg border border-gray-200 p-1.5 text-xs hover:shadow-sm transition min-h-[60px] <?= isset($my_disputes[$entry['id']]) ? 'border-l-2 border-l-amber-500' : '' ?>">
                        <div class="font-semibold text-gray-900 text-[11px]"><?= sanitize($entry['subject_name'] ?? '—') ?></div>
                        <div class="text-[10px] text-gray-500"><?= sanitize($entry['teacher_name'] ?? '—') ?></div>
                        <div class="text-[10px] text-gray-400"><?= sanitize($entry['class_name'] ?? '') ?><?= $entry['room'] ? ' · ' . sanitize($entry['room']) : '' ?></div>
                        <?php if (!has_role('admin')): ?>
                        <div class="mt-1">
                            <?php if (isset($my_disputes[$entry['id']])): ?>
                            <span class="text-[9px] text-amber-600 font-medium"><i class="fas fa-flag"></i> Disputed</span>
                            <?php else: ?>
                            <button type="button" onclick="document.getElementById('dispute-<?= $entry['id'] ?>').classList.remove('hidden')" class="text-[9px] text-gray-400 hover:text-amber-600 transition">
                                <i class="fas fa-flag"></i> Dispute
                            </button>
                            <div id="dispute-<?= $entry['id'] ?>" class="hidden fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)this.classList.add('hidden')">
                                <div class="bg-white rounded-xl p-4 w-full max-w-sm" onclick="event.stopPropagation()">
                                    <h4 class="font-bold text-gray-900 text-sm mb-2">Raise Dispute</h4>
                                    <p class="text-xs text-gray-500 mb-3">
                                        <span class="font-medium"><?= sanitize($entry['subject_name']) ?></span> — <?= sanitize($entry['class_name']) ?><br>
                                        <?= $day_short[$d] ?> · <?= sanitize($p['name']) ?>
                                    </p>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="raise_dispute">
                                        <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                        <textarea name="reason" required rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Why are you disputing this entry?"></textarea>
                                        <div class="flex items-center gap-2 mt-2">
                                            <button type="submit" class="bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-amber-700 transition">Submit Dispute</button>
                                            <button type="button" onclick="document.getElementById('dispute-<?= $entry['id'] ?>').classList.add('hidden')" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="min-h-[60px]"></div>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="mt-3 text-[10px] text-gray-400 flex items-center gap-4">
    <span><i class="fas fa-sun text-amber-400 mr-1"></i> Weekdays</span>
    <span><i class="fas fa-moon text-red-400 mr-1"></i> Weekend</span>
    <?php if (!has_role('admin')): ?>
    <span><i class="fas fa-flag text-amber-600 mr-1"></i> Click flag to dispute an entry</span>
    <?php endif; ?>
</div>
<?php endif; ?>
