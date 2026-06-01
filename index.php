<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    $role = get_user_role();
    header('Location: ' . ($role === 'student' ? 'modules/student/dashboard.php' : 'modules/dashboard/index.php'));
    exit;
}

require_once __DIR__ . '/includes/landing-header.php';
?>

<!-- ====== STICKY NAV ====== -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 nav-blur border-b border-gray-100/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            <a href="/" class="flex items-center gap-2.5 text-xl font-bold">
                <span class="w-9 h-9 bg-gradient-to-br from-teal-500 to-coral-500 rounded-xl flex items-center justify-center text-white text-sm"><i class="fas fa-graduation-cap"></i></span>
                <span class="text-gray-900">Ziada LMS</span>
            </a>
            <div class="hidden lg:flex items-center gap-8 text-sm font-medium text-gray-600">
                <a href="#features" class="hover:text-teal-600 transition">Features</a>
                <a href="#modules" class="hover:text-teal-600 transition">Modules</a>
                <a href="#contact" class="hover:text-teal-600 transition">Pricing</a>
                <a href="#testimonials" class="hover:text-teal-600 transition">Testimonials</a>
                <a href="#faq" class="hover:text-teal-600 transition">FAQ</a>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="modules/auth/login.php" class="hidden sm:inline-flex text-gray-600 hover:text-gray-900 text-sm font-medium transition">Sign In</a>
                <a href="modules/auth/login.php" class="bg-gradient-to-r from-teal-500 to-coral-500 text-white px-4 sm:px-5 py-2.5 rounded-xl text-sm font-semibold hover:shadow-lg hover:shadow-teal-200 transition flex items-center gap-2 whitespace-nowrap">
                    <i class="fas fa-rocket hidden sm:inline"></i> Get Started
                </a>
                <button id="menu-toggle" class="lg:hidden w-10 h-10 flex items-center justify-center rounded-xl text-gray-600 hover:bg-gray-100 transition" aria-label="Toggle menu">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="mobile-menu" class="lg:hidden hidden border-t border-gray-100 bg-white/95 nav-blur">
        <div class="px-4 py-4 space-y-2">
            <a href="#features" class="block px-4 py-3 rounded-xl text-sm font-medium text-gray-600 hover:bg-teal-50 hover:text-teal-700 transition" onclick="closeMenu()">Features</a>
            <a href="#modules" class="block px-4 py-3 rounded-xl text-sm font-medium text-gray-600 hover:bg-teal-50 hover:text-teal-700 transition" onclick="closeMenu()">Modules</a>
                    <a href="#contact" class="block px-4 py-3 rounded-xl text-sm font-medium text-gray-600 hover:bg-teal-50 hover:text-teal-700 transition" onclick="closeMenu()">Pricing</a>
            <a href="#testimonials" class="block px-4 py-3 rounded-xl text-sm font-medium text-gray-600 hover:bg-teal-50 hover:text-teal-700 transition" onclick="closeMenu()">Testimonials</a>
            <a href="#faq" class="block px-4 py-3 rounded-xl text-sm font-medium text-gray-600 hover:bg-teal-50 hover:text-teal-700 transition" onclick="closeMenu()">FAQ</a>
            <hr class="border-gray-100 my-2">
            <a href="modules/auth/login.php" class="block w-full text-center bg-gradient-to-r from-teal-500 to-coral-500 text-white px-5 py-3 rounded-xl text-sm font-semibold transition" onclick="closeMenu()">Sign In</a>
        </div>
    </div>
</nav>

