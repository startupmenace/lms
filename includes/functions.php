<?php
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function set_flash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

function get_flash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function has_flash($key) {
    return isset($_SESSION['flash'][$key]);
}

function format_date($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

function format_currency($amount) {
    return 'KSh ' . number_format($amount, 2);
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d M', $time);
}

function upload_file($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf']) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) return false;
    $filename = uniqid() . '.' . $ext;
    $target = rtrim($target_dir, '/') . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) return $filename;
    return false;
}

function get_sidebar_class($module) {
    $current = basename($_SERVER['PHP_SELF']);
    return strpos($current, $module) !== false ? 'active' : '';
}

function get_avatar($name) {
    $initials = '';
    $parts = explode(' ', $name);
    foreach ($parts as $p) $initials .= strtoupper($p[0] ?? '');
    return substr($initials, 0, 2);
}

function get_upload_base() {
    $default = __DIR__ . '/../uploads';
    if (is_dir($default) && is_writable($default)) {
        return $default;
    }
    $tmp = sys_get_temp_dir() . '/ziada_uploads';
    if (!is_dir($tmp)) mkdir($tmp, 0777, true);
    return $tmp;
}

function ensure_upload_dir($subdir = 'students') {
    $base = get_upload_base();
    $dir = $base . '/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    return $dir;
}

function upload_url($filename, $subdir = 'students') {
    $default = __DIR__ . '/../uploads';
    if (is_dir($default) && is_writable($default)) {
        return BASE_URL . '/uploads/' . $subdir . '/' . $filename;
    }
    return BASE_URL . '/uploads/serve.php?dir=' . $subdir . '&file=' . rawurlencode($filename);
}

function student_avatar_html($student, $size = 'w-20 h-20', $text_size = 'text-3xl') {
    if (!empty($student['profile_image'])) {
        $src = upload_url($student['profile_image'], 'students');
        return '<img src="' . $src . '" alt="Photo" class="' . $size . ' rounded-full object-cover">';
    }
    $initial = get_avatar($student['parent_name'] ?? 'S');
    return '<div class="' . $size . ' rounded-full bg-white/20 flex items-center justify-center ' . $text_size . ' font-bold text-white">' . $initial . '</div>';
}

function paginate($total, $per_page, $current_page) {
    $total_pages = ceil($total / $per_page);
    return [
        'total' => $total_pages,
        'current' => $current_page,
        'prev' => $current_page > 1 ? $current_page - 1 : null,
        'next' => $current_page < $total_pages ? $current_page + 1 : null
    ];
}
