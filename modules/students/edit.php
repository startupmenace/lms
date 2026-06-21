<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
$student = db_get_row("SELECT * FROM students WHERE id = ?", [$id]);

if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('index.php');
}

$page_title = 'Edit Student';
$classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = (int)($_POST['class_id'] ?? 0);
    $parent_name = sanitize($_POST['parent_name'] ?? '');
    $parent_phone = sanitize($_POST['parent_phone'] ?? '');
    $parent_email = sanitize($_POST['parent_email'] ?? '');
    $date_of_birth = sanitize($_POST['date_of_birth'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $blood_group = sanitize($_POST['blood_group'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $guardian_name = sanitize($_POST['guardian_name'] ?? '');
    $guardian_phone = sanitize($_POST['guardian_phone'] ?? '');
    $disabilities = sanitize($_POST['disabilities'] ?? '');

    if (empty($class_id) || empty($parent_name)) {
        set_flash('error', 'Please fill in required fields.');
    } else {
        $profile_image = $student['profile_image'];

        if (!empty($_FILES['profile_image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowed)) {
                $filename = 'student_' . $id . '_' . time() . '.' . $ext;
                $dest = ensure_upload_dir('students') . '/' . $filename;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                    if ($student['profile_image']) {
                        $old_path = get_upload_base() . '/students/' . $student['profile_image'];
                        if (file_exists($old_path)) unlink($old_path);
                    }
                    $profile_image = $filename;
                }
            } else {
                set_flash('error', 'Invalid image format. Allowed: jpg, jpeg, png, webp, gif');
                redirect('edit.php?id=' . $id);
            }
        }

        db_query(
            "UPDATE students SET class_id=?, parent_name=?, parent_phone=?, parent_email=?, date_of_birth=?, gender=?, blood_group=?, address=?, city=?, state=?, guardian_name=?, guardian_phone=?, disabilities=?, profile_image=? WHERE id=?",
            [$class_id, $parent_name, $parent_phone, $parent_email, $date_of_birth, $gender, $blood_group, $address, $city, $state, $guardian_name, $guardian_phone, $disabilities, $profile_image, $id]
        );

        // Remove unlinked parents
        foreach (($_POST['remove_parent_ids'] ?? []) as $rid) {
            $rid = (int)$rid;
            if ($rid > 0) {
                db_query("DELETE FROM student_parents WHERE student_id=? AND parent_user_id=?", [$id, $rid]);
            }
        }

        // Link existing parent users
        foreach (($_POST['parent_user_ids'] ?? []) as $pid) {
            $pid = (int)$pid;
            if ($pid > 0) {
                db_insert("INSERT IGNORE INTO student_parents (student_id, parent_user_id, relationship, is_primary) VALUES (?,?,'parent',0)", [$id, $pid]);
            }
        }

        // Create new parent user on the fly
        $np_name = sanitize($_POST['new_parent_name'] ?? '');
        $np_email = sanitize($_POST['new_parent_email'] ?? '');
        if ($np_name && $np_email) {
            $np_phone = sanitize($_POST['new_parent_phone'] ?? '');
            $np_password = $_POST['new_parent_password'] ?? 'parent123';
            $existing = db_get_row("SELECT id FROM users WHERE email = ?", [$np_email]);
            if ($existing) {
                db_insert("INSERT IGNORE INTO student_parents (student_id, parent_user_id, relationship, is_primary) VALUES (?,?,'parent',0)", [$id, $existing['id']]);
            } else {
                $np_username = strtolower(preg_replace('/[^a-z0-9]/', '', explode('@', $np_email)[0]));
                $np_id = db_insert("INSERT INTO users (username, email, password, full_name, phone, role, is_active) VALUES (?,?,?,?,?,'parent',1)", [$np_username, $np_email, password_hash($np_password, PASSWORD_DEFAULT), $np_name, $np_phone]);
                if ($np_id) {
                    db_insert("INSERT INTO student_parents (student_id, parent_user_id, relationship, is_primary) VALUES (?,?,'parent',1)", [$id, $np_id]);
                }
            }
        }

        set_flash('success', 'Student profile updated successfully.');
        redirect('view.php?id=' . $id);
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900">Edit Student</h1>
        <a href="view.php?id=<?= $id ?>" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition"><i class="fas fa-arrow-left mr-2"></i>Back</a>
    </div>

    <?php if (has_flash('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('error') ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Class <span class="text-red-500">*</span></label>
                <select name="class_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $student['class_id'] == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?> (<?= sanitize($c['section'] ?? 'N/A') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-6">
                <?= student_avatar_html($student, 'w-16 h-16', 'text-2xl') ?>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo (optional)</label>
                    <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp,image/gif" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP, or GIF. Max 5MB. Leave empty to keep current.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parent/Student Name <span class="text-red-500">*</span></label>
                    <input type="text" name="parent_name" required value="<?= sanitize($student['parent_name']) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="parent_phone" value="<?= sanitize($student['parent_phone'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="parent_email" value="<?= sanitize($student['parent_email'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="<?= $student['date_of_birth'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">Select</option>
                        <option value="male" <?= $student['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= $student['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= $student['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                    <input type="text" name="blood_group" value="<?= sanitize($student['blood_group'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"><?= sanitize($student['address'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" value="<?= sanitize($student['city'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <input type="text" name="state" value="<?= sanitize($student['state'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>

            <hr class="border-gray-200">

            <h4 class="font-medium text-gray-900">Guardian Information (Optional)</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Name</label>
                    <input type="text" name="guardian_name" value="<?= sanitize($student['guardian_name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Phone</label>
                    <input type="text" name="guardian_phone" value="<?= sanitize($student['guardian_phone'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>

            <hr class="border-gray-200">

            <?php
            $linked_parents = db_get_all(
                "SELECT sp.*, u.full_name, u.email, u.phone FROM student_parents sp JOIN users u ON sp.parent_user_id = u.id WHERE sp.student_id = ?",
                [$id]
            );
            $all_parent_users = db_get_all("SELECT id, full_name, email FROM users WHERE role='parent' ORDER BY full_name");
            ?>

            <h4 class="font-medium text-gray-900">Parent Accounts <span class="text-xs font-normal text-gray-400">(linked logins for mom & dad)</span></h4>
            <div id="parent-accounts" class="space-y-2">
                <?php foreach ($linked_parents as $lp): ?>
                <div class="flex items-center gap-3 px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                    <i class="fas fa-user-circle text-teal-600 text-lg"></i>
                    <div class="flex-1 text-sm">
                        <span class="font-medium text-gray-800"><?= sanitize($lp['full_name']) ?></span>
                        <span class="text-gray-400 mx-1">·</span>
                        <span class="text-gray-500"><?= sanitize($lp['email']) ?></span>
                        <span class="text-[10px] text-gray-400 ml-2"><?= sanitize($lp['relationship']) ?></span>
                    </div>
                    <label class="text-xs text-red-500 cursor-pointer hover:text-red-700">
                        <input type="checkbox" name="remove_parent_ids[]" value="<?= $lp['parent_user_id'] ?>" class="rounded border-gray-300 text-red-500 focus:ring-red-400 mr-1">
                        Remove
                    </label>
                </div>
                <?php endforeach; ?>
                <div class="flex items-center gap-3">
                    <select name="parent_user_ids[]" class="parent-select w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">-- Select existing parent user --</option>
                        <?php foreach ($all_parent_users as $pu): ?>
                        <option value="<?= $pu['id'] ?>"><?= sanitize($pu['full_name']) ?> (<?= sanitize($pu['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 text-sm"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <button type="button" onclick="addParentRow()" class="text-teal-600 hover:text-teal-800 text-xs font-medium"><i class="fas fa-plus mr-1"></i> Add another parent</button>

            <details class="mt-3 border border-gray-200 rounded-lg p-3">
                <summary class="text-sm text-gray-600 cursor-pointer hover:text-teal-700 font-medium"><i class="fas fa-user-plus mr-1"></i> Or create a new parent account</summary>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Full Name</label>
                        <input type="text" name="new_parent_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                        <input type="email" name="new_parent_email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                        <input type="text" name="new_parent_phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Password <span class="text-gray-400">(default: parent123)</span></label>
                        <input type="text" name="new_parent_password" value="parent123" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                </div>
            </details>

            <hr class="border-gray-200">

            <h4 class="font-medium text-gray-900">Medical Information</h4>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Disabilities / Medical Conditions</label>
                <textarea name="disabilities" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g. Asthma, ADHD, visual impairment, etc."><?= sanitize($student['disabilities'] ?? '') ?></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition"><i class="fas fa-save mr-2"></i>Save Changes</button>
                <a href="view.php?id=<?= $id ?>" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function addParentRow() {
    const container = document.getElementById('parent-accounts');
    const template = container.querySelector('.flex:last-child');
    const clone = template.cloneNode(true);
    clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    container.appendChild(clone);
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