<!-- ====== HERO ====== -->
<section class="relative min-h-screen flex items-center pt-20 overflow-hidden bg-gradient-to-br from-teal-50/80 via-white to-coral-50/80">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-teal-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse-slow"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-coral-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse-slow" style="animation-delay:2s"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-br from-teal-100/20 to-transparent rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-12 lg:py-0">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div class="text-center lg:text-left">
                <div class="inline-flex items-center gap-2 bg-teal-100/80 border border-teal-200/50 text-teal-700 px-4 py-1.5 rounded-full text-sm font-medium mb-6 backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-teal-600 animate-pulse"></span>
                    Now serving 500+ schools across Africa
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-7xl font-bold text-gray-900 leading-[1.1] tracking-tight">
                    Empower Your<br>
                    <span class="gradient-text">School's Future</span>
                </h1>
                <p class="text-lg sm:text-xl text-gray-600 mt-6 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    From attendance and exams to fees and live classes — Ziada LMS brings every aspect of school management into one powerful, beautifully designed platform.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 mt-10 justify-center lg:justify-start">
                    <a href="modules/auth/login.php" class="bg-gradient-to-r from-teal-500 to-coral-500 text-white px-8 py-4 rounded-xl text-base font-semibold hover:shadow-xl hover:shadow-teal-200/50 transition-all inline-flex items-center justify-center gap-3 group">
                        <i class="fas fa-sign-in-alt group-hover:translate-x-1 transition"></i> Sign In to Dashboard
                    </a>
                    <a href="#modules" class="border-2 border-gray-200 text-gray-700 px-8 py-4 rounded-xl text-base font-semibold hover:border-teal-300 hover:bg-teal-50/30 transition-all inline-flex items-center justify-center gap-2">
                        <i class="fas fa-play-circle"></i> Explore Features
                    </a>
                </div>
                <div class="flex flex-wrap items-center gap-6 mt-10 justify-center lg:justify-start">
                    <div class="flex -space-x-2">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-teal-400 to-coral-500 flex items-center justify-center text-white text-xs font-bold border-2 border-white">AP</div>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-teal-500 flex items-center justify-center text-white text-xs font-bold border-2 border-white">VS</div>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white text-xs font-bold border-2 border-white">AK</div>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-400 to-rose-500 flex items-center justify-center text-white text-xs font-bold border-2 border-white">+2k</div>
                    </div>
                    <div class="text-sm text-gray-500"><span class="font-bold text-gray-900">2,000+</span> educators trust us</div>
                </div>
            </div>
            <div class="relative">
                <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 p-3 animate-float">
                    <div class="flex items-center gap-2 mb-3 px-3 pt-1">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-xs text-gray-400 ml-2 font-medium">Ziada LMS — Dashboard</span>
                    </div>
                    <img src="ref-images/LMS1.png" alt="Ziada LMS Dashboard" class="w-full rounded-xl" onerror="this.style.display='none'">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 mt-3 px-1 pb-1">
                        <div class="bg-gradient-to-br from-teal-50 to-teal-100/50 rounded-xl p-3 text-center">
                            <p class="text-xs font-semibold text-teal-700">Students</p>
                            <p class="text-2xl font-bold text-gray-900">10k</p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100/50 rounded-xl p-3 text-center">
                            <p class="text-xs font-semibold text-green-700">Attendance</p>
                            <p class="text-2xl font-bold text-gray-900">95%</p>
                        </div>
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100/50 rounded-xl p-3 text-center">
                            <p class="text-xs font-semibold text-amber-700">Exams</p>
                            <p class="text-2xl font-bold text-gray-900">24</p>
                        </div>
                        <div class="bg-gradient-to-br from-coral-50 to-coral-100/50 rounded-xl p-3 text-center">
                            <p class="text-xs font-semibold text-coral-700">Live</p>
                            <p class="text-2xl font-bold text-gray-900">3</p>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-6 -left-6 w-24 h-24 bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl -rotate-12 flex items-center justify-center shadow-lg hidden lg:flex">
                    <div class="text-center text-white"><p class="text-2xl font-bold">99%</p><p class="text-[10px] font-medium">Uptime</p></div>
                </div>
                <div class="absolute -top-4 -right-4 w-20 h-20 bg-gradient-to-br from-green-400 to-teal-500 rounded-2xl rotate-12 flex items-center justify-center shadow-lg hidden lg:flex">
                    <div class="text-center text-white"><p class="text-lg font-bold">24/7</p><p class="text-[10px] font-medium">Support</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== FEATURES OVERVIEW ====== -->
