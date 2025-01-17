<?php

$baseUrl = 'https://publicapi.myoperator.co'; // Base API URL
$apiKey = 'tqYr8PooE3HGtJ1LGR3fMP4sQUtcpd1rZARFUuwiSc'; // API Key
$companyId = '676fbe2232cb3202'; // Company ID
$phoneNumberId = '553313077858045'; // Sender's Phone Number ID
$customerCountryCode = '91'; // 
$customerNumber = '9638480441'; // this is my nubmer 
$myopRefId = uniqid(); // Unique Reference ID

// Template details
$templateId = '1125101409236154'; // Template ID
$templateName = 'account_creation_confirmation_3'; // Template Name
$languageCode = 'en'; // Template Language Code

// Placeholders
$name = 'Bhargav';
$email = 'bhargav@example.com';

// API endpoint for sending template messages
$url = "$baseUrl/chat/messages";

// Payload for sending a template message
$data = [
    'type' => 'message', // Type of the message
    'source' => 'lc', // Default source: live chat (lc)
    'event' => 'sent', // Initial event status
    'phone_number_id' => $phoneNumberId,
    'customer_country_code' => $customerCountryCode,
    'customer_number' => $customerNumber,
    'data' => [
        'type' => 'template',
        'context' => [
            'id' => $templateId,
            'template_name' => $templateName,
            'language' => [
                'code' => $languageCode
            ],
            'body' => [
                '0' => $name, // Placeholder for {{name}}
                '1' => $email // Placeholder for {{email}}
            ]
        ],
        'status' => 'sent', // Message status (sent by default)
        'metadata' => [
            'waba_msg_id' => uniqid('waba_'), // Unique WABA message ID
            'myop_ref_id' => $myopRefId,
            'errors' => [] // Placeholder for potential error codes
        ],
        'reply_to' => null // Explicitly set to null for first-time messages
    ],
    'conversation' => [
        'id' => uniqid('conversation_'), // Unique conversation ID
        'customer_country_code' => $customerCountryCode,
        'customer_contact' => $customerNumber,
        'customer_name' => $name,
        'assigned_to' => 'Agent_123', // Agent ID assigned to this conversation
        'status' => 'unassigned', // Initial status
        'expire_at' => date('c', strtotime('+24 hours')), // Expiration: 24 hours from now
        'last_message_at' => date('c'), // Current timestamp
        'created' => date('c'), // Current timestamp
        'modified' => date('c'), // Current timestamp
        'unread_count' => 0 // No unread messages initially
    ]
];

// Headers
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

curl_close($curl);

// Parse and display the response
$responseData = json_decode($response, true);

if (isset($responseData['status']) && $responseData['status'] === 'success') {
    echo "Template message sent successfully!";
} else {
    // Log detailed error response for debugging
    echo "Failed to send template message. Response: " . print_r($responseData, true);
    file_put_contents('error_log.txt', print_r($responseData, true), FILE_APPEND);
}

?>
