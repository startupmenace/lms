<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$user_id = get_user_id();
header('Content-Type: application/json');

$unread = ['groups' => [], 'dms' => []];

// Unread group messages — count messages after the user's last read mark
// For simplicity: count unread where user hasn't read (track via a last_read table or is_read)
// Using a simpler approach: count messages in groups user belongs to where is_read=0 and sender != user
$groups = db_get_all("SELECT gm.group_id FROM chat_group_members gm WHERE gm.user_id=?", [$user_id]);
foreach ($groups as $g) {
    $row = db_get_row("SELECT COUNT(*) as c FROM chat_messages WHERE group_id=? AND sender_id!=? AND is_read=0", [$g['group_id'], $user_id]);
    if ($row && $row['c'] > 0) $unread['groups'][$g['group_id']] = (int)$row['c'];
}

// Unread DMs — messages where receiver_id = user_id and is_read=0
$dms = db_get_all("SELECT sender_id, COUNT(*) as c FROM chat_messages WHERE receiver_id=? AND is_read=0 GROUP BY sender_id", [$user_id]);
foreach ($dms as $d) {
    $unread['dms'][$d['sender_id']] = (int)$d['c'];
}

echo json_encode($unread);
