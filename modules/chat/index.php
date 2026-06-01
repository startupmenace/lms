<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('chat');

$page_title = 'Chat';
$user_id = get_user_id();
$user_role = get_user_role();

$tab = $_GET['tab'] ?? 'chat';

// ─── POST Handlers ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Send group message
    if ($action === 'send_group') {
        $group_id = (int)($_POST['group_id'] ?? 0);
        $message = sanitize($_POST['message'] ?? '');
        if ($group_id && $message) {
            $group = db_get_row("SELECT type, is_reply_allowed FROM chat_groups WHERE id=?", [$group_id]);
            $is_member = db_get_row("SELECT id FROM chat_group_members WHERE group_id=? AND user_id=?", [$group_id, $user_id]);
            if ($group && $is_member && ($group['is_reply_allowed'] || has_role('admin', 'teacher'))) {
                db_insert("INSERT INTO chat_messages (sender_id, group_id, message) VALUES (?,?,?)", [$user_id, $group_id, $message]);
            }
        }
        redirect('?group=' . $group_id);
    }

    // Send DM
    if ($action === 'send_dm') {
        $receiver_id = (int)($_POST['receiver_id'] ?? 0);
        $message = sanitize($_POST['message'] ?? '');
        if ($receiver_id && $message) {
            db_insert("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?,?,?)", [$user_id, $receiver_id, $message]);
        }
        redirect('?dm=' . $receiver_id);
    }

    // Create group (admin)
    if ($action === 'create_group' && has_role('admin')) {
        $name = sanitize($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'club';
        $class_id = (int)($_POST['class_id'] ?? 0) ?: null;
        $description = sanitize($_POST['description'] ?? '');
        $members = $_POST['members'] ?? [];
        $is_reply_allowed = ($type !== 'general') ? 1 : 0;

        $gid = db_insert("INSERT INTO chat_groups (name,type,class_id,description,created_by,is_reply_allowed) VALUES (?,?,?,?,?,?)",
            [$name, $type, $class_id, $description, $user_id, $is_reply_allowed]);

        // Add creator as admin
        db_insert("INSERT INTO chat_group_members (group_id,user_id,role) VALUES (?,?,'admin')", [$gid, $user_id]);
        // Add selected members
        foreach ($members as $mid) {
            $mid = (int)$mid;
            if ($mid) db_insert("INSERT IGNORE INTO chat_group_members (group_id,user_id,role) VALUES (?,?,'member')", [$gid, $mid]);
        }
        set_flash('success', 'Group created');
        redirect('?tab=groups');
    }

    // Add member (admin)
    if ($action === 'add_member' && has_role('admin')) {
        $gid = (int)$_POST['group_id'];
        $uid = (int)$_POST['user_id'];
        if ($gid && $uid) {
            db_insert("INSERT IGNORE INTO chat_group_members (group_id,user_id,role) VALUES (?,?,'member')", [$gid, $uid]);
            set_flash('success', 'Member added');
        }
        redirect('?tab=groups');
    }

    // Remove member (admin)
    if ($action === 'remove_member' && has_role('admin')) {
        $gid = (int)$_POST['group_id'];
        $uid = (int)$_POST['user_id'];
        if ($gid && $uid) {
            db_query("DELETE FROM chat_group_members WHERE group_id=? AND user_id=?", [$gid, $uid]);
            set_flash('success', 'Member removed');
        }
        redirect('?tab=groups');
    }
}

