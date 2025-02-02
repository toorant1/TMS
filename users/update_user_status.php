<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo "error";
    exit();
}

if (isset($_POST['user_id']) && isset($_POST['status'])) {
    $user_id = intval($_POST['user_id']);
    $new_status = intval($_POST['status']);

    $update_query = "UPDATE master_users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    
    if ($stmt) {
        $stmt->bind_param("ii", $new_status, $user_id);
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }
        $stmt->close();
    } else {
        echo "error";
    }
}

$conn->close();
?>
