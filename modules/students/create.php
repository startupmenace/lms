<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Add New Student';
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
        $enrollment_id = 'STU-' . date('Y') . '-' . strtoupper(uniqid());
        $admission_date = date('Y-m-d');

        $id = db_insert(
            "INSERT INTO students (class_id, enrollment_id, admission_date, date_of_birth, gender, blood_group, address, city, state, parent_name, parent_phone, parent_email, guardian_name, guardian_phone, disabilities) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$class_id, $enrollment_id, $admission_date, $date_of_birth, $gender, $blood_group, $address, $city, $state, $parent_name, $parent_phone, $parent_email, $guardian_name, $guardian_phone, $disabilities]
        );

        if ($id) {
            $profile_image = '';

            if (!empty($_FILES['profile_image']['name'])) {
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $allowed)) {
                    $filename = 'student_' . $id . '_' . time() . '.' . $ext;
                    $dest = ensure_upload_dir('students') . '/' . $filename;
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                        $profile_image = $filename;
                        db_query("UPDATE students SET profile_image=? WHERE id=?", [$profile_image, $id]);
                    }
                }
            }

            set_flash('success', 'Student added successfully! Enrollment ID: ' . $enrollment_id);
            redirect('view.php?id=' . $id);
        } else {
            set_flash('error', 'Failed to add student.');
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Student Information</h3>

        <?php if (has_flash('error')): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Class <span class="text-red-500">*</span></label>
                <select name="class_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?> (<?= sanitize($c['section'] ?? 'N/A') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parent/Student Name <span class="text-red-500">*</span></label>
                    <input type="text" name="parent_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="parent_phone" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="parent_email" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                    <input type="text" name="blood_group" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo (optional)</label>
                <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp,image/gif" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP, or GIF. Max 5MB. Initials shown if skipped.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <input type="text" name="state" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>

            <hr class="border-gray-200">

            <h4 class="font-medium text-gray-900">Guardian Information (Optional)</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Name</label>
                    <input type="text" name="guardian_name" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Phone</label>
                    <input type="text" name="guardian_phone" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>

            <hr class="border-gray-200">

            <h4 class="font-medium text-gray-900">Medical Information</h4>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Disabilities / Medical Conditions</label>
                <textarea name="disabilities" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g. Asthma, ADHD, visual impairment, etc."></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition"><i class="fas fa-save mr-2"></i>Save Student</button>
                <a href="index.php" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
