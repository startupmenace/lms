<header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <button id="sidebar-toggle" class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition -ml-1 focus:outline-none focus:ring-2 focus:ring-teal-500" aria-label="Toggle sidebar">
            <i class="fas fa-bars text-lg"></i>
        </button>
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate"><?= $page_title ?? 'Dashboard' ?></h2>
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
                <a href="<?= BASE_URL ?>/modules/noticeboard/index.php" class="block px-4 py-2.5 text-center text-xs font-medium text-teal-600 hover:bg-teal-50 border-t border-gray-100 transition">
                    <i class="fas fa-bullhorn mr-1"></i> View All Notices
                </a>
            </div>
        </div>

        <!-- User Menu -->
        <div class="relative" id="user-menu-container">
            <button id="user-menu-btn" class="flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 rounded-lg" aria-label="User menu">
                <?php
                $current_user = db_get_row("SELECT * FROM users WHERE id=?", [get_user_id()]);
                echo user_avatar_html($current_user ?? ['avatar' => null, 'full_name' => get_user_name()]);
                ?>
                <span class="text-sm font-semibold text-gray-800 hidden sm:inline"><?= get_user_name() ?></span>
                <i class="fas fa-chevron-down text-[10px] text-gray-400 hidden sm:inline"></i>
            </button>
            <div id="user-dropdown" class="hidden absolute right-0 top-full mt-2 w-48 bg-white rounded-xl border border-gray-200 shadow-xl z-50 overflow-hidden">
                <a href="<?= BASE_URL ?>/modules/profile/index.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                    <i class="fas fa-user-circle text-gray-400 w-4 text-center"></i> My Profile
                </a>
                <?php if (has_role('student')): ?>
                <a href="<?= BASE_URL ?>/modules/student/profile.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                    <i class="fas fa-graduation-cap text-gray-400 w-4 text-center"></i> Student Profile
                </a>
                <?php endif; ?>
                <div class="border-t border-gray-100"></div>
                <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                    <i class="fas fa-sign-out-alt w-4 text-center"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<script>
(function() {
    // --- User Dropdown ---
    var userBtn = document.getElementById('user-menu-btn');
    var userDropdown = document.getElementById('user-dropdown');
    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
            document.getElementById('notif-dropdown').classList.add('hidden');
        });
        document.addEventListener('click', function() { userDropdown.classList.add('hidden'); });
        userDropdown.addEventListener('click', function(e) { e.stopPropagation(); });
    }

    // --- Notification Bell ---
    var bell = document.getElementById('notif-bell');
    var notifDropdown = document.getElementById('notif-dropdown');
    var notifList = document.getElementById('notif-list');
    var notifBadge = document.getElementById('notif-badge');
    var BASE = '<?= BASE_URL ?>';

    function updateNotifCount() {
        fetch(BASE + '/modules/profile/notif-count.php')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.count > 0) {
                    notifBadge.style.display = 'flex';
                    notifBadge.textContent = d.count > 99 ? '99+' : d.count;
                } else {
                    notifBadge.style.display = 'none';
                }
            });
    }

    function loadNotifications() {
        fetch(BASE + '/modules/profile/notif-list.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.length === 0) {
                    notifList.innerHTML = '<div class="p-6 text-center text-xs text-gray-400"><i class="fas fa-check-circle text-green-300 text-2xl mb-2 block"></i>No new notifications</div>';
                    return;
                }
                var html = '';
                data.forEach(function(n) {
                    var time = n.created_at ? new Date(n.created_at.replace(' ', 'T')).toLocaleDateString() : '';
                    html += '<a href="' + (n.link || '#') + '" class="notif-item flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition ' + (n.is_read ? '' : 'bg-teal-50/50') + '" data-id="' + n.id + '">';
                    html += '    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs flex-shrink-0 ' + (n.type === 'notice' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') + '">';
                    html += '        <i class="fas fa-' + (n.type === 'notice' ? 'bullhorn' : 'bell') + '"></i>';
                    html += '    </div>';
                    html += '    <div class="min-w-0 flex-1">';
                    html += '        <div class="text-xs font-medium text-gray-900 truncate">' + escapeHtml(n.title) + '</div>';
                    html += '        <div class="text-[10px] text-gray-500 truncate">' + escapeHtml((n.message || '').substring(0, 80)) + '</div>';
                    html += '        <div class="text-[9px] text-gray-400 mt-0.5">' + timeAgo(n.created_at) + '</div>';
                    html += '    </div>';
                    html += '    ' + (n.is_read ? '' : '<span class="w-2 h-2 rounded-full bg-teal-500 flex-shrink-0 mt-1.5"></span>');
                    html += '</a>';
                });
                notifList.innerHTML = html;

                // Mark as read on click
                notifList.querySelectorAll('.notif-item').forEach(function(item) {
                    item.addEventListener('click', function() {
                        var id = this.getAttribute('data-id');
                        if (id) {
                            var form = new FormData();
                            form.append('id', id);
                            fetch(BASE + '/modules/profile/notif-read.php', { method: 'POST', body: form });
                            this.classList.remove('bg-teal-50/50');
                            var dot = this.querySelector('span.w-2');
                            if (dot) dot.remove();
                            updateNotifCount();
                        }
                    });
                });
            });
    }

    if (bell && notifDropdown) {
        bell.addEventListener('click', function(e) {
            e.stopPropagation();
            var isOpen = !notifDropdown.classList.contains('hidden');
            notifDropdown.classList.toggle('hidden');
            userDropdown.classList.add('hidden');
            if (!isOpen) loadNotifications();
        });
        document.addEventListener('click', function() { notifDropdown.classList.add('hidden'); });
        notifDropdown.addEventListener('click', function(e) { e.stopPropagation(); });
    }

    document.getElementById('notif-mark-read')?.addEventListener('click', function() {
        fetch(BASE + '/modules/profile/notif-read.php', { method: 'POST', body: new FormData() });
        notifBadge.style.display = 'none';
        notifList.querySelectorAll('.notif-item').forEach(function(item) {
            item.classList.remove('bg-teal-50/50');
            var dot = item.querySelector('span.w-2');
            if (dot) dot.remove();
        });
    });

    function escapeHtml(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function timeAgo(dt) {
        if (!dt) return '';
        var d = new Date(dt.replace(' ', 'T'));
        var now = new Date();
        var diff = Math.floor((now - d) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return d.toLocaleDateString();
    }

    // Initial load and poll every 30s
    updateNotifCount();
    setInterval(updateNotifCount, 30000);
})();
</script>
