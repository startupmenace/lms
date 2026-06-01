<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('hr');

$page_title = 'HR Module';
include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto text-center py-20">
    <div class="w-20 h-20 bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-amber-200">
        <i class="fas fa-users text-3xl text-white"></i>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 mb-2">HR Module</h1>
    <div class="inline-flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold px-3 py-1 rounded-full mb-4">
        <i class="fas fa-flask"></i> Beta Phase
    </div>
    <p class="text-gray-500 text-sm">This module is still in development. Check back soon!</p>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
