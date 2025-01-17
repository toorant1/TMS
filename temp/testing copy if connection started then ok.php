<?php

$baseUrl = 'https://publicapi.myoperator.co'; // Base API URL
$apiKey = 'tqYr8PooE3HGtJ1LGR3fMP4sQUtcpd1rZARFUuwiSc'; // API Key
$companyId = '676fbe2232cb3202'; // Company ID

// Input data for the WhatsApp message
$phoneNumberId = '553313077858045'; // Replace with the phone number ID
$customerCountryCode = '91'; // Country code
$customerNumber = '9638480441'; // Replace with the customer's phone number
$messageBody = 'Hi John!, How are you?'; // Replace with your message
$myopRefId = uniqid(); // Generate a unique reference ID

// API endpoint for sending messages
$url = "$baseUrl/chat/messages";

// Data payload for the API request
$data = [
    'phone_number_id' => $phoneNumberId,
    'customer_country_code' => $customerCountryCode,
    'customer_number' => $customerNumber,
    'data' => [
        'type' => 'text',
        'context' => [
            'body' => $messageBody,
            'preview_url' => false
        ]
    ],
    'reply_to' => null,
    'myop_ref_id' => $myopRefId
];

// Set headers
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $apiKey,
    'X-MYOP-COMPANY-ID: ' . $companyId
];

// Initialize cURL
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => $headers
]);

$response = curl_exec($curl);

// Check for errors
if (curl_errno($curl)) {
    echo 'cURL Error: ' . curl_error($curl);
    exit;
}

// Close cURL
curl_close($curl);

// Parse and display the response
$responseData = json_decode($response, true);

// Check for success and capture the message_id
if (isset($responseData['status']) && $responseData['status'] === 'success') {
    if (isset($responseData['data']['message_id'])) {
        $messageId = $responseData['data']['message_id']; // Extract the message ID
        echo "Message sent successfully! Message ID: " . $messageId;
    } else {
        echo "Message sent successfully, but message ID is not available.";
    }
} else {
    echo "Failed to send message. Response: " . print_r($responseData, true);
}

?>
