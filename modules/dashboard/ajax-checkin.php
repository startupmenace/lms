<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$user_id = get_user_id();
$action = $_POST['action'] ?? '';
$today = date('Y-m-d');

header('Content-Type: application/json');

try {
    if ($action === 'checkin') {
        $existing = db_get_row("SELECT id, check_in, check_out FROM staff_attendance WHERE user_id=? AND date=?", [$user_id, $today]);
        if ($existing) {
            if ($existing['check_out']) {
                echo json_encode(['ok' => false, 'msg' => 'Already completed today. Checked in: ' . date('h:i A', strtotime($existing['check_in'])) . ', Checked out: ' . date('h:i A', strtotime($existing['check_out']))]);
            } else {
                echo json_encode(['ok' => false, 'msg' => 'Already checked in at ' . date('h:i A', strtotime($existing['check_in']))]);
            }
            exit;
        }
        $now = date('H:i:s');
        db_insert("INSERT INTO staff_attendance (user_id, date, status, check_in, marked_by) VALUES (?, ?, 'present', ?, ?)", [$user_id, $today, $now, $user_id]);
        echo json_encode(['ok' => true, 'msg' => 'Checked in at ' . date('h:i A'), 'time' => $now, 'action' => 'checkin']);

    } elseif ($action === 'checkout') {
        $existing = db_get_row("SELECT id, check_in, check_out FROM staff_attendance WHERE user_id=? AND date=?", [$user_id, $today]);
        if (!$existing) {
            echo json_encode(['ok' => false, 'msg' => 'Not checked in today']);
            exit;
        }
        if ($existing['check_out']) {
            echo json_encode(['ok' => false, 'msg' => 'Already checked out at ' . date('h:i A', strtotime($existing['check_out']))]);
            exit;
        }
        $now = date('H:i:s');
        $id = intval($existing['id']);
        $result = db_query("UPDATE staff_attendance SET check_out = '$now' WHERE id = $id");
        if ($result) {
            echo json_encode(['ok' => true, 'msg' => 'Checked out at ' . date('h:i A'), 'time' => $now, 'action' => 'checkout']);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Failed to check out. Please try again.']);
        }

    } elseif ($action === 'status') {
        $row = db_get_row("SELECT check_in, check_out FROM staff_attendance WHERE user_id=? AND date=?", [$user_id, $today]);
        if ($row && $row['check_in']) {
            if ($row['check_out']) {
                echo json_encode(['status' => 'checked_out', 'check_in' => $row['check_in'], 'check_out' => $row['check_out']]);
            } else {
                echo json_encode(['status' => 'checked_in', 'check_in' => $row['check_in']]);
            }
        } else {
            echo json_encode(['status' => 'none']);
        }

    } else {
        echo json_encode(['ok' => false, 'msg' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Server error. Please try again.']);
}
