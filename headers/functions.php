<?php
// Include database connection
require_once '../database/db_connection.php';

session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Function to fetch WhatsApp configuration from the database
function getWhatsAppConfiguration($conn, $master_userid) {
    $query = "SELECT api_url, api_key, phone_number_id, whatsapp_status 
              FROM master_whatsapp_configuration 
              WHERE master_user_id = ? AND whatsapp_status = 1 LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}


// Function to send WhatsApp message
function sendWhatsAppMessage($conn, $master_userid, $phoneNumber, $message) {
    $config = getWhatsAppConfiguration($conn, $master_userid);

    if (!$config) {
        return "Error: No active WhatsApp configuration found.";
    }

    $baseUrl = $config['api_url'];
    $apiKey = $config['api_key'];
    $companyId = $config['company_id'] ?? ''; // If `company_id` is needed and exists
    $phoneNumberId = $config['phone_number_id'];

    if (empty($phoneNumber) || empty($message)) {
        return "Error: Phone number and message are required.";
    }

    $body = [
        'phone_number_id' => $phoneNumberId,
        'customer_country_code' => '91',
        'customer_number' => $phoneNumber,
        'data' => [
            'type' => 'text',
            'context' => [
                'body' => $message,
                'preview_url' => false
            ]
        ],
        'reply_to' => null,
        'myop_ref_id' => uniqid() // Unique reference ID
    ];

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $baseUrl . '/chat/messages',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30, // Reasonable timeout
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey,
            'X-MYOP-COMPANY-ID: ' . $companyId // Include company ID if applicable
        ),
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    if ($error) {
        return "Error sending message: $error";
    }

    return $httpStatus == 200 ? "Message sent successfully." : "Failed to send message. HTTP Status: $httpStatus. Response: $response";
}
?>
