<?php
/**
 * Multi-tenant database routing.
 * DATABASE_URL points to the ROUTER database (teachbetter_router).
 * On each request, resolves school by subdomain and connects to that school's database.
 */

$db_url = parse_url($_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL'));

define('ROUTER_DB_HOST', $db_url['host']);
define('ROUTER_DB_USER', $db_url['user']);
define('ROUTER_DB_PASS', $db_url['pass']);
define('ROUTER_DB_NAME', ltrim($db_url['path'], '/'));
define('ROUTER_DB_PORT', $db_url['port'] ?? 3306);

// ── Default school connection (same as router, overridden below) ──
$GLOBALS['_db_host'] = ROUTER_DB_HOST;
$GLOBALS['_db_user'] = ROUTER_DB_USER;
$GLOBALS['_db_pass'] = ROUTER_DB_PASS;
$GLOBALS['_db_name'] = ROUTER_DB_NAME;
$GLOBALS['_db_port'] = ROUTER_DB_PORT;

// ── Branch: allow CLI scripts to skip subdomain resolution ──
$is_cli = (php_sapi_name() === 'cli');
$host   = !$is_cli ? ($_SERVER['HTTP_HOST'] ?? '') : '';
$parts  = explode('.', $host);
$subdomain = (count($parts) > 2) ? $parts[0] : '';

// ── Resolve school by subdomain ──
if ($subdomain && !in_array($subdomain, ['www', 'admin'], true)) {
    $rconn = new mysqli(ROUTER_DB_HOST, ROUTER_DB_USER, ROUTER_DB_PASS, ROUTER_DB_NAME, ROUTER_DB_PORT);
    if (!$rconn->connect_error) {
        $stmt = $rconn->prepare("SELECT * FROM schools WHERE subdomain = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param('s', $subdomain);
        $stmt->execute();
        $school = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $rconn->close();
        if ($school) {
            define('SCHOOL_ID', (int)$school['id']);
            $GLOBALS['_db_host'] = $school['db_host'] ?: ROUTER_DB_HOST;
            $GLOBALS['_db_user'] = $school['db_user'] ?: ROUTER_DB_USER;
            $GLOBALS['_db_pass'] = $school['db_pass'] ?: ROUTER_DB_PASS;
            $GLOBALS['_db_name'] = $school['db_name'];
            $GLOBALS['_db_port'] = $school['db_port'] ?: ROUTER_DB_PORT;
            define('SITE_NAME', $school['site_name'] ?: 'Ziada LMS');
            define('TIMEZONE', $school['timezone'] ?: 'Africa/Nairobi');
        }
    }
}

// ── Fallback branding (when no school resolved or CLI) ──
if (!defined('SITE_NAME')) define('SITE_NAME', 'Ziada LMS');
if (!defined('TIMEZONE')) define('TIMEZONE', 'Africa/Nairobi');

date_default_timezone_set(TIMEZONE);

// ── DB helper functions (use school DB when resolved, router DB otherwise) ──

function db_connect() {
    $conn = new mysqli(
        $GLOBALS['_db_host'],
        $GLOBALS['_db_user'],
        $GLOBALS['_db_pass'],
        $GLOBALS['_db_name'],
        $GLOBALS['_db_port']
    );
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

function db_query($sql, $params = []) {
    $conn = db_connect();
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $types = '';
            foreach ($params as $p) {
                if (is_int($p)) $types .= 'i';
                elseif (is_double($p)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            $conn->close();
            return $result;
        }
    }
    $result = $conn->query($sql);
    $conn->close();
    return $result;
}

function db_insert($sql, $params = []) {
    $conn = db_connect();
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $types = '';
            foreach ($params as $p) {
                if (is_int($p)) $types .= 'i';
                elseif (is_double($p)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            $conn->close();
            return $id;
        }
    }
    $conn->query($sql);
    $id = $conn->insert_id;
    $conn->close();
    return $id;
}

function db_get_row($sql, $params = []) {
    $result = db_query($sql, $params);
    return $result ? $result->fetch_assoc() : null;
}

function db_get_all($sql, $params = []) {
    $result = db_query($sql, $params);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
