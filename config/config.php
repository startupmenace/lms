<?php
$protocol = 'http';
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') $protocol = 'https';
if ($_SERVER['SERVER_PORT'] == 443) $protocol = 'https';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) $protocol = 'https';
define('BASE_URL', $protocol . '://' . $_SERVER['HTTP_HOST']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 52428800);

// 8x8 / JAAS Jitsi Meet Configuration
define('JAAS_TENANT_KEY', 'vpaas-magic-cookie-f1accb5fcc5a4ba18d56de54efec95a5');
define('JAAS_DOMAIN', '8x8.vc');
define('JAAS_APP_ID', 'vpaas-magic-cookie-f1accb5fcc5a4ba18d56de54efec95a5');

session_start();
