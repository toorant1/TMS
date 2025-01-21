<?php
require_once '../database/db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $billing_id = $_POST['billing_id'];
    $company_id = $_POST['company'];
    $bill_no = $_POST['bill_no'];
    $bill_date = $_POST['bill_date'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $billing_status_id = $_POST['billing_status_id'];
    $remark = $_POST['remark'];

    $query = "UPDATE master_invoices SET 
                company_id = ?, 
                bill_no = ?, 
                bill_date = ?, 
                amount = ?, 
                due_date = ?, 
                billing_status_id = ?, 
                remark = ? 
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issdisii", $company_id, $bill_no, $bill_date, $amount, $due_date, $billing_status_id, $remark, $billing_id);
    if ($stmt->execute()) {
        header("Location: ticket_details.php?ticket_id=" . $_POST['ticket_id']);
        exit;
    } else {
        echo "Failed to update billing details: " . $stmt->error;
    }
}
?>
