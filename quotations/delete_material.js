
document.addEventListener('DOMContentLoaded', function () {
    const materialsTableBody = document.getElementById('materialsTableBody');
    const materialsArray = JSON.parse(document.getElementById('materialsData').textContent);

    // Function to render materials table dynamically
    function renderMaterialsTable(materialsArray, materialsTableBody) {
        materialsTableBody.innerHTML = ''; // Clear the table body

        if (materialsArray.length === 0) {
            materialsTableBody.innerHTML = '<tr><td colspan="11" class="text-center">No materials added to this quotation.</td></tr>';
            return;
        }

        let totalBasic = 0;
        let totalHSN = 0;
        let totalGrand = 0;

        materialsArray.forEach((material) => {
            const basicTotal = material.quantity * material.unit_price;
            const hsnTotal = (basicTotal * material.hsn_percentage) / 100;
            const grandTotal = basicTotal + hsnTotal;

            totalBasic += basicTotal;
            totalHSN += hsnTotal;
            totalGrand += grandTotal;

            const row = `
                <tr data-material-id="${material.master_quotation_material_id}">
                    <td>${material.master_quotation_material_id}</td>
                    <td><a href="#" class="edit-material" data-material-id="${material.master_quotation_material_id}" 
                        data-material-name="${material.material_name}" data-quantity="${material.quantity}" 
                        data-price="${material.unit_price}" data-remark="${material.master_quotation_materials_remark}">
                        ${material.material_name}
                    </a></td>
                    <td>${material.quantity}</td>
                    <td>${material.unit_price}</td>
                    <td>${basicTotal.toFixed(2)}</td>
                    <td>${material.hsn_code}</td>
                    <td>${material.hsn_percentage}%</td>
                    <td>${hsnTotal.toFixed(2)}</td>
                    <td>${grandTotal.toFixed(2)}</td>
                    <td>${material.master_quotation_materials_remark || ''}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm delete-material-button" 
                            data-material-id="${material.master_quotation_material_id}">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
            materialsTableBody.insertAdjacentHTML('beforeend', row);
        });

        // Footer row with totals
        const footerRow = `
            <tr>
                <td colspan="4" class="text-end"><strong>Totals:</strong></td>
                <td><strong>${totalBasic.toFixed(2)}</strong></td>
                <td colspan="2"></td>
                <td><strong>${totalHSN.toFixed(2)}</strong></td>
                <td><strong>${totalGrand.toFixed(2)}</strong></td>
                <td colspan="2"></td>
            </tr>
        `;
        materialsTableBody.insertAdjacentHTML('beforeend', footerRow);

        // Attach event listeners for editing
        document.querySelectorAll('.edit-material').forEach((link) => {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                const materialId = this.getAttribute('data-material-id');
                const materialName = this.getAttribute('data-material-name');
                const quantity = this.getAttribute('data-quantity');
                const price = this.getAttribute('data-price');
                const remark = this.getAttribute('data-remark');

                openEditModal(materialId, materialName, quantity, price, remark);
            });
        });
    }

    // Open the edit modal
    function openEditModal(materialId, materialName, quantity, price, remark) {
        const modal = document.getElementById('editMaterialModal');
        modal.querySelector('#modalMaterialName').value = materialName;
        modal.querySelector('#modalQuantity').value = quantity;
        modal.querySelector('#modalPrice').value = price;
        modal.querySelector('#modalRemark').value = remark;
        modal.querySelector('#saveChangesButton').setAttribute('data-material-id', materialId);

        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }

    // Save changes made in the modal
    document.getElementById('saveChangesButton').addEventListener('click', function () {
        const modal = document.getElementById('editMaterialModal');
        const materialId = parseInt(this.getAttribute('data-material-id'), 10);
        const updatedData = {
            quantity: parseFloat(modal.querySelector('#modalQuantity').value),
            unit_price: parseFloat(modal.querySelector('#modalPrice').value),
            remark: modal.querySelector('#modalRemark').value,
        };

        saveMaterialChanges(materialId, updatedData, materialsArray);

        // Close the modal
        const bootstrapModal = bootstrap.Modal.getInstance(modal);
        bootstrapModal.hide();
    });

    function saveMaterialChanges(materialId, updatedData, materialsArray) {
        // Simulate updating the database via fetch API
        const material = materialsArray.find((mat) => mat.master_quotation_material_id === materialId);
        if (material) {
            material.quantity = updatedData.quantity;
            material.unit_price = updatedData.unit_price;
            material.master_quotation_materials_remark = updatedData.remark;

            renderMaterialsTable(materialsArray, materialsTableBody);
            alert('Material updated successfully.');
        }
    }

    function deleteMaterial(materialId, rowElement, materialsArray) {
        // Show confirmation dialog
        const userConfirmed = confirm('Are you sure you want to delete this Selected Material from Quotation?  This action cannot be undone');
    
        if (!userConfirmed) {
            // If the user cancels, exit the function
            return;
        }
    
        // Proceed with the deletion if the user confirms
        fetch('delete_material.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ material_id: materialId }), // Send material ID in request
        })
            .then((response) => response.json()) // Parse the JSON response
            .then((data) => {
                if (data.success) {
                    // Remove the material from the local array
                    const materialIndex = materialsArray.findIndex((mat) => mat.master_quotation_material_id === materialId);
                    if (materialIndex !== -1) {
                        materialsArray.splice(materialIndex, 1);
                    }
    
                    // Remove the row from the DOM
                    rowElement.remove();
    
                    // Re-render the table to update totals
                    renderMaterialsTable(materialsArray, document.getElementById('materialsTableBody'));
    
                    alert(data.message || 'Material deleted successfully.');
                } else {
                    alert(data.message || 'Failed to delete material.');
                }
            })
            .catch((error) => {
                console.error('Error deleting material:', error);
                alert('An error occurred while deleting the material. Please try again later.');
            });
    }
    
    // Handle delete button click
    materialsTableBody.addEventListener('click', function (event) {
        if (event.target.classList.contains('delete-material-button')) {
            const materialId = parseInt(event.target.getAttribute('data-material-id'), 10);
            const rowElement = event.target.closest('tr');
            deleteMaterial(materialId, rowElement, materialsArray);
        }
    });
    
});
