<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('hr');

$page_title = 'HR Dashboard';
$is_admin = has_role('admin');

$total_staff = db_get_row("SELECT COUNT(*) as c FROM users WHERE role NOT IN ('student','parent')")['c'] ?? 0;
$staff_with_details = db_get_row("SELECT COUNT(*) as c FROM staff_details")['c'] ?? 0;
$active_today = db_get_row("SELECT COUNT(*) as c FROM staff_attendance WHERE date = CURDATE() AND status = 'present'")['c'] ?? 0;
$on_leave_today = db_get_row("SELECT COUNT(*) as c FROM leave_applications WHERE status = 'approved' AND CURDATE() BETWEEN from_date AND to_date")['c'] ?? 0;
$pending_leave = db_get_row("SELECT COUNT(*) as c FROM leave_applications WHERE status = 'pending'")['c'] ?? 0;
$late_today = db_get_row("SELECT COUNT(*) as c FROM staff_attendance WHERE date = CURDATE() AND status = 'late'")['c'] ?? 0;

$staff_list = db_get_all("SELECT u.id, u.full_name, u.email, u.phone, u.role, u.is_active,
    sd.employee_id, sd.qualification, sd.gender
    FROM users u
    LEFT JOIN staff_details sd ON u.id = sd.user_id
    WHERE u.role NOT IN ('student','parent')
    ORDER BY u.full_name");

$pending_apps = db_get_all("SELECT la.*, lt.name as leave_type_name, lt.color as leave_color, u.full_name as staff_name
    FROM leave_applications la
    LEFT JOIN leave_types lt ON la.leave_type_id = lt.id
    LEFT JOIN users u ON la.user_id = u.id
    WHERE la.status = 'pending'
    ORDER BY la.applied_at DESC LIMIT 5");

$recent_staff_attendance = db_get_all("SELECT sa.*, u.full_name
    FROM staff_attendance sa
    LEFT JOIN users u ON sa.user_id = u.id
    WHERE sa.date = CURDATE()
    ORDER BY sa.check_in ASC");

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                <i class="fas fa-users text-teal-600 mr-2"></i> HR Dashboard
            </h1>
            <p class="text-gray-500 text-sm mt-1">Staff management, leave tracking, and attendance</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= BASE_URL ?>/modules/staff/index.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
                <i class="fas fa-user-plus"></i> Manage Staff
            </a>
            <a href="<?= BASE_URL ?>/modules/leave/index.php" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 transition flex items-center gap-2">
                <i class="fas fa-calendar-alt"></i> Leave
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Staff</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $total_staff ?></p>
            <p class="text-xs text-gray-400 mt-1"><?= $staff_with_details ?> with profiles</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Present Today</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= $active_today ?></p>
            <?php if ($late_today > 0): ?>
            <p class="text-xs text-amber-600 mt-1"><?= $late_today ?> late</p>
            <?php endif; ?>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">On Leave</p>
            <p class="text-2xl font-bold text-blue-600 mt-1"><?= $on_leave_today ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pending Leave</p>
            <p class="text-2xl font-bold text-amber-600 mt-1"><?= $pending_leave ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Not Checked In</p>
            <p class="text-2xl font-bold text-red-600 mt-1"><?= max(0, $total_staff - $active_today - $on_leave_today) ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-base font-bold text-gray-900"><i class="fas fa-address-book text-teal-600 mr-2"></i>Staff Directory</h2>
                    <span class="text-xs font-medium text-gray-500"><?= count($staff_list) ?> staff</span>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if (empty($staff_list)): ?>
                    <div class="p-8 text-center text-gray-400">No staff found</div>
                    <?php else: ?>
                    <?php foreach ($staff_list as $s): ?>
                    <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition">
                        <div class="w-9 h-9 rounded-full bg-teal-100 flex items-center justify-center text-sm font-bold text-teal-700 flex-shrink-0">
                            <?= strtoupper(substr($s['full_name'], 0, 1)) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 truncate"><?= sanitize($s['full_name']) ?></p>
                            <p class="text-xs text-gray-500 truncate">
                                <?= ucfirst(sanitize($s['role'])) ?>
                                <?= $s['employee_id'] ? ' &middot; ID: ' . sanitize($s['employee_id']) : '' ?>
                                <?= $s['qualification'] ? ' &middot; ' . sanitize($s['qualification']) : '' ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="w-2 h-2 rounded-full <?= $s['is_active'] ? 'bg-green-500' : 'bg-red-500' ?>" title="<?= $s['is_active'] ? 'Active' : 'Inactive' ?>"></span>
                            <?php if ($s['phone']): ?>
                            <a href="tel:<?= sanitize($s['phone']) ?>" class="text-gray-400 hover:text-teal-600 text-sm" title="<?= sanitize($s['phone']) ?>"><i class="fas fa-phone"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($pending_apps)): ?>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-base font-bold text-gray-900">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-clock text-amber-500"></i> Pending Leave Approvals
                            <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2 py-0.5 rounded-full"><?= $pending_leave ?></span>
                        </span>
                    </h2>
                    <a href="<?= BASE_URL ?>/modules/leave/index.php?tab=all" class="text-sm font-semibold text-teal-600 hover:text-teal-700 hover:underline">View All</a>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($pending_apps as $a): ?>
                    <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0" style="background:<?= $a['leave_color'] ?>20;color:<?= $a['leave_color'] ?>">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate"><?= sanitize($a['staff_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= sanitize($a['leave_type_name']) ?> &middot; <?= format_date($a['from_date']) ?> - <?= format_date($a['to_date']) ?> (<?= $a['total_days'] ?> days)</p>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/modules/leave/index.php?tab=all" class="text-xs font-semibold text-teal-600 hover:text-teal-800 hover:underline flex-shrink-0">Review</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-base font-bold text-gray-900 mb-3"><i class="fas fa-fingerprint text-teal-600 mr-2"></i>Today's Attendance</h2>
                <?php if (empty($recent_staff_attendance)): ?>
                <p class="text-sm text-gray-400 text-center py-6">No staff checked in yet today</p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($recent_staff_attendance as $sa): ?>
                    <div class="flex items-center gap-3 p-2.5 rounded-lg <?= $sa['status'] == 'present' ? 'bg-green-50' : 'bg-amber-50' ?>">
                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-xs font-bold text-gray-700 flex-shrink-0">
                            <?= strtoupper(substr($sa['full_name'], 0, 1)) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 truncate"><?= sanitize($sa['full_name']) ?></p>
                            <p class="text-xs text-gray-500">
                                <?php if ($sa['check_in']): ?>
                                <i class="far fa-clock mr-0.5"></i> <?= date('h:i A', strtotime($sa['check_in'])) ?>
                                <?php endif; ?>
                                <?php if ($sa['check_out']): ?>
                                &middot; Out: <?= date('h:i A', strtotime($sa['check_out'])) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full <?= $sa['status'] == 'present' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>">
                            <?= ucfirst($sa['status']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-base font-bold text-gray-900 mb-3"><i class="fas fa-bolt text-teal-600 mr-2"></i>Quick Actions</h2>
                <div class="space-y-2">
                    <a href="<?= BASE_URL ?>/modules/staff/index.php?tab=accounts" class="flex items-center gap-3 p-3 rounded-xl bg-teal-50 hover:bg-teal-100 border border-teal-100 hover:border-teal-200 transition group">
                        <div class="w-9 h-9 rounded-lg bg-teal-100 flex items-center justify-center group-hover:scale-110 transition"><i class="fas fa-user-plus text-teal-600"></i></div>
                        <div><p class="text-sm font-semibold text-gray-900">Add Staff Account</p><p class="text-xs text-gray-500">Create new user</p></div>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/staff/index.php?tab=details" class="flex items-center gap-3 p-3 rounded-xl bg-blue-50 hover:bg-blue-100 border border-blue-100 hover:border-blue-200 transition group">
                        <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center group-hover:scale-110 transition"><i class="fas fa-id-card text-blue-600"></i></div>
                        <div><p class="text-sm font-semibold text-gray-900">Staff Details</p><p class="text-xs text-gray-500">Manage profiles</p></div>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/leave/index.php?tab=all" class="flex items-center gap-3 p-3 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-100 hover:border-amber-200 transition group">
                        <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center group-hover:scale-110 transition"><i class="fas fa-calendar-check text-amber-600"></i></div>
                        <div><p class="text-sm font-semibold text-gray-900">Review Leave</p><p class="text-xs text-gray-500"><?= $pending_leave ?> pending</p></div>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/leave/index.php?tab=apply" class="flex items-center gap-3 p-3 rounded-xl bg-green-50 hover:bg-green-100 border border-green-100 hover:border-green-200 transition group">
                        <div class="w-9 h-9 rounded-lg bg-green-100 flex items-center justify-center group-hover:scale-110 transition"><i class="fas fa-paper-plane text-green-600"></i></div>
                        <div><p class="text-sm font-semibold text-gray-900">Apply for Leave</p><p class="text-xs text-gray-500">Submit request</p></div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
