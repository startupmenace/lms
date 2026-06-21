<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: login.php?error=invalid');
    exit;
}

$row = db_get_row("SELECT * FROM super_admins WHERE email = ? LIMIT 1", [$email]);

if (!$row || !password_verify($password, $row['password'])) {
    header('Location: login.php?error=invalid');
    exit;
}

$_SESSION['super_admin_id']   = $row['id'];
$_SESSION['super_admin_email'] = $row['email'];
$_SESSION['super_admin_name']  = $row['full_name'];

header('Location: index.php');
exit;
