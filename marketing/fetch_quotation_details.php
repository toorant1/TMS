<?php
// Include database connection
require_once '../database/db_connection.php';

// Retrieve internal_id and master_user_id from GET parameters
$internal_id = isset($_GET['internal_id']) ? trim($_GET['internal_id']) : '';
$master_user_id = isset($_GET['master_user_id']) ? intval($_GET['master_user_id']) : 0;

if (empty($internal_id) || empty($master_user_id)) {
    echo json_encode(['error' => 'Invalid request. Missing Internal ID or Master User ID.']);
    exit;
}

// Debugging logs (Disable these in production)
error_log("Internal ID: $internal_id, Master User ID: $master_user_id");

// Query to fetch quotations
$query = "
    SELECT 
        mq.quotation_id, 
        mq.quotation_reference, 
        mq.quotation_number, 
        mq.quotation_date, 
        mq.quotation_valid_upto_date, 
        mq.quotation_token,
        IFNULL(cmp.company_name, 'N/A') AS company_name,
        IFNULL(s.status_name, 'N/A') AS status_name
    FROM 
        master_quotations mq
    LEFT JOIN 
        master_company cmp ON mq.company_id = cmp.id
    LEFT JOIN 
        master_quotations_status s ON mq.quotation_status_id = s.quotation_status_id
    WHERE 
        mq.quotation_reference = ? AND 
        mq.master_user_id = ?
    ORDER BY 
        mq.quotation_date DESC;
";

$stmt = $conn->prepare($query);

if (!$stmt) {
    error_log('Query Preparation Failed: ' . $conn->error);
    echo json_encode(['error' => 'Failed to prepare statement.', 'details' => $conn->error]);
    exit;
}

$stmt->bind_param("si", $internal_id, $master_user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if rows are fetched
if ($result->num_rows === 0) {
    echo json_encode(['success' => true, 'quotations' => [], 'message' => 'No quotations found']);
    exit;
}

$quotations = [];
while ($row = $result->fetch_assoc()) {
    $quotations[] = [
        'quotation_id' => $row['quotation_id'],
        'quotation_reference' => $row['quotation_reference'],
        'quotation_number' => $row['quotation_number'],
        'quotation_date' => $row['quotation_date'],
        'quotation_valid_upto_date' => $row['quotation_valid_upto_date'],
        'quotation_token' => $row['quotation_token'],
        'company_name' => $row['company_name'],
        'status_name' => $row['status_name']
    ];
}

echo json_encode(['success' => true, 'quotations' => $quotations]);
$stmt->close();
$conn->close();
?>
