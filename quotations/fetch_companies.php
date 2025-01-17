<?php
// Include database connection
require_once '../database/db_connection.php';

$query = "SELECT id, company_name FROM master_company";
$result = $conn->query($query);

$companies = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
}

echo json_encode($companies);
?>
