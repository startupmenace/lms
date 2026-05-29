<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$count = 0;
$row = db_get_row("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0", [get_user_id()]);
if ($row) $count = (int)$row['c'];

header('Content-Type: application/json');
echo json_encode(['count' => $count]);
