<?php


curl_setopt_array($curl, array(
  CURLOPT_URL => '{{baseUrl}}/chat/messages',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
                            "phone_number_id": "87673432323365",
                            "customer_country_code": "91",
                            "customer_number": "9876543XXX",
                            "data": {
                                "type": "text",
                                "context": {
                                "body": "Hi John!, How are you?",
                                "preview_url": false
                                }
                            },
                            "reply_to": null,
                            "myop_ref_id": "<<unique value>>"
                            }',
  CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Authorization: Bearer {{apikey}}',
                        'X-MYOP-COMPANY-ID: {{companyid}}'
  ),
));