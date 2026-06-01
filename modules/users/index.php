<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('users');

$page_title = 'User Management';
$tab = $_GET['tab'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone'] ?? '');
        $role = sanitize($_POST['role']);
        $password = $_POST['password'] ?? 'password';

        $existing = db_get_row("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            set_flash('error', 'A user with this email already exists.');
            redirect('?tab=create');
        }

        $username = strtolower(str_replace(' ', '.', $full_name));
        $check = db_get_row("SELECT id FROM users WHERE username = ?", [$username]);
        if ($check) {
            $username .= rand(100, 999);
        }

        db_insert("INSERT INTO users (username, email, password, full_name, phone, role, is_active) VALUES (?,?,?,?,?,?,1)", [
            $username, $email, password_hash($password, PASSWORD_DEFAULT), $full_name, $phone, $role
        ]);
        set_flash('success', "User '$full_name' created. Default password: <code>$password</code>");
        redirect('?tab=all');
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone'] ?? '');
        $role = sanitize($_POST['role']);
        $is_active = (int)($_POST['is_active'] ?? 1);

        if ($id === get_user_id() && !$is_active) {
            set_flash('error', 'You cannot deactivate your own account.');
            redirect('?tab=all');
        }

        $existing = db_get_row("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
        if ($existing) {
            set_flash('error', 'Another user already has this email.');
            redirect("?tab=edit&id=$id");
        }

        $password = $_POST['password'] ?? '';
        if (!empty($password)) {
            db_query("UPDATE users SET full_name=?, email=?, phone=?, role=?, is_active=?, password=? WHERE id=?", [
                $full_name, $email, $phone, $role, $is_active, password_hash($password, PASSWORD_DEFAULT), $id
            ]);
        } else {
            db_query("UPDATE users SET full_name=?, email=?, phone=?, role=?, is_active=? WHERE id=?", [
                $full_name, $email, $phone, $role, $is_active, $id
            ]);
        }
        set_flash('success', 'User updated.');
        redirect('?tab=all');
    }

    if ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        if ($id === get_user_id()) {
            set_flash('error', 'You cannot change your own status.');
            redirect('?tab=all');
        }
        $user = db_get_row("SELECT is_active FROM users WHERE id=?", [$id]);
        if ($user) {
            $new = $user['is_active'] ? 0 : 1;
            db_query("UPDATE users SET is_active=? WHERE id=?", [$new, $id]);
            set_flash('success', 'User status updated.');
        }
        redirect('?tab=all');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === get_user_id()) {
            set_flash('error', 'You cannot delete your own account.');
            redirect('?tab=all');
        }
        db_query("DELETE FROM users WHERE id=?", [$id]);
        set_flash('success', 'User deleted.');
        redirect('?tab=all');
    }

    if ($action === 'create_role') {
        $name = sanitize($_POST['name']);
        $desc = sanitize($_POST['description'] ?? '');
        $existing = db_get_row("SELECT id FROM roles WHERE name=?", [$name]);
        if ($existing) {
            set_flash('error', 'Role already exists.');
            redirect('?tab=roles');
        }
        $new_id = db_insert("INSERT INTO roles (name, description) VALUES (?,?)", [$name, $desc]);
        set_flash('success', "Role '$name' created. Now assign permissions.");
        redirect("?tab=roles&edit_role=$new_id");
    }

    if ($action === 'save_permissions') {
        $role_id = (int)$_POST['role_id'];
        db_query("DELETE FROM role_permissions WHERE role_id=?", [$role_id]);
        $modules = $_POST['modules'] ?? [];
        foreach ($modules as $mod) {
            db_insert("INSERT INTO role_permissions (role_id, module, can_view) VALUES (?,?,1)", [$role_id, $mod]);
        }
        // Clear cached permissions for all users with this role
        $role_name = db_get_row("SELECT name FROM roles WHERE id=?", [$role_id])['name'] ?? '';
        if ($role_name && isset($_SESSION['_perms']) && $_SESSION['_perms']['role'] === $role_name) {
            unset($_SESSION['_perms']);
        }
        set_flash('success', 'Permissions saved.');
        redirect("?tab=roles&edit_role=$role_id");
    }

    if ($action === 'delete_role') {
        $id = (int)$_POST['id'];
        $role = db_get_row("SELECT name, is_system FROM roles WHERE id=?", [$id]);
        if (!$role || $role['is_system']) {
            set_flash('error', 'Cannot delete system roles.');
        } else {
            db_query("DELETE FROM roles WHERE id=?", [$id]);
            set_flash('success', 'Role deleted.');
        }
        redirect('?tab=roles');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                <i class="fas fa-user-shield text-teal-600 mr-2"></i> User Management
            </h1>
            <p class="text-gray-500 text-sm mt-1">Create, edit and manage users and roles</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="?tab=all" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='all'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-list mr-2"></i>All Users
            </a>
            <a href="?tab=create" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='create'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-plus-circle mr-2"></i>Create User
            </a>
            <a href="?tab=roles" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='roles'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-tags mr-2"></i>Roles & Permissions
            </a>
        </div>
        <div class="p-5">
            <?php
            $tab_file = __DIR__ . '/_' . $tab . '.php';
            if ($tab === 'edit') {
                $edit_id = (int)($_GET['id'] ?? 0);
                include __DIR__ . '/_edit.php';
            } elseif (file_exists($tab_file)) {
                include $tab_file;
            } else {
                echo '<p class="text-gray-500 text-center py-8">Tab not found.</p>';
            }
            ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
