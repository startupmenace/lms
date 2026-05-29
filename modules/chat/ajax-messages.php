<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$user_id = get_user_id();
header('Content-Type: application/json');

$group_id = (int)($_GET['group_id'] ?? 0);
$dm_id = (int)($_GET['dm_id'] ?? 0);
$since = $_GET['since'] ?? date('Y-m-d H:i:s', 0);

if ($group_id) {
    $msgs = db_get_all("SELECT cm.*, u.full_name as sender_name
        FROM chat_messages cm
        JOIN users u ON cm.sender_id=u.id
        WHERE cm.group_id=? AND cm.created_at > ?
        ORDER BY cm.created_at ASC", [$group_id, $since]);
    // Mark as read
    db_query("UPDATE chat_messages SET is_read=1 WHERE group_id=? AND sender_id!=?", [$group_id, $user_id]);
} elseif ($dm_id) {
    $msgs = db_get_all("SELECT cm.*, u.full_name as sender_name
        FROM chat_messages cm
        JOIN users u ON cm.sender_id=u.id
        WHERE ((cm.sender_id=? AND cm.receiver_id=?) OR (cm.sender_id=? AND cm.receiver_id=?)) AND cm.created_at > ?
        ORDER BY cm.created_at ASC", [$dm_id, $user_id, $user_id, $dm_id, $since]);
    // Mark incoming as read
    db_query("UPDATE chat_messages SET is_read=1 WHERE sender_id=? AND receiver_id=?", [$dm_id, $user_id]);
} else {
    echo json_encode([]);
    exit;
}

echo json_encode($msgs);
