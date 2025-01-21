<?php
require_once '../database/db_connection.php'; // Include database connection
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Validate and sanitize input
$id = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$id || !$token) {
    echo "Invalid request. ID and token are required.";
    exit;
}

// Fetch the invoice data from the database
$query = "
    SELECT 
        master_invoices.invoice_attachment, 
        master_invoices.bill_no,
        master_invoices.bill_date,
        master_company.company_name
    FROM master_invoices
    LEFT JOIN master_company ON master_invoices.company_id = master_company.id
    WHERE master_invoices.id = ? AND master_invoices.bill_token = ? AND master_invoices.master_user_id = ?
";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo "Failed to prepare the statement: " . $conn->error;
    exit;
}

$stmt->bind_param("isi", $id, $token, $master_userid);

if (!$stmt->execute()) {
    echo "Error executing the query: " . $stmt->error;
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Invoice not found or invalid token.";
    exit;
}

$invoice = $result->fetch_assoc();
$file_content = $invoice['invoice_attachment'];
$bill_no = htmlspecialchars($invoice['bill_no']); // Sanitizing for safety
$bill_date = htmlspecialchars($invoice['bill_date']); // Sanitizing for safety
$company_name = htmlspecialchars($invoice['company_name']); // Sanitizing for safety

// Format the file name
$file_name = sprintf(
    "Invoice_%s_%s_%s.pdf",
    preg_replace('/[^a-zA-Z0-9]/', '_', $company_name), // Replace special characters with underscores
    $bill_no,
    date('Ymd', strtotime($bill_date)) // Format the date as YYYYMMDD
);

// Send the file for download
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=$file_name");
header("Content-Length: " . strlen($file_content));
echo $file_content;
?>
