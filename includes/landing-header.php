<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ziada LMS — School Management Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4 { font-family: 'Space Grotesk', sans-serif; }
        .animate-float { animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%,100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-20px) rotate(1deg); } }
        .animate-pulse-slow { animation: pulseSlow 4s cubic-bezier(0.4,0,0.6,1) infinite; }
        @keyframes pulseSlow { 0%,100% { opacity: 0.5; transform: scale(1); } 50% { opacity: 0.2; transform: scale(1.1); } }
        .gradient-text { background: linear-gradient(135deg, #0d9488 0%, #f9645a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .gradient-bg { background: linear-gradient(135deg, #0d9488, #0f766e, #f9645a); }
        .feature-card { transition: all 0.4s cubic-bezier(0.4,0,0.2,1); }
        .feature-card:hover { transform: translateY(-8px) scale(1.01); box-shadow: 0 25px 50px -12px rgba(13,148,136,0.25); }
        .nav-blur { backdrop-filter: blur(16px) saturate(180%); -webkit-backdrop-filter: blur(16px) saturate(180%); }
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.6s cubic-bezier(0.4,0,0.2,1); }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        .glow-hover:hover { box-shadow: 0 0 30px rgba(249,100,90,0.3); }
        .corner-accent { position: relative; }
        .corner-accent::after { content: ''; position: absolute; top: -2px; right: -2px; width: 40px; height: 40px; border-top: 3px solid #f9645a; border-right: 3px solid #f9645a; border-radius: 0 12px 0 0; opacity: 0; transition: opacity 0.3s; }
        .corner-accent:hover::after { opacity: 1; }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/favicon.svg">
</head>
<body class="antialiased bg-white">
