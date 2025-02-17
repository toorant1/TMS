<?php
require_once '../database/db_connection.php'; // Include database connection
session_start();

// Ensure user is logged in
if (!isset($_SESSION['master_userid'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$master_userid = $_SESSION['master_userid']; // Retrieve the logged-in user ID

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = $_POST['ticket_id'] ?? null;
    $recipientType = $_POST['recipient_type'] ?? null;

    if (!$ticketId || !$recipientType) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
        exit;
    }

    // Fetch ticket details for the given ticket ID and logged-in user
    $query = "
        SELECT 
            mt.id AS ticket_id,
            mt.problem_statement AS problem_statement,
            mt.ticket_date AS ticket_date,
            ms.status_name AS ticket_status,
            mp.priority AS ticket_priority,
            mc.main_cause AS main_cause,
            acc.account_name AS customer_name,
            c.name AS contact_name,
            c.mobile1 AS contact_phone1,
            c.mobile2 AS contact_phone2,
            acc.address AS address,
            acc.email AS account_email_id
        FROM master_tickets mt
        LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id
        LEFT JOIN master_tickets_priority mp ON mt.ticket_priority_id = mp.id
        LEFT JOIN master_tickets_main_causes mc ON mt.cause_id = mc.id
        LEFT JOIN account acc ON mt.account_id = acc.id
        LEFT JOIN contacts c ON mt.contact_id = c.id
        WHERE mt.id = ? AND mt.master_user_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $ticketId, $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found or access denied.']);
        exit;
    }

    $ticket_data = $result->fetch_assoc();

    // Prepare recipients
    $recipients = [];

    // Add contact's phone numbers
    if (!empty($ticket_data['contact_phone1'])) {
        $recipients[] = $ticket_data['contact_phone1'];
    }
    if (!empty($ticket_data['contact_phone2'])) {
        $recipients[] = $ticket_data['contact_phone2'];
    }

    // Add active users' phone numbers
    $users_query = "
        SELECT mobile 
        FROM master_users 
        WHERE master_user_id = ? AND status = 1
    ";
    $users_stmt = $conn->prepare($users_query);
    $users_stmt->bind_param("i", $master_userid);
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();

    while ($user = $users_result->fetch_assoc()) {
        if (!empty($user['mobile'])) {
            $recipients[] = $user['mobile'];
        }
    }

    // Remove duplicates and ensure valid phone numbers
    $recipients = array_unique(array_filter($recipients, function ($number) {
        return preg_match('/^\d{10}$/', $number); // Ensure the number is exactly 10 digits
    }));

    // WhatsApp configuration
    $phone_number_id = "553313077858045";
    $bearer_id = "tqYr8PooE3HGtJ1LGR3fMP4sQUtcpd1rZARFUuwiSc";
    $api_company_id = "676fbe2232cb3202";
    $templateName = "new_ticket_registration";

    // Send WhatsApp messages
    foreach ($recipients as $send_to_number) {
        $payload = [
            "phone_number_id" => $phone_number_id,
            "customer_country_code" => "91",
            "customer_number" => $send_to_number,
            "data" => [
                "type" => "template",
                "context" => [
                    "template_name" => $templateName,
                    "language" => "en",
                    "body" => [
                        "1" => $ticket_data['customer_name'],
                        "2" => $ticket_data['address'],
                        "3" => $ticket_data['contact_name'],
                        "4" => $ticket_data['contact_phone1'] . (!empty($ticket_data['contact_phone2']) ? " / " . $ticket_data['contact_phone2'] : ""),
                        "5" => $ticket_data['ticket_id'],
                        "6" => $ticket_data['ticket_date'],
                        "7" => $ticket_data['ticket_status'],
                        "8" => $ticket_data['ticket_priority'],
                        "9" => $ticket_data['main_cause'],
                        "10" => $ticket_data['problem_statement'],
                        "11" => $ticket_data['account_email_id']
                    ],
                ],
            ],
            "reply_to" => null,
            "myop_ref_id" => uniqid(),
            
        ];
    
        error_log("Sending to $send_to_number, Payload: " . json_encode($payload));
    
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
            ob_clean(); 
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
    }
    ob_clean(); 
    
    echo json_encode(['status' => 'success', 'message' => 'Messages sent successfully.', 'ticket_id' => $ticket_data['ticket_id']]);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
$conn->close();
?>
