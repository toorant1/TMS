document.addEventListener('DOMContentLoaded', function () {
    const addMaterialButton = document.getElementById('addMaterialButton');

    if (addMaterialButton) {
        addMaterialButton.addEventListener('click', function () {
            // Get values from input fields
            const materialId = document.getElementById('material_name').value; // Material ID from dropdown
            const materialName = document.getElementById('material_name').selectedOptions[0]?.text; // Material name from dropdown
            const quantity = document.getElementById('quantity').value;
            const unit = document.getElementById('unit').value; // Unit should come from the backend or a hidden field
            const remark = document.getElementById('remark').value;
            const ticketId = document.querySelector('input[name="ticket_id"]')?.value; // Hidden ticket ID

            // Log values for debugging
            console.log("Material ID:", materialId);
            console.log("Material Name:", materialName);
            console.log("Quantity:", quantity);
            console.log("Unit:", unit);
            console.log("Remark:", remark);
            console.log("Ticket ID:", ticketId);

            // Validate required fields
            if (!materialId || !quantity || !ticketId) {
                alert("Please fill in all required fields.");
                return;
            }

            // Prepare data to send
            const formData = new FormData();
            formData.append('ticket_id', ticketId);
            formData.append('material_id', materialId); // Pass the material ID
            formData.append('material_name', materialName); // Pass the material name
            formData.append('quantity', quantity);
            formData.append('unit', unit);
            formData.append('remark', remark);

            // Send data to PHP script
            fetch('add_material.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Material added successfully!');
                        location.reload(); // Reload page to refresh material list
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });
        });
    }
});
document.addEventListener('DOMContentLoaded', function () {
    // Ensure the button exists before adding the event listener
    const addMaterialButton = document.getElementById('addMaterialButton');

    if (addMaterialButton) {
        addMaterialButton.addEventListener('click', function () {
            console.log('Add Material Button clicked!');
            // Additional functionality here...
        });
    } else {
        console.error('Add Material Button does not exist.');
    }
});
