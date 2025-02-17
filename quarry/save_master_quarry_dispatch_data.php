<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}



// Set header to return JSON response
header('Content-Type: application/json');
// Use session user ID
$master_userid = $_SESSION['master_userid'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collecting form data
    $company_name = $_POST['company_name'];
    $entry_date = $_POST['date'];
    $delivery_challan = $_POST['delivery_challan'];
    $customer_name = $_POST['customer_name'];
    $customer_site_name = $_POST['customer_site'];
    $contact_person = !empty($_POST['contact_person']) ? $_POST['contact_person'] : NULL;
    $material_id = $_POST['material_name'];
    $gross_weight = !empty($_POST['gross_weight']) ? $_POST['gross_weight'] : 0.000;
    $tare_weight = !empty($_POST['tare_weight']) ? $_POST['tare_weight'] : 0.000;
    $net_weight = !empty($_POST['net_weight']) ? $_POST['net_weight'] : 0.000;
    $royalty_weight = !empty($_POST['royalty_weight']) ? $_POST['royalty_weight'] : 0.000;
    $vehicle = !empty($_POST['vehicle']) ? $_POST['vehicle'] : NULL;
    $loader = !empty($_POST['loader']) ? $_POST['loader'] : NULL;
    $royalty_enabled = isset($_POST['royalty_enabled']) ? 1 : 0;
    $royalty_name = !empty($_POST['royalty_name']) ? $_POST['royalty_name'] : NULL;
    $royalty_pass_no = !empty($_POST['royalty_pass_no']) ? $_POST['royalty_pass_no'] : NULL;
    $royalty_pass_count = !empty($_POST['royalty_pass_count']) ? $_POST['royalty_pass_count'] : NULL;
    $ssp_no = !empty($_POST['ssp_no']) ? $_POST['ssp_no'] : NULL;
    $user_id = $master_userid;

    // Insert Query (Ensure the placeholders `?` match the number of columns)
    $sql = "INSERT INTO master_quarry_dispatch_data (
                master_user_id, company_name_id, entry_date, delivery_challan, customer_name_id, 
                customer_site_name, contact_person, material_id, gross_weight, tare_weight, 
                net_weight, royalty_weight, vehicle, loader, royalty_enabled, royalty_name, 
                royalty_pass_no, royalty_pass_count, ssp_no, user_id
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(
            "iisssssiddddssssssii", // Corrected type string
            $master_userid, $company_name, $entry_date, $delivery_challan, $customer_name, 
            $customer_site_name, $contact_person, $material_id, $gross_weight, $tare_weight, 
            $net_weight, $royalty_weight, $vehicle, $loader, $royalty_enabled, $royalty_name, 
            $royalty_pass_no, $royalty_pass_count, $ssp_no, $user_id
        );

        if ($stmt->execute()) {
            // Get the last inserted ticket ID
            $last_id = $stmt->insert_id;
            echo json_encode(["status" => "success", "message" => "Gate Pass Entry Saved Successfully", "ticket_id" => $last_id]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database Error: " . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "SQL Prepare Error: " . $conn->error]);
    }

    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>