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
$quotationQuery = "
    SELECT 
        q.quotation_number,
        q.quotation_date,
        q.payment_conditions,
        q.delivery_conditions,
        q.other_conditions,
        q.terms_conditions,
        c.company_name,
        c.address AS company_address,
        c.city AS company_city,
        c.state AS company_state,
        c.pincode AS company_pincode,
        c.country AS company_country,
        a.account_name AS customer_name,
        a.address AS customer_address,
        a.city AS customer_city,
        a.state AS customer_state,
        a.pincode AS customer_pincode
    FROM 
        master_quotations q
    INNER JOIN 
        master_company c ON q.company_id = c.id
    INNER JOIN 
        master_marketing m ON q.quotation_reference = m.internal_id AND q.master_user_id = m.master_user_id
    INNER JOIN 
        account a ON m.account_id = a.id
    WHERE 
        q.quotation_id = ? AND 
        q.quotation_token = ? AND 
        q.master_user_id = ?
";

$stmt = $conn->prepare($quotationQuery);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare quotation query.']);
    exit;
}
$stmt->bind_param("isi", $quotation_id, $token, $_SESSION['master_userid']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No valid record found for the given Quotation ID, Token, or Master User ID.']);
    exit;
}

$quotation = $result->fetch_assoc();
$stmt->close();

// Fetch materials for the quotation
$materialsQuery = "
    SELECT 
        mm.name AS material_name,
        mm.unit AS unit,
        mqm.quantity AS quantity,
        mqm.unit_price AS price,
        (mqm.quantity * mqm.unit_price) AS total
    FROM 
        master_quotations_materials mqm
    INNER JOIN 
        master_materials mm ON mqm.material_id = mm.id
    WHERE 
        mqm.master_quotation_id = ?
";

$stmt = $conn->prepare($materialsQuery);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare materials query.']);
    exit;
}
$stmt->bind_param('i', $quotation_id);
$stmt->execute();
$materialsResult = $stmt->get_result();

$materials = [];
while ($row = $materialsResult->fetch_assoc()) {
    $materials[] = $row;
}

$stmt->close();

// Generate the PDF with the `$htmlContent` as described earlier.
// Generate the PDF using Dompdf, and email it using PHPMailer as per your existing logic.

// Fetch materials for the quotation
$materialsQuery = "
    SELECT 
        mm.name AS material_name,
        mm.unit AS unit,
        mqm.quantity AS quantity,
        mqm.unit_price AS price,
        (mqm.quantity * mqm.unit_price) AS total
    FROM 
        master_quotations_materials mqm
    INNER JOIN 
        master_materials mm ON mqm.material_id = mm.id
    WHERE 
        mqm.master_quotation_id = ?
";

$stmt = $conn->prepare($materialsQuery);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare materials query.']);
    exit;
}
$stmt->bind_param('i', $quotation_id);
$stmt->execute();
$materialsResult = $stmt->get_result();

$materials = [];
while ($row = $materialsResult->fetch_assoc()) {
    $materials[] = $row;
}

$stmt->close();

