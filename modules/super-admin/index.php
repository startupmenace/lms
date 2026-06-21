<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/multitenant.php';

// Require super admin auth
if (empty($_SESSION['super_admin_id'])) {
    header('Location: login.php');
    exit;
}

$conn = router_db_connect();
$total_schools  = $conn->query("SELECT COUNT(*) as c FROM schools")->fetch_assoc()['c'];
$active_schools = $conn->query("SELECT COUNT(*) as c FROM schools WHERE is_active=1")->fetch_assoc()['c'];
$recent_schools = $conn->query("SELECT * FROM schools ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$conn->close();

$page_title = 'Super Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold">Super Admin</h1>
                <p class="text-gray-400 text-sm">Welcome, <?= sanitize($_SESSION['super_admin_name'] ?? 'Admin') ?></p>
            </div>
            <a href="logout.php" class="bg-gray-800 hover:bg-gray-700 text-gray-300 px-4 py-2 rounded-xl text-sm transition"><i class="fas fa-sign-out-alt mr-2"></i>Sign Out</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-gray-900 rounded-2xl border border-gray-800 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-teal-900/50 flex items-center justify-center"><i class="fas fa-school text-teal-400 text-xl"></i></div>
                    <div>
                        <p class="text-2xl font-bold"><?= $total_schools ?></p>
                        <p class="text-xs text-gray-400">Total Schools</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-900 rounded-2xl border border-gray-800 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-emerald-900/50 flex items-center justify-center"><i class="fas fa-check-circle text-emerald-400 text-xl"></i></div>
                    <div>
                        <p class="text-2xl font-bold"><?= $active_schools ?></p>
                        <p class="text-xs text-gray-400">Active</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-900 rounded-2xl border border-gray-800 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-amber-900/50 flex items-center justify-center"><i class="fas fa-database text-amber-400 text-xl"></i></div>
                    <div>
                        <p class="text-2xl font-bold"><?= $total_schools - $active_schools ?></p>
                        <p class="text-xs text-gray-400">Inactive</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Schools</h2>
            <a href="create.php" class="bg-teal-600 hover:bg-teal-500 text-white px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-2">
                <i class="fas fa-plus"></i> New School
            </a>
        </div>

        <div class="bg-gray-900 rounded-2xl border border-gray-800 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-950/50 border-b border-gray-800">
                        <th class="text-left py-3 px-4 font-medium text-gray-400">School</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-400">Subdomain</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-400">Database</th>
                        <th class="text-center py-3 px-4 font-medium text-gray-400">Status</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-400">Created</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_schools)): ?>
                    <tr><td colspan="6" class="py-12 text-center text-gray-500">No schools yet. <a href="create.php" class="text-teal-400 hover:underline">Create one</a></td></tr>
                    <?php else: ?>
                    <?php foreach ($recent_schools as $s): ?>
                    <tr class="border-b border-gray-800 hover:bg-gray-800/50">
                        <td class="py-3 px-4 font-medium"><?= sanitize($s['site_name']) ?></td>
                        <td class="py-3 px-4 text-gray-400"><?= sanitize($s['subdomain']) ?>.ziada.com</td>
                        <td class="py-3 px-4 text-gray-400 font-mono text-xs"><?= sanitize($s['db_name']) ?></td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full <?= $s['is_active'] ? 'bg-emerald-900/50 text-emerald-300' : 'bg-red-900/50 text-red-300' ?>">
                                <i class="fas fa-circle text-[6px]"></i> <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-400 text-xs"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                        <td class="py-3 px-4">
                            <a href="schools.php?id=<?= $s['id'] ?>" class="text-teal-400 hover:text-teal-300 text-xs"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