<section id="features" class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-teal-600 font-semibold text-sm uppercase tracking-[0.2em]">Everything You Need</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mt-4 leading-tight">All-in-One School <span class="gradient-text">Management Platform</span></h2>
            <p class="text-gray-500 text-lg mt-4">From classroom management to financial tracking, Ziada LMS equips your school with every tool to thrive in the digital age.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <div class="feature-card bg-white border border-gray-200 rounded-2xl p-8 group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-teal-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-teal-100 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-teal-600 transition-all"><i class="fas fa-user-graduate text-2xl text-teal-600 group-hover:text-white transition-colors"></i></div>
                    <h3 class="text-xl font-bold text-gray-900">Student Management</h3>
                    <p class="text-gray-500 mt-3 leading-relaxed">Complete student profiles with demographics, parent information, academic records, and document management — all in one place.</p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Detailed profiles &amp; enrollment</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Parent &amp; guardian portal</li>
                    </ul>
                </div>
            </div>
            <div class="feature-card bg-white border border-gray-200 rounded-2xl p-8 group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-green-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-green-600 transition-all"><i class="fas fa-calendar-check text-2xl text-green-600 group-hover:text-white transition-colors"></i></div>
                    <h3 class="text-xl font-bold text-gray-900">Attendance Tracking</h3>
                    <p class="text-gray-500 mt-3 leading-relaxed">Real-time attendance marking with powerful analytics, visual charts, and daily/weekly/monthly reports at your fingertips.</p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Daily bulk attendance</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Trends &amp; analytics dashboard</li>
                    </ul>
                </div>
            </div>
            <div class="feature-card bg-white border border-gray-200 rounded-2xl p-8 group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-amber-600 transition-all"><i class="fas fa-pen-clip text-2xl text-amber-600 group-hover:text-white transition-colors"></i></div>
                    <h3 class="text-xl font-bold text-gray-900">Exam &amp; Tests</h3>
                    <p class="text-gray-500 mt-3 leading-relaxed">Create and schedule exams, generate tests with MCQs and subjective questions, auto-grade, and track performance.</p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> MCQ &amp; subjective questions</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Difficulty &amp; marks control</li>
                    </ul>
                </div>
            </div>
            <div class="feature-card bg-white border border-gray-200 rounded-2xl p-8 group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-coral-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-coral-100 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-coral-600 transition-all"><i class="fas fa-wallet text-2xl text-coral-600 group-hover:text-white transition-colors"></i></div>
                    <h3 class="text-xl font-bold text-gray-900">Fee Management</h3>
                    <p class="text-gray-500 mt-3 leading-relaxed">Create recurring fee structures, track collections in real time, send payment reminders, and generate professional invoices.</p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Multi-step fee wizard</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Automated reminders</li>
                    </ul>
                </div>
            </div>
            <div class="feature-card bg-white border border-gray-200 rounded-2xl p-8 group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-rose-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-rose-100 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-rose-600 transition-all"><i class="fas fa-video text-2xl text-rose-600 group-hover:text-white transition-colors"></i></div>
                    <h3 class="text-xl font-bold text-gray-900">Live Classes</h3>
                    <p class="text-gray-500 mt-3 leading-relaxed">Schedule and conduct virtual classes with interactive polls, hand raise, screen sharing, and integrated Zoom/Meet support.</p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Real-time polls &amp; quizzes</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Meeting link integration</li>
                    </ul>
                </div>
            </div>
            <div class="feature-card bg-white border border-gray-200 rounded-2xl p-8 group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-cyan-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-cyan-100 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-cyan-600 transition-all"><i class="fas fa-comments text-2xl text-cyan-600 group-hover:text-white transition-colors"></i></div>
                    <h3 class="text-xl font-bold text-gray-900">Communication Hub</h3>
                    <p class="text-gray-500 mt-3 leading-relaxed">Built-in chat, notice board announcements, and direct messaging keep teachers, students, and parents connected.</p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Real-time chat system</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Notice board with targeting</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== DEEP-DIVE: MODULE SHOWCASE ====== -->
