        </main>
    </div>
</div>
<?php include __DIR__ . '/chatbot.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
(function() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    var toggle = document.getElementById('sidebar-toggle');
    var close = document.getElementById('sidebar-close');
    var body = document.body;

    console.log('Student sidebar init:', { sidebar: !!sidebar, overlay: !!overlay, toggle: !!toggle, close: !!close });

    function openSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.add('open');
        body.classList.add('overflow-hidden', 'lg:overflow-auto');
    }
    function closeSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.add('-translate-x-full');
        overlay.classList.remove('open');
        body.classList.remove('overflow-hidden', 'lg:overflow-auto');
    }
    if (toggle) toggle.addEventListener('click', openSidebar);
    if (close) close.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);
})();
</script>
<script>
(function() {
    var BASE = '<?= BASE_URL ?>';

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

    function updateNotifCount() {
        fetch(BASE + '/modules/profile/notif-count.php')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.count > 0) { notifBadge.style.display = 'flex'; notifBadge.textContent = d.count > 99 ? '99+' : d.count; }
                else { notifBadge.style.display = 'none'; }
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
                    var timeAgo = (function(dt) { if (!dt) return ''; var d = new Date(dt.replace(' ', 'T')); var diff = Math.floor((new Date() - d) / 1000); if (diff < 60) return 'Just now'; if (diff < 3600) return Math.floor(diff / 60) + 'm ago'; if (diff < 86400) return Math.floor(diff / 3600) + 'h ago'; return d.toLocaleDateString(); })(n.created_at);
                    var esc = (function(str) { if (!str) return ''; var d = document.createElement('div'); d.textContent = str; return d.innerHTML; });
                    html += '<a href="' + (n.link || '#') + '" class="notif-item flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition ' + (n.is_read ? '' : 'bg-teal-50/50') + '" data-id="' + n.id + '">';
                    html += '<div class="w-7 h-7 rounded-full flex items-center justify-center text-xs flex-shrink-0 bg-amber-100 text-amber-700"><i class="fas fa-bullhorn"></i></div>';
                    html += '<div class="min-w-0 flex-1"><div class="text-xs font-medium text-gray-900 truncate">' + esc(n.title) + '</div><div class="text-[10px] text-gray-500 truncate">' + esc((n.message || '').substring(0, 80)) + '</div><div class="text-[9px] text-gray-400 mt-0.5">' + timeAgo + '</div></div>';
                    html += (n.is_read ? '' : '<span class="w-2 h-2 rounded-full bg-teal-500 flex-shrink-0 mt-1.5"></span>');
                    html += '</a>';
                });
                notifList.innerHTML = html;
                notifList.querySelectorAll('.notif-item').forEach(function(item) {
                    item.addEventListener('click', function() {
                        var id = this.getAttribute('data-id');
                        if (id) { var f = new FormData(); f.append('id', id); fetch(BASE + '/modules/profile/notif-read.php', { method: 'POST', body: f }); this.classList.remove('bg-teal-50/50'); var dot = this.querySelector('span.w-2'); if (dot) dot.remove(); updateNotifCount(); }
                    });
                });
            });
    }
    if (bell && notifDropdown) {
        bell.addEventListener('click', function(e) { e.stopPropagation(); var isOpen = !notifDropdown.classList.contains('hidden'); notifDropdown.classList.toggle('hidden'); if (userDropdown) userDropdown.classList.add('hidden'); if (!isOpen) loadNotifications(); });
        document.addEventListener('click', function() { notifDropdown.classList.add('hidden'); });
        notifDropdown.addEventListener('click', function(e) { e.stopPropagation(); });
    }
    document.getElementById('notif-mark-read')?.addEventListener('click', function() {
        fetch(BASE + '/modules/profile/notif-read.php', { method: 'POST', body: new FormData() });
        notifBadge.style.display = 'none';
        notifList.querySelectorAll('.notif-item').forEach(function(item) { item.classList.remove('bg-teal-50/50'); var dot = item.querySelector('span.w-2'); if (dot) dot.remove(); });
    });
    updateNotifCount();
    setInterval(updateNotifCount, 30000);
})();
</script>
</body>
</html>
