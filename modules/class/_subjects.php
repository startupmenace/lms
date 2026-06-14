<?php
$all_subjects = db_get_all("SELECT * FROM subjects WHERE is_active=1 ORDER BY name");
$class_subject_ids = db_get_all("SELECT subject_id FROM class_subjects WHERE class_id=?", [$class_id]);
$cs_ids = array_column($class_subject_ids, 'subject_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subjects'])) {
    $cid = (int)$_POST['class_id'];
    db_query("DELETE FROM class_subjects WHERE class_id=?", [$cid]);
    $subjects = $_POST['subjects'] ?? [];
    foreach ($subjects as $sid) {
        db_insert("INSERT INTO class_subjects (class_id, subject_id) VALUES (?,?)", [$cid, (int)$sid]);
    }
    set_flash('success', 'Subjects updated.');
    redirect("?id=$cid&tab=subjects");
}
?>
<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500"><?= count($cs_ids) ?> subject(s) assigned to this class</p>
</div>

<?php if (has_role('admin')): ?>
<form method="post">
    <input type="hidden" name="save_subjects" value="1">
    <input type="hidden" name="class_id" value="<?= $class_id ?>">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-6">
        <?php foreach ($all_subjects as $s): ?>
        <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition <?= in_array($s['id'], $cs_ids) ? 'border-teal-300 bg-teal-50' : 'border-gray-200 hover:border-gray-300' ?>">
            <input type="checkbox" name="subjects[]" value="<?= $s['id'] ?>" <?= in_array($s['id'], $cs_ids) ? 'checked' : '' ?> class="accent-teal-600">
            <span class="text-xs font-medium text-gray-700"><?= sanitize($s['name']) ?></span>
        </label>
        <?php endforeach; ?>
    </div>
    <div class="flex justify-end">
        <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg text-xs font-medium hover:bg-teal-700 transition">Save Subjects</button>
    </div>
</form>
<?php endif; ?>