<section id="modules" class="py-20 lg:py-28 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-teal-600 font-semibold text-sm uppercase tracking-[0.2em]">Deep Dive</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mt-4 leading-tight">Every Module, <span class="gradient-text">Beautifully Crafted</span></h2>
        </div>

        <!-- Attendance Module -->
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-20 items-center mb-16 lg:mb-24">
            <div>
                <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center mb-6"><i class="fas fa-calendar-check text-2xl text-green-600"></i></div>
                <h3 class="text-2xl lg:text-3xl font-bold text-gray-900">Smart Attendance<br><span class="gradient-text">Analytics at a Glance</span></h3>
                <p class="text-gray-600 mt-4 leading-relaxed">Mark attendance in seconds with our intuitive interface. Get instant visual feedback with color-coded status indicators and comprehensive analytics.</p>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Bulk marking</strong> — Mark entire class at once with one click</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Trend charts</strong> — Daily, weekly, and monthly attendance trends</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Export reports</strong> — Download ready-to-share attendance reports</p></li>
                </ul>
            </div>
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4">
                <img src="ref-images/attendance.png" alt="Attendance Module" class="w-full rounded-xl" onerror="this.innerHTML='<div class=bg-gray-100 rounded-xl p-12 text-center text-gray-400><i class=\'fas fa-calendar-check text-6xl mb-4 block\'></i><p>Attendance Dashboard Preview</p></div>'">
            </div>
        </div>

        <!-- Exam Planner Module -->
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-20 items-center mb-16 lg:mb-24">
            <div class="order-last lg:order-first">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4">
                    <img src="ref-images/web-sol-2.webp" alt="Exam Planner" class="w-full rounded-xl" onerror="this.innerHTML='<div class=bg-gray-100 rounded-xl p-12 text-center text-gray-400><i class=\'fas fa-calendar-alt text-6xl mb-4 block\'></i><p>Exam Planner Preview</p></div>'">
                </div>
            </div>
            <div>
                <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center mb-6"><i class="fas fa-calendar-alt text-2xl text-amber-600"></i></div>
                <h3 class="text-2xl lg:text-3xl font-bold text-gray-900">Exam Planner<br><span class="gradient-text">Schedule with Confidence</span></h3>
                <p class="text-gray-600 mt-4 leading-relaxed">Plan your entire academic calendar with our visual exam scheduler. Drag-and-drop simplicity meets powerful timetable management.</p>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Visual timetable</strong> — See all exams in a clean grid layout</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Subject-wise</strong> — Assign subjects with max marks &amp; pass marks</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Class grouping</strong> — Filter and view by class or grade</p></li>
                </ul>
            </div>
        </div>

        <!-- Fee Management Module -->
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-20 items-center mb-16 lg:mb-24">
            <div>
                <div class="w-14 h-14 bg-coral-100 rounded-2xl flex items-center justify-center mb-6"><i class="fas fa-file-invoice-dollar text-2xl text-coral-600"></i></div>
                <h3 class="text-2xl lg:text-3xl font-bold text-gray-900">Fee Management<br><span class="gradient-text">Complete Financial Control</span></h3>
                <p class="text-gray-600 mt-4 leading-relaxed">From creating recurring fee structures to tracking payments and sending automated reminders — take full control of your school's finances.</p>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Multi-step wizard</strong> — Classes &rarr; Details &rarr; Fee types in 3 steps</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Collection tracking</strong> — Real-time paid vs due per class</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Smart reminders</strong> — Automated payment reminder system</p></li>
                </ul>
            </div>
            <div>
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4">
                    <img src="ref-images/fee-management-2.webp" alt="Fee Management" class="w-full rounded-xl" onerror="this.innerHTML='<div class=bg-gray-100 rounded-xl p-12 text-center text-gray-400><i class=\'fas fa-file-invoice-dollar text-6xl mb-4 block\'></i><p>Fee Management Preview</p></div>'">
                </div>
            </div>
        </div>

        <!-- Evaluation & Test Creation -->
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-20 items-center mb-16 lg:mb-24">
            <div class="order-last lg:order-first">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4">
                    <img src="ref-images/test.png" alt="Test Creation" class="w-full rounded-xl" onerror="this.innerHTML='<div class=bg-gray-100 rounded-xl p-12 text-center text-gray-400><i class=\'fas fa-flask text-6xl mb-4 block\'></i><p>Test Creation Preview</p></div>'">
                </div>
            </div>
            <div>
                <div class="w-14 h-14 bg-rose-100 rounded-2xl flex items-center justify-center mb-6"><i class="fas fa-flask text-2xl text-rose-600"></i></div>
                <h3 class="text-2xl lg:text-3xl font-bold text-gray-900">Test Builder &amp;<br><span class="gradient-text">Evaluation Engine</span></h3>
                <p class="text-gray-600 mt-4 leading-relaxed">Create rich assessments with mixed question types, set difficulty levels, and evaluate submissions with detailed feedback tools.</p>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>MCQ + Subjective</strong> — Mix question types in one test</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Difficulty slider</strong> — Easy &rarr; Medium &rarr; Hard per question</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Feedback system</strong> — Grade, comment, request resubmission</p></li>
                </ul>
            </div>
        </div>

        <!-- Live Class -->
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-20 items-center mb-16 lg:mb-24">
            <div>
                <div class="w-14 h-14 bg-sky-100 rounded-2xl flex items-center justify-center mb-6"><i class="fas fa-video text-2xl text-sky-600"></i></div>
                <h3 class="text-2xl lg:text-3xl font-bold text-gray-900">Live Virtual<br><span class="gradient-text">Classroom Experience</span></h3>
                <p class="text-gray-600 mt-4 leading-relaxed">Conduct engaging live classes with interactive features. Keep students participative with polls, hand raise, and real-time chat.</p>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Live polls</strong> — Create instant polls during class</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Hand raise</strong> — Student attention management</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Zoom/Meet</strong> — Integrate with any meeting platform</p></li>
                </ul>
            </div>
            <div>
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4">
                    <img src="ref-images/web-sol-8.webp" alt="Live Class" class="w-full rounded-xl" onerror="this.innerHTML='<div class=bg-gray-100 rounded-xl p-12 text-center text-gray-400><i class=\'fas fa-video text-6xl mb-4 block\'></i><p>Live Class Preview</p></div>'">
                </div>
            </div>
        </div>

        <!-- Chat & Communication -->
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div class="order-last lg:order-first">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4 max-w-sm mx-auto">
                    <img src="ref-images/chat.png" alt="Chat" class="w-full rounded-xl" onerror="this.innerHTML='<div class=bg-gray-100 rounded-xl p-12 text-center text-gray-400><i class=\'fas fa-comments text-6xl mb-4 block\'></i><p>Chat Preview</p></div>'">
                </div>
            </div>
            <div>
                <div class="w-14 h-14 bg-cyan-100 rounded-2xl flex items-center justify-center mb-6"><i class="fas fa-comments text-2xl text-cyan-600"></i></div>
                <h3 class="text-2xl lg:text-3xl font-bold text-gray-900">Instant Communication<br><span class="gradient-text">Stay Connected Always</span></h3>
                <p class="text-gray-600 mt-4 leading-relaxed">Built-in messaging and notice board keep your school community connected. Announcements reach the right people instantly.</p>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>1-on-1 chat</strong> — Direct messaging between teachers and students</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Notice board</strong> — Targeted announcements by audience</p></li>
                    <li class="flex items-start gap-3"><div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-xs text-green-600"></i></div><p class="text-sm text-gray-600"><strong>Read receipts</strong> — Know when messages are seen</p></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ====== ENRICHING EXPERIENCE ====== -->
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div>
                <span class="text-teal-600 font-semibold text-sm uppercase tracking-[0.2em]">Enriching Experience</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mt-4 leading-tight">An Ecosystem That<br><span class="gradient-text">Benefits Everyone</span></h2>
                <p class="text-gray-500 mt-4 text-lg leading-relaxed">Ziada LMS isn't just software — it's a complete ecosystem designed to make every stakeholder's life easier and more productive.</p>
                <div class="mt-10 grid sm:grid-cols-2 gap-6">
                    <div class="bg-teal-50/50 rounded-2xl p-6 border border-teal-100/50">
                        <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center mb-3"><i class="fas fa-user-graduate text-lg text-teal-600"></i></div>
                        <h4 class="font-bold text-gray-900">For Students</h4>
                        <p class="text-sm text-gray-600 mt-2">Track grades, view attendance, take tests, and communicate with teachers — all from one portal.</p>
                    </div>
                    <div class="bg-green-50/50 rounded-2xl p-6 border border-green-100/50">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mb-3"><i class="fas fa-chalkboard-teacher text-lg text-green-600"></i></div>
                        <h4 class="font-bold text-gray-900">For Teachers</h4>
                        <p class="text-sm text-gray-600 mt-2">Mark attendance, create tests, grade submissions, and conduct live classes effortlessly.</p>
                    </div>
                    <div class="bg-coral-50/50 rounded-2xl p-6 border border-coral-100/50">
                        <div class="w-10 h-10 bg-coral-100 rounded-xl flex items-center justify-center mb-3"><i class="fas fa-user-tie text-lg text-coral-600"></i></div>
                        <h4 class="font-bold text-gray-900">For Admin</h4>
                        <p class="text-sm text-gray-600 mt-2">Full oversight of students, fees, exams, attendance, and staff with powerful analytics.</p>
                    </div>
                    <div class="bg-amber-50/50 rounded-2xl p-6 border border-amber-100/50">
                        <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center mb-3"><i class="fas fa-users text-lg text-amber-600"></i></div>
                        <h4 class="font-bold text-gray-900">For Parents</h4>
                        <p class="text-sm text-gray-600 mt-2">Stay informed with real-time updates on attendance, fees, exam results, and school notices.</p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-3xl p-8 border border-gray-200">
                <img src="ref-images/enriching-exp-2.svg" alt="Enriching Experience" class="w-full" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</section>

