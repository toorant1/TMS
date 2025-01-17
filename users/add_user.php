<?php
require_once '../database/db_connection.php';
session_start();

$message = "";

// Check if the user ID is available in the session
if (!isset($_SESSION['master_userid'])) {
    header('Location: ../index.php');
    exit();
}

$master_userid = $_SESSION['master_userid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    function validate_input($data) {
        return htmlspecialchars(trim($data));
    }

    $fields = [
        'name', 'sex', 'address', 'state', 'district', 'city',
        'pincode', 'mobile', 'email', 'password'
    ];

    $data = [];
    foreach ($fields as $field) {
        $data[$field] = validate_input($_POST[$field] ?? '');
    }

    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        $message = "Please fill out all required fields!";
    } else {
        // Check if email is unique
        $email_check_query = "SELECT email FROM master_users WHERE email = ?";
        $stmt = $conn->prepare($email_check_query);
        $stmt->bind_param("s", $data['email']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Error: Email is already registered.";
            $stmt->close();
        } else {
            $stmt->close();

            // Generate a unique token and hash the password
            $token = uniqid('user_', true);
            $password_reset_token = bin2hex(random_bytes(16));
            $password_reset_token_status = 0;
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

            // SQL query to insert data
            $sql = "INSERT INTO master_users 
                    (master_user_id, token, " . implode(',', $fields) . ", password_reset_token, password_reset_token_status, created_on) 
                    VALUES (?, ?, " . implode(',', array_fill(0, count($fields), '?')) . ", ?, ?, NOW())";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $types = str_repeat('s', count($fields)) . "ssi";
                $params = array_merge([$master_userid, $token], array_values($data), [$password_reset_token, $password_reset_token_status]);
                $stmt->bind_param("s" . $types, ...$params);

                if ($stmt->execute()) {
                    $message = "User added successfully!";
                } else {
                    error_log("Database error: " . $stmt->error);
                    $message = "An error occurred while adding the user. Please try again.";
                }

                $stmt->close();
            } else {
                error_log("Database error: " . $conn->error);
                $message = "An error occurred. Please try again.";
            }
        }
    }
}

$conn->close();
?>
