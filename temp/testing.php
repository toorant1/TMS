<?php
   
    $phone_number_id = "553313077858045";
    $bearer_id = "tqYr8PooE3HGtJ1LGR3fMP4sQUtcpd1rZARFUuwiSc";
    $api_company_id = "676fbe2232cb3202";

    $send_to_number = "9638480441";


    $templateName = "new_ticket_generated";


   
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
                   "1" => "new_ticket_generated ",
                   "2" => "63",
                   "3" => "63",
                   "4" => "63",
                   "5" => "63",
                   "6" => "63",
                   "7" => "63",
                   "8" => "63",
                   "9" => "63"
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
   ?>

