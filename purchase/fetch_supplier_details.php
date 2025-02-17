<?php
require_once '../database/db_connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['supplier_id'])) {
    $supplier_id = $_POST['supplier_id'];
    $master_user_id = $_SESSION['master_userid'];

    // Fetch Supplier Address with additional details
    $addressQuery = "SELECT address, district, city, state, pincode, country 
                     FROM account 
                     WHERE id = ? AND master_user_id = ?";
    $stmt = mysqli_prepare($conn, $addressQuery);
    mysqli_stmt_bind_param($stmt, "ii", $supplier_id, $master_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $supplier = mysqli_fetch_assoc($result);

    if ($supplier) {
        $address_parts = array_filter([
            $supplier['address'],
            $supplier['district'],
            $supplier['city'],
            $supplier['state'],
            $supplier['pincode'],
            $supplier['country']
        ]);
        $full_address = implode(", ", $address_parts);
    } else {
        $full_address = '';
    }

    // Fetch Contacts for the Supplier
    $contactQuery = "SELECT id, name, mobile1 FROM contacts WHERE account_id = ? AND status = 1";
    $stmt = mysqli_prepare($conn, $contactQuery);
    mysqli_stmt_bind_param($stmt, "i", $supplier_id);
    mysqli_stmt_execute($stmt);
    $contactResult = mysqli_stmt_get_result($stmt);

    $contacts = [];
    while ($row = mysqli_fetch_assoc($contactResult)) {
        $contacts[] = $row;
    }

    // Return JSON Response
    echo json_encode(['address' => $full_address, 'contacts' => $contacts]);
    exit;
}
?>
