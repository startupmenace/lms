<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_user_name() {
    return $_SESSION['user_name'] ?? 'Guest';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
}

function require_role(...$roles) {
    require_login();
    foreach ($roles as $role) {
        if ($_SESSION['user_role'] === $role) return;
    }
    // Redirect to their own dashboard if logged in but wrong role
    $user_role = $_SESSION['user_role'] ?? null;
    if ($user_role === 'student') {
        header('Location: ' . BASE_URL . '/modules/student/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
    }
    exit;
}

function has_role(...$roles) {
    foreach ($roles as $role) {
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role) return true;
    }
    return false;
}

function get_user_dashboard() {
    $role = get_user_role();
    if ($role === 'student') return BASE_URL . '/modules/student/dashboard.php';
    return BASE_URL . '/modules/dashboard/index.php';
}

function logout() {
    session_destroy();
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}
