    <?php if (is_logged_in()): ?>
            </main>
        </div>
    </div>
    <?php include __DIR__ . '/chatbot.php'; ?>
    <?php endif; ?>
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    <?php if (is_logged_in()): ?>
    <script>
    (function() {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        var toggle = document.getElementById('sidebar-toggle');
        var close = document.getElementById('sidebar-close');
        var body = document.body;

        console.log('Sidebar init:', { sidebar: !!sidebar, overlay: !!overlay, toggle: !!toggle, close: !!close });

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
    document.addEventListener('click', function(e) {
        var el = e.target.closest('[data-confirm]');
        if (!el) return;
        if (!confirm(el.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    });
    </script>
    <?php endif; ?>
</body>
</html>
