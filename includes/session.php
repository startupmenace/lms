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
    if (empty($role)) return ['modules' => [], 'manage_modules' => []];
    if (isset($_SESSION['_perms']) && $_SESSION['_perms']['role'] === $role) {
        return $_SESSION['_perms'];
    }
    if ($role === 'admin') {
        $result = ['role' => $role, 'modules' => ['*'], 'manage_modules' => ['*']];
        $_SESSION['_perms'] = $result;
        return $result;
    }
    $rows = db_get_all(
        "SELECT rp.module, rp.can_manage FROM role_permissions rp
         JOIN roles r ON rp.role_id = r.id
         WHERE r.name = ? AND rp.can_view = 1",
        [$role]
    );
    $modules = array_column($rows, 'module');
    $manage = [];
    foreach ($rows as $r) {
        if ($r['can_manage']) $manage[] = $r['module'];
    }
    $result = ['role' => $role, 'modules' => $modules, 'manage_modules' => $manage];
    $_SESSION['_perms'] = $result;
    return $result;
}

function has_module_access($module) {
    $perms = get_role_permissions();
    return in_array('*', $perms['modules']) || in_array($module, $perms['modules']);
}

function can_manage_module($module) {
    $perms = get_role_permissions();
    return in_array('*', $perms['manage_modules']) || in_array($module, $perms['manage_modules']);
}

function require_module_access($module, $level = 'view') {
    require_login();
    $has = $level === 'manage' ? can_manage_module($module) : has_module_access($module);
    if (!$has) {
        $dest = get_user_dashboard();
        // Avoid redirect loop — if already at the dashboard URL, show error instead
        $current = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
        $dest_path = parse_url($dest, PHP_URL_PATH);
        if ($dest_path && $current && strpos($current, $dest_path) !== false) {
            http_response_code(403);
            echo '<html><head><title>Access Denied</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f9fafb;color:#374151;text-align:center;padding:2rem}</style></head><body><div><h1 style="font-size:2rem;color:#dc2626;margin-bottom:.5rem">Access Denied</h1><p>You don\'t have permission to access this module.</p><p style="margin-top:1.5rem"><a href="' . BASE_URL . '/modules/auth/logout.php" style="color:#14b8a6">Logout</a></p></div></body></html>';
            exit;
        }
        header('Location: ' . $dest);
        exit;
    }
}
