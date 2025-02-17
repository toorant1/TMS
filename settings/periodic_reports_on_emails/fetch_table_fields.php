<?php
require_once '../../database/db_connection.php'; // Ensure the database connection is correct

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['table_name'])) {
    $table_name = $_POST['table_name'];

    // Fetch column names and data types from `auto_generated_reports_tables_fields`
    $query = "SELECT field_name, field_description, field_data_type FROM auto_generated_reports_tables_fields WHERE table_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $table_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<div class="mb-3"><h5>Select Fields for Report:</h5>';
        echo '<form id="dynamicForm">';
        while ($row = $result->fetch_assoc()) {
            $field_name = $row['field_name'];
            $field_description = $row['field_description'];
            $field_data_type = strtoupper($row['field_data_type']); // Convert to uppercase for consistency

            echo '<div class="form-check">
                    <input class="form-check-input field-checkbox" type="checkbox" name="fields[]" value="' . $field_name . '" 
                           onchange="toggleInput(\'' . $field_name . '\', \'' . $field_data_type . '\')">
                    <label class="form-check-label">' . $field_description . '</label>
                  </div>
                  <div class="mb-2" id="input_' . $field_name . '" style="display: none;">
                      <input type="text" class="form-control field-input" name="conditions[' . $field_name . ']" 
                             placeholder="Enter value for ' . $field_description . '">
                      <input type="hidden" name="field_types[' . $field_name . ']" value="' . $field_data_type . '">
                  </div>';
        }
        echo '</form>';
        echo '<button type="button" class="btn btn-success mt-3" onclick="generateQuery()">Generate Query</button>';
        echo '<div id="queryResult" class="alert alert-info mt-3" style="display: none;"></div>';
    } else {
        echo '<div class="alert alert-warning">No fields found for the selected table.</div>';
    }
}
?>

<script>
    function toggleInput(field, fieldType) {
        let inputBox = document.getElementById("input_" + field);
        if (document.querySelector("input[value='" + field + "']").checked) {
            inputBox.style.display = "block";
        } else {
            inputBox.style.display = "none";
            inputBox.querySelector("input").value = "";
        }
    }

    function generateQuery() {
    let selectedFields = [];
    let conditions = [];
    let tableName = document.getElementById("reportTable").value;

    document.querySelectorAll(".field-checkbox:checked").forEach((checkbox) => {
        let fieldName = checkbox.value;
        selectedFields.push(fieldName);
        let conditionValue = document.querySelector("input[name='conditions[" + fieldName + "]']").value;
        let fieldType = document.querySelector("input[name='field_types[" + fieldName + "]']").value;

        if (conditionValue.trim() !== "") {
            // Use BINARY for case-sensitive comparison
            if (fieldType.includes("VARCHAR") || fieldType.includes("TEXT")) {
                conditions.push(fieldName + " LIKE BINARY '%" + conditionValue + "%'"); // Case-sensitive LIKE
            } else {
                conditions.push(fieldName + " = BINARY '" + conditionValue + "'"); // Case-sensitive equality
            }
        }
    });

    if (selectedFields.length === 0) {
        document.getElementById("queryResult").style.display = "block";
        document.getElementById("queryResult").innerHTML = "⚠️ Please select at least one field.";
        return;
    }

    let query = "SELECT * FROM " + tableName;
    if (conditions.length > 0) {
        query += " WHERE " + conditions.join(" AND ");
    }

    // Display the query without refreshing the page
    document.getElementById("queryResult").style.display = "block";
    document.getElementById("queryResult").innerHTML = "<strong>Generated SQL Query (Case-Sensitive):</strong><br>" + query;
}
</script>
