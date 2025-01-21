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
    // Validate and sanitize inputs
    $invoice_id = intval($_POST['invoice_id']);
    $invoice_token = htmlspecialchars($_POST['invoice_token'], ENT_QUOTES);
    $payment_date = htmlspecialchars($_POST['payment_date'], ENT_QUOTES);
    $payment_mode = htmlspecialchars($_POST['payment_mode'], ENT_QUOTES);
    $receipt_number = htmlspecialchars($_POST['receipt_number'], ENT_QUOTES);
    $payment_amount = floatval($_POST['payment_amount']);
    $payment_reference = htmlspecialchars($_POST['payment_reference'], ENT_QUOTES);

    // Check if all required fields are provided
    if (!$invoice_id || !$invoice_token || !$payment_date || !$payment_mode || !$receipt_number || !$payment_amount || !$payment_reference) {
        echo "All fields are required.";
        exit;
    }

    // Insert data into the database
    $query = "
        INSERT INTO payment_receipts 
        (invoice_id, invoice_token, payment_date, payment_mode, receipt_number, payment_amount, payment_reference, master_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo "Failed to prepare the statement: " . $conn->error;
        exit;
    }

    $stmt->bind_param(
        "issssdsi",
        $invoice_id,
        $invoice_token,
        $payment_date,
        $payment_mode,
        $receipt_number,
        $payment_amount,
        $payment_reference,
        $master_userid
    );


    if ($stmt->execute()) {
        echo "Payment receipt saved successfully!";
        header("Location: save_payment_receipt.php?id=" . $invoice_id . "&token=" . urlencode($invoice_token));
exit;
    } else {
        echo "Error saving the payment receipt: " . $stmt->error;
        exit;
    }
} else {
    echo "Invalid request method.";
    exit;
}
?>
