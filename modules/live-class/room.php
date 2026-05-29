<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$live_id = (int)($_GET['id'] ?? 0);
$live = db_get_row("SELECT lc.*, u.full_name as teacher_name FROM live_classes lc LEFT JOIN users u ON lc.teacher_id = u.id WHERE lc.id = ?", [$live_id]);

if (!$live) {
    set_flash('error', 'Live class not found.');
    redirect('index.php');
}

// Mark as live if scheduled
if ($live['status'] === 'scheduled') {
    db_query("UPDATE live_classes SET status = 'live' WHERE id = ? AND status = 'scheduled'", [$live_id]);
    $live['status'] = 'live';
}

// Handle polls
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['poll_question'])) {
        $question = sanitize($_POST['poll_question'] ?? '');
        $options = [];
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($_POST['poll_option_' . $i])) $options[] = sanitize($_POST['poll_option_' . $i]);
        }
        if (!empty($question) && count($options) >= 2) {
            db_insert("INSERT INTO polls (live_class_id, question, options) VALUES (?, ?, ?)",
                [$live_id, $question, json_encode($options)]);
            set_flash('success', 'Poll created!');
            redirect('room.php?id=' . $live_id);
        }
    }
    if (isset($_POST['poll_response'])) {
        $poll_id = (int)$_POST['poll_id'];
        $option = (int)$_POST['poll_response'];
        db_insert("INSERT INTO poll_responses (poll_id, user_id, selected_option) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE selected_option = ?",
            [$poll_id, get_user_id(), $option, $option]);
        redirect('room.php?id=' . $live_id);
    }
}

