<?php
$file = basename($_GET['file'] ?? '');
$subdir = preg_replace('/[^a-z0-9_]/', '', $_GET['dir'] ?? 'students');
$upload_base = defined('UPLOAD_PATH') ? rtrim(UPLOAD_PATH, '/') : (sys_get_temp_dir() . '/ziada_uploads');
$path = $upload_base . '/' . $subdir . '/' . $file;
if (!file_exists($path)) {
    http_response_code(404);
    exit;
}
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mimes = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'];
$mime = $mimes[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=31536000');
readfile($path);
