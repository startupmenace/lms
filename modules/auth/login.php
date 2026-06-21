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
    <style>
        body { margin: 0; }

        /* ─── Loader Screen ─── */
        #loader-screen {
            position: fixed; inset: 0; z-index: 9999;
            background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 40%, #fff7ed 100%);
            display: flex; align-items: center; justify-content: center;
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        #loader-screen.hidden {
            opacity: 0; transform: scale(1.05); pointer-events: none;
        }

        .loader-content { text-align: center; }
        .loader-content p { font-family: 'Inter', system-ui, sans-serif; }

        /* ─── Book Animation (Aaron Iker) ─── */
        .book {
            --color: #0d9488;
            --duration: 3s;
            width: 32px; height: 12px;
            position: relative; margin: 48px auto 36px;
            zoom: 2;
        }
        .book .inner {
            width: 32px; height: 12px; position: relative;
            transform-origin: 2px 2px; transform: rotateZ(-90deg);
            animation: book var(--duration) ease infinite;
            backface-visibility: hidden;
        }
        .book .inner .left,
        .book .inner .right {
            width: 60px; height: 4px; top: 0; border-radius: 2px;
            background: var(--color); position: absolute;
            backface-visibility: hidden;
        }
        .book .inner .left:before,
        .book .inner .right:before {
            content: ''; width: 48px; height: 4px; border-radius: 2px;
            background: inherit; position: absolute; top: -10px; left: 6px;
        }
        .book .inner .left {
            right: 28px; transform-origin: 58px 2px; transform: rotateZ(90deg);
            animation: left var(--duration) ease infinite;
            backface-visibility: hidden;
        }
        .book .inner .right {
            left: 28px; transform-origin: 2px 2px; transform: rotateZ(-90deg);
            animation: right var(--duration) ease infinite;
            backface-visibility: hidden;
        }
        .book .inner .middle {
            width: 32px; height: 12px; border: 4px solid var(--color);
            border-top: 0; border-radius: 0 0 9px 9px; transform: translateY(2px);
        }
        .book ul {
            margin: 0; padding: 0; list-style: none;
            position: absolute; left: 50%; top: 0;
        }
        .book ul li {
            height: 4px; border-radius: 2px; transform-origin: 100% 2px;
            width: 48px; right: 0; top: -10px; position: absolute;
            background: var(--color);
            transform: rotateZ(0deg) translateX(-18px);
            animation: pageFlip var(--duration) ease infinite backwards;
            will-change: transform;
        }
        .book ul li:nth-child(1)  { animation-delay: -0.08s; }
        .book ul li:nth-child(2)  { animation-delay: -0.15s; }
        .book ul li:nth-child(3)  { animation-delay: -0.23s; }
        .book ul li:nth-child(4)  { animation-delay: -0.31s; }
        .book ul li:nth-child(5)  { animation-delay: -0.38s; }
        .book ul li:nth-child(6)  { animation-delay: -0.46s; }
        .book ul li:nth-child(7)  { animation-delay: -0.54s; }
        .book ul li:nth-child(8)  { animation-delay: -0.62s; }
        .book ul li:nth-child(9)  { animation-delay: -0.69s; }
        .book ul li:nth-child(10) { animation-delay: -0.77s; }
        .book ul li:nth-child(11) { animation-delay: -0.85s; }
        .book ul li:nth-child(12) { animation-delay: -0.92s; }

        @keyframes pageFlip {
            0%   { transform: rotateZ(0deg) translateX(-18px); }
            6%   { transform: rotateZ(0deg) translateX(-18px); }
            20%  { transform: rotateZ(180deg) translateX(-18px); }
            50%  { transform: rotateZ(180deg) translateX(-18px); }
            64%  { transform: rotateZ(0deg) translateX(-18px); }
            100% { transform: rotateZ(0deg) translateX(-18px); }
        }

        @keyframes left {
            0%, 100% { transform: rotateZ(90deg); }
            5%   { transform: rotateZ(90deg); }
            12%, 50%  { transform: rotateZ(0deg); }
            57%  { transform: rotateZ(90deg); }
        }
        @keyframes right {
            0%, 100% { transform: rotateZ(-90deg); }
            5%   { transform: rotateZ(-90deg); }
            12%, 50%  { transform: rotateZ(0deg); }
            57%  { transform: rotateZ(-90deg); }
        }
        @keyframes book {
            0%, 100% { transform: rotateZ(-90deg); transform-origin: 2px 2px; }
            3%   { transform: rotateZ(-90deg); transform-origin: 2px 2px; }
            10%, 50%  { transform: rotateZ(0deg); transform-origin: 2px 2px; }
            50.01%, 54% { transform-origin: 30px 2px; }
            57%  { transform: rotateZ(90deg); }
            60%, 95%  { transform: rotateZ(0deg); transform-origin: 2px 2px; }
            97%  { transform: rotateZ(-90deg); transform-origin: 2px 2px; }
        }

        /* ─── Loading Text ─── */
        .loader-msg {
            font-size: 1rem; font-weight: 500; color: #0d9488;
            min-height: 1.6em; transition: opacity 0.35s ease;
        }
        .loader-msg .dots::after {
            content: '...';
            animation: dotAnim 1.5s steps(4, end) infinite;
        }
        @keyframes dotAnim {
            0%   { content: ''; }
            25%  { content: '.'; }
            50%  { content: '..'; }
            75%  { content: '...'; }
        }

        .loader-sub {
            font-size: 0.8rem; color: #94a3b8; margin-top: 8px;
        }

        /* ─── Loading bar ─── */
        .loader-bar-wrap {
            width: 180px; height: 3px; margin: 24px auto 0;
            background: #e2e8f0; border-radius: 4px; overflow: hidden;
        }
        .loader-bar-fill {
            height: 100%; width: 0%;
            background: linear-gradient(90deg, #0d9488, #14b8a6);
            border-radius: 4px;
            animation: barGrow 3s ease-in-out infinite;
        }
        @keyframes barGrow {
            0%   { width: 0%; }
            50%  { width: 85%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-teal-50 via-white to-coral-50 min-h-screen flex items-center justify-center p-4">

    <!-- ─── Loader ─── -->
    <div id="loader-screen">
        <div class="loader-content">
            <div class="book">
                <div class="inner">
                    <div class="left"></div>
                    <div class="middle"></div>
                    <div class="right"></div>
                </div>
                <ul>
                    <li></li><li></li><li></li><li></li>
                    <li></li><li></li><li></li><li></li>
                    <li></li><li></li><li></li><li></li>
                </ul>
            </div>
            <p class="loader-msg"><span id="loader-text">Getting learner records</span><span class="dots"></span></p>
            <p class="loader-sub">Ziada LMS</p>
            <div class="loader-bar-wrap"><div class="loader-bar-fill"></div></div>
        </div>
    </div>

    <!-- ─── Login Content ─── -->
    <div id="login-content" style="opacity:0">
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
    </div>

    <script>
    (function() {
        const messages = [
            'Getting learner records',
            'Prepping teachers',
            'Loading class schedules',
            'Preparing attendance logs',
            'Fetching academic reports',
            'Organizing homework'
        ];

        let idx = 0;
        const el = document.getElementById('loader-text');
        const interval = setInterval(() => {
            idx = (idx + 1) % messages.length;
            el.style.opacity = '0';
            setTimeout(() => {
                el.textContent = messages[idx];
                el.style.opacity = '1';
            }, 200);
        }, 700);

        const totalDuration = messages.length * 700 + 1000;
        setTimeout(() => {
            clearInterval(interval);
            document.getElementById('loader-screen').classList.add('hidden');
            document.getElementById('login-content').style.opacity = '1';
            document.getElementById('login-content').style.transition = 'opacity 0.6s ease';
        }, totalDuration);
    })();
    </script>
</body>
</html>
