<?php
require_once '../database/db_connection.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Retrieve query parameters and sanitize
$quotation_id = isset($_GET['quotation_id']) ? intval($_GET['quotation_id']) : 0;
$token = isset($_GET['token']) ? filter_var($_GET['token'], FILTER_SANITIZE_STRING) : '';

if (!$quotation_id || empty($token)) {
    die("Invalid request. Missing Quotation ID or Token.");
}

// Fetch quotation details
$query = "
    SELECT 
    q.quotation_id,
    q.quotation_reference,
    q.quotation_number,
    q.quotation_date,
    q.quotation_valid_upto_date,
    q.terms_conditions,
    q.payment_conditions,
    q.delivery_conditions,
    q.other_conditions,
    q.internal_remark_conditions,
    c.company_name,
    c.address AS company_address,
    c.state AS company_state,
    c.district AS company_district,
    c.city AS company_city,
    c.pincode AS company_pincode,
    c.country AS company_country,
    s.status_name,
    a.account_name AS customer_name,
    a.address AS customer_address,
    a.state AS customer_state,
    a.district AS customer_district,
    a.city AS customer_city,
    a.pincode AS customer_pincode,
    a.country AS customer_country
FROM 
    master_quotations q
INNER JOIN 
    master_company c ON q.company_id = c.id
INNER JOIN 
    master_quotations_status s ON q.quotation_status_id = s.quotation_status_id
INNER JOIN 
    master_marketing m ON q.quotation_reference = m.internal_id
INNER JOIN 
    account a ON m.account_id = a.id
WHERE 
    q.quotation_id = ? AND 
    q.quotation_token = ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query Preparation Failed: " . htmlspecialchars($conn->error));
}

// Bind parameters and execute
$stmt->bind_param("is", $quotation_id, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No valid record found for the given Quotation ID or Token.");
}

$quotation = $result->fetch_assoc();
$stmt->close();

// Fetch materials for the quotation
$query = "
    SELECT 
        mm.name AS material_name,
        mm.description AS material_description,
        mmake.make AS material_make_name,
        mt.material_type AS material_type_name,
        mu.unit_name AS unit,
        mqm.quantity,
        mqm.unit_price AS price,
        (mqm.quantity * mqm.unit_price) AS total,
        mqm.hsn_code,
        mqm.hsn_percentage,
        ((mqm.quantity * mqm.unit_price) * mqm.hsn_percentage / 100) AS hsn_total,
        ((mqm.quantity * mqm.unit_price) + ((mqm.quantity * mqm.unit_price) * mqm.hsn_percentage / 100)) AS grand_total,
        mqm.master_quotation_materials_remark
    FROM master_quotations_materials mqm
    INNER JOIN master_materials mm ON mqm.material_id = mm.id
    INNER JOIN master_materials_make mmake ON mm.make = mmake.id
    INNER JOIN master_materials_type mt ON mm.material_type = mt.id
    INNER JOIN master_materials_unit mu ON mm.unit = mu.id
    WHERE mqm.master_quotation_id = ?
    ORDER BY mm.name
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query Preparation Failed: " . htmlspecialchars($conn->error));
}

// Bind parameters
$stmt->bind_param("i", $quotation_id);

// Execute and fetch results
$stmt->execute();
$materialsResult = $stmt->get_result();
$materials = [];

// Iterate through the results
while ($row = $materialsResult->fetch_assoc()) {
    $materials[] = $row;
}
$stmt->close();

// Generate HTML content for the PDF
$htmlContent = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Quotation Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .header-title {
            font-size: 1.8rem;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }
        .info-table, .material-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table th, .info-table td, .material-table th, .material-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .info-table th, .material-table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class='header-title'>{$quotation['company_name']}</div>
    <p>Address: {$quotation['company_address']}, {$quotation['company_city']}, {$quotation['company_state']} - {$quotation['company_pincode']}, {$quotation['company_country']}</p>
    <h3>Quotation Details</h3>
    <table class='info-table'>
        <tr>
            <th>Quotation Number</th>
            <td>{$quotation['quotation_number']}</td>
        </tr>
        <tr>
            <th>Date</th>
            <td>{$quotation['quotation_date']}</td>
        </tr>
        <tr>
            <th>Valid Upto</th>
            <td>{$quotation['quotation_valid_upto_date']}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{$quotation['status_name']}</td>
        </tr>
    </table>
    <h3>Materials</h3>
    <table class='material-table'>
        <thead>
            <tr>
                <th>Material Name</th>
                <th>Type</th>
                <th>Make</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>";

foreach ($materials as $material) {
    $htmlContent .= "
        <tr>
            <td>{$material['material_name']}</td>
            <td>{$material['material_type_name']}</td>
            <td>{$material['material_make_name']}</td>
            <td>{$material['quantity']}</td>
            <td>{$material['unit']}</td>
            <td>{$material['price']}</td>
            <td>{$material['total']}</td>
        </tr>";
}

$htmlContent .= "
        </tbody>
    </table>
</body>
</html>
";

// Generate PDF using Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output the generated PDF
$dompdf->stream("quotation_{$quotation_id}.pdf", ["Attachment" => false]);
exit;
?>
