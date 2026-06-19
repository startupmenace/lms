<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$id = (int)($_GET['id'] ?? 0);
$student = db_get_row("SELECT s.*, c.name as class_name, c.section as class_section FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ?", [$id]);

if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('index.php');
}

$page_title = sanitize($student['parent_name']) . ' - Student Profile';

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-5xl mx-auto">
    <?php if (has_flash('success')): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
            <i class="fas fa-check-circle"></i> <?= get_flash('success') ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-teal-500 to-coral-600 px-6 py-8">
            <div class="flex items-center gap-6">
                <?= student_avatar_html($student) ?>
                <div class="text-white">
                    <h2 class="text-2xl font-bold"><?= sanitize($student['parent_name']) ?></h2>
                    <p class="text-white/80"><?= sanitize($student['class_name'] ?? 'Unassigned') ?></p>
                    <p class="text-white/60 text-sm">Enrollment: <?= sanitize($student['enrollment_id']) ?></p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Enrollment ID</label>
                    <p class="text-gray-900 font-medium mt-1"><?= sanitize($student['enrollment_id']) ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Admission Date</label>
                    <p class="text-gray-900 font-medium mt-1"><?= $student['admission_date'] ? format_date($student['admission_date']) : 'N/A' ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Class / Stream</label>
                    <p class="text-gray-900 font-medium mt-1"><?= sanitize($student['class_name'] ?? 'N/A') ?><?= !empty($student['class_section']) ? ' · ' . sanitize($student['class_section']) : '' ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Date of Birth</label>
                    <p class="text-gray-900 font-medium mt-1"><?= $student['date_of_birth'] ? format_date($student['date_of_birth']) : 'N/A' ?></p>
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
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-map-marker-alt text-green-600 mr-2"></i>Address & Demographics</h3>
            <div class="space-y-3">
                <div><span class="text-gray-500 text-sm">Address:</span> <span class="text-gray-900"><?= sanitize($student['address'] ?? 'N/A') ?></span></div>
                <div><span class="text-gray-500 text-sm">City:</span> <span class="text-gray-900"><?= sanitize($student['city'] ?? 'N/A') ?></span></div>
                <div><span class="text-gray-500 text-sm">State:</span> <span class="text-gray-900"><?= sanitize($student['state'] ?? 'N/A') ?></span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-chart-line text-amber-600 mr-2"></i>Academics</h3>
            <?php
            $attendance_stats = db_get_row("SELECT COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present FROM attendance WHERE student_id = ?", [$id]);
            $total_days = $attendance_stats['total'] ?? 0;
            $present_days = $attendance_stats['present'] ?? 0;
            $attendance_pct = $total_days > 0 ? round(($present_days / $total_days) * 100, 1) : 0;
            ?>
            <div class="space-y-3">
                <div><span class="text-gray-500 text-sm">Overall Attendance:</span>
                    <span class="text-gray-900 font-medium"><?= $attendance_pct ?>% (<?= $present_days ?>/<?= $total_days ?> days)</span>
                </div>
                <div><span class="text-gray-500 text-sm">Tests Taken:</span>
                    <span class="text-gray-900 font-medium"><?= db_get_row("SELECT COUNT(*) as count FROM test_submissions WHERE student_id = ?", [$id])['count'] ?? 0 ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-3 mt-6">
        <a href="edit.php?id=<?= $id ?>" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 transition"><i class="fas fa-edit mr-2"></i>Edit Profile</a>
        <a href="delete.php?id=<?= $id ?>" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-600 transition" data-confirm="Delete this student?"><i class="fas fa-trash mr-2"></i>Delete</a>
        <a href="index.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition"><i class="fas fa-arrow-left mr-2"></i>Back to List</a>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
