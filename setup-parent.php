<?php
/** Visit once, then DELETE this file. /setup-parent.php?key=setup2026 */
$secret = $_GET['key'] ?? '';
if ($secret !== 'setup2026') { http_response_code(403); die('Forbidden'); }

require_once __DIR__ . '/config/database.php';

echo "<pre>\n🔧 Parent setup...\n\n";

// Fix: ensure role column accepts 'parent'
$col = db_get_row("SHOW COLUMNS FROM users WHERE Field = 'role'");
if ($col && strpos($col['Type'], 'enum') !== false) {
    db_query("ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL DEFAULT 'staff'");
    echo "✅ Changed users.role from ENUM to VARCHAR\n\n";
}

// Run migration SQL
$migration = file_get_contents(__DIR__ . '/database/migration-parent-role.sql');
foreach (explode(';', $migration) as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    try { db_query($stmt); } catch (Throwable $e) { echo "⚠️  " . $e->getMessage() . "\n"; }
}
echo "✅ Migration applied\n\n";

// Create parent user
$email = 'parent@jewelhouse.sc.ke';
$existing = db_get_row("SELECT id FROM users WHERE email = ?", [$email]);
if ($existing) {
    echo "👤 Parent exists (ID: {$existing['id']})\n";
    $pid = $existing['id'];
    // Re-hash password just in case
    db_query("UPDATE users SET password = ? WHERE id = ?", [password_hash('password', PASSWORD_BCRYPT), $pid]);
    echo "✅ Password re-hashed\n";
} else {
    $pid = db_insert(
        "INSERT INTO users (username, email, password, full_name, phone, role, is_active) VALUES (?, ?, ?, ?, ?, 'parent', 1)",
        ['parent', $email, password_hash('password', PASSWORD_BCRYPT), 'Parent User', '9876500100']
    );
    echo "✅ Created parent user (ID: {$pid})\n";
}

// Link to student 1
$linked = db_get_row("SELECT id FROM student_parents WHERE student_id = 1 AND parent_user_id = ?", [$pid]);
if ($linked) {
    echo "👤 Already linked to student ID 1\n";
} else {
    db_insert("INSERT INTO student_parents (student_id, parent_user_id) VALUES (1, ?)", [$pid]);
    echo "✅ Linked to student ID 1\n";
}

echo "\n──────────────────────────────\n";
echo "📧 parent@jewelhouse.sc.ke\n🔑 password\n──────────────────────────────\n";
echo "⚠️  DELETE this file after use!\n</pre>";
