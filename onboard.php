<?php
/**
 * School Onboarding Script
 * Run from CLI: php onboard.php
 *
 * Creates a fresh database, imports schema + migrations,
 * configures branding, and creates the admin account.
 */

// ── Config ─────────────────────────────────────────────
$migration_files = glob(__DIR__ . '/database/migration-*.sql');
$schema_file     = __DIR__ . '/database/teachbetter_lms.sql';
$config_file     = __DIR__ . '/config/config.php';
$holidays_file   = __DIR__ . '/database/seed-holidays.sql';

// ── Helpers ────────────────────────────────────────────
function prompt(string $label, string $default = ''): string {
    $display = $default ? "$label [$default]: " : "$label: ";
    echo $display;
    $input = trim(fgets(STDIN));
    return $input !== '' ? $input : $default;
}

function prompt_yes(string $label): bool {
    return strtolower(trim(prompt($label . ' [y/N]', 'N'))) === 'y';
}

function exec_sql_file(mysqli $conn, string $path, string $label): void {
    if (!file_exists($path)) { echo "  ⚠  $label: file not found at $path\n"; return; }
    $sql = file_get_contents($path);
    if (!$sql || trim($sql) === '') { echo "  ⚠  $label: empty file\n"; return; }
    echo "  → Running $label...\n";
    if (!$conn->multi_query($sql)) {
        echo "  ✖  $label failed: " . $conn->error . "\n";
        exit(1);
    }
    // Consume all result sets
    while ($conn->more_results()) {
        $conn->next_result();
        if ($conn->errno) {
            // Ignore "no database selected" errors during schema creation
            if ($conn->errno !== 1046) {
                echo "  ⚠  $label: " . $conn->error . "\n";
            }
        }
    }
    echo "  ✓  $label done\n";
}

// ── Welcome ────────────────────────────────────────────
echo "\n";
echo "═══════════════════════════════════════════\n";
echo "       Ziada LMS — School Onboarding\n";
echo "═══════════════════════════════════════════\n\n";

// ── Gather info ───────────────────────────────────────
$school_name     = prompt('School name', 'My School');
$admin_email     = prompt('Admin email', 'admin@myschool.sc.ke');
$admin_password  = prompt('Admin password', 'admin123');
$timezone        = prompt('Timezone', 'Africa/Nairobi');
$site_url        = prompt('Site URL (no trailing slash)', 'http://localhost');

// ── Database connection (no DB selected yet) ───────────
$db_host = prompt('Database host', '127.0.0.1');
$db_user = prompt('Database user', 'root');
$db_pass = prompt('Database password', '');
$db_port = prompt('Database port', '3306');
$db_name = prompt('Database name', 'teachbetter_lms');

echo "\n── Connecting to MySQL...\n";
$conn = new mysqli($db_host, $db_user, $db_pass, '', (int)$db_port);
if ($conn->connect_error) {
    echo "✖  Cannot connect: " . $conn->connect_error . "\n";
    exit(1);
}
echo "✓  Connected\n";

// ── Create database ─────────────────────────────────────
echo "\n── Creating database `$db_name`...\n";
if ($conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    echo "✓  Database ready\n";
} else {
    echo "✖  " . $conn->error . "\n";
    exit(1);
}
$conn->select_db($db_name);

// ── Schema ─────────────────────────────────────────────
echo "\n── Importing schema...\n";
exec_sql_file($conn, $schema_file, 'teachbetter_lms.sql');

// ── Run all migrations ─────────────────────────────────
echo "\n── Running migrations...\n";
foreach ($migration_files as $mf) {
    $basename = basename($mf);
    exec_sql_file($conn, $mf, $basename);
}

// ── Optional: holidays ─────────────────────────────────
if (prompt_yes("\nSeed Kenya public holidays?")) {
    exec_sql_file($conn, $holidays_file, 'seed-holidays.sql');
}

// ── Update config.php ────────────────────────────────────
echo "\n── Writing config...\n";
$config_raw = file_get_contents($config_file);
$config_raw = preg_replace(
    "/define\('SITE_NAME',\s*'[^']*'\)/",
    "define('SITE_NAME', '$school_name')",
    $config_raw
);
$config_raw = preg_replace(
    "/define\('TIMEZONE',\s*'[^']*'\)/",
    "define('TIMEZONE', '$timezone')",
    $config_raw
);
// Update BASE_URL to static (or leave dynamic — keep dynamic for dev, prompt for prod)
// $config_raw = preg_replace(
//     "/define\('BASE_URL',.*\)/",
//     "define('BASE_URL', '$site_url')",
//     $config_raw
// );
file_put_contents($config_file, $config_raw);
echo "✓  Config updated (SITE_NAME, TIMEZONE)\n";

