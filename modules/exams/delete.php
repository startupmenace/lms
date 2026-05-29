<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$id = (int)($_GET['id'] ?? 0);
$exam = db_get_row("SELECT * FROM exams WHERE id = ?", [$id]);

if (!$exam || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request.');
    redirect('index.php');
}

db_query("DELETE FROM exams WHERE id = ?", [$id]);
set_flash('success', 'Exam deleted successfully.');
redirect('index.php');
