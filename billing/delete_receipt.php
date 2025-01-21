<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$receiptId = $_GET['receipt_id'] ?? null;

if ($receiptId) {
    $stmt = $conn->prepare("DELETE FROM payment_receipts WHERE receipt_number = ?");
    $stmt->bind_param("s", $receiptId);
    if ($stmt->execute()) {
        echo "Receipt deleted successfully.";
    } else {
        echo "Failed to delete the receipt.";
    }
    $stmt->close();
} else {
    echo "Invalid receipt ID.";
}
?>
