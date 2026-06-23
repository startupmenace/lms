<?php
$class_teacher = db_get_row("SELECT u.full_name, u.email, u.phone FROM class_teachers ct JOIN users u ON ct.teacher_id=u.id WHERE ct.class_id=? AND ct.role='class_teacher' LIMIT 1", [$class_id]);
$date = $_GET['att_date'] ?? date('Y-m-d');
$students = db_get_all("SELECT s.id, u.full_name FROM students s JOIN users u ON s.user_id=u.id WHERE s.class_id=? AND s.is_active=1 ORDER BY u.full_name", [$class_id]);

$existing = db_get_all("SELECT student_id, status, remark as absent_reason FROM attendance WHERE class_id=? AND date=?", [$class_id, $date]);
$att_map = [];
$reason_map = [];
foreach ($existing as $a) {
    $att_map[$a['student_id']] = $a['status'];
    $reason_map[$a['student_id']] = $a['absent_reason'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    if (!has_role('admin')) {
        $is_ct = db_get_row("SELECT id FROM class_teachers WHERE class_id = ? AND teacher_id = ? AND role = 'class_teacher'", [$class_id, get_user_id()]);
        if (!$is_ct) {
            set_flash('error', 'Only class teachers can mark attendance.');
            redirect("?id=$class_id&tab=attendance&att_date=$date");
        }
    }
    db_query("DELETE FROM attendance WHERE class_id=? AND date=?", [$class_id, $date]);
    $statuses = $_POST['status'] ?? [];
    $reasons = $_POST['absent_reason'] ?? [];
    foreach ($statuses as $sid => $status) {
        if (in_array($status, ['present','absent','late','excused'])) {
            $reason = $reasons[$sid] ?? null;
            db_insert("INSERT INTO attendance (class_id, student_id, date, status, remark) VALUES (?,?,?,?,?)", [$class_id, (int)$sid, $date, $status, $reason ?: null]);
        }
    }
    set_flash('success', 'Attendance saved.');
    redirect("?id=$class_id&tab=attendance&att_date=$date");
}

$stats = db_get_row("SELECT COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present, SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent, SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late, SUM(CASE WHEN status='excused' THEN 1 ELSE 0 END) as excused FROM attendance WHERE class_id=? AND date=?", [$class_id, $date]);
?>
<?php if ($class_teacher): ?>
<div class="flex items-center gap-3 mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold text-xs">
        <i class="fas fa-star"></i>
    </div>
    <div>
        <p class="text-xs font-bold text-amber-800">Class Teacher: <?= sanitize($class_teacher['full_name']) ?></p>
        <p class="text-[10px] text-amber-600"><?= sanitize($class_teacher['email']) ?><?= $class_teacher['phone'] ? ' · ' . sanitize($class_teacher['phone']) : '' ?></p>
    </div>
</div>
<?php endif; ?>

<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <form method="get" class="flex items-center gap-2">
        <input type="hidden" name="id" value="<?= $class_id ?>">
        <input type="hidden" name="tab" value="attendance">
        <input type="date" name="att_date" value="<?= $date ?>" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
    </form>
    <div class="flex gap-3 text-xs">
        <span class="text-green-600"><i class="fas fa-check-circle"></i> Present: <?= (int)($stats['present']??0) ?></span>
        <span class="text-red-600"><i class="fas fa-times-circle"></i> Absent: <?= (int)($stats['absent']??0) ?></span>
        <span class="text-amber-600"><i class="fas fa-clock"></i> Late: <?= (int)($stats['late']??0) ?></span>
        <span class="text-blue-600"><i class="fas fa-question-circle"></i> Excused: <?= (int)($stats['excused']??0) ?></span>
    </div>
</div>

<form method="post">
    <input type="hidden" name="save_attendance" value="1">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-200">
                    <th class="pb-2 pr-3 font-medium">Student</th>
                    <th class="pb-2 font-medium">Status</th>
                    <th class="pb-2 font-medium">Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                <tr><td colspan="3" class="py-8 text-center text-gray-400 text-xs">No students enrolled.</td></tr>
                <?php else: ?>
                <?php foreach ($students as $s): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                    <td class="py-2 pr-3">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-teal-400 to-coral-400 flex items-center justify-center text-white font-bold text-[10px]"><?= strtoupper(substr($s['full_name'], 0, 1)) ?></div>
                            <span class="text-xs font-medium text-gray-900"><?= sanitize($s['full_name']) ?></span>
                        </div>
                    </td>
                    <td class="py-2">
                        <div class="flex gap-1">
                            <?php $cur = $att_map[$s['id']] ?? 'present'; ?>
                            <?php foreach (['present'=>'green','absent'=>'red','late'=>'amber','excused'=>'blue'] as $val=>$color): ?>
                            <label class="flex items-center gap-1 px-2 py-1 rounded text-[10px] cursor-pointer border <?= $cur===$val ? "border-{$color}-300 bg-{$color}-50 text-{$color}-700" : 'border-gray-200 text-gray-400 hover:bg-gray-50' ?>">
                                <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $val ?>" <?= $cur===$val?'checked':'' ?> class="hidden" onchange="toggleClassReason(this, <?= $s['id'] ?>)">
                                <?= ucfirst($val) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="py-2">
                        <input type="text" name="absent_reason[<?= $s['id'] ?>]" value="<?= sanitize($reason_map[$s['id']] ?? '') ?>" placeholder="Sick, traffic..." class="w-36 border border-gray-200 rounded px-2 py-1 text-xs focus:ring-2 focus:ring-teal-500 outline-none reason-<?= $s['id'] ?>" <?= ($att_map[$s['id']] ?? 'present') === 'present' ? 'style="opacity:0.4"' : '' ?>>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($students)): ?>
    <div class="mt-4 flex justify-end">
        <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg text-xs font-medium hover:bg-teal-700 transition">Save Attendance</button>
    </div>
    <?php endif; ?>
</form>

<script>
function toggleClassReason(el, sid) {
    const inp = document.querySelector('.reason-' + sid);
    if (inp) inp.style.opacity = el.value === 'present' ? '0.4' : '1';
}
</script>
