<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/multitenant.php';

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: login.php?error=invalid');
    exit;
}

$conn = router_db_connect();
$stmt = $conn->prepare("SELECT * FROM super_admins WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row || !password_verify($password, $row['password'])) {
    header('Location: login.php?error=invalid');
    exit;
}

$_SESSION['super_admin_id']   = $row['id'];
$_SESSION['super_admin_email'] = $row['email'];
$_SESSION['super_admin_name']  = $row['full_name'];

header('Location: index.php');
exit;
