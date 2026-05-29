<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/modules/auth/login.php');
}

$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    set_flash('error', 'Please enter both email and password.');
    redirect(BASE_URL . '/modules/auth/login.php');
}

$user = db_get_row("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);

if (!$user || !password_verify($password, $user['password'])) {
    set_flash('error', 'Invalid email or password.');
    redirect(BASE_URL . '/modules/auth/login.php');
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_email'] = $user['email'];

if ($user['role'] === 'student') {
    redirect(BASE_URL . '/modules/student/dashboard.php');
}
redirect(BASE_URL . '/modules/dashboard/index.php');
