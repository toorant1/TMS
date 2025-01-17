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
  </style>
</head>
<body>
  <h1>Template Dropdown</h1>

  <!-- Dropdown Box -->
  <div class="form-group">
    <label for="templateSelect">Select Template:</label>
    <select id="templateSelect">
      <option value="">-- Select a Template --</option>
    </select>
  </div>

  <!-- Response Output -->
  <div class="response-output" id="responseOutput">Response will appear here...</div>

  <script>
    $(document).ready(function () {
      // API configuration
      const baseUrl = "https://publicapi.myoperator.co";
      const apiKey = "8dxzTcBVKKrS2NtCcW2DrS0095uCIcmh1moPUYY7uO";
      const companyId = "676fbe2232cb3202";
      const wabaId = "458185417387712";
      const url_string = "https://publicapi.myoperator.co/chat/templates?waba_id=458185417387712&waba_template_status=approved&category=marketing,utility&limit=10&offset=0";

      
      // Fetch templates
      function fetchTemplates() {
        const templateSettings = {
          url: url_string,
          method: "GET",
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${apiKey}`,
            "X-MYOP-COMPANY-ID": companyId
          }
        };

        $.ajax(templateSettings)
          .done(function (response) {
            if (response.results && response.results.length > 0) {
              const templateSelect = $("#templateSelect");
              response.results.forEach((template) => {
                templateSelect.append(
                  `<option value="${template.body}">${template.name}</option>`
                );
              });
              $("#responseOutput").text("Templates loaded successfully.");
            } else {
              $("#responseOutput").text("No templates found.");
            }
          })
          .fail(function (jqXHR, textStatus, errorThrown) {
            $("#responseOutput").html(
              `<strong>Error fetching templates:</strong> ${textStatus} - ${errorThrown}<br>${jqXHR.responseText}`
            );
          });
      }

      // Call fetchTemplates on page load
      fetchTemplates();
    });
  </script>
</body>
</html>
