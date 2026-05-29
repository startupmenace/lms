<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$notifications = db_get_all("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 10", [get_user_id()]);

header('Content-Type: application/json');
echo json_encode($notifications);
