<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../database/db_connection.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Check if user is logged in
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

header('Content-Type: application/json');

// Decode JSON request
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
$email = $data['email'] ?? '';
$master_userid = $_SESSION['master_userid'];

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
    exit;
}

// Fetch SMTP Configuration from the database for the logged-in user
$query = "SELECT smtp_host, smtp_port, smtp_user, smtp_password, smtp_status 
          FROM master_email_configuration 
          WHERE master_user_id = ? LIMIT 1";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit;
}

$stmt->bind_param('i', $master_userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $config = $result->fetch_assoc();

    // Attempt to send email
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_user'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp_port'];
        $mail->Timeout = 10;

        $mail->setFrom($config['smtp_user'], 'Test Email');
        $mail->addAddress($email);
        $mail->Subject = 'Test Email';
        $mail->Body = 'This is a test email sent using your SMTP configuration. Sent on: ' . date('D - d-M-Y H:i:s');


        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Test email sent successfully to ' . htmlspecialchars($email)]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No SMTP configuration found']);
}

$stmt->close();
$conn->close();
?>
