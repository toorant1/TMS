document.addEventListener('DOMContentLoaded', function () {
    const debounceTimeout = 10000; // 10 seconds
    let timers = {}; // Track timers for each field

    // Function to convert date from dd-mm-yyyy to yyyy-mm-dd
    function formatDateToISO(dateStr) {
        if (dateStr.includes('-')) {
            const [day, month, year] = dateStr.split('-');
            return `${year}-${month}-${day}`; // Convert dd-mm-yyyy to yyyy-mm-dd
        }
        return dateStr; // Already in ISO format
    }

    // Function to update fields in the database
    function updateField(fieldName, fieldValue) {
        const quotationId = document.querySelector('input[name="quotation_id"]').value;

        // Format dates if necessary
        if (fieldName === 'quotation_date' || fieldName === 'quotation_valid_upto_date') {
            fieldValue = formatDateToISO(fieldValue);
        }

        fetch('update_headers.php', {
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

    // Function to handle updates on blur or after timeout
    function handleFieldUpdate(event) {
        const field = event.target;
        const fieldName = field.dataset.field; // Get the field name from the data-field attribute
        const fieldValue = field.value.trim();

        if (timers[fieldName]) {
            clearTimeout(timers[fieldName]); // Clear the timer if it exists
        }

        // Trigger the update immediately when focus is lost
        if (event.type === 'blur') {
            updateField(fieldName, fieldValue);
        } else {
            // Trigger the update after debounceTimeout (10 seconds)
            timers[fieldName] = setTimeout(() => {
                updateField(fieldName, fieldValue);
            }, debounceTimeout);
        }
    }

    // Attach event listeners to fields
    const updatableFields = document.querySelectorAll('.update-field');
    updatableFields.forEach((field) => {
        field.addEventListener('blur', handleFieldUpdate);
        field.addEventListener('input', handleFieldUpdate);
        field.addEventListener('change', handleFieldUpdate); // For dropdowns
    });
});
