document.addEventListener('DOMContentLoaded', function () {
    const addMaterialButton = document.getElementById('addMaterialButton');
    const materialsTableBody = document.getElementById('materialsTableBody');
    let materialsArray = JSON.parse(document.getElementById('materialsData').textContent);

    // Function to render the materials table
    function renderMaterialsTable() {
        materialsTableBody.innerHTML = ''; // Clear the table body

        if (materialsArray.length === 0) {
            materialsTableBody.innerHTML = '<tr><td colspan="12" class="text-center">No materials added to this quotation.</td></tr>';
            return;
        }

        materialsArray.forEach((material) => {
            const basicTotal = material.quantity * material.unit_price;
            const hsnTotal = (basicTotal * material.hsn_percentage) / 100;
            const grandTotal = basicTotal + hsnTotal;

            const row = `
                <tr data-material-id="${material.master_quotation_material_id}">
                    <td>${material.master_quotation_material_id}</td>
                    <td>${material.material_name}</td>
                    <td><input type="number" class="form-control quantity-input" value="${material.quantity}" min="0"></td>
                    <td><input type="number" class="form-control price-input" value="${material.unit_price}" min="0"></td>
                    <td>${basicTotal.toFixed(2)}</td>
                    <td>${material.hsn_code}</td>
                    <td>${material.hsn_percentage}%</td>
                    <td>${hsnTotal.toFixed(2)}</td>
                    <td>${grandTotal.toFixed(2)}</td>
                    <td><input type="text" class="form-control" value="${material.master_quotation_materials_remark || ''}"></td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm delete-material-button" data-material-id="${material.master_quotation_material_id}">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
            materialsTableBody.insertAdjacentHTML('beforeend', row);
        });

        attachDeleteEventListeners(); // Attach delete event listeners after rendering rows
    }

    // Add new material to the table
    addMaterialButton.addEventListener('click', function () {
        const materialID = document.getElementById('material_name').value;
        const materialQuantity = parseFloat(document.getElementById('quantity').value) || 0;
        const materialPrice = parseFloat(document.getElementById('unit_price').value) || 0;
        const remark = document.getElementById('remark').value;

        if (!materialID || materialQuantity <= 0 || materialPrice <= 0) {
            alert('Please fill in all required fields.');
            return;
        }

        // Send the data to the server to insert into the database
        fetch('add_material.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                quotation_id: document.querySelector('input[name="quotation_id"]').value,
                material_id: materialID,
                quantity: materialQuantity,
                unit_price: materialPrice,
                remark: remark,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    materialsArray.push(data.new_material); // Add the new material from the server response
                    renderMaterialsTable();

                    // Clear input fields
                    document.getElementById('material_name').value = '';
                    document.getElementById('quantity').value = '';
                    document.getElementById('unit_price').value = '';
                    document.getElementById('remark').value = '';
                } else {
                    alert('Failed to add material. Please try again.');
                }
            })
            .catch((error) => console.error('Error adding material:', error));
    });

    // Function to delete a material
    function deleteMaterial(materialId) {
        if (confirm('Are you sure you want to delete this material?')) {
            fetch('delete_material.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ material_id: materialId }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        // Remove the material from the materialsArray
                        materialsArray = materialsArray.filter(material => material.master_quotation_material_id !== materialId);
                        renderMaterialsTable();
                        alert('Material deleted successfully.');
                    } else {
                        alert('Failed to delete material. Please try again.');
                    }
                })
                .catch((error) => {
                    console.error('Error deleting material:', error);
                    alert('An error occurred while deleting the material. Please try again later.');
                });
        }
    }

    // Attach delete button event listeners
    function attachDeleteEventListeners() {
        const deleteButtons = document.querySelectorAll('.delete-material-button');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const materialId = this.getAttribute('data-material-id');
                deleteMaterial(materialId);
            });
        });
    }

    // Initial table load
    renderMaterialsTable();
});
