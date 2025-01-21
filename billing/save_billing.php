<?php
require_once '../database/db_connection.php'; // Include database connection
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = $_POST['company'] ?? null;
    $bill_no = $_POST['bill_no'] ?? null;
    $bill_date = $_POST['bill_date'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $due_date = $_POST['due_date'] ?? null;
    $bill_attachment = $_FILES['bill_attachment'] ?? null;
    $ticket_id = $_POST['ticket_id'] ?? null;
    $billing_status_id = $_POST['billing_status_id'] ?? null;

    $bill_remark = $_POST['remark'] ?? null;

    // Generate a unique bill token
    $bill_token = bin2hex(random_bytes(15)); // Generates a 30-character token

    // Validate required fields
    if (!$company_id || !$bill_no || !$bill_date || !$amount || !$due_date || !$bill_attachment || !$ticket_id || !$billing_status_id) {
        echo "All fields are required.";
        exit;
    }

    // Validate file type and size
    $allowed_types = ['application/pdf'];
    $file_tmp = $bill_attachment['tmp_name'];
    $file_type = mime_content_type($file_tmp);
    $file_size = $bill_attachment['size'];

    if (!in_array($file_type, $allowed_types)) {
        echo "Only PDF files are allowed.";
        exit;
    }

    if ($file_size > 1048576) { // 1 MB = 1048576 bytes
        echo "File size must not exceed 1 MB.";
        exit;
    }

    // Read file contents
    $file_content = file_get_contents($file_tmp);

    // Insert data into the database
    $query = "
        INSERT INTO master_invoices (company_id, ticket_id, billing_status_id, bill_no, bill_date, amount, due_date, invoice_attachment, bill_token, master_user_id, bill_remark, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo "Failed to prepare the statement: " . $conn->error;
        exit;
    }

    $stmt->bind_param("iiissdssssi", $company_id, $ticket_id, $billing_status_id, $bill_no, $bill_date, $amount, $due_date, $file_content, $bill_token, $master_userid, $bill_remark);

    if ($stmt->execute()) {
        echo "Billing data saved successfully. Bill Token: $bill_token";
    } else {
        echo "Error saving billing data: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}

$conn->close();
?>
