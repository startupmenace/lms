<?php
/**
 * Schema Checker — compares expected columns from SQL dump against live DB.
 * Usage: php database/schema-check.php
 * Requires DATABASE_URL env var or --host --user --pass --db flags.
 */

$longopts = [
    'host:', 'user:', 'pass:', 'db:', 'port:',
    'sql:',
];
$opts = getopt('', $longopts);

// ── DB connection ──
if ($url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL')) {
    $parts = parse_url($url);
    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? 3306;
    $user = $parts['user'] ?? 'root';
    $pass = $parts['pass'] ?? '';
    $db   = ltrim($parts['path'] ?? '', '/');
} else {
    $host = $opts['host'] ?? 'localhost';
    $port = $opts['port'] ?? 3306;
    $user = $opts['user'] ?? 'root';
    $pass = $opts['pass'] ?? '';
    $db   = $opts['db'] ?? 'teachbetter_lms';
}

$sql_file = $opts['sql'] ?? __DIR__ . '/teachbetter_lms.sql';

try {
    $conn = new mysqli($host, $user, $pass, $db, (int)$port);
    if ($conn->connect_error) die("❌ Connection failed: " . $conn->connect_error . "\n");
} catch (Throwable $e) {
    die("❌ " . $e->getMessage() . "\n");
}

echo "🔍 Connected to $db on $host:$port\n";
echo "📄 Loading expected schema from $sql_file ...\n\n";

// ── Parse expected CREATE TABLE statements from SQL file ──
$content = file_get_contents($sql_file);
preg_match_all('/CREATE TABLE\s+`?(\w+)`?\s*\(((?:[^()]|\([^()]*\))*)\)\s*(?:ENGINE|=)/is', $content, $matches, PREG_SET_ORDER);

$expected_tables = [];
foreach ($matches as $m) {
    $table = $m[1];
    $body  = $m[2];
    $cols = [];

    // Split on top-level commas only (skip commas inside parentheses)
    $parts = [];
    $depth = 0;
    $buf = '';
    for ($i = 0, $len = strlen($body); $i < $len; $i++) {
        $ch = $body[$i];
        if ($ch === '(') $depth++;
        elseif ($ch === ')') $depth--;
        elseif ($ch === ',' && $depth === 0) { $parts[] = trim($buf); $buf = ''; continue; }
        $buf .= $ch;
    }
    if (trim($buf)) $parts[] = trim($buf);

    foreach ($parts as $part) {
        if (!preg_match('/^\s*`(\w+)`\s+(.+)/i', $part, $cm)) continue;
        $col = $cm[1];
        $def = trim($cm[2]);
        // Base type + modifiers (stop before NOT NULL, DEFAULT, etc.)
        if (preg_match('/^(\w+(?:\([^)]*\))?(?:\s+(?:unsigned|signed|character\s+set\s+\w+|collate\s+\w+|binary|zerofill|ascii|unicode))*)/i', $def, $type_m)) {
            $raw = rtrim($type_m[1], ' ,');
        } else {
            $raw = rtrim($def, ' ,');
        }
        $cols[$col] = $raw;
    }
    $expected_tables[$table] = $cols;
}

// ── Compare against live DB ──
$result = $conn->query("SHOW TABLES");
$live_tables = [];
while ($row = $result->fetch_array()) $live_tables[] = $row[0];

$exit = 0;
$all_ok = true;

foreach ($expected_tables as $table => $expected_cols) {
    $exists = in_array($table, $live_tables);
    if (!$exists) {
        echo "❌ Table `$table` is MISSING in live DB\n";
        $all_ok = false;
        $exit = 1;
        continue;
    }

    $res = $conn->query("SHOW COLUMNS FROM `$table`");
    $live_cols = [];
    while ($row = $res->fetch_assoc()) $live_cols[$row['Field']] = $row['Type'];

    $missing = array_diff_key($expected_cols, $live_cols);
    $extra   = array_diff_key($live_cols, $expected_cols);
    $type_mismatch = [];

    foreach ($expected_cols as $col => $exp_type) {
        if (isset($live_cols[$col])) {
            $norm1 = strtoupper(preg_replace('/\s/', '', $exp_type));
            $norm2 = strtoupper(preg_replace('/\s/', '', $live_cols[$col]));
            if ($norm1 !== $norm2) {
                $type_mismatch[] = "  `$col` expected $exp_type, got {$live_cols[$col]}";
            }
        }
    }

    if ($missing || $extra || $type_mismatch) {
        echo !$all_ok ? "\n" : '';
        echo "⚠️  Table `$table`:\n";
        foreach ($missing as $col => $type) echo "  ➕ MISSING column `$col` ($type)\n";
        foreach ($extra as $col => $type) echo "  🗑️  EXTRA column `$col` ($type) — may need cleanup\n";
        foreach ($type_mismatch as $m) echo "  🔄 $m\n";
        $all_ok = false;
        $exit = 1;
    }
}

// ── Check for live tables not in schema ──
$extra_tables = array_diff($live_tables, array_keys($expected_tables), ['role_permissions', 'roles']);
// Skip known migration-created tables
$migration_tables = ['class_teachers', 'homework', 'homework_submissions', 'class_resources'];
$extra_tables = array_diff($extra_tables, $migration_tables);

if ($extra_tables) {
    echo "\n📦 Tables in live DB but NOT in schema:\n";
    foreach ($extra_tables as $t) echo "  • `$t`\n";
}

// ── Summary ──
if ($all_ok) {
    echo "✅ Schema is in sync — all " . count($expected_tables) . " tables match.\n";
} else {
    echo "\n❌ Schema drift detected. Run the migration SQL files to fix.\n";
}

$conn->close();
exit($exit);
