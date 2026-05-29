<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$id = (int)($_GET['id'] ?? 0);
$student = db_get_row("SELECT * FROM students WHERE id = ?", [$id]);

if (!$student || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request.');
    redirect('index.php');
}

db_query("DELETE FROM students WHERE id = ?", [$id]);
set_flash('success', 'Student deleted successfully.');
redirect('index.php');
