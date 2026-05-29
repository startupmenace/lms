<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    db_query("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?", [$id, get_user_id()]);
} else {
    // Mark all as read
    db_query("UPDATE notifications SET is_read=1 WHERE user_id=?", [get_user_id()]);
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
