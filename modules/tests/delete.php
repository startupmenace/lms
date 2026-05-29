<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$id = (int)($_GET['id'] ?? 0);
$test = db_get_row("SELECT * FROM tests WHERE id = ?", [$id]);

if (!$test || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request.');
    redirect('index.php');
}

db_query("DELETE FROM tests WHERE id = ?", [$id]);
set_flash('success', 'Test deleted successfully.');
redirect('index.php');
