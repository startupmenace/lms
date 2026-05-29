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

include __DIR__ . '/../../includes/student-header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-teal-500 to-coral-600 px-6 py-8">
            <div class="flex items-center gap-6">
                <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center text-3xl font-bold text-white">
                    <?= get_avatar($student['parent_name'] ?? 'S') ?>
                </div>
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
            </div>
        </div>
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
