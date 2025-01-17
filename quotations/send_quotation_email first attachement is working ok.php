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
$query = "
    SELECT smtp_host, smtp_port, smtp_user, smtp_password, smtp_status 
    FROM master_email_configuration 
    WHERE master_user_id = ? 
    LIMIT 1
";

$stmt = $conn->prepare($query);
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
        c.company_name,
        c.address AS company_address,
        c.city AS company_city,
        c.state AS company_state,
        c.pincode AS company_pincode,
        c.country AS company_country
    FROM 
        master_quotations q
    INNER JOIN 
        master_company c ON q.company_id = c.id
    WHERE 
        q.quotation_id = ? AND 
        q.quotation_token = ? AND 
        q.master_user_id = ?
";

$stmt = $conn->prepare($query);
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

// Generate HTML for the PDF manually
$htmlContent = "

<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Quotation</title>
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
        .footer {
            text-align: center;
            margin-top: 30px;
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
    </table>
    <div class='footer'>
        <p>Prepared By: {$quotation['company_name']}</p>
    </div>
</body>
</html>
";

// Generate PDF using Dompdf
try {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); // Enable remote resources like images

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
