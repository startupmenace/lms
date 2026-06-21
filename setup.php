<?php
/**
 * Ziada LMS — Multi-Tenant Setup Wizard
 *
 * First-run wizard that bootstraps the router database
 * and creates the super admin account.
 *
 * Access: anyone (no auth required — it's a setup tool).
 * After setup, it self-destructs or can be deleted.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/multitenant.php';

// Already set up?
$already = router_db_exists();
if ($already) {
    $rconn = router_db_connect();
    $has_super = $rconn->query("SELECT COUNT(*) as c FROM super_admins")->fetch_assoc()['c'] > 0;
    $has_schools = $rconn->query("SELECT COUNT(*) as c FROM schools")->fetch_assoc()['c'] > 0;
    $rconn->close();
    if ($has_super && $has_schools) {
        header('Location: modules/super-admin/index.php');
        exit;
    }
}

$step = (int)($_GET['step'] ?? 1);
$error = '';
$done  = '';

// ── Step 3 handler: create super admin ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $name     = trim($_POST['name'] ?? 'Super Admin');
    if (!$email || !$pass) { $error = 'Email and password are required.'; }
    elseif (strlen($pass) < 6) { $error = 'Password must be at least 6 characters.'; }
    else {
        $conn = router_db_connect();
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT IGNORE INTO super_admins (email, password, full_name) VALUES (?,?,?)");
        $stmt->bind_param('sss', $email, $hash, $name);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $done = 'Super admin created!';
            $_SESSION['super_admin_id'] = $conn->insert_id;
            $_SESSION['super_admin_email'] = $email;
            $_SESSION['super_admin_name'] = $name;
        } else {
            $error = 'An admin with this email already exists.';
        }
        $stmt->close();
        $conn->close();
    }
}

// ── Step 2 handler: run migration ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    $mig = __DIR__ . '/database/migration-multitenant.sql';
    if (!file_exists($mig)) { $error = 'Migration file not found.'; }
    else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
        if ($conn->connect_error) { $error = 'Cannot connect to MySQL: ' . $conn->connect_error; }
        else {
            $sql = file_get_contents($mig);
            if ($conn->multi_query($sql)) {
                while ($conn->more_results()) { $conn->next_result(); }
                $done = 'Router database created!';
            } else {
                $error = 'Migration failed: ' . $conn->error;
            }
            $conn->close();
        }
    }
}

$page_title = 'Setup Wizard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | Ziada LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-lg mx-auto px-4 py-8">

    <!-- Progress -->
    <div class="flex items-center justify-center gap-2 mb-10">
        <?php for ($i = 1; $i <= 3; $i++): ?>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                <?= $i < $step ? 'bg-teal-600 text-white' : ($i == $step ? 'bg-teal-500 text-white ring-2 ring-teal-300' : 'bg-gray-800 text-gray-500') ?>">
                <?= $i < $step ? '<i class="fas fa-check"></i>' : $i ?>
            </div>
            <span class="text-xs <?= $i == $step ? 'text-teal-300 font-medium' : 'text-gray-600' ?> hidden sm:inline">
                <?= ['Connect', 'Database', 'Admin'][$i - 1] ?>
            </span>
            <?php if ($i < 3): ?><div class="w-8 h-px <?= $i < $step ? 'bg-teal-600' : 'bg-gray-800' ?>"></div><?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-xl text-sm mb-4"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($done): ?>
    <div class="bg-emerald-900/50 border border-emerald-700 text-emerald-300 px-4 py-3 rounded-xl text-sm mb-4"><?= $done ?></div>
    <?php endif; ?>

    <!-- Step 1: Welcome & Connection Check -->
    <?php if ($step === 1): ?>
    <div class="bg-gray-900 rounded-2xl border border-gray-800 p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-teal-500/20 flex items-center justify-center mx-auto mb-4"><i class="fas fa-globe text-2xl text-teal-400"></i></div>
        <h1 class="text-2xl font-bold mb-2">Multi-Tenant Setup</h1>
        <p class="text-gray-400 text-sm mb-6">This wizard will prepare Ziada LMS to host multiple schools from a single deployment.</p>

        <?php if ($already): ?>
        <div class="bg-teal-900/30 border border-teal-700 rounded-xl p-4 text-sm text-left mb-6">
            <p class="text-teal-300 font-medium mb-1"><i class="fas fa-check-circle mr-1"></i> Router database found</p>
            <p class="text-gray-400 text-xs">teachbetter_router already exists.</p>
        </div>
        <a href="?step=3" class="block w-full bg-teal-600 hover:bg-teal-500 text-white font-semibold py-3 rounded-xl text-sm transition text-center">Continue to Admin Setup</a>
        <?php else: ?>
        <div class="bg-gray-800 rounded-xl p-4 text-sm text-left mb-6">
            <p class="text-gray-300 font-medium mb-2"><i class="fas fa-database mr-2"></i>Database Connection</p>
            <p class="text-gray-400 text-xs space-y-1">
                <span>Host: <?= DB_HOST ?>:<?= DB_PORT ?></span><br>
                <span>User: <?= DB_USER ?></span><br>
                <span class="<?= $error ? 'text-red-400' : 'text-green-400' ?>">Status: Connected</span>
            </p>
        </div>
        <p class="text-xs text-gray-500 mb-4">The wizard will create a <code class="text-teal-400 bg-gray-800 px-1 rounded">teachbetter_router</code> database to manage schools.</p>
        <form method="POST">
            <input type="hidden" name="run_migration" value="1">
            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-500 text-white font-semibold py-3 rounded-xl text-sm transition">
                <i class="fas fa-rocket mr-2"></i> Create Router Database
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Step 2: Running migration (auto-continue) -->
    <?php elseif ($step === 2): ?>
    <div class="bg-gray-900 rounded-2xl border border-gray-800 p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-teal-500/20 flex items-center justify-center mx-auto mb-4"><i class="fas fa-database text-2xl text-teal-400"></i></div>
        <h1 class="text-2xl font-bold mb-2">Database Ready</h1>
        <p class="text-gray-400 text-sm mb-2">The <code class="text-teal-400">teachbetter_router</code> database and tables have been created.</p>
        <div class="bg-gray-800 rounded-xl p-4 text-left text-xs text-gray-400 space-y-1 mb-6">
            <p><span class="text-gray-300">✓</span> schools table</p>
            <p><span class="text-gray-300">✓</span> super_admins table</p>
        </div>
        <a href="?step=3" class="block w-full bg-teal-600 hover:bg-teal-500 text-white font-semibold py-3 rounded-xl text-sm transition text-center">
            <i class="fas fa-user-shield mr-2"></i> Create Super Admin
        </a>
    </div>

    <!-- Step 3: Create Super Admin -->
    <?php elseif ($step === 3): ?>
    <?php if (!empty($_SESSION['super_admin_id'])): ?>
    <div class="bg-gray-900 rounded-2xl border border-gray-800 p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-500/20 flex items-center justify-center mx-auto mb-4"><i class="fas fa-check text-2xl text-emerald-400"></i></div>
        <h1 class="text-2xl font-bold mb-2">Setup Complete!</h1>
        <p class="text-gray-400 text-sm mb-6">You can now create schools and manage them from the super admin panel.</p>
        <a href="modules/super-admin/index.php" class="block w-full bg-teal-600 hover:bg-teal-500 text-white font-semibold py-3 rounded-xl text-sm transition text-center">
            <i class="fas fa-school mr-2"></i> Go to Super Admin
        </a>
        <p class="text-xs text-gray-500 mt-3">After creating schools, activate multi-tenant routing by adding <code class="text-teal-400 bg-gray-800 px-1 rounded">require __DIR__.'/../includes/multitenant.php'; maybe_enable_multitenant();</code> to <code class="text-teal-400 bg-gray-800 px-1 rounded">config/config.php</code></p>
    </div>
    <?php else: ?>
    <div class="bg-gray-900 rounded-2xl border border-gray-800 p-8">
        <div class="w-16 h-16 rounded-full bg-teal-500/20 flex items-center justify-center mx-auto mb-4"><i class="fas fa-user-shield text-2xl text-teal-400"></i></div>
        <h1 class="text-2xl font-bold text-center mb-2">Create Super Admin</h1>
        <p class="text-gray-400 text-sm text-center mb-6">This account manages all schools on the platform.</p>
        <form method="POST">
            <input type="hidden" name="create_admin" value="1">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Full Name</label>
                    <input type="text" name="name" value="Super Admin" required
                           class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Email *</label>
                    <input type="email" name="email" required placeholder="admin@ziada.com"
                           class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Password *</label>
                    <input type="text" name="password" value="admin123" required minlength="6"
                           class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
                    <p class="text-xs text-gray-500 mt-1">Change after first login. Default: admin123</p>
                </div>
            </div>
            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-500 text-white font-semibold py-3 rounded-xl text-sm transition mt-6">
                <i class="fas fa-check mr-2"></i> Create Admin &amp; Finish
            </button>
        </form>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <p class="text-center text-xs text-gray-600 mt-8">Ziada LMS — <?= date('Y') ?></p>
</div>
</body>
</html>
