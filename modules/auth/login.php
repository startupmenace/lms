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

        /* ─── Book Loader (3D flip) ─── */
        .bk-loader {
            position: relative;
            width: 110px; height: 85px;
            margin: 36px auto 30px;
            perspective: 900px;
        }
        .bk-loader .bk-spine {
            position: absolute; left: 0; top: 0;
            width: 8px; height: 100%;
            background: linear-gradient(180deg, #0f766e, #115e59);
            border-radius: 3px 0 0 3px;
            z-index: 6;
        }
        .bk-loader .bk-cover {
            position: absolute; left: 8px; top: 0;
            width: 102px; height: 100%;
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            border-radius: 0 5px 5px 0;
            box-shadow: 0 10px 30px rgba(13,148,136,0.3), inset 0 1px 0 rgba(255,255,255,0.15);
            z-index: 5;
        }
        .bk-loader .bk-cover::after {
            content: ''; position: absolute;
            inset: 10px 14px;
            border: 1.5px solid rgba(255,255,255,0.2);
            border-radius: 3px;
        }
        .bk-loader .bk-pages {
            position: absolute; left: 8px; top: 2px;
            width: 102px; height: calc(100% - 4px);
            transform-style: preserve-3d;
        }
        .bk-loader .bk-page {
            position: absolute; inset: 0;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0fdfa 100%);
            border-radius: 0 4px 4px 0;
            transform-origin: left center;
            backface-visibility: hidden;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
            box-shadow: inset -1px 0 2px rgba(0,0,0,0.03);
        }
        .bk-loader .bk-page::after {
            content: ''; position: absolute;
            top: 0; right: 0; bottom: 0; left: 88%;
            background: linear-gradient(90deg, transparent 0%, rgba(0,0,0,0.015) 100%);
            border-radius: 0 4px 4px 0;
        }
        .bk-loader .bk-page.active {
            z-index: 10;
            transform: rotateY(-178deg);
        }
        .bk-loader .bk-page.done {
            z-index: 1;
            transform: rotateY(-178deg);
        }
        .bk-loader .bk-pages .bk-page:nth-child(1) { z-index: 4; }
        .bk-loader .bk-pages .bk-page:nth-child(2) { z-index: 3; }
        .bk-loader .bk-pages .bk-page:nth-child(3) { z-index: 2; }
        .bk-loader .bk-pages .bk-page:nth-child(4) { z-index: 1; }

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
            <div class="bk-loader">
                <div class="bk-spine"></div>
                <div class="bk-cover"></div>
                <div class="bk-pages">
                    <div class="bk-page"></div>
                    <div class="bk-page"></div>
                    <div class="bk-page"></div>
                    <div class="bk-page"></div>
                </div>
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

        let idx = 0, pageIdx = 0;
        const el = document.getElementById('loader-text');
        const pages = document.querySelectorAll('.bk-page');

        function cyclePage() {
            pages.forEach(p => { p.classList.remove('active', 'done'); });
            const cur = pages[pageIdx];
            cur.classList.add('active');
            setTimeout(() => {
                cur.classList.remove('active');
                cur.classList.add('done');
            }, 500);
            pageIdx = (pageIdx + 1) % pages.length;
        }

        const msgInt = setInterval(() => {
            idx = (idx + 1) % messages.length;
            el.style.opacity = '0';
            setTimeout(() => {
                el.textContent = messages[idx];
                el.style.opacity = '1';
            }, 200);
        }, 700);

        const pageInt = setInterval(cyclePage, 600);
        cyclePage();

        const totalDuration = messages.length * 700 + 1000;
        setTimeout(() => {
            clearInterval(msgInt);
            clearInterval(pageInt);
            document.getElementById('loader-screen').classList.add('hidden');
            document.getElementById('login-content').style.opacity = '1';
            document.getElementById('login-content').style.transition = 'opacity 0.6s ease';
        }, totalDuration);
    })();
    </script>
</body>
</html>
