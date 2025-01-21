<?php
require_once '../database/db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receipt_id = $_POST['receipt_id'];
    $invoice_id = $_POST['invoice_id'];
    $payment_date = $_POST['payment_date'];
    $payment_mode = $_POST['payment_mode'];
    $receipt_number = $_POST['receipt_number'];
    $payment_amount = $_POST['payment_amount'];
    $payment_reference = $_POST['payment_reference'];

    $query = "UPDATE payment_receipts SET 
                payment_date = ?, 
                payment_mode = ?, 
                receipt_number = ?, 
                payment_amount = ?, 
                payment_reference = ? 
              WHERE receipt_number = ? AND invoice_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "sssdsis", 
        $payment_date, 
        $payment_mode, 
        $receipt_number, 
        $payment_amount, 
        $payment_reference, 
        $receipt_id, 
        $invoice_id
    );

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    } else {
        echo "Failed to update receipt: " . $stmt->error;
    }
}
?>
