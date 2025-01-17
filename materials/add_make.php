<?php
require_once '../database/db_connection.php';
session_start();

$response = ['success' => false, 'message' => '', 'id' => '', 'make' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $make = trim($_POST['newMake']);
    $remark = trim($_POST['remark']);
    $master_user_id = intval($_POST['master_user_id']);
    $created_on = date('Y-m-d H:i:s');
    $token = uniqid('make_', true);
    $status = 1; // Default status for a new make

    if (!empty($make)) {
        // Check for duplicate make
        $check_query = "SELECT id FROM master_materials_make WHERE master_user_id = ? AND make = ? AND status = 1";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("is", $master_user_id, $make);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $response['message'] = "Make already exists.";
        } else {
            // Insert the new make
            $sql = "INSERT INTO master_materials_make (master_user_id, make, remark, created_on, updated_on, token, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssi", $master_user_id, $make, $remark, $created_on, $created_on, $token, $status);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['id'] = $conn->insert_id;
                $response['make'] = $make;
            } else {
                $response['message'] = "Error adding make: " . $conn->error;
            }

            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $response['message'] = "Make name cannot be empty.";
    }
} else {
    $response['message'] = "Invalid request method.";
}

header("Content-Type: application/json");
echo json_encode($response);
?>
