<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$test_id = (int)($_GET['test_id'] ?? 0);
$qid = (int)($_GET['qid'] ?? 0);
$test = db_get_row("SELECT * FROM tests WHERE id = ?", [$test_id]);

if (!$test || !$qid) {
    set_flash('error', 'Invalid request.');
    redirect('index.php');
}

db_query("DELETE FROM questions WHERE id = ? AND test_id = ?", [$qid, $test_id]);
set_flash('success', 'Question removed.');
redirect('add-questions.php?id=' . $test_id);
