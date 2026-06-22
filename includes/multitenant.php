<?php
/**
 * Multi-tenant helpers — purely additive, zero core file changes.
 */

// sanitize() for super-admin pages (they don't load includes/functions.php)
if (!function_exists('sanitize')) {
    function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input ?? '')), ENT_QUOTES, 'UTF-8');
    }
}

/**
 *
 * When you're ready to activate multi-tenant mode, uncomment the
 * include line in config/config.php. Until then, the system runs
 * as a single-school deployment with zero changes.
 *
 * The router DB (teachbetter_router) lives on the same MySQL server
 * as your school DBs. It stores:
 *   - schools    : subdomain, db_name, branding, etc.
 *   - super_admins : platform-level admin accounts
 */

// Bootstrap DB constants if included before config/database.php
if (!defined('DB_HOST')) {
    $db = parse_url($_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL'));
    define('DB_HOST', $db['host']);
    define('DB_USER', $db['user']);
    define('DB_PASS', $db['pass']);
    define('DB_NAME', ltrim($db['path'], '/'));
    define('DB_PORT', $db['port'] ?? 3306);
}
// Router DB can be on a separate MySQL container
if (!defined('ROUTER_DB_HOST')) {
    $rurl = getenv('ROUTER_DATABASE_URL');
    if ($rurl) {
        $rdb = parse_url($rurl);
        define('ROUTER_DB_HOST', $rdb['host']);
        define('ROUTER_DB_USER', $rdb['user']);
        define('ROUTER_DB_PASS', $rdb['pass']);
        define('ROUTER_DB_NAME', ltrim($rdb['path'], '/'));
        define('ROUTER_DB_PORT', $rdb['port'] ?? 3306);
    } else {
        define('ROUTER_DB_HOST', DB_HOST);
        define('ROUTER_DB_USER', DB_USER);
        define('ROUTER_DB_PASS', DB_PASS);
        define('ROUTER_DB_NAME', 'teachbetter_router');
        define('ROUTER_DB_PORT', DB_PORT);
    }
}

// ── Check if router DB exists ────────────────────────
function router_db_exists() {
    $conn = new mysqli(ROUTER_DB_HOST, ROUTER_DB_USER, ROUTER_DB_PASS, '', ROUTER_DB_PORT);
    if ($conn->connect_error) return false;
    $exists = $conn->select_db(ROUTER_DB_NAME);
    $conn->close();
    return $exists;
}

// ── Router DB connection ──────────────────────────────
function router_db_connect() {
    $conn = new mysqli(ROUTER_DB_HOST, ROUTER_DB_USER, ROUTER_DB_PASS, '', ROUTER_DB_PORT);
    if ($conn->connect_error) die("Router DB: " . $conn->connect_error);
    $conn->select_db(ROUTER_DB_NAME);
    $conn->set_charset("utf8mb4");
    return $conn;
}

// ── Resolve school by hostname ────────────────────────
function resolve_school_from_domain($host = null) {
    $host = $host ?: ($_SERVER['HTTP_HOST'] ?? '');
    $parts = explode('.', $host);
    // Known second-level domains (multi-part TLDs like .co.ke, .com.au)
    $known_sld = ['co', 'com', 'org', 'net', 'gov', 'ac', 'sch', 'me', 'edu'];
    $end = count($parts) - 1;
    // Root domain is last 2 parts (e.g., example.com) or last 3 (e.g., example.co.ke)
    $root_len = ($end >= 2 && in_array($parts[$end - 1], $known_sld)) ? 3 : 2;
    if (count($parts) <= $root_len) return null; // bare domain, no subdomain
    $subdomain = $parts[0];
    if (in_array($subdomain, ['www', 'admin'], true)) return null;

    $conn = router_db_connect();
    // Look for active AND inactive schools — let caller decide what to show
    $stmt = $conn->prepare("SELECT * FROM schools WHERE subdomain = ? LIMIT 1");
    $stmt->bind_param('s', $subdomain);
    $stmt->execute();
    $school = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $school;
}

// ── Re-define db_connect to use school's DB ──────────
// Call this at the top of config.php when multi-tenant mode is active:
//   require_once __DIR__ . '/../includes/multitenant.php';
//   maybe_enable_multitenant();
function maybe_enable_multitenant() {
    $school = resolve_school_from_domain();
    if (!$school) return; // Unknown subdomain — stay on original DB
    if (!$school['is_active']) {
        // School is disabled — show clear message instead of silent fallback
        http_response_code(503);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>School Disabled</title>';
        echo '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f9fafb;color:#374151;text-align:center;padding:2rem}</style>';
        echo '</head><body><div><div style="font-size:4rem;margin-bottom:1rem">&#128274;</div>';
        echo '<h1 style="font-size:1.5rem;font-weight:700;margin-bottom:.5rem">School Disabled</h1>';
        echo '<p style="color:#6b7280">' . htmlspecialchars($school['site_name']) . ' is currently disabled.</p>';
        echo '<p style="color:#9ca3af;font-size:.875rem;margin-top:1.5rem">Contact your platform administrator to reactivate.</p>';
        echo '</div></body></html>';
        exit;
    }

    // Override DB constants for this school
    $GLOBALS['_mt_db_host'] = $school['db_host'] ?: DB_HOST;
    $GLOBALS['_mt_db_user'] = $school['db_user'] ?: DB_USER;
    $GLOBALS['_mt_db_pass'] = $school['db_pass'] ?: DB_PASS;
    $GLOBALS['_mt_db_name'] = $school['db_name'];
    $GLOBALS['_mt_db_port'] = $school['db_port'] ?: DB_PORT;

    // Redefine db_connect to use school DB
    // (original db_connect lives as _original_db_connect)
    if (!function_exists('_original_db_connect')) {
        eval('
            function _original_db_connect() {
                return call_user_func_array("db_connect", func_get_args());
            }
            function db_connect() {
                $conn = new mysqli(
                    $GLOBALS["_mt_db_host"],
                    $GLOBALS["_mt_db_user"],
                    $GLOBALS["_mt_db_pass"],
                    $GLOBALS["_mt_db_name"],
                    $GLOBALS["_mt_db_port"]
                );
                if ($conn->connect_error) die("School DB: " . $conn->connect_error);
                $conn->set_charset("utf8mb4");
                return $conn;
            }
        ');
    }

    if (!defined('SITE_NAME') && !empty($school['site_name'])) define('SITE_NAME', $school['site_name']);
    if (!empty($school['timezone'])) {
        date_default_timezone_set($school['timezone']);
    }
}

// ── Provision a new school database ───────────────────
function provision_school_db($db_name, $db_host = null, $db_user = null, $db_pass = null, $db_port = null) {
    $db_host = $db_host ?: DB_HOST;
    $db_user = $db_user ?: DB_USER;
    $db_pass = $db_pass ?: DB_PASS;
    $db_port = $db_port ?: DB_PORT;

    $conn = new mysqli($db_host, $db_user, $db_pass, '', $db_port);
    if ($conn->connect_error) throw new Exception("Cannot connect: " . $conn->connect_error);

    // Try to create — if DB already exists or lacks privilege, proceed anyway
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($db_name);

    // Schema
    $schema = file_get_contents(__DIR__ . '/../database/teachbetter_lms.sql');
    if (!$conn->multi_query($schema)) throw new Exception("Schema failed: " . $conn->error);
    while ($conn->more_results()) { $conn->next_result(); }

    // Migrations
    foreach (glob(__DIR__ . '/../database/migration-*.sql') as $mf) {
        $sql = file_get_contents($mf);
        if (trim($sql) === '') continue;
        $sql = preg_replace('/^USE\s+`[^`]+`;/im', '', $sql);
        $conn->multi_query($sql);
        while ($conn->more_results()) { $conn->next_result(); }
    }

    $conn->close();
}

// ── Create admin user in school's DB ──────────────────
function create_school_admin($db_name, $email, $password, $full_name = 'Administrator') {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    $conn->select_db($db_name);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $username = explode('@', $email)[0];
    $stmt = $conn->prepare("INSERT IGNORE INTO users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, 'admin', 1)");
    $stmt->bind_param('ssss', $username, $email, $hash, $full_name);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    return $id;
}

// ── Register school in router DB ──────────────────────
function register_school($subdomain, $site_name, $db_name, $timezone = 'Africa/Nairobi', $db_host = null, $db_user = null, $db_pass = null, $db_port = null) {
    $conn = router_db_connect();
    $stmt = $conn->prepare("INSERT IGNORE INTO schools (subdomain, site_name, timezone, db_host, db_port, db_name, db_user, db_pass) VALUES (?,?,?,?,?,?,?,?)");
    $db_host = $db_host ?: DB_HOST;
    $db_user = $db_user ?: DB_USER;
    $db_pass = $db_pass ?: DB_PASS;
    $db_port = $db_port ?: DB_PORT;
    $stmt->bind_param('ssssisss', $subdomain, $site_name, $timezone, $db_host, $db_port, $db_name, $db_user, $db_pass);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    return $id;
}
