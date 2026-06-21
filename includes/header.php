<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? SITE_NAME ?> | <?= SITE_NAME ?></title>
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
    <style>#app-loader{position:fixed;inset:0;z-index:9999;background:#0f172a;display:flex;flex-direction:column;align-items:center;justify-content:center;transition:opacity .4s}#app-loader.hide{opacity:0;pointer-events:none}.ldr-book{width:60px;height:40px;position:relative;perspective:120px}.ldr-book .page{position:absolute;width:30px;height:40px;background:linear-gradient(135deg,#14b8a6,#0d9488);border-radius:2px;transform-origin:left center;animation:bookFlip 1.4s ease-in-out infinite}.ldr-book .page:nth-child(2){animation-delay:.2s;background:linear-gradient(135deg,#f97316,#ea580c)}.ldr-book .page:nth-child(3){animation-delay:.4s;background:linear-gradient(135deg,#8b5cf6,#7c3aed)}@keyframes bookFlip{0%,100%{transform:rotateY(0)}50%{transform:rotateY(-180deg)}}.ldr-text{margin-top:20px;font-size:13px;font-weight:600;letter-spacing:2px;color:#14b8a6;text-transform:uppercase;animation:pulseText 1.6s ease-in-out infinite}@keyframes pulseText{0%,100%{opacity:.4}50%{opacity:1}}</style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/favicon.svg">
</head>
<body class="bg-gray-50 font-sans antialiased">

<div id="app-loader">
    <div class="ldr-book"><div class="page"></div><div class="page"></div><div class="page"></div></div>
    <div class="ldr-text">Loading</div>
</div>
<script>window.addEventListener('load',function(){var l=document.getElementById('app-loader');if(l)setTimeout(function(){l.classList.add('hide')},300)})</script>

    <?php if (is_logged_in()): ?>
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-30 lg:hidden"></div>
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">
            <?php include __DIR__ . '/topbar.php'; ?>
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            <?php if ($flash = get_flash('success')): ?>
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center gap-2">
                <i class="fas fa-check-circle"></i> <?= sanitize($flash) ?>
            </div>
            <?php elseif ($flash = get_flash('error')): ?>
            <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i> <?= sanitize($flash) ?>
            </div>
            <?php endif; ?>
    <?php endif; ?>
