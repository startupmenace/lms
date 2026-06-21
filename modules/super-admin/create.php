<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/multitenant.php';

if (empty($_SESSION['super_admin_id'])) { header('Location: login.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_name = trim($_POST['school_name'] ?? '');
    $subdomain   = trim(strtolower(preg_replace('/[^a-z0-9-]/', '', $_POST['subdomain'] ?? '')));
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_pass  = $_POST['admin_password'] ?? 'admin123';
    $timezone    = trim($_POST['timezone'] ?? 'Africa/Nairobi');
    $db_host     = trim($_POST['db_host'] ?? DB_HOST);
    $db_port     = (int)($_POST['db_port'] ?? DB_PORT);
    $db_user     = trim($_POST['db_user'] ?? DB_USER);
    $db_pass     = $_POST['db_pass'] ?? DB_PASS;
    $db_name     = trim(preg_replace('/[^a-z0-9_]/', '', $_POST['db_name'] ?? ''));
    $seed_classes = isset($_POST['seed_classes']);
    $seed_subjects = isset($_POST['seed_subjects']);
    $seed_holidays = isset($_POST['seed_holidays']);

    if (!$school_name || !$subdomain || !$admin_email || !$db_name) {
        $error = 'School name, subdomain, admin email, and database name are required.';
    } elseif (strlen($subdomain) < 3) {
        $error = 'Subdomain must be at least 3 characters.';
    } else {
        // Check uniqueness
        $rconn = router_db_connect();
        $existing = $rconn->query("SELECT id FROM schools WHERE subdomain='$subdomain' OR db_name='$db_name'")->fetch_assoc();
        $rconn->close();
        if ($existing) {
            $error = 'A school with this subdomain or database name already exists.';
        } else {
            try {
                provision_school_db($db_name, $db_host, $db_user, $db_pass, $db_port);

                // 4. Seed holidays
                if ($seed_holidays) {
                    $hfile = __DIR__ . '/../../database/seed-holidays.sql';
                    if (file_exists($hfile)) {
                        $rconn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
                        $hsql = file_get_contents($hfile);
                        $rconn->multi_query($hsql);
                        while ($rconn->more_results()) { $rconn->next_result(); }
                        $rconn->close();
                    }
                }

                // 5. Create admin user in school's DB
                create_school_admin($db_name, $admin_email, $admin_pass, $school_name . ' Admin');

                // 6. Seed classes
                if ($seed_classes) {
                    $categories = ['Primary', 'Middle', 'Secondary'];
                    $streams = ['Alpha', 'Beta'];
                    for ($i = 1; $i <= 8; $i++) {
                        $cat = $i <= 4 ? 'Primary' : ($i <= 6 ? 'Middle' : 'Secondary');
                        foreach ($streams as $st) {
                            $name = "Class $i";
                            $desc = "$name $st";
                            $stmt = $rconn->prepare("INSERT IGNORE INTO classes (name, category, section, description, is_active) VALUES (?, ?, ?, ?, 1)");
                            $stmt->bind_param('ssss', $name, $cat, $st, $desc);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }

                    // 7. Seed subjects (and assign if classes were created)
                    if ($seed_subjects) {
                        $subj_list = ['Mathematics', 'English', 'Kiswahili', 'Science', 'Social Studies', 'Religious Education', 'Computer Science', 'Physical Education', 'Art & Craft', 'Music'];
                        foreach ($subj_list as $sname) {
                            $rconn->query("INSERT IGNORE INTO subjects (name, is_active) VALUES ('" . $rconn->real_escape_string($sname) . "', 1)");
                        }
                        $class_rows = $rconn->query("SELECT id, name FROM classes WHERE is_active=1 GROUP BY name")->fetch_all(MYSQLI_ASSOC);
                        $subj_rows  = $rconn->query("SELECT id, name FROM subjects WHERE is_active=1")->fetch_all(MYSQLI_ASSOC);
                        foreach ($class_rows as $c) {
                            $cnum = (int)filter_var($c['name'], FILTER_SANITIZE_NUMBER_INT);
                            foreach ($subj_rows as $s) {
                                if ($cnum <= 4 && in_array($s['name'], ['Art & Craft', 'Music', 'Computer Science'])) continue;
                                $rconn->query("INSERT IGNORE INTO class_subjects (class_id, subject_id) VALUES ({$c['id']}, {$s['id']})");
                            }
                        }
                    }
                }

                // 8. Register school in router DB
                register_school($subdomain, $school_name, $db_name, $timezone, $db_host, $db_user, $db_pass, $db_port);

                $success = "School <strong>" . sanitize($school_name) . "</strong> created!<br>
                    Login: <strong>" . sanitize($admin_email) . "</strong> / <strong>" . sanitize($admin_pass) . "</strong><br>
                    URL: <a href='http://$subdomain." . str_replace(['http://', 'https://'], '', BASE_URL) . "' target='_blank' class='underline'>http://$subdomain." . str_replace(['http://', 'https://'], '', BASE_URL) . "</a>";

            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$page_title = 'Create School';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">
<div class="max-w-2xl mx-auto px-4 py-8">
    <a href="index.php" class="text-gray-400 hover:text-gray-300 text-sm mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Dashboard</a>
    <h1 class="text-2xl font-bold mb-6">Create New School</h1>

    <?php if ($error): ?>
    <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-xl text-sm mb-4"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="bg-emerald-900/50 border border-emerald-700 text-emerald-300 px-4 py-6 rounded-xl text-sm mb-4"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-gray-900 rounded-2xl border border-gray-800 p-6 space-y-5">
        <h2 class="text-lg font-semibold border-b border-gray-800 pb-3">School Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">School Name *</label>
                <input type="text" name="school_name" value="<?= sanitize($_POST['school_name'] ?? '') ?>" required class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Subdomain *</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="subdomain" value="<?= sanitize($_POST['subdomain'] ?? '') ?>" required pattern="[a-z0-9-]{3,}" class="flex-1 bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
                    <span class="text-gray-500 text-xs whitespace-nowrap">.<?= str_replace(['http://', 'https://'], '', BASE_URL) ?></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Timezone</label>
                <select name="timezone" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="Africa/Nairobi" <?= ($_POST['timezone'] ?? '') == 'Africa/Nairobi' ? 'selected' : '' ?>>Africa/Nairobi (UTC+3)</option>
                    <option value="Africa/Lagos" <?= ($_POST['timezone'] ?? '') == 'Africa/Lagos' ? 'selected' : '' ?>>Africa/Lagos (UTC+1)</option>
                    <option value="Africa/Johannesburg" <?= ($_POST['timezone'] ?? '') == 'Africa/Johannesburg' ? 'selected' : '' ?>>Africa/Johannesburg (UTC+2)</option>
                    <option value="Africa/Cairo" <?= ($_POST['timezone'] ?? '') == 'Africa/Cairo' ? 'selected' : '' ?>>Africa/Cairo (UTC+2)</option>
                    <option value="Africa/Accra" <?= ($_POST['timezone'] ?? '') == 'Africa/Accra' ? 'selected' : '' ?>>Africa/Accra (UTC+0)</option>
                </select>
            </div>
        </div>

        <h2 class="text-lg font-semibold border-b border-gray-800 pb-3">Admin Account</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Admin Email *</label>
                <input type="email" name="admin_email" value="<?= sanitize($_POST['admin_email'] ?? '') ?>" required class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Admin Password</label>
                <input type="text" name="admin_password" value="admin123" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
                <p class="text-xs text-gray-500 mt-1">Change after first login.</p>
            </div>
        </div>

        <h2 class="text-lg font-semibold border-b border-gray-800 pb-3">Database</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">DB Host</label>
                <input type="text" name="db_host" value="<?= sanitize($_POST['db_host'] ?? ROUTER_DB_HOST) ?>" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">DB Port</label>
                <input type="number" name="db_port" value="<?= $_POST['db_port'] ?? ROUTER_DB_PORT ?>" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">DB Name *</label>
                <input type="text" name="db_name" value="<?= sanitize($_POST['db_name'] ?? '') ?>" required pattern="[a-z0-9_]+" placeholder="e.g. school_name" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">DB User</label>
                <input type="text" name="db_user" value="<?= sanitize($_POST['db_user'] ?? ROUTER_DB_USER) ?>" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">DB Password</label>
                <input type="text" name="db_pass" value="<?= sanitize($_POST['db_pass'] ?? ROUTER_DB_PASS) ?>" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
        </div>

        <h2 class="text-lg font-semibold border-b border-gray-800 pb-3">Seed Data</h2>
        <div class="space-y-2">
            <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="seed_classes" checked class="rounded bg-gray-800 border-gray-600 text-teal-500 focus:ring-teal-500"> <span>Create sample classes (Class 1–8 with Alpha/Beta streams)</span></label>
            <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="seed_subjects" checked class="rounded bg-gray-800 border-gray-600 text-teal-500 focus:ring-teal-500"> <span>Create sample subjects (Math, English, Science, etc.)</span></label>
            <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="seed_holidays" checked class="rounded bg-gray-800 border-gray-600 text-teal-500 focus:ring-teal-500"> <span>Seed Kenya public holidays</span></label>
        </div>

        <button type="submit" class="w-full bg-teal-600 hover:bg-teal-500 text-white font-semibold py-3 rounded-xl text-sm transition flex items-center justify-center gap-2">
            <i class="fas fa-rocket"></i> Create School &amp; Provision Database
        </button>
    </form>
</div>
</body>
</html>
