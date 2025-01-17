<?php

$waba_id = '458185417387712';  
$api_key = 'tqYr8PooE3HGtJ1LGR3fMP4sQUtcpd1rZARFUuwiSc';  
$company_id = '676fbe2232cb3202';  

// Initialize cURL
$curl = curl_init();

// Set up the cURL options
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://publicapi.myoperator.co/chat/templates?waba_id=$waba_id&waba_template_status=approved&category=marketing,utility&limit=10&offset=0",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json',
    'Authorization: Bearer ' . $api_key,
    'X-MYOP-COMPANY-ID: ' . $company_id
  ),
));

$response = curl_exec($curl);

if(curl_errno($curl)) {
  echo 'cURL Error: ' . curl_error($curl);
  exit;
}
curl_close($curl);
$template_data = json_decode($response, true);
if (isset($template_data['data']['results'])) {
  $templates = $template_data['data']['results'];
} else {
  $templates = [];
}
$selected_template_content = '';
if (isset($_GET['template_id'])) {
 foreach ($templates as $template) {
    if ($template['waba_template_id'] == $_GET['template_id']) {
      $selected_template_content = $template;
      break;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Template Dropdown</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
    }
    label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }
    select {
      width: 100%;
      padding: 10px;
      font-size: 16px;
    }
    .response-output {
      margin-top: 20px;
      padding: 10px;
      border: 1px solid #ccc;
      background-color: #f8f9fa;
    }
    .template-content {
      margin-top: 20px;
      padding: 10px;
      border: 1px solid #ccc;
      background-color: #e9ecef;
    }
  </style>
</head>
<body>
  <h1>Template Dropdown</h1>

 
  <div class="form-group">
    <label for="templateSelect">Select Template:</label>
    <select id="templateSelect" onchange="window.location.href='?template_id=' + this.value">
      <option value="">-- Select a Template --</option>
      <?php
        foreach ($templates as $template) {
          echo "<option value='{$template['waba_template_id']}'>{$template['name']}</option>";
        }
      ?>
    </select>
  </div>
  <div class="response-output" id="responseOutput">
    <?php if (empty($templates)) { echo "No templates found."; } else { echo "Templates loaded successfully."; } ?>
  </div>

  <?php if ($selected_template_content): ?>
    <div class="template-content">
      <h2>Template Content</h2>
      <p><strong>Id:</strong> <?= $selected_template_content['id'] ?></p>
      <p><strong>Name:</strong> <?= $selected_template_content['name'] ?></p>
      <p><strong>Category:</strong> <?= $selected_template_content['category'] ?></p>
      <p><strong>Language:</strong> <?= $selected_template_content['language'] ?></p>

      <h3>Components:</h3>
      <ul>
        <?php foreach ($selected_template_content['components'] as $component): ?>
          <li>
            <strong><?= $component['type'] ?>:</strong>
            <?php
              if ($component['type'] == 'BODY' || $component['type'] == 'HEADER' || $component['type'] == 'FOOTER') {
                echo "<p>{$component['text']}</p>";
              }
              if ($component['type'] == 'BUTTONS') {
                echo "<ul>";
                foreach ($component['buttons'] as $button) {
                  echo "<li>{$button['text']}</li>";
                }
                echo "</ul>";
              }
            ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <script>
    $(document).ready(function () {
      
      if ($("#templateSelect option").length <= 1) {
        $("#responseOutput").text("No templates found.");
      }
    });
  </script>
</body>
</html>