<!-- ====== INTERNATIONAL PROGRAMS ====== -->
<section class="py-20 lg:py-28 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div class="bg-white rounded-3xl p-8 border border-gray-200 shadow-lg">
                <img src="ref-images/international-enriching-exp-1.svg" alt="International Programs" class="w-full" onerror="this.style.display='none'">
            </div>
            <div>
                <span class="text-teal-600 font-semibold text-sm uppercase tracking-[0.2em]">Global Reach</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mt-4 leading-tight">International<br><span class="gradient-text">Programs &amp; Exchange</span></h2>
                <p class="text-gray-500 mt-4 text-lg leading-relaxed">Support for international curricula, multi-language interfaces, and student exchange program management — designed for globally-minded institutions.</p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <span class="bg-teal-100 text-teal-700 px-4 py-2 rounded-xl text-sm font-medium">IB Curriculum</span>
                    <span class="bg-green-100 text-green-700 px-4 py-2 rounded-xl text-sm font-medium">Cambridge</span>
                    <span class="bg-coral-100 text-coral-700 px-4 py-2 rounded-xl text-sm font-medium">Multi-Language</span>
                    <span class="bg-amber-100 text-amber-700 px-4 py-2 rounded-xl text-sm font-medium">Exchange Programs</span>
                    <span class="bg-rose-100 text-rose-700 px-4 py-2 rounded-xl text-sm font-medium">Global Grading</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== HOW IT WORKS ====== -->
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-teal-600 font-semibold text-sm uppercase tracking-[0.2em]">Getting Started</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mt-4 leading-tight">Get Your School Online in <span class="gradient-text">3 Simple Steps</span></h2>
        </div>
        <div class="grid md:grid-cols-3 gap-8 lg:gap-12 relative">
            <div class="hidden md:block absolute top-12 left-[16%] right-[16%] h-0.5 bg-gradient-to-r from-teal-200 via-coral-200 to-transparent"></div>
            <div class="text-center relative">
                <div class="w-16 h-16 bg-gradient-to-br from-teal-500 to-coral-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-teal-200 relative z-10"><span class="text-2xl font-bold text-white">1</span></div>
                <h3 class="text-xl font-bold text-gray-900">Create Your Account</h3>
                <p class="text-gray-500 mt-3">Sign up, set up your school profile, and invite teachers and staff to join your platform.</p>
            </div>
            <div class="text-center relative">
                <div class="w-16 h-16 bg-gradient-to-br from-coral-500 to-coral-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-coral-200 relative z-10"><span class="text-2xl font-bold text-white">2</span></div>
                <h3 class="text-xl font-bold text-gray-900">Configure Everything</h3>
                <p class="text-gray-500 mt-3">Set up classes, fee structures, exam schedules, attendance policies, and communication channels.</p>
            </div>
            <div class="text-center relative">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-green-200 relative z-10"><span class="text-2xl font-bold text-white">3</span></div>
                <h3 class="text-xl font-bold text-gray-900">Go Live &amp; Grow</h3>
                <p class="text-gray-500 mt-3">Start managing students, conducting classes, tracking attendance, and collecting fees seamlessly.</p>
            </div>
        </div>
    </div>
