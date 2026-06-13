<?php
/**
 * One-time parent setup — visit this URL once then DELETE this file.
 * Creates the student_parents table, adds parent role + permissions,
 * creates a parent user, and links parent to student ID 1.
 */
$secret = $_GET['key'] ?? '';
if ($secret !== 'setup2026') {
    http_response_code(403);
    die('Forbidden');
}

require_once __DIR__ . '/config/database.php';

echo "<pre>\n";
echo "🔧 Running parent setup...\n\n";

// 1. Run migration SQL
$migration = file_get_contents(__DIR__ . '/database/migration-parent-role.sql');
$statements = explode(';', $migration);
$success = 0;
$errors = 0;
foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    try {
        db_query($stmt);
        $success++;
    } catch (Throwable $e) {
        echo "⚠️  " . $e->getMessage() . "\n";
        $errors++;
    }
}
echo "✅ Migration: {$success} OK, {$errors} skipped\n\n";

// 2. Create parent user
$email = 'parent@jewelhouse.sc.ke';
$existing = db_get_row("SELECT id FROM users WHERE email = ?", [$email]);
if ($existing) {
    echo "👤 Parent user already exists (ID: {$existing['id']})\n";
    $parent_id = $existing['id'];
} else {
    $hash = password_hash('password', PASSWORD_BCRYPT);
    $parent_id = db_insert(
        "INSERT INTO users (username, email, password, full_name, phone, role, is_active) VALUES (?, ?, ?, ?, ?, 'parent', 1)",
        ['parent', $email, $hash, 'Parent User', '9876500100']
    );
    echo "✅ Created parent user (ID: {$parent_id})\n";
}

// 3. Link to student ID 1
$linked = db_get_row("SELECT id FROM student_parents WHERE student_id = 1 AND parent_user_id = ?", [$parent_id]);
if ($linked) {
    echo "👤 Already linked to student ID 1\n";
} else {
    db_insert("INSERT INTO student_parents (student_id, parent_user_id) VALUES (1, ?)", [$parent_id]);
    echo "✅ Linked parent to student ID 1\n";
}

echo "\n──────────────────────────────\n";
echo "📧 Email:    parent@jewelhouse.sc.ke\n";
echo "🔑 Password: password\n";
echo "──────────────────────────────\n";
echo "\n⚠️  DELETE THIS FILE after use!\n";
echo "</pre>";
