<?php
require_once '../database/db_connection.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

try {
    // Send email using PHPMailer
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

    $mail->send();
    echo json_encode(['status' => 'success', 'message' => 'Email sent successfully to ' . htmlspecialchars($email)]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send email: ' . $e->getMessage()]);
}
?>