// Generate HTML for the PDF manually
// Generate HTML for the PDF
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
        .info-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
            padding: 10px;
        }
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class='container mt-4'>
        <h5 class='text-center'>Quotation</h5>
        <div>
            <h1 class='text-center mb-2 header-title'>" . htmlspecialchars($quotation['company_name']) . "</h1>
            <h5 class='text-center mb-2'>
                " . htmlspecialchars($quotation['company_address']) . "<br>
                " . htmlspecialchars($quotation['company_city'] . ", " . $quotation['company_district']) . "<br>
                " . htmlspecialchars($quotation['company_state'] . " - " . $quotation['company_pincode']) . "<br>
                " . htmlspecialchars($quotation['company_country']) . "
            </h5>
        </div>

        <div class='row mt-4'>
            <!-- Customer Information Card -->
            <div class='col-md-6'>
                <div class='card info-card'>
                    <div class='card-header bg-dark text-white text-center' style='font-weight: bold; font-size: 1.2rem;'>
                        Customer Information
                    </div>
                    <div class='card-body'>
                        <table class='table table-bordered'>
                            <tr>
                                <th>Name</th>
                                <td>" . htmlspecialchars($quotation['customer_name']) . "</td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td>
                                    " . htmlspecialchars($quotation['customer_address']) . "<br>
                                    " . htmlspecialchars($quotation['customer_city'] . ", " . $quotation['customer_district']) . "<br>
                                    " . htmlspecialchars($quotation['customer_state'] . " - " . $quotation['customer_pincode']) . "<br>
                                    " . htmlspecialchars($quotation['customer_country']) . "
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Quotation Summary Card -->
            <div class='col-md-6'>
                <div class='card info-card'>
                    <div class='card-header bg-dark text-white text-center' style='font-weight: bold; font-size: 1.2rem;'>
                        Quotation Summary
                    </div>
                    <div class='card-body'>
                        <table class='table table-bordered'>
                            <tr>
                                <th>Quotation Number</th>
                                <td>" . htmlspecialchars($quotation['quotation_number']) . " Date : " . htmlspecialchars($quotation['quotation_date']) . "</td>
                            </tr>
                            <tr>
                                <th>Quotation Reference</th>
                                <td>" . htmlspecialchars($quotation['quotation_reference']) . "</td>
                            </tr>
                            <tr>
                                <th>Valid Upto</th>
                                <td>" . htmlspecialchars($quotation['quotation_valid_upto_date']) . "</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>" . htmlspecialchars($quotation['status_name']) . "</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Materials Details Section -->
        <h2 class='section-title'>Materials Details</h2>
        <table class='table table-bordered'>
            <thead>
                <tr>
                    <th>Material Type</th>
                    <th>Make</th>
                    <th>Material Name</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Basic Total</th>
                    <th>HSN Code</th>
                    <th>HSN %</th>
                    <th>HSN Total</th>
                    <th>Grand Total</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>";
foreach ($materials as $material) {
    $htmlContent .= "
                <tr>
                    <td>" . htmlspecialchars($material['material_type_name']) . "</td>
                    <td>" . htmlspecialchars($material['material_make_name']) . "</td>
                    <td>" . htmlspecialchars($material['material_name']) . "</td>
                    <td>" . htmlspecialchars($material['quantity']) . "</td>
                    <td>" . htmlspecialchars($material['unit']) . "</td>
                    <td>" . htmlspecialchars(number_format($material['price'], 2)) . "</td>
                    <td>" . htmlspecialchars(number_format($material['total'], 2)) . "</td>
                    <td>" . htmlspecialchars($material['hsn_code']) . "</td>
                    <td>" . htmlspecialchars($material['hsn_percentage']) . "</td>
                    <td>" . htmlspecialchars(number_format($material['hsn_total'], 2)) . "</td>
                    <td>" . htmlspecialchars(number_format($material['grand_total'], 2)) . "</td>
                    <td>" . htmlspecialchars($material['master_quotation_materials_remark'] ?? 'N/A') . "</td>
                </tr>";
}
$htmlContent .= "
            </tbody>
        </table>

        <!-- Terms and Conditions Section -->
        <div class='mt-5'>
            <h2 class='section-title'>Terms and Conditions</h2>
            <div class='card info-card'>
                <div class='card-body'>
                    <ul>
                        <li><strong>Payment Conditions:</strong> " . htmlspecialchars($quotation['payment_conditions'] ?? 'N/A') . "</li>
                        <li><strong>Delivery Conditions:</strong> " . htmlspecialchars($quotation['delivery_conditions'] ?? 'N/A') . "</li>
                        <li><strong>Other Conditions:</strong> " . htmlspecialchars($quotation['other_conditions'] ?? 'N/A') . "</li>
                        <li><strong>Additional Terms:</strong> " . nl2br(htmlspecialchars($quotation['terms_conditions'] ?? 'No additional terms and conditions provided.')) . "</li>
                    </ul>
                </div>
            </div>
        </div>
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
