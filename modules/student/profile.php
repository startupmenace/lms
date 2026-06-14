<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('student');

$page_title = 'My Profile';

$user_id = get_user_id();
$student = db_get_row("SELECT s.*, c.name as class_name, u.full_name, u.email, u.phone FROM students s LEFT JOIN classes c ON s.class_id = c.id LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ? ORDER BY s.id LIMIT 1", [$user_id]);

if (!$student) {
    $student = db_get_row("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id ORDER BY s.id LIMIT 1");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_photo']) && $student) {
    if (!empty($_FILES['profile_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed)) {
            $filename = 'student_' . $student['id'] . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../../uploads/students/' . $filename;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                if ($student['profile_image'] && file_exists(__DIR__ . '/../../uploads/students/' . $student['profile_image'])) {
                    unlink(__DIR__ . '/../../uploads/students/' . $student['profile_image']);
                }
                db_query("UPDATE students SET profile_image=? WHERE id=?", [$filename, $student['id']]);
                $student['profile_image'] = $filename;
                set_flash('photo_success', 'Profile photo updated.');
            } else {
                set_flash('photo_error', 'Failed to upload photo.');
            }
        } else {
            set_flash('photo_error', 'Invalid image format. Allowed: jpg, jpeg, png, webp, gif');
        }
    } else {
        set_flash('photo_error', 'No file selected.');
    }
    redirect('profile.php');
}

include __DIR__ . '/../../includes/student-header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-teal-500 to-coral-600 px-6 py-8">
            <div class="flex items-center gap-6">
                <?= student_avatar_html($student) ?>
                <div class="text-white">
                    <h2 class="text-2xl font-bold"><?= sanitize($student['parent_name'] ?? 'Student') ?></h2>
                    <p class="text-white/80"><?= sanitize($student['class_name'] ?? 'Unassigned') ?></p>
                    <p class="text-white/60 text-sm">Enrollment: <?= sanitize($student['enrollment_id'] ?? 'N/A') ?></p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Enrollment ID</label>
                    <p class="text-gray-900 font-medium mt-1"><?= sanitize($student['enrollment_id'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Admission Date</label>
                    <p class="text-gray-900 font-medium mt-1"><?= !empty($student['admission_date']) ? format_date($student['admission_date']) : 'N/A' ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Class</label>
                    <p class="text-gray-900 font-medium mt-1"><?= sanitize($student['class_name'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Date of Birth</label>
                    <p class="text-gray-900 font-medium mt-1"><?= !empty($student['date_of_birth']) ? format_date($student['date_of_birth']) : 'N/A' ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Gender</label>
                    <p class="text-gray-900 font-medium mt-1"><?= ucfirst($student['gender'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Blood Group</label>
                    <p class="text-gray-900 font-medium mt-1"><?= sanitize($student['blood_group'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Disabilities / Medical Conditions</label>
                    <p class="text-gray-900 font-medium mt-1"><?= !empty($student['disabilities']) ? nl2br(sanitize($student['disabilities'])) : 'None' ?></p>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 px-6 py-4 flex items-center gap-4">
            <?= student_avatar_html($student, 'w-14 h-14', 'text-xl') ?>
            <form method="post" enctype="multipart/form-data" class="flex-1 flex items-center gap-3">
                <input type="hidden" name="update_photo" value="1">
                <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp,image/gif" class="text-sm text-gray-500 flex-1 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                <button type="submit" class="bg-teal-600 text-white px-4 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition">Upload</button>
            </form>
        </div>

        <?php if (get_flash('photo_success')): ?>
            <div class="px-6 pb-4">
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg text-sm flex items-center gap-2"><i class="fas fa-check-circle"></i> <?= get_flash('photo_success') ?></div>
            </div>
        <?php endif; ?>
        <?php if (get_flash('photo_error')): ?>
            <div class="px-6 pb-4">
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm flex items-center gap-2"><?= get_flash('photo_error') ?></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-users text-teal-600 mr-2"></i>Parent Information</h3>
            <div class="space-y-3">
                <div><span class="text-gray-500 text-sm">Name:</span> <span class="text-gray-900 font-medium"><?= sanitize($student['parent_name'] ?? 'N/A') ?></span></div>
                <div><span class="text-gray-500 text-sm">Phone:</span> <span class="text-gray-900 font-medium"><?= sanitize($student['parent_phone'] ?? 'N/A') ?></span></div>
                <div><span class="text-gray-500 text-sm">Email:</span> <span class="text-gray-900 font-medium"><?= sanitize($student['parent_email'] ?? 'N/A') ?></span></div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-shield-alt text-coral-600 mr-2"></i>Guardian Information</h3>
            <div class="space-y-3">
                <div><span class="text-gray-500 text-sm">Name:</span> <span class="text-gray-900 font-medium"><?= sanitize($student['guardian_name'] ?? 'N/A') ?></span></div>
                <div><span class="text-gray-500 text-sm">Phone:</span> <span class="text-gray-900 font-medium"><?= sanitize($student['guardian_phone'] ?? 'N/A') ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/student-footer.php'; ?>
