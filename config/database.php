<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'Fortuner');
define('DB_PASS', 'teachbetterlms@001');
define('DB_NAME', 'teachbetter_lms');

function db_connect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

function db_query($sql, $params = []) {
    $conn = db_connect();
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $types = '';
            foreach ($params as $p) {
                if (is_int($p)) $types .= 'i';
                elseif (is_double($p)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            $conn->close();
            return $result;
        }
    }
    $result = $conn->query($sql);
    $conn->close();
    return $result;
}

function db_insert($sql, $params = []) {
    $conn = db_connect();
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $types = '';
            foreach ($params as $p) {
                if (is_int($p)) $types .= 'i';
                elseif (is_double($p)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            $conn->close();
            return $id;
        }
    }
    $conn->query($sql);
    $id = $conn->insert_id;
    $conn->close();
    return $id;
}

function db_get_row($sql, $params = []) {
    $result = db_query($sql, $params);
    return $result ? $result->fetch_assoc() : null;
}

function db_get_all($sql, $params = []) {
    $result = db_query($sql, $params);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