$polls = db_get_all("SELECT p.*, 
    (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as total_votes
    FROM polls p WHERE p.live_class_id = ? AND p.is_active = 1", [$live_id]);

// Record attendance
$existing = db_get_row("SELECT id FROM live_class_attendance WHERE live_class_id = ? AND user_id = ? AND status = 'active'", [$live_id, get_user_id()]);
if (!$existing) {
    db_insert("INSERT INTO live_class_attendance (live_class_id, user_id, joined_at, status) VALUES (?, ?, NOW(), 'active')", [$live_id, get_user_id()]);
    $attendance_id = db_query("SELECT LAST_INSERT_ID() as id")->fetch_assoc()['id'];
} else {
    $attendance_id = $existing['id'];
}

// AJAX: mark user as left
if (isset($_GET['leave'])) {
    $record = db_get_row("SELECT id, joined_at FROM live_class_attendance WHERE id = ? AND status = 'active'", [(int)$_GET['leave']]);
    if ($record) {
        $joined = strtotime($record['joined_at']);
        $duration = time() - $joined;
        db_query("UPDATE live_class_attendance SET left_at = NOW(), duration_seconds = ?, status = 'completed' WHERE id = ?", [$duration, $record['id']]);
    }
    exit;
}

$page_title = sanitize($live['title']) . ' - Live Class';

$room_name = JAAS_TENANT_KEY . '/jewel-' . $live_id;
$display_name = get_user_name();
$user_email = $_SESSION['user_email'] ?? '';
$is_moderator = (has_role('admin', 'teacher') && ($live['teacher_id'] == get_user_id() || has_role('admin')));
$user_id = get_user_id();

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <?php if (has_flash('success')): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm"><?= get_flash('success') ?></div>
    <?php endif; ?>

    <!-- Header Bar -->
    <div class="bg-gradient-to-r from-teal-500 to-coral-600 rounded-xl p-4 mb-6 text-white flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold"><?= sanitize($live['title']) ?></h2>
            <p class="text-white/80 text-sm mt-0.5"><?= sanitize($live['teacher_name']) ?> &middot; Scheduled: <?= format_date($live['scheduled_at'], 'd M Y h:i A') ?></p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs bg-white/20 rounded-full px-3 py-1 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span> <?= ucfirst($live['status']) ?>
            </span>
            <?php if ($live['meeting_url']): ?>
            <a href="<?= sanitize($live['meeting_url']) ?>" target="_blank" class="text-xs bg-white/20 hover:bg-white/30 rounded-full px-3 py-1 transition">
                <i class="fas fa-external-link-alt mr-1"></i> External Link
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="live-class-grid">
        <!-- Left: Video Call -->
        <div class="bg-gray-900 rounded-xl overflow-hidden relative" style="height: 70vh; min-height: 500px;">
            <div id="jaas-container" style="height: 100%; width: 100%;"></div>
        </div>

        <!-- Right: Sidebar -->
        <div class="space-y-4">
            <!-- Polls -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2 text-sm">
                    <i class="fas fa-poll text-teal-600"></i> Polls
                </h4>
                <?php if (empty($polls)): ?>
                <p class="text-xs text-gray-400">No active polls</p>
                <?php else: ?>
                <?php foreach ($polls as $p): ?>
                <div class="mb-3 p-3 bg-gray-50 rounded-lg text-sm">
                    <p class="font-medium text-gray-900 mb-2"><?= sanitize($p['question']) ?></p>
                    <?php $options = json_decode($p['options'], true) ?? []; ?>
                    <form method="POST" class="space-y-1.5">
                        <input type="hidden" name="poll_id" value="<?= $p['id'] ?>">
                        <?php foreach ($options as $i => $opt): ?>
                        <label class="flex items-center gap-2 cursor-pointer text-xs">
                            <input type="radio" name="poll_response" value="<?= $i ?>" class="text-teal-600" onchange="this.form.submit()">
                            <?= sanitize($opt) ?>
                        </label>
                        <?php endforeach; ?>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($is_moderator): ?>
                <button onclick="document.getElementById('pollForm').classList.toggle('hidden')" class="text-teal-600 text-xs hover:underline mt-2 flex items-center gap-1">
                    <i class="fas fa-plus"></i> Create Poll
                </button>
                <form id="pollForm" method="POST" class="hidden mt-3 space-y-2 border-t pt-3">
                    <input type="text" name="poll_question" placeholder="Poll question..." class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <input type="text" name="poll_option_<?= $i ?>" placeholder="Option <?= $i ?>" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                    <?php endfor; ?>
                    <button type="submit" class="bg-teal-600 text-white px-3 py-1.5 rounded text-xs font-medium">Launch Poll</button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Hand Raise -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2 text-sm">
                    <i class="fas fa-hand-paper text-amber-600"></i> Hand Raise
                </h4>
                <form method="POST" action="room.php?id=<?= $live_id ?>">
                    <button type="button" onclick="raiseHand()" class="w-full bg-amber-100 text-amber-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-amber-200 transition flex items-center justify-center gap-2">
                        <i class="fas fa-hand-paper"></i> <span id="handBtnText">Raise Hand</span>
                    </button>
                </form>
            </div>

            <!-- Participants (placeholder) -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2 text-sm">
                    <i class="fas fa-users text-teal-600"></i> In Call
                </h4>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <div class="w-6 h-6 rounded-full bg-teal-100 flex items-center justify-center text-xs font-bold text-teal-700"><?= get_avatar(get_user_name()) ?></div>
                    <span><?= sanitize(get_user_name()) ?></span>
                    <?php if ($is_moderator): ?><span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">Host</span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Control Bar (decorative - actual controls are in Jitsi) -->
    <div class="bg-white rounded-xl border border-gray-200 p-3 mt-4 flex items-center justify-center gap-4">
        <span class="text-xs text-gray-400"><i class="fas fa-info-circle mr-1"></i> Use the toolbar below for mic, camera, screen share controls</span>
        <a href="index.php" class="bg-red-100 text-red-700 px-4 py-1.5 rounded-lg text-xs font-medium hover:bg-red-200 transition flex items-center gap-1.5">
            <i class="fas fa-phone-slash"></i> Leave
        </a>
    </div>
</div>

<!-- Hidden form for hand raise (submits via JS) -->
<form id="handRaiseForm" method="POST" style="display:none;">
    <input type="hidden" name="hand_raise" value="1">
</form>

<script>
var attendanceId = <?= $attendance_id ?? 0 ?>;
window.addEventListener('beforeunload', function() {
    if (attendanceId > 0) {
        navigator.sendBeacon('room.php?leave=' + attendanceId);
    }
});
</script>
<script src="https://<?= JAAS_DOMAIN ?>/<?= JAAS_TENANT_KEY ?>/external_api.js" async></script>
<script>
window.onload = () => {
    try {
        const api = new JitsiMeetExternalAPI("<?= JAAS_DOMAIN ?>", {
            roomName: "<?= $room_name ?>",
            parentNode: document.querySelector('#jaas-container'),
            userInfo: {
                displayName: "<?= addslashes($display_name) ?>",
                email: "<?= addslashes($user_email) ?>"
            },
            configOverrides: {
                startWithAudioMuted: true,
                startWithVideoMuted: true,
                disableDeepLinking: true,
                prejoinPageEnabled: false,
                toolbarAlwaysVisible: true,
            },
            interfaceConfigOverrides: {
                SHOW_JITSI_WATERMARK: false,
                SHOW_WATERMARK_FOR_GUESTS: false,
                TOOLBAR_ALWAYS_VISIBLE: true,
                TOOLBAR_BUTTONS: [
                    'microphone', 'camera', 'desktop', 'fullscreen',
                    'fodeviceselection', 'hangup', 'chat',
                    'settings', 'raisehand', 'tileview', 'sharedvideo'
                ]
            }
        });

        // Listen for hand raise from Jitsi
        api.addListener('raise-hand-updated', function (participants) {
            console.log('Hands raised:', participants);
        });

    } catch (e) {
        document.getElementById('jaas-container').innerHTML = 
            '<div class="flex items-center justify-center h-full text-white/60 text-sm">' +
            '<div class="text-center"><i class="fas fa-video-slash text-5xl mb-4 block"></i>' +
            '<p>Could not load video call. <a href="index.php" class="text-teal-400 hover:underline">Go back</a></p></div></div>';
    }
};

function raiseHand() {
    const btn = document.getElementById('handBtnText');
    if (btn.textContent === 'Raise Hand') {
        btn.textContent = 'Lower Hand';
        btn.closest('button').classList.add('bg-amber-300');
    } else {
        btn.textContent = 'Raise Hand';
        btn.closest('button').classList.remove('bg-amber-300');
    }
    // Use Jitsi API if available
    if (typeof api !== 'undefined' && api) {
        api.executeCommand('toggleRaiseHand');
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
