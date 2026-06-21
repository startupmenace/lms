<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/multitenant.php';

if (empty($_SESSION['super_admin_id'])) { header('Location: login.php'); exit; }

$error   = '';
$success = '';
$step    = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $school_name  = trim($_POST['school_name'] ?? '');
    $subdomain    = trim(strtolower(preg_replace('/[^a-z0-9-]/', '', $_POST['subdomain'] ?? '')));
    $admin_email  = trim($_POST['admin_email'] ?? '');
    $admin_pass   = $_POST['admin_password'] ?? 'admin123';
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
        $rconn->close();
        if (!$error) {
            try {
                provision_school_db($db_name, $db_host, $db_user, $db_pass, $db_port);
                $sconn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
                if ($seed_holidays) {
                    $hfile = __DIR__ . '/../../database/seed-holidays.sql';
                    if (file_exists($hfile)) {
                        $hsql = file_get_contents($hfile);
                        $sconn->multi_query($hsql);
                        while ($sconn->more_results()) { $sconn->next_result(); }
                    }
                }
                $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                $uname = explode('@', $admin_email)[0];
                $sconn->prepare("INSERT INTO users (username, email, password, full_name, role, is_active) VALUES (?,?,?,?,'admin',1)")->execute([$uname, $admin_email, $hash, $school_name . ' Admin']);
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
                $success = true;
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

<?php if ($success): ?>
<div class="bg-emerald-900/40 border border-emerald-700 rounded-2xl p-8 text-center">
    <div class="w-16 h-16 rounded-full bg-emerald-500/20 flex items-center justify-center mx-auto mb-4"><i class="fas fa-check text-3xl text-emerald-400"></i></div>
    <h2 class="text-2xl font-bold mb-2">School Created!</h2>
    <p class="text-gray-400 mb-2"><?= sanitize($_POST['school_name'] ?? '') ?></p>
    <div class="bg-gray-950/50 rounded-xl p-4 text-left text-sm space-y-1 my-4">
        <p><span class="text-gray-500">Subdomain:</span> <?= sanitize($_POST['subdomain'] ?? '') ?></p>
        <p><span class="text-gray-500">Admin:</span> <?= sanitize($_POST['admin_email'] ?? '') ?></p>
        <p><span class="text-gray-500">Password:</span> <code class="text-emerald-300"><?= sanitize($_POST['admin_password'] ?? '') ?></code></p>
        <p><span class="text-gray-500">Database:</span> <code class="text-teal-300"><?= sanitize($_POST['db_name'] ?? '') ?></code></p>
    </div>
    <div class="flex gap-3 justify-center">
        <a href="create.php" class="bg-teal-600 hover:bg-teal-500 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition"><i class="fas fa-plus mr-2"></i>Add Another School</a>
        <a href="index.php" class="bg-gray-800 hover:bg-gray-700 text-gray-300 px-6 py-2.5 rounded-xl text-sm font-medium transition"><i class="fas fa-arrow-left mr-2"></i>Dashboard</a>
    </div>
</div>
<?php else: ?>

    <a href="index.php" class="text-gray-400 hover:text-gray-300 text-sm mb-6 inline-block"><i class="fas fa-arrow-left mr-1"></i> Dashboard</a>

    <div class="flex items-center gap-4 mb-8">
        <div class="w-12 h-12 rounded-xl bg-teal-900/50 flex items-center justify-center"><i class="fas fa-school text-teal-400 text-xl"></i></div>
        <div><h1 class="text-2xl font-bold">New School</h1><p class="text-gray-400 text-sm">Provision a school in under 30 seconds</p></div>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-xl text-sm mb-4"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-gray-900 rounded-2xl border border-gray-800 p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">School Name *</label>
                <input type="text" name="school_name" value="<?= sanitize($_POST['school_name'] ?? '') ?>" required
                       oninput="document.getElementById('sub_preview').textContent=this.value.toLowerCase().replace(/[^a-z0-9-]/g,'-').replace(/--+/g,'-').replace(/^-|-$/g,'')||'school-name'"
                       class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g. Nairobi Academy">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Subdomain *</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="subdomain" id="subdomain_input" value="<?= sanitize($_POST['subdomain'] ?? '') ?>" required pattern="[a-z0-9-]{3,}"
                           class="flex-1 bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none"
                           placeholder="nairobi-academy">
                    <span class="text-gray-500 text-xs whitespace-nowrap">.<?= str_replace(['http://', 'https://'], '', BASE_URL) ?></span>
                </div>
                <p class="text-xs text-gray-500 mt-1">URL preview: <span id="sub_preview" class="text-teal-400" data-default="school-name">school-name</span>.<?= str_replace(['http://', 'https://'], '', BASE_URL) ?></p>
            </div>
        </div>

        <hr class="border-gray-800">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Admin Email *</label>
                <input type="email" name="admin_email" value="<?= sanitize($_POST['admin_email'] ?? '') ?>" required
                       class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none" placeholder="admin@school.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Admin Password</label>
                <div class="flex gap-2">
                    <input type="text" name="admin_password" id="admin_pass" value="admin123"
                           class="flex-1 bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-teal-500 outline-none">
                    <button type="button" onclick="document.getElementById('admin_pass').value=Math.random().toString(36).slice(2,10)" class="bg-gray-800 hover:bg-gray-700 text-gray-400 px-3 rounded-xl text-sm border border-gray-700"><i class="fas fa-dice"></i></button>
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
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">DB Name</label>
                <input type="text" name="db_name" value="<?= sanitize($_POST['db_name'] ?? '') ?>" pattern="[a-z0-9_]+" placeholder="auto: school_subdomain"
                       class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none">
                <p class="text-xs text-gray-500 mt-1">Leave blank to auto-generate</p>
            </div>
        </div>

        <details class="bg-gray-950/50 rounded-xl p-4 border border-gray-800">
            <summary class="text-sm text-gray-400 cursor-pointer hover:text-gray-300"><i class="fas fa-database mr-2"></i>Database connection <span class="text-gray-600">(uses same MySQL server by default)</span></summary>
            <div class="grid grid-cols-2 gap-3 mt-3">
                <div><label class="block text-xs text-gray-500 mb-1">Host</label><input type="text" name="db_host" value="<?= DB_HOST ?>" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Port</label><input type="number" name="db_port" value="<?= DB_PORT ?>" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                <div><label class="block text-xs text-gray-500 mb-1">User</label><input type="text" name="db_user" value="<?= DB_USER ?>" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Password</label><input type="text" name="db_pass" value="<?= DB_PASS ?>" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
            </div>
        </details>

        <hr class="border-gray-800">

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Seed starter data</label>
            <div class="space-y-2">
                <label class="flex items-center gap-3 text-sm bg-gray-950/50 rounded-xl px-4 py-3 border border-gray-800 cursor-pointer hover:border-teal-700">
                    <input type="checkbox" name="seed_classes" checked class="rounded bg-gray-800 border-gray-600 text-teal-500 focus:ring-teal-500">
                    <span><i class="fas fa-users text-teal-500 w-5"></i> Classes with streams <span class="text-gray-500">(Class 1–8 × Alpha/Beta)</span></span>
                </label>
                <label class="flex items-center gap-3 text-sm bg-gray-950/50 rounded-xl px-4 py-3 border border-gray-800 cursor-pointer hover:border-teal-700">
                    <input type="checkbox" name="seed_subjects" checked class="rounded bg-gray-800 border-gray-600 text-teal-500 focus:ring-teal-500">
                    <span><i class="fas fa-book text-amber-500 w-5"></i> Subjects <span class="text-gray-500">(Math, English, Science, etc.)</span></span>
                </label>
                <label class="flex items-center gap-3 text-sm bg-gray-950/50 rounded-xl px-4 py-3 border border-gray-800 cursor-pointer hover:border-teal-700">
                    <input type="checkbox" name="seed_holidays" checked class="rounded bg-gray-800 border-gray-600 text-teal-500 focus:ring-teal-500">
                    <span><i class="fas fa-calendar-day text-purple-500 w-5"></i> Kenya public holidays <span class="text-gray-500">(recurring annually)</span></span>
                </label>
            </div>
        </div>

        <button type="submit" name="create" class="w-full bg-teal-600 hover:bg-teal-500 text-white font-semibold py-3 rounded-xl text-sm transition flex items-center justify-center gap-2">
            <i class="fas fa-rocket"></i> Create School &amp; Provision Database
        </button>
    </form>

    <script>
    document.querySelector('[name="school_name"]')?.addEventListener('input', function() {
        var val = this.value.toLowerCase().replace(/[^a-z0-9-]/g,'-').replace(/--+/g,'-').replace(/^-|-$/g,'') || 'school-name';
        var input = document.getElementById('subdomain_input');
        if (!input.value || input.dataset.autofilled !== 'false') {
            input.value = val;
            input.dataset.autofilled = 'true';
        }
        document.getElementById('sub_preview').textContent = input.value || 'school-name';
    });
    document.getElementById('subdomain_input')?.addEventListener('input', function() {
        this.dataset.autofilled = 'false';
        document.getElementById('sub_preview').textContent = this.value || 'school-name';
    });
    </script>

<?php endif; ?>
</div>
</body>
</html>