// ─── Auto-join user to relevant groups ───
$already_member = db_get_row("SELECT id FROM chat_group_members WHERE user_id=? LIMIT 1", [$user_id]);
if (!$already_member || has_role('admin')) {
    // Admin: join all groups
    if (has_role('admin')) {
        $all_groups_for_admin = db_get_all("SELECT id FROM chat_groups WHERE is_active=1");
        foreach ($all_groups_for_admin as $g) {
            db_query("INSERT IGNORE INTO chat_group_members (group_id,user_id,role) VALUES (?,?,'admin')", [$g['id'], $user_id]);
        }
    }
    // Teachers: join all class groups
    if (has_role('teacher')) {
        $class_groups = db_get_all("SELECT id FROM chat_groups WHERE type='class' AND is_active=1");
        foreach ($class_groups as $g) {
            db_query("INSERT IGNORE INTO chat_group_members (group_id,user_id,role) VALUES (?,?,'member')", [$g['id'], $user_id]);
        }
        // Also join General
        db_query("INSERT IGNORE INTO chat_group_members (group_id,user_id,role) VALUES (1,?,'member')", [$user_id]);
    }
    // Students: join their class group
    if (has_role('student')) {
        $student_class = db_get_row("SELECT s.class_id FROM students s WHERE s.user_id=?", [$user_id]);
        if ($student_class && $student_class['class_id']) {
            $class_chat = db_get_row("SELECT id FROM chat_groups WHERE type='class' AND class_id=? AND is_active=1", [$student_class['class_id']]);
            if ($class_chat) {
                db_query("INSERT IGNORE INTO chat_group_members (group_id,user_id,role) VALUES (?,?,'member')", [$class_chat['id'], $user_id]);
            }
        }
        // Students also see General (but can't reply)
        db_query("INSERT IGNORE INTO chat_group_members (group_id,user_id,role) VALUES (1,?,'member')", [$user_id]);
    }
}

