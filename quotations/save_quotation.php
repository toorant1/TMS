<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../database/db_connection.php';
session_start();

// Retrieve data from POST request
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request data."]);
    exit;
}

// Extract data
$companyID = $data['companyID'];
$quotationDate = $data['quotationDate'];
$quotationValidDate = $data['quotationValidDate'];
$statusID = $data['statusID'];
$internalID = $data['internalID'];
$termsConditions = $data['termsConditions'];
$materials = $data['materials'];

// Begin transaction
$conn->begin_transaction();

try {
    // Generate unique quotation number
    $masterUserID = $_SESSION['master_userid'];
    $currentYear = date('Y');
    $currentMonth = date('m');

    // Fetch the last quotation number for the current year and master user
    $query = "SELECT quotation_number 
              FROM master_quotations 
              WHERE master_user_id = ? AND YEAR(quotation_date) = ? 
              ORDER BY quotation_id DESC 
              LIMIT 1";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparing query for fetching last quotation number: " . $conn->error);
    }
    $stmt->bind_param("ii", $masterUserID, $currentYear);
    $stmt->execute();
    $result = $stmt->get_result();

    $lastQuotationNumber = $result->fetch_assoc()['quotation_number'] ?? null;
    $stmt->close();

    // Determine the next quotation number
    if ($lastQuotationNumber) {
        // Extract the last numeric part and increment it
        $lastNumber = (int)substr($lastQuotationNumber, -4);
        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        // Start with 0001 if no quotations exist for the current year
        $nextNumber = '0001';
    }

    $quotationNumber = "Quote-$currentYear-$currentMonth-$nextNumber";
    $quotationToken = "QUOTE_" . uniqid(); // Generate token for the quotation

    // Insert into the master_quotations table
    $query = "INSERT INTO master_quotations (
        quotation_reference, company_id, quotation_number, quotation_date, 
        quotation_valid_upto_date, quotation_status_id, 
        payment_conditions, delivery_conditions, other_conditions, internal_remark_conditions, 
        master_user_id, quotation_token
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparing query for master_quotations: " . $conn->error);
    }
    $stmt->bind_param(
        "sissssssssis",
        $internalID,
        $companyID,
        $quotationNumber,
        $quotationDate,
        $quotationValidDate,
        $statusID,
        $termsConditions['paymentConditions'], // Individual field
        $termsConditions['deliveryConditions'], // Individual field
        $termsConditions['otherConditions'], // Individual field
        $termsConditions['internalRemarkConditions'], // Individual field
        $masterUserID,
        $quotationToken
    );
    
    $stmt->execute();
    $quotationID = $stmt->insert_id; // Get the inserted quotation ID
    $stmt->close();

    // Insert materials into the master_quotations_materials table
    $materialQuery = "INSERT INTO master_quotations_materials (
                        master_quotation_id, material_id, quantity, 
                        unit_price, hsn_code, hsn_percentage, 
                        master_quotation_materials_remark, master_quotation_materials_token
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($materialQuery);
    if (!$stmt) {
        throw new Exception("Error preparing query for master_quotations_materials: " . $conn->error);
    }

    foreach ($materials as $material) {
        $materialToken = "QUOTE_MAT_" . uniqid(); // Generate token for each material

        $stmt->bind_param(
            "iiddssss",
            $quotationID,
            $material['materialID'],
            $material['quantity'],
            $material['price'],
            $material['hsnCode'],
            $material['hsnPercentage'],
            $material['materialRemark'],
            $materialToken
        );
        $stmt->execute();
    }
    $stmt->close();

    // Commit transaction
    $conn->commit();

    // Respond with success
    echo json_encode([
        "success" => true,
        "message" => "Quotation saved successfully.",
        "quotation_number" => $quotationNumber,
        "quotation_token" => $quotationToken
    ]);

} catch (Exception $e) {
    // Rollback transaction in case of error
    $conn->rollback();

    // Respond with error
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
