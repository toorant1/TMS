<?php
require_once '../database/db_connection.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

session_start();

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

// Get the JSON payload
$data = json_decode(file_get_contents('php://input'), true);

$quotation_id = $data['quotation_id'] ?? null;
$token = $data['token'] ?? '';
$email = $data['email'] ?? '';
$subject = $data['subject'] ?? 'Quotation Details';
$body = $data['body'] ?? '';

if (!$quotation_id || empty($token) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit;
}

// Fetch SMTP configuration for the logged-in user
$smtpQuery = "
    SELECT smtp_host, smtp_port, smtp_user, smtp_password, smtp_status 
    FROM master_email_configuration 
    WHERE master_user_id = ? 
    LIMIT 1
";

$stmt = $conn->prepare($smtpQuery);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare SMTP query.']);
    exit;
}
$stmt->bind_param('i', $_SESSION['master_userid']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No SMTP configuration found for the user.']);
    exit;
}

$config = $result->fetch_assoc();
$stmt->close();
// Fetch quotation details
$query = "
    SELECT 
        q.quotation_number,
        q.quotation_date,
        q.quotation_valid_upto_date,
        q.terms_conditions,
        q.payment_conditions,
        q.delivery_conditions,
        q.other_conditions,
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
        master_marketing m ON q.quotation_reference = m.internal_id AND q.master_user_id = m.master_user_id
    INNER JOIN 
        account a ON m.account_id = a.id
    WHERE 
        q.quotation_id = ? AND 
        q.quotation_token = ? AND 
        q.master_user_id = ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("isi", $quotation_id, $token, $_SESSION['master_userid']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No valid record found for the given Quotation ID, Token, or Master User ID.");
}

$quotation = $result->fetch_assoc();
$stmt->close();

// Fetch materials for the quotation
$materialsQuery = "
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

