<aside id="sidebar" class="fixed lg:relative inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-300">
    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <h1 class="text-xl font-bold text-teal-600"><i class="fas fa-graduation-cap mr-2"></i>Jewel House School</h1>
        <button id="sidebar-close" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"><i class="fas fa-times"></i></button>
    </div>
    <nav class="flex-1 overflow-y-auto p-4 space-y-1">
        <a href="<?= BASE_URL ?>/modules/dashboard/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-teal-50 text-teal-700 font-bold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' ?>">
            <i class="fas fa-th-large w-5 text-center"></i> Dashboard
        </a>

        <a href="<?= BASE_URL ?>/modules/profile/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-user-circle w-5 text-center"></i> My Profile
        </a>

        <?php if (has_role('admin', 'teacher')): ?>
        <div class="pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider">Management</div>

        <a href="<?= BASE_URL ?>/modules/students/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-user-graduate w-5 text-center"></i> Students
        </a>

        <a href="<?= BASE_URL ?>/modules/attendance/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-calendar-check w-5 text-center"></i> Attendance
        </a>

        <a href="<?= BASE_URL ?>/modules/exams/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-pen-clip w-5 text-center"></i> Exam Planner
        </a>

        <a href="<?= BASE_URL ?>/modules/tests/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-flask w-5 text-center"></i> Tests
        </a>

        <a href="<?= BASE_URL ?>/modules/evaluation/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-check-double w-5 text-center"></i> Evaluation
        </a>

        <?php if (has_role('admin')): ?>
        <a href="<?= BASE_URL ?>/modules/transport/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-bus w-5 text-center"></i> Transport
        </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/modules/staff/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-users-cog w-5 text-center"></i> Staff
        </a>

        <a href="<?= BASE_URL ?>/modules/timetable/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-table w-5 text-center"></i> Timetable
        </a>

        <a href="<?= BASE_URL ?>/modules/leave/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-calendar-alt w-5 text-center"></i> Leave
        </a>

        <div class="pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider">Finance</div>

        <a href="<?= BASE_URL ?>/modules/fees/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-wallet w-5 text-center"></i> Fee Management
        </a>
        <a href="<?= BASE_URL ?>/modules/fees/gateway-settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-cog w-5 text-center"></i> Payment Gateways
        </a>
        <a href="<?= BASE_URL ?>/modules/fees/verify-payment.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-check-double w-5 text-center"></i> Verify Payments
        </a>

        <div class="pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider">Communication</div>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/modules/chat/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-comments w-5 text-center"></i> Chat
        </a>

        <a href="<?= BASE_URL ?>/modules/noticeboard/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-bullhorn w-5 text-center"></i> Notice Board
        </a>

        <a href="<?= BASE_URL ?>/modules/holidays/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-calendar-alt w-5 text-center"></i> Calendar
        </a>

        <a href="<?= BASE_URL ?>/modules/diary/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-book w-5 text-center"></i> Diary
        </a>

        <a href="<?= BASE_URL ?>/modules/live-class/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <i class="fas fa-video w-5 text-center"></i> Live Class
        </a>
        <a href="<?= BASE_URL ?>/modules/live-class/attendance-report.php" class="flex items-center gap-3 pl-10 pr-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition">
            <i class="fas fa-clipboard-list w-4 text-center"></i> Attendance Report
        </a>
    </nav>
    <div class="p-4 border-t border-gray-200">
        <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700 transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
            <i class="fas fa-sign-out-alt w-5 text-center"></i> Logout
        </a>
    </div>
</aside>
