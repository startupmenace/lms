<?php
$protocol = 'http';
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') $protocol = 'https';
if ($_SERVER['SERVER_PORT'] == 443) $protocol = 'https';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) $protocol = 'https';
define('BASE_URL', $protocol . '://' . $_SERVER['HTTP_HOST']);
define('SITE_NAME', 'Ziada LMS');
define('TIMEZONE', 'Africa/Nairobi');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 52428800);

// 8x8 / JAAS Jitsi Meet Configuration
define('JAAS_TENANT_KEY', 'vpaas-magic-cookie-f1accb5fcc5a4ba18d56de54efec95a5');
define('JAAS_DOMAIN', '8x8.vc');
define('JAAS_APP_ID', 'vpaas-magic-cookie-f1accb5fcc5a4ba18d56de54efec95a5');

// Router DB for multi-tenant — separate from main app DB
if (!defined('ROUTER_DB_HOST')) {
    define('ROUTER_DB_HOST', '31.97.69.182');
    define('ROUTER_DB_PORT', 3310);
    define('ROUTER_DB_USER', 'fortunelangat54@gmail.com');
    define('ROUTER_DB_PASS', 'teachbetter_router@001');
    define('ROUTER_DB_NAME', 'teachbetter_router');
}

// Multi-tenant subdomain routing — comment out to disable
require_once __DIR__ . '/../includes/multitenant.php';
maybe_enable_multitenant();

date_default_timezone_set(TIMEZONE);
session_start();
