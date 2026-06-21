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

        /* ─── Book Animation ─── */
        .book {
            position: relative;
            width: 96px; height: 124px;
            margin: 0 auto 28px;
            perspective: 600px;
        }
        .book-inner {
            position: relative; width: 100%; height: 100%;
            transform-style: preserve-3d;
            animation: bookFloat 2.6s ease-in-out infinite;
        }
        @keyframes bookFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        /* Cover */
        .book-cover {
            position: absolute; inset: 0;
            background: linear-gradient(165deg, #0d9488 0%, #0f766e 100%);
            border-radius: 3px 10px 10px 3px;
            box-shadow: 0 8px 28px rgba(13, 148, 136, 0.3);
        }
        /* Spine */
        .book-cover::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 14px;
            background: linear-gradient(180deg, #115e59, #0f766e);
            border-radius: 3px 0 0 3px;
        }
        /* Spine highlight */
        .book-cover::after {
            content: ''; position: absolute; left: 11px; top: 5px; bottom: 5px; width: 2px;
            background: rgba(255,255,255,0.1);
            border-radius: 1px;
        }
        .book-cover .band {
            position: absolute; left: 18px; right: 4px; height: 4px;
            background: rgba(255,255,255,0.06);
            border-radius: 1px;
        }
        .book-cover .band.t { top: 20px; }
        .book-cover .band.b { bottom: 20px; }

        /* Pages — stacked thinner strips fanning in sequence */
        .page {
            position: absolute; top: 5px; bottom: 5px;
            width: 36px;
            background: linear-gradient(to bottom, #ffffff, #f1f5f9);
            border-radius: 1px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            transform-origin: right center;
            backface-visibility: hidden;
        }
        .page.l {
            left: 16px;
            border-right: 1px solid #e2e8f0;
        }
        .page.r {
            left: 44px;
            border-left: 1px solid #e2e8f0;
            transform-origin: left center;
        }

        /* Cascade 5 layers on each side */
        .page.l:nth-child(1) { z-index: 6; animation: fanL 2.4s ease-in-out -0.5s infinite; }
        .page.l:nth-child(2) { z-index: 5; animation: fanL 2.4s ease-in-out -0.35s infinite; }
        .page.l:nth-child(3) { z-index: 4; animation: fanL 2.4s ease-in-out -0.2s infinite; }
        .page.l:nth-child(4) { z-index: 3; animation: fanL 2.4s ease-in-out -0.05s infinite; }
        .page.l:nth-child(5) { z-index: 2; animation: fanL 2.4s ease-in-out 0.1s infinite; }

        .page.r:nth-child(6) { z-index: 6; animation: fanR 2.4s ease-in-out -0.5s infinite; }
        .page.r:nth-child(7) { z-index: 5; animation: fanR 2.4s ease-in-out -0.35s infinite; }
        .page.r:nth-child(8) { z-index: 4; animation: fanR 2.4s ease-in-out -0.2s infinite; }
        .page.r:nth-child(9) { z-index: 3; animation: fanR 2.4s ease-in-out -0.05s infinite; }
        .page.r:nth-child(10){ z-index: 2; animation: fanR 2.4s ease-in-out 0.1s infinite; }

        /* Text lines on innermost pages only */
        .page.l:nth-child(3)::after, .page.r:nth-child(8)::after {
            content: ''; position: absolute; left: 6px; right: 6px; top: 14px; height: 16px;
            background: repeating-linear-gradient(180deg, #cbd5e1 0, #cbd5e1 1px, transparent 1px, transparent 6px);
            opacity: 0.4;
        }

        @keyframes fanL {
            0%, 100% { transform: rotateY(0deg) translateZ(0); }
            25% { transform: rotateY(-110deg) translateZ(1px); }
            50% { transform: rotateY(-110deg) translateZ(1px); }
            75% { transform: rotateY(0deg) translateZ(0); }
        }
        @keyframes fanR {
            0%, 100% { transform: rotateY(0deg) translateZ(0); }
            25% { transform: rotateY(110deg) translateZ(1px); }
            50% { transform: rotateY(110deg) translateZ(1px); }
            75% { transform: rotateY(0deg) translateZ(0); }
        }

        /* ─── Loading Text ─── */
        .loader-msg {
            font-size: 1.05rem; font-weight: 500; color: #0d9488;
            min-height: 1.6em;
            transition: opacity 0.35s ease;
        }
        .loader-msg .dots::after {
            content: ''; display: inline-block; width: 1.2em; text-align: left;
            animation: dotAnim 1.5s steps(3, end) infinite;
        }
        @keyframes dotAnim {
            0% { content: ''; }
            33% { content: '.'; }
            66% { content: '..'; }
            100% { content: '...'; }
        }
        .loader-msg .dots::after { content: '...'; animation: dotAnim 1.5s steps(4, end) infinite; }

        .loader-sub {
            font-size: 0.8rem; color: #94a3b8; margin-top: 6px;
        }

        /* ─── Loading bar ─── */
        .loader-bar-wrap {
            width: 180px; height: 3px; margin: 20px auto 0;
            background: #e2e8f0; border-radius: 4px; overflow: hidden;
        }
        .loader-bar-fill {
            height: 100%; width: 0%;
            background: linear-gradient(90deg, #0d9488, #14b8a6);
            border-radius: 4px;
            animation: barGrow 2.8s ease-in-out infinite;
        }
        @keyframes barGrow {
            0% { width: 0%; }
            25% { width: 30%; }
            50% { width: 65%; }
            75% { width: 85%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-teal-50 via-white to-coral-50 min-h-screen flex items-center justify-center p-4">

    <!-- ─── Loader ─── -->
    <div id="loader-screen">
        <div class="loader-content">
            <div class="book">
                <div class="book-inner">
                    <div class="book-cover">
                        <div class="band t"></div>
                        <div class="band b"></div>
                    </div>
                    <div class="page l"></div>
                    <div class="page l"></div>
                    <div class="page l"></div>
                    <div class="page l"></div>
                    <div class="page l"></div>
                    <div class="page r"></div>
                    <div class="page r"></div>
                    <div class="page r"></div>
                    <div class="page r"></div>
                    <div class="page r"></div>
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
            'Warming up the chat',
            'Organizing homework',
            'Calibrating timetables'
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
        }, 900);

        // Fade out loader after messages complete
        const totalDuration = messages.length * 900 + 1200;
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
