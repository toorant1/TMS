document.addEventListener('DOMContentLoaded', function () {
    const debounceTimeout = 10000; // 10 seconds
    let timers = {}; // Track timers for each textarea

    // Function to update conditions in the database
    function updateCondition(fieldName, fieldValue) {
        const quotationId = document.querySelector('input[name="quotation_id"]').value;

        fetch('update_conditions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                quotation_id: quotationId,
                field_name: fieldName,
                field_value: fieldValue
            })
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    console.log(`${fieldName} updated successfully.`);
                } else {
                    console.error(`Failed to update ${fieldName}: ${data.message}`);
                }
            })
            .catch((error) => {
                console.error(`Error updating ${fieldName}:`, error);
            });
    }

    // Function to handle the update on blur or after timeout
    function handleConditionUpdate(event) {
        const field = event.target;
        const fieldName = field.id; // The id matches the database column name
        const fieldValue = field.value.trim();

        if (timers[fieldName]) {
            clearTimeout(timers[fieldName]); // Clear the timer if it exists
        }

        // Trigger the update immediately when focus is lost
        if (event.type === 'blur') {
            updateCondition(fieldName, fieldValue);
        } else {
            // Trigger the update after debounceTimeout (10 seconds)
            timers[fieldName] = setTimeout(() => {
                updateCondition(fieldName, fieldValue);
            }, debounceTimeout);
        }
    }

    // Attach event listeners to the textareas
    ['payment_conditions', 'delivery_conditions', 'other_conditions', 'internal_remark_conditions'].forEach((fieldId) => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', handleConditionUpdate);
            field.addEventListener('input', handleConditionUpdate);
        }
    });
});
