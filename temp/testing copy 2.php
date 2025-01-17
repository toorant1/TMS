<?php

// Configuration
$baseUrl = 'https://publicapi.myoperator.co';
$wabaId = '458185417387712'; // WABA ID
$apiKey = 'hfjuhlR3RuHOxhG3coKxIhM3UBHJYKkBEtwLwEoIXz'; // API Key
$companyId = '676fbe2232cb3202'; // Company ID

// Function to fetch templates
function fetchTemplates($baseUrl, $wabaId, $apiKey, $companyId) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $baseUrl . '/chat/templates?waba_id=' . $wabaId . '&waba_template_status=approved&category=marketing&limit=10&offset=0',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey,
            'X-MYOP-COMPANY-ID: ' . $companyId
        ),
    ));

    $response = curl_exec($curl);
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        echo json_encode(array("error" => $curlError), JSON_PRETTY_PRINT);
        return null;
    } else {
        if ($httpStatus == 200) {
            $data = json_decode($response, true);
            if (isset($data['data']) && is_array($data['data'])) {
                echo json_encode(array("approved_templates" => $data['data']), JSON_PRETTY_PRINT);
                return $data['data'];
            } else {
                echo json_encode(array("message" => "No templates found or invalid response structure."), JSON_PRETTY_PRINT);
                return null;
            }
        } else {
            echo json_encode(array("http_status" => $httpStatus, "response" => $response), JSON_PRETTY_PRINT);
            return null;
        }
    }
}

// Fetch Templates
$templates = fetchTemplates($baseUrl, $wabaId, $apiKey, $companyId);

$templateName = null;
if ($templates) {
    // Use the first valid template
    foreach ($templates as $template) {
        if (isset($template['name'])) {
            $templateName = $template['name'];
            break;
        }
    }

    if ($templateName) {
        echo json_encode(array("using_template_name" => $templateName), JSON_PRETTY_PRINT);
    } else {
        echo json_encode(array("message" => "No valid templates found. Cannot proceed."), JSON_PRETTY_PRINT);
    }
} else {
    echo json_encode(array("message" => "No templates fetched. Cannot proceed."), JSON_PRETTY_PRINT);
    exit;
}

// Function to send a WhatsApp message
function sendMessage($baseUrl, $wabaId, $apiKey, $companyId, $templateName, $recipientPhoneNumber) {
    if (empty($templateName)) {
        echo json_encode(array("message" => "Template name is empty. Cannot send message."), JSON_PRETTY_PRINT);
        return;
    }

    $payload = json_encode(array(
        'waba_id' => $wabaId,
        'to' => $recipientPhoneNumber,
        'type' => 'template',
        'template' => array(
            'name' => $templateName,
            'language' => array('policy' => 'deterministic', 'code' => 'en') // Update language if needed
        )
    ));

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $baseUrl . '/chat/messages',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey,
            'X-MYOP-COMPANY-ID: ' . $companyId
        ),
    ));

    $response = curl_exec($curl);
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        echo json_encode(array("error" => $curlError), JSON_PRETTY_PRINT);
    } else {
        if ($httpStatus == 200 || $httpStatus == 201) {
            echo json_encode(array("message" => "Message sent successfully.", "response" => json_decode($response, true)), JSON_PRETTY_PRINT);
        } else {
            echo json_encode(array("http_status" => $httpStatus, "response" => $response), JSON_PRETTY_PRINT);
        }
    }
}

// Send the message using the approved template
$recipientPhoneNumber = '9638480441'; // Replace with the recipient's phone number
if ($templateName) {
    sendMessage($baseUrl, $wabaId, $apiKey, $companyId, $templateName, $recipientPhoneNumber);
} else {
    echo json_encode(array("message" => "Message sending skipped due to missing template."), JSON_PRETTY_PRINT);
}
?>
