<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['super_admin_id'])) { header('Location: login.php'); exit; }

$toggle = $_GET['toggle'] ?? null;
$delete = $_GET['delete'] ?? null;

if ($toggle) {
    $s = db_get_row("SELECT id, is_active FROM schools WHERE id=?", [(int)$toggle]);
    if ($s) {
        $new = $s['is_active'] ? 0 : 1;
        db_query("UPDATE schools SET is_active=? WHERE id=?", [$new, $s['id']]);
    }
    header('Location: schools.php');
    exit;
}

if ($delete) {
    $s = db_get_row("SELECT id, db_name FROM schools WHERE id=?", [(int)$delete]);
    if ($s) {
        // Optionally drop the school database
        db_query("UPDATE schools SET is_active=0 WHERE id=?", [$s['id']]);
    }
    header('Location: schools.php');
    exit;
}

$search  = $_GET['search'] ?? '';
$where   = '';
$params  = [];
if ($search) {
    $where = "WHERE site_name LIKE ? OR subdomain LIKE ?";
    $p = "%$search%";
    $params = [$p, $p];
}
$schools = db_get_all("SELECT * FROM schools $where ORDER BY created_at DESC", $params);

$page_title = 'Manage Schools';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="index.php" class="text-gray-400 hover:text-gray-300 text-sm mb-1 inline-block"><i class="fas fa-arrow-left mr-1"></i> Dashboard</a>
            <h1 class="text-2xl font-bold">All Schools</h1>
        </div>
        <a href="create.php" class="bg-teal-600 hover:bg-teal-500 text-white px-4 py-2 rounded-xl text-sm transition flex items-center gap-2"><i class="fas fa-plus"></i> New School</a>
    </div>

    <form method="GET" class="mb-6">
        <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search by name or subdomain..." class="w-full max-w-md bg-gray-900 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-teal-500 outline-none">
    </form>

    <div class="bg-gray-900 rounded-2xl border border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-gray-950/50 border-b border-gray-800">
                <th class="text-left py-3 px-4 font-medium text-gray-400">School</th>
                <th class="text-left py-3 px-4 font-medium text-gray-400">Subdomain</th>
                <th class="text-left py-3 px-4 font-medium text-gray-400">DB</th>
                <th class="text-left py-3 px-4 font-medium text-gray-400">Timezone</th>
                <th class="text-center py-3 px-4 font-medium text-gray-400">Status</th>
                <th class="text-left py-3 px-4 font-medium text-gray-400">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($schools as $s): ?>
            <tr class="border-b border-gray-800 hover:bg-gray-800/50">
                <td class="py-3 px-4 font-medium"><?= sanitize($s['site_name']) ?></td>
                <td class="py-3 px-4 text-gray-400"><?= sanitize($s['subdomain']) ?></td>
                <td class="py-3 px-4 text-gray-400 font-mono text-xs"><?= sanitize($s['db_name']) ?></td>
                <td class="py-3 px-4 text-gray-400 text-xs"><?= sanitize($s['timezone']) ?></td>
                <td class="py-3 px-4 text-center">
                    <a href="?toggle=<?= $s['id'] ?>" class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full <?= $s['is_active'] ? 'bg-emerald-900/50 text-emerald-300' : 'bg-red-900/50 text-red-300 hover:bg-emerald-900/50 hover:text-emerald-300' ?>">
                        <i class="fas fa-circle text-[6px]"></i> <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                    </a>
                </td>
                <td class="py-3 px-4">
                    <a href="?delete=<?= $s['id'] ?>" class="text-red-400 hover:text-red-300 text-xs" onclick="return confirm('Deactivate this school?')"><i class="fas fa-ban"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
