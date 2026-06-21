<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

if (is_logged_in()) {
    redirect(get_user_dashboard());
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/favicon.svg">
</head>
<body class="bg-gradient-to-br from-teal-50 via-white to-coral-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-teal-100 rounded-2xl mb-4">
                <i class="fas fa-graduation-cap text-3xl text-teal-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Welcome to Ziada LMS</h1>
            <p class="text-gray-500 mt-1">Sign in to your LMS account</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if (has_flash('error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> <?= get_flash('error') ?>
                </div>
            <?php endif; ?>

            <?php if (has_flash('success')): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> <?= get_flash('success') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="authenticate.php" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none"
                               placeholder="admin@jewelhouse.sc.ke">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none"
                               placeholder="Enter your password">
                    </div>
                </div>
                <button type="submit"
                        class="w-full bg-teal-600 text-white py-2.5 rounded-lg font-medium hover:bg-teal-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-gray-400 text-xs mt-6">
            Default: admin@jewelhouse.sc.ke / password
        </p>
    </div>
</body>
</html>