</section>

<!-- ====== TESTIMONIALS ====== -->
<section id="testimonials" class="py-20 lg:py-28 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-teal-600 font-semibold text-sm uppercase tracking-[0.2em]">Testimonials</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mt-4 leading-tight">What School Leaders <span class="gradient-text">Say About Us</span></h2>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm hover:shadow-lg transition-all">
                <div class="flex text-amber-400 gap-1 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="text-gray-600 leading-relaxed">"Ziada LMS transformed how we manage our school in Nairobi. Attendance tracking alone saved us hours every week. The fee management module is a game-changer."</p>
                <div class="mt-6 pt-4 border-t border-gray-100 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-sm font-bold text-teal-700">JM</div>
                    <div><p class="text-sm font-bold text-gray-900">Jane Mwangi</p><p class="text-xs text-gray-500">Principal</p></div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm hover:shadow-lg transition-all">
                <div class="flex text-amber-400 gap-1 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="text-gray-600 leading-relaxed">"The exam planner and test creation tools are incredible. We created our entire mid-term schedule in under an hour. Our students love the portal."</p>
                <div class="mt-6 pt-4 border-t border-gray-100 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-sm font-bold text-green-700">PO</div>
                    <div><p class="text-sm font-bold text-gray-900">Peter Otieno</p><p class="text-xs text-gray-500">Vice Principal</p></div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm hover:shadow-lg transition-all">
                <div class="flex text-amber-400 gap-1 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="text-gray-600 leading-relaxed">"Having everything — attendance, fees, exams, chat — in one platform eliminated our need for 4 different tools. The ROI has been incredible for our school."</p>
                <div class="mt-6 pt-4 border-t border-gray-100 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-coral-100 flex items-center justify-center text-sm font-bold text-coral-700">FK</div>
                    <div><p class="text-sm font-bold text-gray-900">Faith Kamau</p><p class="text-xs text-gray-500">Director</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== CONTACT / QUOTE ====== -->
