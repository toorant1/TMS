<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['master_userid'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

// Retrieve the logged-in user's ID
$master_userid = $_SESSION['master_userid'];

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_category = trim($_POST['main_cause'] ?? '');

    // Validate input
    if (empty($new_category)) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Category name is required.']);
        exit;
    }

    try {
        // Check if category already exists (case-insensitive)
        $check_query = "SELECT COUNT(*) AS count FROM master_tickets_main_causes WHERE TRIM(LOWER(main_cause)) = TRIM(LOWER(?)) AND master_user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        if (!$check_stmt) {
            throw new Exception("Failed to prepare check query: " . $conn->error);
        }
        $check_stmt->bind_param("si", $new_category, $master_userid);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $row = $check_result->fetch_assoc();

        if ($row['count'] > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'Category name already exists.']);
            exit;
        }

        // Insert new category
        $insert_query = "INSERT INTO master_tickets_main_causes (main_cause, master_user_id, status) VALUES (?, ?, 1)";
        $insert_stmt = $conn->prepare($insert_query);
        if (!$insert_stmt) {
            throw new Exception("Failed to prepare insert query: " . $conn->error);
        }
        $insert_stmt->bind_param("si", $new_category, $master_userid);

        if ($insert_stmt->execute()) {
            echo json_encode(['success' => 'Category added successfully.']);
        } else {
            throw new Exception("Failed to execute insert query: " . $insert_stmt->error);
        }
    } catch (Exception $e) {
        error_log("Error in add_main_causes.php: " . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'An error occurred while adding the category. Please try again.']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method. Use POST.']);
}
