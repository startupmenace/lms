<?php
$classes = db_get_all("SELECT * FROM classes WHERE is_active=1 ORDER BY name");
$teachers = db_get_all("SELECT id, full_name FROM users WHERE role IN ('admin','teacher') AND is_active=1 ORDER BY full_name");
$subjects = db_get_all("SELECT * FROM subjects WHERE is_active=1 ORDER BY name");
$periods = db_get_all("SELECT * FROM timetable_periods WHERE is_active=1 ORDER BY sort_order");
$day_names = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$day_short = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

$academic_year = $_GET['year'] ?? date('Y');

$entries = db_get_all("SELECT e.*, s.name as subject_name, u.full_name as teacher_name, c.name as class_name
    FROM timetable_entries e
    LEFT JOIN subjects s ON e.subject_id=s.id
    LEFT JOIN users u ON e.teacher_id=u.id
    LEFT JOIN classes c ON e.class_id=c.id
    WHERE e.academic_year=?
    ORDER BY e.day_of_week, e.period_id", [$academic_year]);

// Group by day for easier display
$entries_by_day = [];
foreach ($entries as $e) {
    $entries_by_day[$e['day_of_week']][] = $e;
}

// Editing
$edit_entry = null;
if (isset($_GET['edit_entry']) && isset($_GET['id'])) {
    $edit_entry = db_get_row("SELECT e.*, s.name as subject_name, u.full_name as teacher_name, c.name as class_name
        FROM timetable_entries e
        LEFT JOIN subjects s ON e.subject_id=s.id
        LEFT JOIN users u ON e.teacher_id=u.id
        LEFT JOIN classes c ON e.class_id=c.id
        WHERE e.id=?", [(int)$_GET['id']]);
}
?>

<div class="mb-4">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="entries">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Academic Year</label>
            <select name="year" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $academic_year==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="button" onclick="document.getElementById('add-entry-form').classList.toggle('hidden')" class="bg-teal-600 text-white px-3 py-2 rounded-lg text-xs font-medium hover:bg-teal-700 transition">
            <i class="fas fa-plus mr-1"></i> Add Entry
        </button>
    </form>
</div>

<!-- Add / Edit Form -->
<div id="add-entry-form" class="<?= $edit_entry ? '' : 'hidden' ?> bg-gray-50 rounded-xl p-4 border border-gray-200 mb-4">
    <h4 class="font-bold text-gray-900 text-sm mb-3 flex items-center gap-2">
        <i class="fas fa-<?= $edit_entry ? 'edit' : 'plus' ?> text-teal-600"></i>
        <?= $edit_entry ? 'Edit Entry' : 'New Timetable Entry' ?>
    </h4>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $edit_entry ? 'edit_entry' : 'add_entry' ?>">
        <?php if ($edit_entry): ?><input type="hidden" name="id" value="<?= $edit_entry['id'] ?>"><?php endif; ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Day *</label>
                <select name="day_of_week" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <?php foreach ($day_names as $i => $dn): ?>
                    <option value="<?= $i ?>" <?= ($edit_entry['day_of_week']??'')==$i?'selected':'' ?>><?= $dn ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Period *</label>
                <select name="period_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <?php foreach ($periods as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($edit_entry['period_id']??'')==$p['id']?'selected':'' ?>><?= sanitize($p['name']) ?> (<?= date('h:i A', strtotime($p['start_time'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Class *</label>
                <select name="class_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($edit_entry['class_id']??'')==$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Subject</label>
                <select name="subject_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">— None —</option>
                    <?php foreach ($subjects as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($edit_entry['subject_id']??'')==$s['id']?'selected':'' ?>><?= sanitize($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Teacher</label>
                <select name="teacher_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">— None —</option>
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($edit_entry['teacher_id']??'')==$t['id']?'selected':'' ?>><?= sanitize($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Room</label>
                <input type="text" name="room" value="<?= sanitize($edit_entry['room'] ?? '') ?>" placeholder="e.g. Room 12" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Year</label>
                <input type="text" name="academic_year" value="<?= $edit_entry['academic_year'] ?? $academic_year ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Active</label>
                <select name="is_active" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="1" <?= ($edit_entry['is_active']??1)?'selected':'' ?>>Yes</option>
                    <option value="0" <?= !($edit_entry['is_active']??1)?'selected':'' ?>>No</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-xs font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-check mr-1"></i> <?= $edit_entry ? 'Update' : 'Save' ?>
                </button>
                <?php if ($edit_entry): ?>
                <a href="?tab=entries" class="px-4 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition">Cancel</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Entries List -->
<?php if (empty($entries)): ?>
<div class="text-center py-10 text-gray-400">
    <i class="fas fa-table text-3xl mb-3 block text-gray-300"></i>
    <p class="text-sm">No timetable entries for <?= $academic_year ?>. Click "Add Entry" to start building.</p>
</div>
<?php else: ?>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/50">
                <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Day</th>
                <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Period</th>
                <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Class</th>
                <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">Subject</th>
                <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden sm:table-cell">Teacher</th>
                <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider hidden md:table-cell">Room</th>
                <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Active</th>
                <th class="text-right py-2 px-2 font-bold text-gray-700 uppercase text-[10px] tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $e): ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="py-2 px-2 font-medium text-gray-900 text-xs <?= $e['day_of_week']==0||$e['day_of_week']==6?'text-red-500':'' ?>"><?= $day_short[$e['day_of_week']] ?></td>
                <td class="py-2 px-2 text-gray-600 text-xs"><?= sanitize($e['subject_name'] ?? '—') ?></td>
                <td class="py-2 px-2 text-gray-600 text-xs"><?= sanitize($e['class_name'] ?? '—') ?></td>
                <td class="py-2 px-2 text-gray-600 text-xs hidden sm:table-cell"><?= sanitize($e['subject_name'] ?? '—') ?></td>
                <td class="py-2 px-2 text-gray-600 text-xs hidden sm:table-cell"><?= sanitize($e['teacher_name'] ?? '—') ?></td>
                <td class="py-2 px-2 text-center text-xs text-gray-600 hidden md:table-cell"><?= sanitize($e['room'] ?? '—') ?></td>
                <td class="py-2 px-2 text-center">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $e['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>"><?= $e['is_active'] ? 'Yes' : 'No' ?></span>
                </td>
                <td class="py-2 px-2 text-right">
                    <a href="?tab=entries&edit_entry&id=<?= $e['id'] ?>" class="text-amber-600 hover:text-amber-800 text-xs mr-1.5"><i class="fas fa-edit"></i></a>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this entry?')">
                        <input type="hidden" name="action" value="delete_entry">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <button class="text-red-600 hover:text-red-800 text-xs"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
