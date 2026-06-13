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
    } elseif ($user_role === 'parent') {
        header('Location: ' . BASE_URL . '/modules/parent/dashboard.php');
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
    if ($role === 'parent') return BASE_URL . '/modules/parent/dashboard.php';
    return BASE_URL . '/modules/dashboard/index.php';
}

function logout() {
    session_destroy();
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

function get_role_permissions() {
    $role = get_user_role();
    if (empty($role)) return [];
    if (isset($_SESSION['_perms']) && $_SESSION['_perms']['role'] === $role) {
        return $_SESSION['_perms']['modules'];
    }
    if ($role === 'admin') {
        $modules = ['*'];
        $_SESSION['_perms'] = ['role' => $role, 'modules' => $modules];
        return $modules;
    }
    $rows = db_get_all(
        "SELECT rp.module FROM role_permissions rp
         JOIN roles r ON rp.role_id = r.id
         WHERE r.name = ? AND rp.can_view = 1",
        [$role]
    );
    $modules = array_column($rows, 'module');
    $_SESSION['_perms'] = ['role' => $role, 'modules' => $modules];
    return $modules;
}

function has_module_access($module) {
    $perms = get_role_permissions();
    return in_array('*', $perms) || in_array($module, $perms);
}

function require_module_access($module) {
    require_login();
    if (!has_module_access($module)) {
        header('Location: ' . get_user_dashboard());
        exit;
    }
}