<section id="contact" class="py-20 lg:py-28 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="text-teal-600 font-semibold text-sm uppercase tracking-[0.2em]">Get Started</span>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mt-4 leading-tight">Contact Us Today to<br><span class="gradient-text">Get a Quote</span></h2>
        <p class="text-gray-500 text-lg mt-4 max-w-2xl mx-auto">Ready to streamline your school management? Reach out to our team and we'll tailor a plan that fits your institution's needs.</p>
        <div class="mt-12 grid sm:grid-cols-3 gap-6 max-w-2xl mx-auto">
            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center mx-auto mb-4"><i class="fas fa-envelope text-xl text-teal-600"></i></div>
                <h4 class="font-bold text-gray-900 text-sm">Email Us</h4>
                <p class="text-gray-500 text-sm mt-1">hello@ziadalms.com</p>
            </div>
            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                <div class="w-12 h-12 bg-coral-100 rounded-xl flex items-center justify-center mx-auto mb-4"><i class="fas fa-phone text-xl text-coral-600"></i></div>
                <h4 class="font-bold text-gray-900 text-sm">Call Us</h4>
                <p class="text-gray-500 text-sm mt-1">+254 703 388 261</p>
            </div>
            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mx-auto mb-4"><i class="fab fa-whatsapp text-xl text-amber-600"></i></div>
                <h4 class="font-bold text-gray-900 text-sm">WhatsApp</h4>
                <p class="text-gray-500 text-sm mt-1">+254 703 388 261</p>
            </div>
        </div>
    </div>
</section>

<!-- ====== FAQ ====== -->
<section id="faq" class="py-20 lg:py-28 bg-gray-50">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-teal-600 font-semibold text-sm uppercase tracking-[0.2em]">FAQ</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mt-4 leading-tight">Frequently Asked <span class="gradient-text">Questions</span></h2>
        </div>
        <div class="space-y-4">
            <details class="bg-white rounded-2xl border border-gray-200 p-6 group open:border-teal-200 open:shadow-sm transition-all">
                <summary class="flex items-center justify-between cursor-pointer font-semibold text-gray-900 list-none">
                    What is Ziada LMS and who is it for?
                    <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition"></i>
                </summary>
                <p class="mt-4 text-gray-600 leading-relaxed">Ziada LMS is a comprehensive school management platform designed for K-12 schools, coaching institutes, and educational organizations. It serves administrators, teachers, students, and parents.</p>
            </details>
            <details class="bg-white rounded-2xl border border-gray-200 p-6 group open:border-teal-200 open:shadow-sm transition-all">
                <summary class="flex items-center justify-between cursor-pointer font-semibold text-gray-900 list-none">
                    Can I import existing student data?
                    <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition"></i>
                </summary>
                <p class="mt-4 text-gray-600 leading-relaxed">Absolutely. Ziada LMS supports bulk import from CSV/Excel files. Our onboarding team will help you migrate your existing data seamlessly.</p>
            </details>
            <details class="bg-white rounded-2xl border border-gray-200 p-6 group open:border-teal-200 open:shadow-sm transition-all">
                <summary class="flex items-center justify-between cursor-pointer font-semibold text-gray-900 list-none">
                    Is my data secure?
                    <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition"></i>
                </summary>
                <p class="mt-4 text-gray-600 leading-relaxed">Security is our top priority. All data is encrypted in transit and at rest. We follow industry best practices and conduct regular security audits.</p>
            </details>
            <details class="bg-white rounded-2xl border border-gray-200 p-6 group open:border-teal-200 open:shadow-sm transition-all">
                <summary class="flex items-center justify-between cursor-pointer font-semibold text-gray-900 list-none">
                    Do you offer training for staff?
                    <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition"></i>
                </summary>
                <p class="mt-4 text-gray-600 leading-relaxed">Yes! All paid plans include onboarding training for your staff. We provide video tutorials, documentation, and live training sessions to ensure smooth adoption.</p>
            </details>
        </div>
    </div>
