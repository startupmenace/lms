<?php
$teachers = db_get_all("SELECT ct.*, u.full_name, u.email
    FROM class_teachers ct JOIN users u ON ct.teacher_id=u.id
    WHERE ct.class_id=? ORDER BY FIELD(ct.role,'class_teacher','teacher')", [$class_id]);
$all_teachers = db_get_all("SELECT id, full_name, email FROM users WHERE role='teacher' AND is_active=1 ORDER BY full_name");

$class_teacher = db_get_row("SELECT u.full_name, u.email FROM class_teachers ct JOIN users u ON ct.teacher_id=u.id WHERE ct.class_id=? AND ct.role='class_teacher' LIMIT 1", [$class_id]);
?>
<div class="flex items-center justify-between mb-4">
    <div>
        <p class="text-sm text-gray-500"><?= count($teachers) ?> assigned teacher(s)</p>
        <?php if ($class_teacher): ?>
        <p class="text-xs text-teal-600 mt-1">
            <i class="fas fa-star text-[10px]"></i> Class Teacher: <?= sanitize($class_teacher['full_name']) ?> (<?= sanitize($class_teacher['email']) ?>)
        </p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($all_teachers)): ?>
<form method="post" class="flex items-end gap-3 mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
    <input type="hidden" name="action" value="assign_teacher">
    <input type="hidden" name="class_id" value="<?= $class_id ?>">
    <div>
        <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">Teacher</label>
        <select name="teacher_id" required class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs" style="min-width:180px">
            <option value="">— Select —</option>
            <?php foreach ($all_teachers as $t): ?>
            <option value="<?= $t['id'] ?>"><?= sanitize($t['full_name']) ?> (<?= sanitize($t['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">Subject (optional)</label>
        <select name="subject_id" class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
            <option value="">All subjects</option>
            <?php $subjects = db_get_all("SELECT * FROM subjects WHERE is_active=1 ORDER BY name"); ?>
            <?php foreach ($subjects as $sub): ?>
            <option value="<?= $sub['id'] ?>"><?= sanitize($sub['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <input type="hidden" name="role" value="teacher">
    <button type="submit" class="bg-teal-600 text-white px-4 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition whitespace-nowrap">Assign</button>
</form>
<?php endif; ?>

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-200">
                <th class="pb-2 pr-3 font-medium">Teacher</th>
                <th class="pb-2 pr-3 font-medium">Email</th>
                <th class="pb-2 pr-3 font-medium">Subject</th>
                <th class="pb-2 pr-3 font-medium">Role</th>
                <th class="pb-2"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($teachers)): ?>
            <tr><td colspan="5" class="py-8 text-center text-gray-400 text-xs">No teachers assigned.</td></tr>
            <?php else: ?>
            <?php foreach ($teachers as $t): ?>
            <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                <td class="py-2 pr-3">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-coral-400 to-amber-400 flex items-center justify-center text-white font-bold text-[10px]"><?= strtoupper(substr($t['full_name'], 0, 1)) ?></div>
                        <span class="text-xs font-medium text-gray-900"><?= sanitize($t['full_name']) ?></span>
                    </div>
                </td>
                <td class="py-2 pr-3 text-xs text-gray-500"><?= sanitize($t['email']) ?></td>
                <td class="py-2 pr-3 text-xs text-gray-500"><?= $t['subject_id'] ? (sanitize(db_get_row("SELECT name FROM subjects WHERE id=?", [$t['subject_id']])['name'] ?? '')) : 'All' ?></td>
                <td class="py-2 pr-3">
                    <?php if ($t['role'] === 'class_teacher'): ?>
                    <span class="inline-flex items-center gap-1 text-teal-600 text-xs"><i class="fas fa-star text-[10px]"></i> Class Teacher</span>
                    <?php else: ?>
                    <span class="text-gray-400 text-xs">Teacher</span>
                    <?php endif; ?>
                </td>
                <td class="py-2 text-right">
                    <form method="post" class="inline" onsubmit="return confirm('Remove this teacher?')">
                        <input type="hidden" name="action" value="remove_teacher">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <input type="hidden" name="class_id" value="<?= $class_id ?>">
                        <button type="submit" class="text-gray-300 hover:text-red-500 transition"><i class="fas fa-times text-xs"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($all_teachers)): ?>
<div class="mt-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
    <h4 class="font-bold text-gray-900 text-xs mb-2">Set Class Teacher</h4>
    <form method="post" class="flex items-end gap-3">
        <input type="hidden" name="action" value="set_class_teacher">
        <input type="hidden" name="class_id" value="<?= $class_id ?>">
        <div class="flex-1">
            <select name="teacher_id" required class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
                <option value="">— Select teacher —</option>
                <?php foreach ($all_teachers as $t): ?>
                <option value="<?= $t['id'] ?>"><?= sanitize($t['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-amber-600 text-white px-4 py-1.5 rounded-lg text-xs font-medium hover:bg-amber-700 transition">Update</button>
    </form>
</div>
<?php endif; ?>