// ── Create admin user ───────────────────────────────────
echo "\n── Creating admin user...\n";
$hash = password_hash($admin_password, PASSWORD_DEFAULT);
$username = explode('@', $admin_email)[0];

// Check role_permissions table exists (from migration)
$rp_exists = $conn->query("SHOW TABLES LIKE 'role_permissions'")->num_rows > 0;

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $admin_email);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    echo "•  Admin user already exists (id={$existing['id']})\n";
} else {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, 'admin', 1)");
    $full_name = prompt('Admin display name', 'Administrator');
    $stmt->bind_param('ssss', $username, $admin_email, $hash, $full_name);
    $stmt->execute();
    $admin_id = $conn->insert_id;
    echo "✓  Admin created (id=$admin_id)\n";
}

// ── Optional: seed sample classes ─────────────────────
$classes_seeded = false;
if (prompt_yes("\nCreate sample classes (Class 1–8 with streams)?")) {
    echo "\n── Creating sample classes...\n";
    $categories = ['Primary', 'Middle', 'Secondary'];
    $streams    = ['Alpha', 'Beta'];
    for ($i = 1; $i <= 8; $i++) {
        $cat = $i <= 4 ? 'Primary' : ($i <= 6 ? 'Middle' : 'Secondary');
        foreach ($streams as $st) {
            $name = "Class $i";
            $stmt = $conn->prepare("INSERT IGNORE INTO classes (name, category, section, description, is_active) VALUES (?, ?, ?, ?, 1)");
            $desc = "$name $st";
            $stmt->bind_param('ssss', $name, $cat, $st, $desc);
            $stmt->execute();
        }
    }
    echo "✓  8 classes × 2 streams = 16 class entries created\n";
    $classes_seeded = true;
}

// ── Optional: create sample subjects ─────────────────
if (prompt_yes("\nCreate sample subjects?")) {
    echo "\n── Creating subjects...\n";
    $subjects = [
        'Mathematics', 'English', 'Kiswahili', 'Science',
        'Social Studies', 'Religious Education', 'Computer Science',
        'Physical Education', 'Art & Craft', 'Music'
    ];
    foreach ($subjects as $s) {
        $stmt = $conn->prepare("INSERT IGNORE INTO subjects (name, is_active) VALUES (?, 1)");
        $stmt->bind_param('s', $s);
        $stmt->execute();
    }
    echo "✓  " . count($subjects) . " subjects created\n";

    // If classes exist, assign subjects to primary/middle classes
    if ($classes_seeded) {
        $class_rows = $conn->query("SELECT id, name FROM classes WHERE is_active=1 GROUP BY name")->fetch_all(MYSQLI_ASSOC);
        $subj_rows  = $conn->query("SELECT id, name FROM subjects WHERE is_active=1")->fetch_all(MYSQLI_ASSOC);
        $stmt = $conn->prepare("INSERT IGNORE INTO class_subjects (class_id, subject_id) VALUES (?, ?)");
        foreach ($class_rows as $c) {
            $cnum = (int)filter_var($c['name'], FILTER_SANITIZE_NUMBER_INT);
            foreach ($subj_rows as $s) {
                // Secondary gets all; Primary gets core; Middle gets core + some electives
                if ($cnum <= 4 && in_array($s['name'], ['Art & Craft', 'Music', 'Computer Science'])) continue;
                $stmt->bind_param('ii', $c['id'], $s['id']);
                $stmt->execute();
            }
        }
        echo "✓  Subjects assigned to classes\n";
    }
}

// ── Summary ─────────────────────────────────────────────
echo "\n";
echo "═══════════════════════════════════════════\n";
echo "           Onboarding Complete!\n";
echo "═══════════════════════════════════════════\n";
echo "\n";
echo "  School:        $school_name\n";
echo "  Site URL:      $site_url\n";
echo "  Timezone:      $timezone\n";
echo "\n";
echo "  Login:         $admin_email\n";
echo "  Password:      $admin_password\n";
echo "  Role:          admin\n";
echo "\n";
echo "  Next steps:\n";
echo "    1. Set DATABASE_URL=mysql://$db_user:****@$db_host:$db_port/$db_name\n";
echo "    2. Log in at $site_url\n";
echo "    3. Create teachers via Users module\n";
echo "    4. Assign teachers to classes via Class module\n";
echo "    5. Import students via CSV or manually\n";
echo "    6. Configure fees, transport, timetable\n";
echo "\n";
echo "═══════════════════════════════════════════\n";

$conn->close();
