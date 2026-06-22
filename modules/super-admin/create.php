<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/multitenant.php';

if (empty($_SESSION['super_admin_id'])) { header('Location: login.php'); exit; }

$error   = '';
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $school_name  = trim($_POST['school_name'] ?? '');
    $subdomain    = trim(strtolower(preg_replace('/[^a-z0-9-]/', '', $_POST['subdomain'] ?? '')));
    $admin_email  = trim($_POST['admin_email'] ?? '');
    $admin_pass   = $_POST['admin_password'] ?? 'Admin@123';
    $timezone     = trim($_POST['timezone'] ?? 'Africa/Nairobi');
    $db_host      = trim($_POST['db_host'] ?? DB_HOST);
    $db_port      = (int)($_POST['db_port'] ?? DB_PORT);
    $db_user      = trim($_POST['db_user'] ?? DB_USER);
    $db_pass      = $_POST['db_pass'] ?? DB_PASS;
    $db_name      = trim(preg_replace('/[^a-z0-9_]/', '', $_POST['db_name'] ?? 'school_' . $subdomain));
    $seed_classes = isset($_POST['seed_classes']);
    $seed_subjects = isset($_POST['seed_subjects']);
    $seed_holidays = isset($_POST['seed_holidays']);

    if (!$school_name || !$subdomain || !$admin_email) {
        $error = 'School name, subdomain, and admin email are required.';
    } elseif (strlen($subdomain) < 3) {
        $error = 'Subdomain must be at least 3 characters.';
    } else {
        $rconn = router_db_connect();
        $dup = $rconn->query("SELECT id FROM schools WHERE subdomain='$subdomain'")->fetch_assoc();
        if ($dup) { $error = "Subdomain '$subdomain' is already taken."; }
        else {
            $dup2 = $rconn->query("SELECT id FROM schools WHERE db_name='$db_name'")->fetch_assoc();
            if ($dup2) { $error = "Database name '$db_name' is already in use."; }
        }
        if (!$error) {
            try {
                provision_school_db($db_name, $db_host, $db_user, $db_pass, $db_port);
                $sconn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

                // Run all migrations, skip idempotent errors
                foreach (glob(__DIR__ . '/../../database/migration-*.sql') as $mf) {
                    $bn = basename($mf);
                    if ($bn === 'migration-multitenant.sql') continue;
                    $sql = file_get_contents($mf);
                    if (trim($sql) === '') continue;
                    $sql = preg_replace('/^USE\s+`[^`]+`;/im', '', $sql);
                    try {
                        $sconn->multi_query($sql);
                        while ($sconn->more_results()) { try { $sconn->next_result(); } catch (Exception $e) {} }
                    } catch (Exception $e) {}
                }

                if ($seed_holidays) {
                    $hfile = __DIR__ . '/../../database/seed-holidays.sql';
                    if (file_exists($hfile)) {
                        try {
                            $sconn->multi_query(file_get_contents($hfile));
                            while ($sconn->more_results()) { try { $sconn->next_result(); } catch (Exception $e) {} }
                        } catch (Exception $e) {}
                    }
                }

                // Update default admin (id=1) instead of insert
                $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                $sconn->query("UPDATE users SET email='$admin_email', password='$hash', full_name='" . $sconn->real_escape_string($school_name) . " Admin' WHERE id=1");
                $sconn->query("UPDATE users SET email='" . $sconn->real_escape_string(str_replace('admin@', 'teacher@', $admin_email)) . "', password='' WHERE email='teacher@jewelhouse.sc.ke'");
                $sconn->query("UPDATE users SET email='" . $sconn->real_escape_string(str_replace('admin@', 'student@', $admin_email)) . "', password='' WHERE email='student@jewelhouse.sc.ke'");

                if ($seed_classes) {
                    $streams = ['Alpha', 'Beta'];
                    for ($i = 1; $i <= 8; $i++) {
                        $cat = $i <= 4 ? 'Primary' : ($i <= 6 ? 'Middle' : 'Secondary');
                        foreach ($streams as $st) {
                            $name = "Class $i";
                            $sconn->query("INSERT IGNORE INTO classes (name, category, section, is_active) VALUES ('$name', '$cat', '$st', 1)");
                        }
                    }
                    if ($seed_subjects) {
                        foreach (['Mathematics','English','Kiswahili','Science','Social Studies','Religious Education','Computer Science','Physical Education','Art & Craft','Music'] as $sn) {
                            $sconn->query("INSERT IGNORE INTO subjects (name, is_active) VALUES ('" . $sconn->real_escape_string($sn) . "', 1)");
                        }
                        $cr = $sconn->query("SELECT id, name FROM classes WHERE is_active=1 GROUP BY name")->fetch_all(MYSQLI_ASSOC);
                        $sr = $sconn->query("SELECT id, name FROM subjects WHERE is_active=1")->fetch_all(MYSQLI_ASSOC);
                        foreach ($cr as $c) {
                            $cn = (int)filter_var($c['name'], FILTER_SANITIZE_NUMBER_INT);
                            foreach ($sr as $s) {
                                if ($cn <= 4 && in_array($s['name'], ['Art & Craft','Music','Computer Science'])) continue;
                                $sconn->query("INSERT IGNORE INTO class_subjects (class_id, subject_id) VALUES ({$c['id']}, {$s['id']})");
                            }
                        }
                    }
                }
                $sconn->close();

                register_school($subdomain, $school_name, $db_name, $timezone, $db_host, $db_user, $db_pass, $db_port);
                // Save credentials
                $rconn->query("UPDATE schools SET admin_email='" . $rconn->real_escape_string($admin_email) . "', admin_password='" . $rconn->real_escape_string($admin_pass) . "' WHERE subdomain='$subdomain'");
                $success = [
                    'name' => $school_name,
                    'subdomain' => $subdomain,
                    'email' => $admin_email,
                    'password' => $admin_pass,
                    'db_name' => $db_name,
                    'db_host' => $db_host,
                    'db_port' => $db_port,
                ];
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        $rconn->close();
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
<div class="max-w-3xl mx-auto px-4 py-8">

<?php if (!empty($success)): ?>
<div class="bg-emerald-900/40 border border-emerald-700 rounded-2xl p-8 text-center">
    <div class="w-16 h-16 rounded-full bg-emerald-500/20 flex items-center justify-center mx-auto mb-4"><i class="fas fa-check text-3xl text-emerald-400"></i></div>
    <h2 class="text-2xl font-bold mb-2">School Created!</h2>
    <p class="text-gray-400 mb-2"><?= sanitize($success['name']) ?></p>

    <div class="bg-gray-950/50 rounded-xl p-4 text-left text-sm space-y-1 my-4">
        <p><span class="text-gray-500">URL:</span> <code class="text-teal-300">https://<?= sanitize($success['subdomain']) ?>.ziada.co.ke</code></p>
        <p><span class="text-gray-500">Email:</span> <?= sanitize($success['email']) ?></p>
        <p><span class="text-gray-500">Password:</span> <code class="text-emerald-300"><?= sanitize($success['password']) ?></code></p>
        <p><span class="text-gray-500">DB:</span> <code class="text-teal-300"><?= sanitize($success['db_name']) ?></code> on <?= $success['db_host'] ?>:<?= $success['db_port'] ?></p>
    </div>

    <div class="bg-amber-900/30 border border-amber-700/50 rounded-xl p-4 text-left text-xs space-y-2 my-4">
        <p class="text-amber-300 font-semibold"><i class="fas fa-exclamation-triangle mr-1"></i> Next steps:</p>
        <p class="text-amber-200">1. <strong>DNS setup:</strong> Add an A record for <code class="text-amber-100"><?= sanitize($success['subdomain']) ?>.ziada.co.ke</code> pointing to your server IP.</p>
        <p class="text-amber-200">2. <strong>Access:</strong> After DNS propagates, visit <code class="text-amber-100">https://<?= sanitize($success['subdomain']) ?>.ziada.co.ke</code> and log in with the credentials above.</p>
    </div>

    <div class="flex flex-wrap gap-3 justify-center">
        <a href="create.php" class="bg-teal-600 hover:bg-teal-500 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition"><i class="fas fa-plus mr-2"></i>Add Another School</a>
        <a href="schools.php" class="bg-gray-800 hover:bg-gray-700 text-gray-300 px-6 py-2.5 rounded-xl text-sm font-medium transition"><i class="fas fa-list mr-2"></i>All Schools</a>
        <a href="index.php" class="bg-gray-800 hover:bg-gray-700 text-gray-300 px-6 py-2.5 rounded-xl text-sm font-medium transition"><i class="fas fa-arrow-left mr-2"></i>Dashboard</a>
    </div>
</div>
<?php else: ?>

<a href="schools.php" class="text-gray-400 hover:text-gray-300 text-sm mb-6 inline-block"><i class="fas fa-arrow-left mr-1"></i> All Schools</a>

<div class="flex items-center gap-4 mb-6">
    <div class="w-12 h-12 rounded-xl bg-teal-900/50 flex items-center justify-center"><i class="fas fa-school text-teal-400 text-xl"></i></div>
    <div><h1 class="text-2xl font-bold">New School</h1><p class="text-gray-400 text-sm">Provision a school database and register it</p></div>
</div>

<?php if ($error): ?>
<div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-xl text-sm mb-4"><?= $error ?></div>
<?php endif; ?>

<form method="POST" class="space-y-6">

<!-- Step indicator -->
<div class="flex items-center gap-2 text-xs mb-6">
    <span class="flex items-center gap-1 text-teal-400"><span class="w-6 h-6 rounded-full bg-teal-600 flex items-center justify-center text-white font-bold">1</span> Details</span>
    <span class="text-gray-700"><i class="fas fa-chevron-right"></i></span>
    <span class="flex items-center gap-1 text-gray-500"><span class="w-6 h-6 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 font-bold">2</span> Database</span>
    <span class="text-gray-700"><i class="fas fa-chevron-right"></i></span>
    <span class="flex items-center gap-1 text-gray-500"><span class="w-6 h-6 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 font-bold">3</span> Seed &amp; Create</span>
</div>

<!-- Step 1: School Details -->
<div class="bg-gray-900 rounded-2xl border border-gray-800 p-6 space-y-4">
    <div class="flex items-center gap-2 mb-2">
        <span class="w-7 h-7 rounded-full bg-teal-600 flex items-center justify-center text-white text-xs font-bold">1</span>
        <h2 class="font-semibold">School Details</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">School Name *</label>
            <input type="text" name="school_name" value="<?= sanitize($_POST['school_name'] ?? '') ?>" required
                   oninput="autoSubdomain(this)"
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g. Nairobi Academy">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Subdomain *</label>
            <div class="flex items-center gap-2">
                <input type="text" name="subdomain" id="subdomain_input" value="<?= sanitize($_POST['subdomain'] ?? '') ?>" required pattern="[a-z0-9-]{3,}"
                       class="flex-1 bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none"
                       placeholder="nairobi-academy">
                <span class="text-gray-500 text-xs whitespace-nowrap">.ziada.co.ke</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">URL: <span id="sub_preview" class="text-teal-400">school-name</span>.ziada.co.ke</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Admin Email *</label>
            <input type="email" name="admin_email" value="<?= sanitize($_POST['admin_email'] ?? '') ?>" required
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none" placeholder="admin@school.com">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Admin Password</label>
            <div class="flex gap-2">
                <input type="text" name="admin_password" id="admin_pass" value="Admin@123"
                       class="flex-1 bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none font-mono">
                <button type="button" onclick="document.getElementById('admin_pass').value=Math.random().toString(36).slice(2,10)+'@'+Math.random().toString(36).slice(2,5).toUpperCase()" class="bg-gray-800 hover:bg-gray-700 text-gray-400 px-3 rounded-xl text-sm border border-gray-700"><i class="fas fa-dice"></i></button>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Timezone</label>
            <select name="timezone" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
                <option value="Africa/Nairobi">Africa/Nairobi (UTC+3)</option>
                <option value="Africa/Lagos">Africa/Lagos (UTC+1)</option>
                <option value="Africa/Johannesburg">Africa/Johannesburg (UTC+2)</option>
                <option value="Africa/Cairo">Africa/Cairo (UTC+2)</option>
                <option value="Africa/Accra">Africa/Accra (UTC+0)</option>
            </select>
        </div>
    </div>
</div>

<!-- Step 2: Database -->
<div class="bg-gray-900 rounded-2xl border border-gray-800 p-6 space-y-4">
    <div class="flex items-center gap-2 mb-2">
        <span class="w-7 h-7 rounded-full bg-gray-700 flex items-center justify-center text-white text-xs font-bold">2</span>
        <h2 class="font-semibold">Database Connection</h2>
    </div>
    <div class="bg-amber-900/20 border border-amber-700/40 rounded-xl p-4 text-xs text-amber-200 space-y-2 mb-4">
        <p><i class="fas fa-info-circle mr-1 text-amber-400"></i> <strong>Using Dokploy?</strong> Create a new MySQL service in Dokploy first, then enter its connection details below.</p>
        <p class="text-amber-300">In Dokploy: <strong>+ New Service</strong> → MySQL → pick image (<code>mysql:8</code>) → deploy → copy the host, port, user, password from the service details.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Database Name</label>
            <input type="text" name="db_name" value="<?= sanitize($_POST['db_name'] ?? '') ?>" pattern="[a-z0-9_]+"
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none"
                   placeholder="auto: school_subdomain">
            <p class="text-xs text-gray-500 mt-1">Leave blank to auto-generate</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Host *</label>
            <input type="text" name="db_host" value="<?= sanitize($_POST['db_host'] ?? DB_HOST) ?>" required
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Port *</label>
            <input type="number" name="db_port" value="<?= (int)($_POST['db_port'] ?? DB_PORT) ?>" required
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">User *</label>
            <input type="text" name="db_user" value="<?= sanitize($_POST['db_user'] ?? DB_USER) ?>" required
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Password *</label>
            <input type="text" name="db_pass" value="<?= sanitize($_POST['db_pass'] ?? DB_PASS) ?>" required
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none font-mono">
        </div>
    </div>
</div>

<!-- Step 3: Seed & Create -->
<div class="bg-gray-900 rounded-2xl border border-gray-800 p-6 space-y-4">
    <div class="flex items-center gap-2 mb-2">
        <span class="w-7 h-7 rounded-full bg-gray-700 flex items-center justify-center text-white text-xs font-bold">3</span>
        <h2 class="font-semibold">Seed Starter Data</h2>
    </div>
    <div class="space-y-2">
        <label class="flex items-center gap-3 text-sm bg-gray-950/50 rounded-xl px-4 py-3 border border-gray-800 cursor-pointer hover:border-teal-700">
            <input type="checkbox" name="seed_classes" checked class="rounded bg-gray-800 border-gray-600 text-teal-500 focus:ring-teal-500">
            <span><i class="fas fa-users text-teal-500 w-5"></i> Classes with streams <span class="text-gray-500">(Class 1–8 × Alpha/Beta)</span></span>
        </label>
        <label class="flex items-center gap-3 text-sm bg-gray-950/50 rounded-xl px-4 py-3 border border-gray-800 cursor-pointer hover:border-teal-700">
            <input type="checkbox" name="seed_subjects" checked class="rounded bg-gray-800 border-gray-600 text-teal-500 focus:ring-teal-500">
            <span><i class="fas fa-book text-amber-500 w-5"></i> Subjects <span class="text-gray-500">(Math, English, Science...)</span></span>
        </label>
        <label class="flex items-center gap-3 text-sm bg-gray-950/50 rounded-xl px-4 py-3 border border-gray-800 cursor-pointer hover:border-teal-700">
            <input type="checkbox" name="seed_holidays" checked class="rounded bg-gray-800 border-gray-600 text-teal-500 focus:ring-teal-500">
            <span><i class="fas fa-calendar-day text-purple-500 w-5"></i> Kenya public holidays <span class="text-gray-500">(recurring annually)</span></span>
        </label>
    </div>

    <hr class="border-gray-800">

    <div class="bg-teal-900/20 border border-teal-700/40 rounded-xl p-4 text-xs text-teal-200 space-y-1">
        <p><i class="fas fa-rocket mr-1 text-teal-400"></i> This will:</p>
        <ul class="list-disc list-inside space-y-0.5 text-teal-300">
            <li>Provision the database with schema + all migrations</li>
            <li>Create an admin account (credentials saved in router DB)</li>
            <li>Register the school for subdomain routing</li>
            <li class="text-amber-300">You'll still need to set up DNS after creation</li>
        </ul>
    </div>

    <button type="submit" name="create" class="w-full bg-teal-600 hover:bg-teal-500 text-white font-semibold py-3 rounded-xl text-sm transition flex items-center justify-center gap-2 text-base">
        <i class="fas fa-rocket"></i> Create School &amp; Provision Database
    </button>
</div>

</form>

<script>
function autoSubdomain(el) {
    var val = el.value.toLowerCase().replace(/[^a-z0-9-]/g,'-').replace(/--+/g,'-').replace(/^-|-$/g,'') || 'school-name';
    var input = document.getElementById('subdomain_input');
    if (!input.value || input.dataset.autofilled !== 'false') {
        input.value = val;
        input.dataset.autofilled = 'true';
    }
    document.getElementById('sub_preview').textContent = input.value || 'school-name';
}
document.getElementById('subdomain_input')?.addEventListener('input', function() {
    this.dataset.autofilled = 'false';
    document.getElementById('sub_preview').textContent = this.value || 'school-name';
});
</script>

<?php endif; ?>
</div>
</body>
</html>