// ─── Load conversations for sidebar ───
$my_groups = [];
if (has_role('admin', 'teacher')) {
    $my_groups = db_get_all("SELECT cg.*,
        (SELECT cm.message FROM chat_messages cm WHERE cm.group_id=cg.id ORDER BY cm.created_at DESC LIMIT 1) as last_msg,
        (SELECT cm.created_at FROM chat_messages cm WHERE cm.group_id=cg.id ORDER BY cm.created_at DESC LIMIT 1) as last_time,
        (SELECT COUNT(*) FROM chat_messages cm WHERE cm.group_id=cg.id AND cm.sender_id!=? AND cm.is_read=0) as unread
        FROM chat_groups cg WHERE cg.is_active=1 ORDER BY cg.type, cg.name", [$user_id]);
} else {
    $my_groups = db_get_all("SELECT cg.*,
        (SELECT cm.message FROM chat_messages cm WHERE cm.group_id=cg.id ORDER BY cm.created_at DESC LIMIT 1) as last_msg,
        (SELECT cm.created_at FROM chat_messages cm WHERE cm.group_id=cg.id ORDER BY cm.created_at DESC LIMIT 1) as last_time,
        (SELECT COUNT(*) FROM chat_messages cm WHERE cm.group_id=cg.id AND cm.sender_id!=? AND cm.is_read=0) as unread
        FROM chat_groups cg
        JOIN chat_group_members cgm ON cgm.group_id=cg.id AND cgm.user_id=?
        WHERE cg.is_active=1 ORDER BY cg.type, cg.name", [$user_id, $user_id]);
}

// DM conversations
$dm_users = db_get_all("SELECT DISTINCT u.id, u.full_name, u.role,
    (SELECT cm.message FROM chat_messages cm WHERE (cm.sender_id=u.id AND cm.receiver_id=?) OR (cm.sender_id=? AND cm.receiver_id=u.id) ORDER BY cm.created_at DESC LIMIT 1) as last_msg,
    (SELECT cm.created_at FROM chat_messages cm WHERE (cm.sender_id=u.id AND cm.receiver_id=?) OR (cm.sender_id=? AND cm.receiver_id=u.id) ORDER BY cm.created_at DESC LIMIT 1) as last_time,
    (SELECT COUNT(*) FROM chat_messages cm WHERE cm.sender_id=u.id AND cm.receiver_id=? AND cm.is_read=0) as unread
    FROM users u
    WHERE u.id!=? AND EXISTS (
        SELECT 1 FROM chat_messages cm WHERE (cm.sender_id=u.id AND cm.receiver_id=?) OR (cm.sender_id=? AND cm.receiver_id=u.id)
    )
    ORDER BY last_time DESC", [$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);

// All users (for new DM)
$all_users = db_get_all("SELECT id, full_name, role FROM users WHERE id!=? AND is_active=1 ORDER BY full_name", [$user_id]);

// Active conversation
$active_group = (int)($_GET['group'] ?? 0);
$active_dm = (int)($_GET['dm'] ?? 0);
$messages = [];

if ($active_group) {
    $active_group_data = db_get_row("SELECT * FROM chat_groups WHERE id=?", [$active_group]);
    $messages = db_get_all("SELECT cm.*, u.full_name as sender_name FROM chat_messages cm JOIN users u ON cm.sender_id=u.id WHERE cm.group_id=? ORDER BY cm.created_at ASC LIMIT 100", [$active_group]);
    // Mark group messages as read
    db_query("UPDATE chat_messages SET is_read=1 WHERE group_id=? AND sender_id!=?", [$active_group, $user_id]);
} elseif ($active_dm) {
    $active_dm_data = db_get_row("SELECT id, full_name, role FROM users WHERE id=?", [$active_dm]);
    $messages = db_get_all("SELECT cm.*, u.full_name as sender_name FROM chat_messages cm JOIN users u ON cm.sender_id=u.id WHERE (cm.sender_id=? AND cm.receiver_id=?) OR (cm.sender_id=? AND cm.receiver_id=?) ORDER BY cm.created_at ASC LIMIT 100", [$user_id, $active_dm, $active_dm, $user_id]);
    db_query("UPDATE chat_messages SET is_read=1 WHERE sender_id=? AND receiver_id=?", [$active_dm, $user_id]);
}

// For student header vs admin/teacher header
$is_student = ($user_role === 'student');
include __DIR__ . '/../../includes/' . ($is_student ? 'student-header.php' : 'header.php');
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chat.css">

<?php if ($tab === 'groups'): ?>
    <?php include __DIR__ . '/_groups.php'; ?>
<?php else: ?>

<div class="chat-layout max-w-7xl mx-auto rounded-xl border border-gray-200 overflow-hidden bg-white shadow-sm">
    <!-- ─── Sidebar ─── -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-search">
            <input type="text" id="chat-search" placeholder="Search or start new chat" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div class="chat-sidebar-list" id="conversation-list">
            <!-- General -->
            <?php $general_groups = array_filter($my_groups, fn($g) => $g['type'] === 'general'); ?>
            <?php if (!empty($general_groups)): ?>
            <div class="chat-section-header">Announcements</div>
            <?php foreach ($general_groups as $g): ?>
                <a href="?group=<?= $g['id'] ?>" class="chat-convo-item <?= $active_group == $g['id'] ? 'active' : '' ?>" data-name="<?= sanitize(strtolower($g['name'])) ?>">
                    <div class="chat-convo-avatar bg-amber-100 text-amber-700"><i class="fas fa-bullhorn"></i></div>
                    <div class="chat-convo-info">
                        <div class="chat-convo-name"><?= sanitize($g['name']) ?></div>
                        <div class="chat-convo-preview"><?= sanitize($g['last_msg'] ?? 'No messages yet') ?></div>
                    </div>
                    <div class="chat-convo-meta">
                        <div class="chat-convo-time"><?= $g['last_time'] ? time_ago($g['last_time']) : '' ?></div>
                        <?php if (($g['unread'] ?? 0) > 0): ?>
                        <div class="chat-convo-badge"><?= min($g['unread'], 99) ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Class Groups -->
            <?php $class_groups = array_filter($my_groups, fn($g) => $g['type'] === 'class'); ?>
            <?php if (!empty($class_groups)): ?>
            <div class="chat-section-header">Classes</div>
            <?php foreach ($class_groups as $g): ?>
                <a href="?group=<?= $g['id'] ?>" class="chat-convo-item <?= $active_group == $g['id'] ? 'active' : '' ?>" data-name="<?= sanitize(strtolower($g['name'])) ?>">
                    <div class="chat-convo-avatar bg-blue-100 text-blue-700"><i class="fas fa-chalkboard"></i></div>
                    <div class="chat-convo-info">
                        <div class="chat-convo-name"><?= sanitize($g['name']) ?></div>
                        <div class="chat-convo-preview"><?= sanitize($g['last_msg'] ?? 'No messages yet') ?></div>
                    </div>
                    <div class="chat-convo-meta">
                        <div class="chat-convo-time"><?= $g['last_time'] ? time_ago($g['last_time']) : '' ?></div>
                        <?php if (($g['unread'] ?? 0) > 0): ?>
                        <div class="chat-convo-badge"><?= min($g['unread'], 99) ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Clubs -->
            <?php $club_groups = array_filter($my_groups, fn($g) => $g['type'] === 'club'); ?>
            <?php if (!empty($club_groups)): ?>
            <div class="chat-section-header">Clubs</div>
            <?php foreach ($club_groups as $g): ?>
                <a href="?group=<?= $g['id'] ?>" class="chat-convo-item <?= $active_group == $g['id'] ? 'active' : '' ?>" data-name="<?= sanitize(strtolower($g['name'])) ?>">
                    <div class="chat-convo-avatar bg-purple-100 text-purple-700"><i class="fas fa-futbol"></i></div>
                    <div class="chat-convo-info">
                        <div class="chat-convo-name"><?= sanitize($g['name']) ?></div>
                        <div class="chat-convo-preview"><?= sanitize($g['last_msg'] ?? 'No messages yet') ?></div>
                    </div>
                    <div class="chat-convo-meta">
                        <div class="chat-convo-time"><?= $g['last_time'] ? time_ago($g['last_time']) : '' ?></div>
                        <?php if (($g['unread'] ?? 0) > 0): ?>
                        <div class="chat-convo-badge"><?= min($g['unread'], 99) ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- DMs -->
            <?php if (!empty($dm_users)): ?>
            <div class="chat-section-header">Direct Messages</div>
            <?php foreach ($dm_users as $u): ?>
                <a href="?dm=<?= $u['id'] ?>" class="chat-convo-item <?= $active_dm == $u['id'] ? 'active' : '' ?>" data-name="<?= sanitize(strtolower($u['full_name'])) ?>">
                    <div class="chat-convo-avatar bg-teal-100 text-teal-700"><?= get_avatar($u['full_name']) ?></div>
                    <div class="chat-convo-info">
                        <div class="chat-convo-name"><?= sanitize($u['full_name']) ?></div>
                        <div class="chat-convo-preview"><?= sanitize($u['last_msg'] ?? 'Start chatting') ?></div>
                    </div>
                    <div class="chat-convo-meta">
                        <div class="chat-convo-time"><?= $u['last_time'] ? time_ago($u['last_time']) : '' ?></div>
                        <?php if (($u['unread'] ?? 0) > 0): ?>
                        <div class="chat-convo-badge"><?= min($u['unread'], 99) ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (empty($my_groups) && empty($dm_users)): ?>
            <div class="chat-empty py-12">
                <i class="fas fa-comments"></i>
                <p class="text-sm mt-2">No conversations yet</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ─── Main Chat Area ─── -->
    <div class="chat-main">
        <?php if ($active_group): ?>
            <?php
            $can_reply = $active_group_data['is_reply_allowed'] || has_role('admin', 'teacher');
            $header_icon = $active_group_data['type'] == 'general' ? 'bg-amber-100 text-amber-700 fa-bullhorn' : ($active_group_data['type'] == 'class' ? 'bg-blue-100 text-blue-700 fa-chalkboard' : 'bg-purple-100 text-purple-700 fa-futbol');
            $member_count = db_get_row("SELECT COUNT(*) as c FROM chat_group_members WHERE group_id=?", [$active_group])['c'];
            ?>
            <div class="chat-header">
                <div class="chat-header-avatar <?= explode(' ', $header_icon)[0] . ' ' . explode(' ', $header_icon)[1] ?>"><i class="fas <?= explode(' ', $header_icon)[2] ?>"></i></div>
                <div class="chat-header-info">
                    <div class="chat-header-name"><?= sanitize($active_group_data['name']) ?></div>
                    <div class="chat-header-meta"><?= $member_count ?> members</div>
                </div>
            </div>

            <div class="chat-messages" id="chat-messages">
                <?php if (empty($messages)): ?>
                <div class="chat-empty">
                    <i class="fas fa-comment-dots"></i>
                    <p class="text-sm mt-2">No messages yet</p>
                </div>
                <?php else: ?>
                <?php
                $last_date = '';
                foreach ($messages as $msg):
                    $msg_date = date('Y-m-d', strtotime($msg['created_at']));
                    $is_mine = $msg['sender_id'] == $user_id;
                ?>
                    <?php if ($msg_date != $last_date): $last_date = $msg_date; ?>
                    <div class="chat-date-divider"><span><?= date('d M Y', strtotime($msg['created_at'])) ?></span></div>
                    <?php endif; ?>
                    <div class="chat-msg <?= $is_mine ? 'sent' : 'received' ?>">
                        <?php if (!$is_mine): ?><div class="text-[10px] font-semibold mb-0.5 opacity-70"><?= sanitize($msg['sender_name']) ?></div><?php endif; ?>
                        <div><?= sanitize($msg['message']) ?></div>
                        <div class="msg-time">
                            <?= date('h:i A', strtotime($msg['created_at'])) ?>
                            <?php if ($is_mine): ?><span class="msg-read"><i class="fas fa-check<?= $msg['is_read'] ? '-double' : '' ?>"></i></span><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-reply">
                <?php if ($can_reply): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="send_group">
                    <input type="hidden" name="group_id" value="<?= $active_group ?>">
                    <input type="text" name="message" required placeholder="Type a message..." autocomplete="off">
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
                <?php else: ?>
                <div class="chat-reply-disabled"><i class="fas fa-lock mr-1"></i> Announcements — replies are disabled</div>
                <?php endif; ?>
            </div>

        <?php elseif ($active_dm): ?>
            <div class="chat-header">
                <div class="chat-header-avatar bg-teal-100 text-teal-700"><?= get_avatar($active_dm_data['full_name']) ?></div>
                <div class="chat-header-info">
                    <div class="chat-header-name"><?= sanitize($active_dm_data['full_name']) ?></div>
                    <div class="chat-header-meta"><?= ucfirst($active_dm_data['role']) ?></div>
                </div>
            </div>

            <div class="chat-messages" id="chat-messages">
                <?php if (empty($messages)): ?>
                <div class="chat-empty">
                    <i class="fas fa-comment-dots"></i>
                    <p class="text-sm mt-2">No messages yet</p>
                </div>
                <?php else: ?>
                <?php
                $last_date = '';
                foreach ($messages as $msg):
                    $msg_date = date('Y-m-d', strtotime($msg['created_at']));
                    $is_mine = $msg['sender_id'] == $user_id;
                ?>
                    <?php if ($msg_date != $last_date): $last_date = $msg_date; ?>
                    <div class="chat-date-divider"><span><?= date('d M Y', strtotime($msg['created_at'])) ?></span></div>
                    <?php endif; ?>
                    <div class="chat-msg <?= $is_mine ? 'sent' : 'received' ?>">
                        <div><?= sanitize($msg['message']) ?></div>
                        <div class="msg-time">
                            <?= date('h:i A', strtotime($msg['created_at'])) ?>
                            <?php if ($is_mine): ?><span class="msg-read"><i class="fas fa-check<?= $msg['is_read'] ? '-double' : '' ?>"></i></span><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-reply">
                <form method="POST">
                    <input type="hidden" name="action" value="send_dm">
                    <input type="hidden" name="receiver_id" value="<?= $active_dm ?>">
                    <input type="text" name="message" required placeholder="Type a message..." autocomplete="off">
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>

        <?php else: ?>
            <!-- No conversation selected -->
            <div class="chat-empty">
                <i class="fas fa-comments text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-500 mb-1">Chat</h3>
                <p class="text-sm text-gray-400 mb-4">Send and receive messages in groups or privately</p>
                <div class="flex gap-2">
                    <?php if (has_role('admin')): ?>
                    <a href="?tab=groups" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition"><i class="fas fa-cog mr-1"></i> Manage Groups</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick start DM -->
            <div class="border-t border-gray-200 px-5 py-3 bg-white">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Start a conversation</p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($all_users as $u): ?>
                    <a href="?dm=<?= $u['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-teal-50 rounded-full text-xs font-medium text-gray-700 hover:text-teal-700 transition">
                        <span class="w-5 h-5 rounded-full bg-teal-100 flex items-center justify-center text-[8px] font-bold text-teal-700"><?= get_avatar($u['full_name']) ?></span>
                        <?= sanitize(explode(' ', $u['full_name'])[0]) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var BASE = '<?= BASE_URL ?>';
    var activeGroup = <?= $active_group ?: 'null' ?>;
    var activeDm = <?= $active_dm ?: 'null' ?>;
    var msgsEl = document.getElementById('chat-messages');
    var searchInput = document.getElementById('chat-search');

    // Auto-scroll to bottom
    function scrollToBottom() {
        if (msgsEl) msgsEl.scrollTop = msgsEl.scrollHeight;
    }
    scrollToBottom();

    // Conversation search filter
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll('.chat-convo-item').forEach(function(item) {
                var name = item.getAttribute('data-name') || '';
                item.style.display = !q || name.includes(q) ? 'flex' : 'none';
            });
        });
    }

    // Poll for new unread counts every 5s
    function updateUnread() {
        fetch(BASE + '/modules/chat/ajax-unread.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.querySelectorAll('.chat-convo-item').forEach(function(item) {
                    var href = item.getAttribute('href') || '';
                    var badge = item.querySelector('.chat-convo-badge');
                    var groupMatch = href.match(/group=(\d+)/);
                    var dmMatch = href.match(/dm=(\d+)/);
                    var count = 0;
                    if (groupMatch && data.groups[groupMatch[1]]) count = data.groups[groupMatch[1]];
                    if (dmMatch && data.dms[dmMatch[1]]) count = data.dms[dmMatch[1]];
                    if (count > 0) {
                        if (badge) { badge.textContent = Math.min(count, 99); }
                        else {
                            var meta = item.querySelector('.chat-convo-meta');
                            if (meta) {
                                var b = document.createElement('div');
                                b.className = 'chat-convo-badge';
                                b.textContent = Math.min(count, 99);
                                meta.appendChild(b);
                            }
                        }
                    } else {
                        if (badge) badge.remove();
                    }
                });
            });
    }

    // Poll for new messages every 3s
    function pollMessages() {
        if (!activeGroup && !activeDm) return;
        var since = '<?= !empty($messages) ? end($messages)['created_at'] : date('Y-m-d H:i:s', 0) ?>';
        var url = BASE + '/modules/chat/ajax-messages.php?since=' + encodeURIComponent(since);
        if (activeGroup) url += '&group_id=' + activeGroup;
        else if (activeDm) url += '&dm_id=' + activeDm;

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.length === 0) return;
                var lastDate = msgsEl ? (msgsEl.querySelector('.chat-msg:last-child')?.getAttribute('data-date') || '') : '';
                // Clear empty state
                var empty = msgsEl?.querySelector('.chat-empty');
                if (empty) empty.remove();

                data.forEach(function(msg) {
                    var isMine = parseInt(msg.sender_id) === <?= $user_id ?>;
                    var dateDiv = document.createElement('div');
                    dateDiv.className = 'chat-date-divider';

                    var msgDiv = document.createElement('div');
                    msgDiv.className = 'chat-msg ' + (isMine ? 'sent' : 'received');
                    msgDiv.setAttribute('data-date', msg.created_at);
                    if (!isMine && activeGroup) {
                        var nameEl = document.createElement('div');
                        nameEl.className = 'text-[10px] font-semibold mb-0.5 opacity-70';
                        nameEl.textContent = msg.sender_name || '';
                        msgDiv.appendChild(nameEl);
                    }
                    var textEl = document.createElement('div');
                    textEl.textContent = msg.message || '';
                    msgDiv.appendChild(textEl);
                    var timeEl = document.createElement('div');
                    timeEl.className = 'msg-time';
                    var d = new Date(msg.created_at.replace(' ', 'T'));
                    timeEl.textContent = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    if (isMine) {
                        var readEl = document.createElement('span');
                        readEl.className = 'msg-read';
                        readEl.innerHTML = '<i class="fas fa-check' + (msg.is_read ? '-double' : '') + '"></i>';
                        timeEl.appendChild(readEl);
                    }
                    msgDiv.appendChild(timeEl);
                    if (msgsEl) msgsEl.appendChild(msgDiv);
                });
                scrollToBottom();
            });
    }

    // Poll intervals
    updateUnread();
    setInterval(updateUnread, 5000);
    setInterval(pollMessages, 3000);
})();
</script>
<?php endif; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
