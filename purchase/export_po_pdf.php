<?php
session_start();
require_once '../database/db_connection.php';
require_once '../vendor/autoload.php'; // Include Dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

// Ensure user is logged in
if (!isset($_SESSION['master_userid'])) {
    die("Unauthorized access.");
}

$master_userid = $_SESSION['master_userid'];

// Validate token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("❌ Invalid Access: Token is required.");
}

$po_token = $_GET['token'];

// Fetch Purchase Order details
$sql = "SELECT po.id, po.po_number, po.po_date, po.quotation_no, 
               po.inco_term, po.payment_term, po.general_terms, po.delivery_terms, 
               acc.account_name AS supplier, acc.address AS supplier_address, acc.mobile AS supplier_phone,
               mc.company_name AS company, mc.address AS company_address, mc.pan AS company_pan, 
               mc.gst AS company_gst, mc.phone AS company_phone, mc.mobile AS company_mobile, mc.email AS company_email,
               po.po_status, pos.status_name AS po_status_name,
               COALESCE(SUM(pom.grand_total), 0) AS total_amount
        FROM purchase_orders po
        INNER JOIN purchase_order_status pos ON po.po_status = pos.id
        LEFT JOIN account acc ON po.supplier_id = acc.id
        LEFT JOIN master_company mc ON po.company_name_id = mc.id
        LEFT JOIN purchase_order_materials pom ON po.id = pom.po_id
        WHERE po.token = ? AND po.master_user_id = ?
        GROUP BY po.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $po_token, $master_userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ Purchase Order Not Found or Unauthorized Access.");
}

$po = $result->fetch_assoc();

// Fetch Purchase Order Materials
$sql_materials = "
    SELECT 
        mm.name AS material_name,
        mmu.unit_name AS unit,
        mm.hsn_code AS hsn_sac,
        pom.make, 
        pom.quantity, 
        pom.unit_price, 
        pom.total, 
        pom.gst_percentage, 
        pom.gst_total, 
        pom.grand_total,
        pom.material_description, 
        pom.special_remark
    FROM purchase_order_materials pom
    INNER JOIN master_materials mm ON pom.material_id = mm.id
    LEFT JOIN master_materials_unit mmu ON mm.unit = mmu.id
    WHERE pom.po_id = ?";

$stmt_mat = $conn->prepare($sql_materials);
$stmt_mat->bind_param("i", $po['id']);
$stmt_mat->execute();
$materials_result = $stmt_mat->get_result();

// Start HTML content for PDF
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - ' . htmlspecialchars($po['po_number']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .container { width: 100%; }
        .header { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 10px; }
        .card { border: 1px solid #ccc; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid black; padding: 6px; text-align: left; }
        .table th { background: #f4f4f4; font-size: 12px; }
        .summary { font-weight: bold; background: #f8f9fa; }
        .signature { margin-top: 30px; text-align: center; }
        .signature div { width: 50%; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">

        <!-- Company Details -->
       <div class="card" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 10px; font-size: 12px;">
    <div style="text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 5px;">
        ' . htmlspecialchars($po['company']) . '
    </div>
    <div style="text-align: center; margin-bottom: 5px;">
        ' . nl2br(htmlspecialchars($po['company_address'])) . '
    </div>
    <div style="text-align: center;">
        <strong>PAN:</strong> ' . htmlspecialchars($po['company_pan']) . ' | 
        <strong>GST:</strong> ' . htmlspecialchars($po['company_gst']) . ' |
        <strong>Phone:</strong> ' . htmlspecialchars($po['company_phone']) . ' | 
        <strong>Email:</strong> ' . htmlspecialchars($po['company_email']) . '
    </div>
</div>


        <div class="header">Purchase Order</div>

        <!-- PO Details Card -->
        <div class="card">
            <table style="width: 100%;">
                <tr>
                    <td><strong>PO Number:</strong> ' . htmlspecialchars($po['po_number']) . '</td>
                    <td><strong>PO Date:</strong> ' . date("d-M-Y", strtotime($po['po_date'])) . '</td>
                </tr>
                <tr>
                    <td><strong>Supplier:</strong> ' . htmlspecialchars($po['supplier']) . '</td>
                    <td><strong>Supplier Address:</strong> ' . nl2br(htmlspecialchars($po['supplier_address'])) . '</td>
                </tr>
                <tr>
                    <td><strong>Supplier Phone:</strong> ' . htmlspecialchars($po['supplier_phone']) . '</td>
                    <td><strong>PO Status:</strong> ' . htmlspecialchars($po['po_status_name']) . '</td>
                </tr>
            </table>
        </div>

        <!-- Materials Ordered Table -->
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Material Name</th>
                        <th>Make</th>
                        <th>HSN/SAC</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>GST%</th>
                        <th>GST Total</th>
                        <th>Grand Total</th>
                    </tr>
                </thead>
                <tbody>';

$total_amount = $total_gst_total = $total_grand_total = 0;
while ($mat = $materials_result->fetch_assoc()) {
    $total_amount += $mat['total'];
    $total_gst_total += $mat['gst_total'];
    $total_grand_total += $mat['grand_total'];
    $html .= '
                    <tr>
                        <td>' . htmlspecialchars($mat['material_name']) . '</td>
                        <td>' . htmlspecialchars($mat['make'] ?? "N/A") . '</td>
                        <td>' . htmlspecialchars($mat['hsn_sac'] ?? "N/A") . '</td>
                        <td>' . number_format($mat['quantity'], 2) . '</td>
                        <td>' . htmlspecialchars($mat['unit'] ?? "N/A") . '</td>
                        <td>' . number_format($mat['unit_price'], 2) . '</td>
                        <td>' . number_format($mat['total'], 2) . '</td>
                        <td>' . number_format($mat['gst_percentage'], 2) . '%</td>
                        <td>' . number_format($mat['gst_total'], 2) . '</td>
                        <td>' . number_format($mat['grand_total'], 2) . '</td>
                    </tr>';
}

// Signatures
$html .= '
            </tbody>
        </table>
    </div>

    <div class="signature">
        <div>
            <strong>Created By:</strong>
            <br><br>______________________
        </div>
        <div>
            <strong>Authorized By:</strong>
            <br><br>______________________
        </div>
    </div>

</body>
</html>';

$dompdf = new Dompdf(new Options());
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Purchase_Order_" . htmlspecialchars($po['po_number']) . ".pdf", ["Attachment" => true]);
exit;