$stmt = $conn->prepare($materialsQuery);
if (!$stmt) {
    die("Query preparation failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$materialsResult = $stmt->get_result();
$materials = [];

while ($row = $materialsResult->fetch_assoc()) {
    $materials[] = $row;
}

$stmt->close();

// Initialize totals
$totalBasic = 0;
$totalGST = 0;
$totalGrand = 0;

// Generate HTML content for the PDF
$htmlContent = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Quotation PDF</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .header p { margin: 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; vertical-align: top; border: 1px solid black; }
        th { font-size: 12px; font-weight: bold; background-color: #f9f9f9; }
        td { font-size: 12px; }
        @page {
            margin: 50px 25px; /* Adjust margins for header/footer space */
        }

        body {
            margin: 0;
            padding: 0;
        }

        .page-footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #555;
        }

    </style>
</head>
<body>
    <div class='header'>
        <h1>" . htmlspecialchars($quotation['company_name']) . "</h1>
        <p>" . htmlspecialchars($quotation['company_address']) . ", " . htmlspecialchars($quotation['company_city']) . ", " . htmlspecialchars($quotation['company_state']) . " - " . htmlspecialchars($quotation['company_pincode']) . "</p>
        <p>" . htmlspecialchars($quotation['company_country']) . "</p>
    </div>

    <!-- Customer Information and Quotation Summary in a Table -->
    <table>
        <tr>
            <td>
                <p><strong>" . htmlspecialchars($quotation['customer_name']) . "</strong></p>
                <p><strong>Address:</strong><br> " . htmlspecialchars($quotation['customer_address']) . ",<br> 
                " . htmlspecialchars($quotation['customer_city']) . ", " . htmlspecialchars($quotation['customer_state']) . " - " . htmlspecialchars($quotation['customer_pincode']) . "<br>
                " . htmlspecialchars($quotation['customer_country']) . "</p>
            </td>
            <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 5px;'><strong>Quotation Number:</strong></td>
                        <td style='padding: 5px;'>" . htmlspecialchars($quotation['quotation_number']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px;'><strong>Date:</strong></td>
                        <td style='padding: 5px;'>" . htmlspecialchars($quotation['quotation_date']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px;'><strong>Valid Upto:</strong></td>
                        <td style='padding: 5px;'>" . htmlspecialchars($quotation['quotation_valid_upto_date']) . "</td>
                    </tr>
                </table>
        </tr>
    </table>

    <h3 class='header'>Materials / Services <br> Details  </h3>
    <table>
        <thead>
            <tr>
                <th class='header'>Material Name</th>
                <th class='header'>Quantity <br> Unit</th>
                <th class='header'>Price</th>
                <th class='header'>Total</th>
                <th class='header'>HSN Code <br> GST %</th>
                <th class='header'>GST Total</th>
                <th class='header'>Grand Total</th>
            </tr>
        </thead>
        <tbody>";

foreach ($materials as $material) {
    $totalBasic += $material['total'];
    $totalGST += $material['hsn_total'];
    $totalGrand += $material['grand_total'];

    $htmlContent .= "
            <tr>
                <td>" . htmlspecialchars($material['material_name']) . " <br> Make : "
                . htmlspecialchars($material['material_make_name']) . "
                <br>Type : " . htmlspecialchars($material['material_type_name']) . "
                <br>Remark : " . htmlspecialchars($material['master_quotation_materials_remark']) . "</td>
                <td style='text-align: center;'>" . htmlspecialchars($material['quantity']) . "<br>
                " . htmlspecialchars($material['unit']) . "</td>
                <td style='text-align: right;'>" . htmlspecialchars(number_format($material['price'], 2)) . "</td>
                <td style='text-align: right;'>" . htmlspecialchars(number_format($material['total'], 2)) . "</td>
                <td style='text-align: center;'>" . htmlspecialchars($material['hsn_code']) . "<br> " . htmlspecialchars($material['hsn_percentage']) ."%</td>
                <td style='text-align: right;'>" . htmlspecialchars(number_format($material['hsn_total'], 2)) . "</td>
                <td style='text-align: right;'>" . htmlspecialchars(number_format($material['grand_total'], 2)) . "</td>
            </tr>";
}

$htmlContent .= "
  </tbody>
        <tfoot>
            <tr class='totals'>
                <td colspan='3' style='text-align: right;'><strong>Grand Total:</td>
                <td style='text-align: right;'>" . htmlspecialchars(number_format($totalBasic, 2)) . "</td>
                <td></td>
                <td style='text-align: right;'>" . htmlspecialchars(number_format($totalGST, 2)) . "</td>
                <td style='text-align: right;'>" . htmlspecialchars(number_format($totalGrand, 2)) . "</td>
                </strong>
            </tr>
        </tfoot>
    </table>

    <div class='info-section'>
        <h3>Terms and Conditions</h3>
        <p><strong>Payment:</strong> " . htmlspecialchars($quotation['payment_conditions']) . "</p>
        <p><strong>Delivery:</strong> " . htmlspecialchars($quotation['delivery_conditions']) . "</p>
        <p><strong>Other:</strong> " . htmlspecialchars($quotation['other_conditions']) . "</p>
        <p><strong>Additional:</strong> " . nl2br(htmlspecialchars($quotation['terms_conditions'])) . "</p>
    </div>
    <!-- Footer Section -->
    <div style='position: fixed; bottom: 0; width: 100%;'>
        <table style='width: 100%; border-collapse: collapse; margin-top: 30px;'>
            <tr>
                <td style='text-align: center; padding: 10px;'><strong>Prepared By</strong></td>
                <td style='text-align: center; padding: 10px;'><strong>Approved By: (" . htmlspecialchars(trim($quotation['company_name'])) . ")</strong></td>
                <td style='text-align: center; padding: 10px;'><strong>Customer Sign: (" . htmlspecialchars($quotation['customer_name']) . ")</strong></td>
            </tr>
            <tr>
                <td style='text-align: center; padding: 20px;'>________________________</td>
                <td style='text-align: center; padding: 20px;'>________________________</td>
                <td style='text-align: center; padding: 20px;'>________________________</td>
            </tr>
            
        </table>
    </div>
</body>
</html>";

// Generate PDF using Dompdf
try {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($htmlContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Get the PDF as a string
    $pdfContent = $dompdf->output();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to generate PDF: ' . $e->getMessage()]);
    exit;
}

// Send the PDF as an email attachment
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_user'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['smtp_port'];

    $mail->setFrom($config['smtp_user'], $quotation['company_name']);
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = htmlspecialchars($subject);
    $mail->Body = nl2br(htmlspecialchars($body));

    // Attach the PDF from memory
    $mail->addStringAttachment($pdfContent, "quotation_{$quotation_id}.pdf");

    $mail->send();

    echo json_encode(['status' => 'success', 'message' => 'Email sent successfully with the quotation attached.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send email: ' . $e->getMessage()]);
}
?>
