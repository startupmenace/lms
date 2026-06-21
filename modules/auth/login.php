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

        /* ─── Book Opening Animation (Elo's 3D) ─── */
        .book {
            position: relative;
            margin: 36px auto 30px;
            width: 18.5rem;
            height: 12.5rem;
            perspective: 70rem;
        }
        .cover {
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            transform: rotateY(0deg);
            width: 9.25rem;
            height: 12.5rem;
        }
        .page {
            top: 0.25rem;
            left: 0.25rem;
            background-color: #f0fdf4;
            transform: rotateY(0deg);
            width: 9rem;
            height: 12rem;
        }
        .page::before, .page::after {
            display: block;
            border-top: 1px dashed rgba(13,148,136,0.18);
            content: "";
            padding-bottom: 1rem;
        }
        .cover, .page {
            position: absolute;
            padding: 1rem;
            transform-origin: 100% 0;
            border-radius: 5px 0 0 5px;
            box-shadow: inset 3px 0 20px rgba(0,0,0,0.08),
                        0 0 15px rgba(0,0,0,0.06);
            box-sizing: border-box;
        }
        .cover.turn {
            animation: bookCover 3s forwards;
        }
        .page.turn {
            animation: bookOpen 3s forwards;
        }
        .page:nth-of-type(1) { animation-delay: 0.05s; }
        .page:nth-of-type(2) { animation-delay: 0.33s; }
        .page:nth-of-type(3) { animation-delay: 0.66s; }
        .page:nth-of-type(4) { animation: bookOpen150deg 3s forwards; animation-delay: 0.99s; }
        .page:nth-of-type(5) { animation: bookOpen30deg 3s forwards; animation-delay: 1.2s; }
        .page:nth-of-type(6) { animation: bookOpen55deg 3s forwards; animation-delay: 1.25s; }
        @keyframes bookOpen {
            30% { z-index: 999; }
            100% { transform: rotateY(180deg); z-index: 999; }
        }
        @keyframes bookCover {
            30% { z-index: 999; }
            100% { transform: rotateY(180deg); z-index: 1; }
        }
        @keyframes bookOpen150deg {
            30% { z-index: 999; }
            100% { transform: rotateY(150deg); z-index: 999; }
        }
        @keyframes bookOpen55deg {
            30% { z-index: 999; }
            100% { transform: rotateY(55deg); z-index: 999; }
        }
        @keyframes bookOpen30deg {
            50% { z-index: 999; }
            100% { transform: rotateY(30deg); z-index: 999; }
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
            <div class="book" style="font-size:10px">
              <span class="page turn"></span>
              <span class="page turn"></span>
              <span class="page turn"></span>
              <span class="page turn"></span>
              <span class="page turn"></span>
              <span class="page turn"></span>
              <span class="cover"></span>
              <span class="page"></span>
              <span class="cover turn"></span>
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

        const msgInt = setInterval(() => {
            idx = (idx + 1) % messages.length;
            el.style.opacity = '0';
            setTimeout(() => {
                el.textContent = messages[idx];
                el.style.opacity = '1';
            }, 200);
        }, 700);

        const totalDuration = messages.length * 700 + 1000;
        setTimeout(() => {
            clearInterval(msgInt);
            document.getElementById('loader-screen').classList.add('hidden');
            document.getElementById('login-content').style.opacity = '1';
            document.getElementById('login-content').style.transition = 'opacity 0.6s ease';
        }, totalDuration);
    })();
    </script>
</body>
</html>
