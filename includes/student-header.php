<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Dashboard' ?> | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                },
                colors: {
                    coral: {
                        50: '#fef3f2', 100: '#fee4e2', 200: '#fecdca',
                        300: '#fda29b', 400: '#f97066', 500: '#f9645a',
                        600: '#e8453a', 700: '#c22f25',
                    }
                }
            }
        }
    }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">
<div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-30 lg:hidden"></div>
<div class="flex h-screen overflow-hidden">
    <aside id="sidebar" class="fixed lg:relative inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-teal-600 flex items-center gap-2">
                    <i class="fas fa-graduation-cap"></i>
                    <a href="<?= BASE_URL ?>/modules/student/dashboard.php">Ziada LMS</a>
                </h1>
                <p class="text-xs text-gray-400 mt-1">Student Portal</p>
            </div>
            <button id="sidebar-close" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition self-start"><i class="fas fa-times"></i></button>
        </div>
        <nav class="flex-1 overflow-y-auto p-4 space-y-1">
            <a href="<?= BASE_URL ?>/modules/student/dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-teal-50 text-teal-700 font-bold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-th-large w-5 text-center"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/modules/student/attendance.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'bg-teal-50 text-teal-700 font-bold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-calendar-check w-5 text-center"></i> My Attendance
            </a>
            <a href="<?= BASE_URL ?>/modules/student/tests.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?= basename($_SERVER['PHP_SELF']) == 'tests.php' ? 'bg-teal-50 text-teal-700 font-bold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-flask w-5 text-center"></i> My Tests
            </a>
            <a href="<?= BASE_URL ?>/modules/student/fees.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?= basename($_SERVER['PHP_SELF']) == 'fees.php' ? 'bg-teal-50 text-teal-700 font-bold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-wallet w-5 text-center"></i> My Fees
            </a>
            <a href="<?= BASE_URL ?>/modules/student/pay-fees.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?= basename($_SERVER['PHP_SELF']) == 'pay-fees.php' ? 'bg-teal-50 text-teal-700 font-bold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-paper-plane w-5 text-center"></i> Pay Fees
            </a>
            <a href="<?= BASE_URL ?>/modules/student/notices.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?= basename($_SERVER['PHP_SELF']) == 'notices.php' ? 'bg-teal-50 text-teal-700 font-bold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-bullhorn w-5 text-center"></i> Notices
            </a>
            <a href="<?= BASE_URL ?>/modules/diary/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?= basename(dirname($_SERVER['PHP_SELF'])) == 'diary' ? 'bg-teal-50 text-teal-700 font-bold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-book w-5 text-center"></i> My Diary
            </a>
            <a href="<?= BASE_URL ?>/modules/student/profile.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-teal-50 text-teal-700 font-bold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-user w-5 text-center"></i> My Profile
            </a>
            <a href="<?= BASE_URL ?>/modules/chat/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                <i class="fas fa-comments w-5 text-center"></i> Chat
            </a>
            <a href="<?= BASE_URL ?>/modules/live-class/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                <i class="fas fa-video w-5 text-center"></i> Live Class
            </a>
        </nav>
        <div class="p-4 border-t border-gray-200">
            <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700 transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                <i class="fas fa-sign-out-alt w-5 text-center"></i> Logout
            </a>
        </div>
    </aside>
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button id="sidebar-toggle" class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition -ml-1 focus:outline-none focus:ring-2 focus:ring-teal-500" aria-label="Toggle sidebar">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <h2 class="text-base sm:text-lg font-bold text-gray-900 truncate"><?= $page_title ?? 'Dashboard' ?></h2>
            </div>
            <div class="flex items-center gap-4">
                <!-- Notification Bell -->
                <div class="relative" id="notif-container">
                    <button id="notif-bell" class="relative w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition focus:outline-none focus:ring-2 focus:ring-teal-500" aria-label="Notifications">
                        <i class="fas fa-bell text-lg"></i>
                        <span id="notif-badge" class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center border border-white" style="display:none">0</span>
                    </button>
                    <div id="notif-dropdown" class="hidden absolute right-0 top-full mt-2 w-80 bg-white rounded-xl border border-gray-200 shadow-xl z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-900">Notifications</span>
                            <button id="notif-mark-read" class="text-[10px] text-teal-600 hover:text-teal-800 font-medium">Mark all as read</button>
                        </div>
                        <div id="notif-list" class="max-h-72 overflow-y-auto divide-y divide-gray-50">
                            <div class="p-4 text-center text-xs text-gray-400">Loading...</div>
                        </div>
                        <a href="<?= BASE_URL ?>/modules/student/notices.php" class="block px-4 py-2.5 text-center text-xs font-medium text-teal-600 hover:bg-teal-50 border-t border-gray-100 transition">
                            <i class="fas fa-bullhorn mr-1"></i> View All Notices
                        </a>
                    </div>
                </div>
                <!-- User Menu -->
                <div class="relative" id="user-menu-container">
                    <button id="user-menu-btn" class="flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 rounded-lg" aria-label="User menu">
                        <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-sm font-bold text-teal-700">
                            <?= get_avatar(get_user_name()) ?>
                        </div>
                        <span class="text-sm font-semibold text-gray-800 hidden sm:inline"><?= get_user_name() ?></span>
                        <i class="fas fa-chevron-down text-[10px] text-gray-400 hidden sm:inline"></i>
                    </button>
                    <div id="user-dropdown" class="hidden absolute right-0 top-full mt-2 w-48 bg-white rounded-xl border border-gray-200 shadow-xl z-50 overflow-hidden">
                        <a href="<?= BASE_URL ?>/modules/profile/index.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-user-circle text-gray-400 w-4 text-center"></i> My Profile
                        </a>
                        <a href="<?= BASE_URL ?>/modules/student/profile.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-graduation-cap text-gray-400 w-4 text-center"></i> Student Profile
                        </a>
                        <div class="border-t border-gray-100"></div>
                        <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                            <i class="fas fa-sign-out-alt w-4 text-center"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
