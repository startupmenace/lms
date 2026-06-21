<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$target_role = $_GET['role'] ?? '';
$profiles = $_SESSION['user_profiles'] ?? [];

if (!in_array($target_role, $profiles)) {
    set_flash('error', 'Invalid profile switch');
    redirect(get_user_dashboard());
}

$_SESSION['user_role'] = $target_role;

if ($target_role === 'student') {
    redirect(BASE_URL . '/modules/student/dashboard.php');
}
if ($target_role === 'parent') {
    redirect(BASE_URL . '/modules/parent/dashboard.php');
}
redirect(BASE_URL . '/modules/dashboard/index.php');
