<?php
session_start();
require_once '../database/db_connection.php';

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Default date range: From (3 months ago, 1st day) to Today
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date("Y-m-01", strtotime("-3 months"));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date("Y-m-d");

// Fetch Purchase Orders based on search & date range
$sql = "SELECT po.id, po.po_number, po.token, acc.account_name AS supplier, 
               mc.company_name AS company, po.po_date, 
               COALESCE(SUM(pom.grand_total), 0) AS total_amount,
               pos.status_name AS status
        FROM purchase_orders po
        LEFT JOIN account acc ON po.supplier_id = acc.id
        LEFT JOIN master_company mc ON po.company_name_id = mc.id
        LEFT JOIN purchase_order_materials pom ON po.id = pom.po_id
        LEFT JOIN purchase_order_status pos ON po.po_status = pos.id
        WHERE po.master_user_id = ? 
        AND po.po_date BETWEEN ? AND ?";

// Apply search filter
if (!empty($search_query)) {
    $sql .= " AND (po.po_number LIKE ? OR acc.account_name LIKE ? OR mc.company_name LIKE ?)";
}

$sql .= " GROUP BY po.id ORDER BY po.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param("isssss", $master_userid, $from_date, $to_date, $search_param, $search_param, $search_param);
} else {
    $stmt->bind_param("iss", $master_userid, $from_date, $to_date);
}
$stmt->execute();
$result = $stmt->get_result();

$po_data = [];
while ($row = $result->fetch_assoc()) {
    $po_data[] = [
        "po_number" => htmlspecialchars($row['po_number']),
        "token" => htmlspecialchars($row['token']),
        "company" => htmlspecialchars($row['company']),
        "supplier" => htmlspecialchars($row['supplier']),
        "po_date" => date("d-M-Y", strtotime($row['po_date'])),
        "total_amount" => number_format($row['total_amount'], 2),
        "status" => htmlspecialchars($row['status']) // Default Status
    ];
}

// Send response as JSON
echo json_encode(["status" => "success", "data" => $po_data]);
exit();
?>
