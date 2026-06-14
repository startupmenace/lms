<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('student');
set_flash('info', 'Fee management is handled by your parent/guardian.');
redirect('dashboard.php');
