<?php
require_once '../database/db_connection.php';
require_once '../vendor/autoload.php'; // Load PHPMailer via Composer if installed

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log('save_ticket.php: Script started.');

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Generate a new ticket token
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];

// Get POST data
$ticket_date = $_POST['date'] ?? null;
$ticket_type_id = $_POST['ticket_type'] ?? null;
$ticket_priority_id = $_POST['priority'] ?? null;
$ticket_status_id = $_POST['ticket_status'] ?? null;
$account_id = $_POST['customer_name'] ?? null;
$contact_id = $_POST['contact_person'] ?? null;
$cause_id = $_POST['main_cause'] ?? null;
$problem_statement = $_POST['problem_statement'] ?? null;
$ticket_token = generateToken();

// Validate input data
if (!$ticket_date || !$ticket_type_id || !$ticket_priority_id || !$ticket_status_id || !$account_id || !$contact_id || !$cause_id || empty($problem_statement)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

try {
    $current_year = date('Y', strtotime($ticket_date));
    $prefix = $current_year . '-%';

    // Query to get the last ticket_id for the current year
    $query = "SELECT MAX(ticket_id) AS last_ticket FROM master_tickets WHERE master_user_id = ? AND ticket_id LIKE ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $master_userid, $prefix);
    if (!$stmt->execute()) {
        throw new Exception('Database query failed: ' . $stmt->error);
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Generate ticket ID
    $last_number = $row ? intval(substr($row['last_ticket'], -4)) : 0;
    $next_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
    $ticket_id = $current_year . '-' . $next_number;

    // Insert ticket
    $query = "
        INSERT INTO master_tickets 
        (ticket_id, ticket_date, master_user_id, ticket_type_id, ticket_priority_id, ticket_status_id, account_id, contact_id, cause_id, problem_statement, ticket_token) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssiiiiiiiss",
        $ticket_id,
        $ticket_date,
        $master_userid,
        $ticket_type_id,
        $ticket_priority_id,
        $ticket_status_id,
        $account_id,
        $contact_id,
        $cause_id,
        $problem_statement,
        $ticket_token
    );

    if (!$stmt->execute()) {
        throw new Exception('Error inserting ticket: ' . $stmt->error);
    }

    // Fetch email configuration for SMTP settings
    $email_query = "
                SELECT smtp_host, smtp_port, smtp_user, smtp_password 
                FROM master_email_configuration 
                WHERE master_user_id = ? AND smtp_status = 1 LIMIT 1
                ";
    $email_stmt = $conn->prepare($email_query);
    $email_stmt->bind_param("i", $master_userid);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    $email_config = $email_result->fetch_assoc();

    if (!$email_config) {
        throw new Exception('Email configuration not found.');
    }

    // Fetch account and contact email details in a single query
    $email_details_query = "
                        SELECT 
                            account.email AS account_email, 
                            contacts.email AS contact_email, 
                            contacts.name AS contact_name 
                        FROM 
                            account 
                        LEFT JOIN 
                            contacts 
                        ON 
                            account.id = contacts.account_id 
                        WHERE 
                            account.id = ? 
                        AND 
                            contacts.id = ? 
                        LIMIT 1
                        ";
    $email_details_stmt = $conn->prepare($email_details_query);
    $email_details_stmt->bind_param("ii", $account_id, $contact_id);
    $email_details_stmt->execute();
    $email_details_result = $email_details_stmt->get_result();
    $email_details = $email_details_result->fetch_assoc();

    if (!$email_details || empty($email_details['account_email']) || empty($email_details['contact_email'])) {
        throw new Exception('Email details for account or contact not found.');
    }


    // Fetch details with proper joins for the ticket
    $query = "
SELECT 
    mt.ticket_id,
    mt.ticket_date,
    tp.priority AS ticket_priority,
    ts.status_name AS ticket_status,
    mc.main_cause AS main_cause,
    a.account_name AS customer_name,
    c.name AS contact_name,
    mt.problem_statement
FROM master_tickets mt
LEFT JOIN master_tickets_priority tp ON mt.ticket_priority_id = tp.id
LEFT JOIN master_tickets_status ts ON mt.ticket_status_id = ts.id
LEFT JOIN master_tickets_main_causes mc ON mt.cause_id = mc.id
LEFT JOIN account a ON mt.account_id = a.id
LEFT JOIN contacts c ON mt.contact_id = c.id
WHERE mt.ticket_id = ?
";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket_data = $result->fetch_assoc();

    if (!$ticket_data) {
        echo "Error: Ticket data not found!";
        exit;
    }

    // Now prepare the email details for sending
    $to_email = $email_details['contact_email'];
    $to_name = $email_details['contact_name'];
    $cc_email_account = $email_details['account_email']; // Account email in CC
    $cc_email_smtp = $email_config['smtp_user']; // SMTP user in CC

    // Use the fetched SMTP configuration to send the email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $email_config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $email_config['smtp_user'];
    $mail->Password = $email_config['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $email_config['smtp_port'];

    $mail->setFrom($email_config['smtp_user'], 'The Toorant CRM Sofware');
    $mail->addAddress($to_email, $to_name);
    $mail->addCC($cc_email_account); // Add account email in CC
    $mail->addCC($cc_email_smtp); // Add smtp_user in CC

    // Prepare the email body
    $mail->isHTML(true);
    $mail->Subject = '###' . $ticket_id . '### - Ticket Generated Successfully';
    // Create a card layout with the ticket details
    $mail->Body = "
<p>Dear {$ticket_data['contact_name']},</p>
<p>Your ticket has been successfully generated with the following details:</p>

<div style='max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; background-color: #f9f9f9;'>
    <h3 style='text-align: center; background-color: #007bff; color: white; margin: 0; padding: 10px; border-radius: 8px 8px 0 0;'>Ticket Details</h3>
    <table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; margin-top: 15px;'>
        <tr>
            <th style='background-color: #f2f2f2; text-align: left; padding: 8px;'>Field</th>
            <th style='background-color: #f2f2f2; text-align: left; padding: 8px;'>Details</th>
        </tr>
        <tr>
            <td style='padding: 8px;'><strong>Ticket ID:</strong></td>
            <td style='padding: 8px;'>{$ticket_data['ticket_id']}</td>
            
        </tr>
        <tr>
            <td style='padding: 8px;'><strong>Date:</strong></td>
            <td style='padding: 8px;'>{$ticket_data['ticket_date']}</td>
        </tr>
        <tr>
            <td style='padding: 8px;'><strong>Priority:</strong></td>
            <td style='padding: 8px;'>{$ticket_data['ticket_priority']}</td>
        </tr>
        <tr>
            <td style='padding: 8px;'><strong>Status:</strong></td>
            <td style='padding: 8px;'>{$ticket_data['ticket_status']}</td>
        </tr>
        <tr>
            <td style='padding: 8px;'><strong>Main Cause:</strong></td>
            <td style='padding: 8px;'>{$ticket_data['main_cause']}</td>
        </tr>
        <tr>
            <td style='padding: 8px;'><strong>Customer Name:</strong></td>
            <td style='padding: 8px;'>{$ticket_data['customer_name']}</td>
        </tr>
        <tr>
            <td style='padding: 8px;'><strong>Contact Person:</strong></td>
            <td style='padding: 8px;'>{$ticket_data['contact_name']}</td>
        </tr>
        <tr>
            <td style='padding: 8px;'><strong>Problem Statement:</strong></td>
            <td style='padding: 8px;'>{$ticket_data['problem_statement']}</td>
        </tr>
    </table>
</div>
    <p style='text-align: center; margin-top: 15px;'>
        <a href='https://thetoorant.com' target='_blank' style='display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Click here for Updates</a>
    </p>

<p style='margin-top: 15px;'>Thank you for reaching out to us. We will get back to you soon.</p>
<p>Best Regards,</p>
";

    $mail->AltBody = "Ticket (ID: {$ticket_id}) has been successfully generated. Please check your email for more details.";

    // Send the email
    if ($mail->send()) {
            // Prepare WhatsApp configuration
            $phone_number_id = "553313077858045";
            $bearer_id = "tqYr8PooE3HGtJ1LGR3fMP4sQUtcpd1rZARFUuwiSc";
            $api_company_id = "676fbe2232cb3202";
        
            $send_to_number = "9638480441";
        
        
            $templateName = "new_ticket_confirmation";
        
        
           
           $payload = [
               "phone_number_id" => $phone_number_id,
               "customer_country_code" => "91",
               "customer_number" =>  $send_to_number,
               "data" => [
                   "type" => "template",
                   "context" => [
                       "template_name" => $templateName,
                       "language" => "en",
                       "body" => [
                           "1" => $ticket_data['contact_name'],
                           "2" => $ticket_data['customer_name'],
                           "3" => $ticket_data['ticket_id'],
                           "4" => $ticket_data['ticket_date'],
                           "5" => "New Ticket",
                           "6" => $ticket_data['ticket_status'],
                           "7" => $ticket_data['ticket_priority'], 
                           "8" => $ticket_data['problem_statement'], 
                           "9" => "a"
                       ]
                   ]
               ],
               "reply_to" => null,
               "myop_ref_id" => uniqid()
           ];
        
           $curl = curl_init();
        
           curl_setopt_array($curl, array(
               CURLOPT_URL => 'https://publicapi.myoperator.co/chat/messages',
               CURLOPT_RETURNTRANSFER => true,
               CURLOPT_CUSTOMREQUEST => 'POST',
               CURLOPT_POSTFIELDS => json_encode($payload),
               CURLOPT_HTTPHEADER => array(
                   'Content-Type: application/json',
                   'Authorization: Bearer ' . $bearer_id,
                   'X-MYOP-COMPANY-ID: ' . $api_company_id
               ),
           ));
        
           $response = curl_exec($curl);
        
           if (curl_errno($curl)) {
               echo "<p class='text-red-500 mt-4'>Error: Unable to send the message. Please try again later.</p>";
           } else {
               $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
               $responseBody = json_decode($response, true);
        
               if ($httpCode === 200 && isset($responseBody['status']) && $responseBody['status'] === 'success') {
                   $messageId = $responseBody['data']['message_id'] ?? 'N/A';
                   echo "<p class='text-green-500 mt-4'>Message sent successfully! Message ID: $messageId</p>";
               } else {
                   echo "<p class='text-red-500 mt-4'>Failed to send message. Please try again later.</p>";
               }
           }
        
           curl_close($curl);
    
        echo json_encode(['status' => 'success', 'message' => 'Ticket created successfully and email sent.', 'ticket_id' => $ticket_id]);
    } else {
        throw new Exception('Ticket created but email could not be sent.');
    }

    
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
