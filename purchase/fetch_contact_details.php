<?php
require_once '../database/db_connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contact_id'])) {
    $contact_id = $_POST['contact_id'];

    // Fetch Contact Details
    $query = "SELECT mobile1, mobile2, email FROM contacts WHERE id = ? AND status = 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $contact_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $contact = mysqli_fetch_assoc($result);

    // Return JSON Response
    echo json_encode([
        'mobile1' => $contact['mobile1'] ?? '',
        'mobile2' => $contact['mobile2'] ?? '',
        'email' => $contact['email'] ?? ''
    ]);
    exit;
}
?>
