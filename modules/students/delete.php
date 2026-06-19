<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
$student = db_get_row("SELECT * FROM students WHERE id = ?", [$id]);

if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('index.php');
}

db_query("DELETE FROM students WHERE id = ?", [$id]);
set_flash('success', 'Student deleted successfully.');
redirect('index.php');