</section>

<!-- ====== FINAL CTA ====== -->
<section class="relative py-20 lg:py-28 overflow-hidden gradient-bg">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-20 -right-20 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight">Ready to Transform<br>Your School?</h2>
        <p class="text-teal-100 mt-6 text-lg max-w-2xl mx-auto">Join schools across East Africa already using Ziada LMS to streamline operations, engage students, and empower teachers.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center mt-10">
            <a href="modules/auth/login.php" class="bg-white text-teal-700 px-10 py-4 rounded-xl text-lg font-bold hover:bg-teal-50 transition-all shadow-2xl inline-flex items-center justify-center gap-3 group">
                <i class="fas fa-sign-in-alt group-hover:translate-x-1 transition"></i> Sign In Free
            </a>
        </div>
        <p class="text-teal-200 text-sm mt-6">Get in touch with our team for a personalised demo and quote</p>
    </div>
</section>

<!-- ====== FOOTER ====== -->
<footer class="bg-gray-900 text-gray-400 pt-16 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-12 pb-12 border-b border-gray-800">
            <div class="lg:col-span-2">
                <h3 class="text-white text-xl font-bold mb-4 flex items-center gap-2">
                    <span class="w-9 h-9 bg-gradient-to-br from-teal-500 to-coral-600 rounded-xl flex items-center justify-center text-white text-sm"><i class="fas fa-graduation-cap"></i></span>
                    Ziada LMS
                </h3>
                <p class="text-sm leading-relaxed max-w-sm">A complete school management platform designed for modern education. Empowering schools across East Africa.</p>
                <div class="flex gap-4 mt-6">
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-teal-600 transition"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-teal-600 transition"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-teal-600 transition"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-teal-600 transition"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Platform</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="#features" class="hover:text-white transition">Features</a></li>
                    <li><a href="#contact" class="hover:text-white transition">Pricing</a></li>
                    <li><a href="#" class="hover:text-white transition">Integrations</a></li>
                    <li><a href="#" class="hover:text-white transition">API</a></li>
                    <li><a href="#" class="hover:text-white transition">Changelog</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Company</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="#" class="hover:text-white transition">About Us</a></li>
                    <li><a href="#" class="hover:text-white transition">Blog</a></li>
                    <li><a href="#" class="hover:text-white transition">Careers</a></li>
                    <li><a href="#" class="hover:text-white transition">Press Kit</a></li>
                    <li><a href="#" class="hover:text-white transition">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Support</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                    <li><a href="#" class="hover:text-white transition">Documentation</a></li>
                    <li><a href="#faq" class="hover:text-white transition">FAQ</a></li>
                    <li><a href="#" class="hover:text-white transition">Status</a></li>
                    <li><a href="#" class="hover:text-white transition">Community</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
            <p>&copy; <?= date('Y') ?> Ziada LMS. All rights reserved.</p>
            <div class="flex gap-6">
                <a href="#" class="hover:text-white transition">Privacy Policy</a>
                <a href="#" class="hover:text-white transition">Terms of Service</a>
                <a href="#" class="hover:text-white transition">Cookie Policy</a>
            </div>
        </div>
    </div>
</footer>

<script>
(function() {
    var btn = document.getElementById('menu-toggle');
    var menu = document.getElementById('mobile-menu');
    if (btn && menu) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });
        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    }
})();
</script>
<?php require_once __DIR__ . '/includes/landing-footer.php'; ?>
