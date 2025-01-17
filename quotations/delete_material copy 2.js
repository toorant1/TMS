// Function to render the materials table dynamically with footer totals
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
                    <button 
                        type="button" 
                        class="btn btn-danger btn-sm delete-material-button" 
                        data-material-id="${material.master_quotation_material_id}">
                        Delete
                    </button>
                </td>
            </tr>
        `;
        materialsTableBody.insertAdjacentHTML('beforeend', row);
    });

    // Add the footer with totals
    const footerRow = `
        <tr>
            <td colspan="4" class="text-right"><strong>Totals:</strong></td>
            <td><strong>${totalBasic.toFixed(2)}</strong></td>
            <td colspan="2"></td>
            <td><strong>${totalHSN.toFixed(2)}</strong></td>
            <td><strong>${totalGrand.toFixed(2)}</strong></td>
            <td colspan="2"></td>
        </tr>
    `;
    materialsTableBody.insertAdjacentHTML('beforeend', footerRow);
}

// Function to delete a material
function deleteMaterial(materialId, rowElement, materialsArray) {
    if (!materialId) {
        alert('Invalid material ID. Please try again.');
        return;
    }

    if (confirm('Are you sure you want to delete this material?')) {
        fetch('delete_material.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ material_id: materialId }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // Remove the specific row from the DOM
                    rowElement.remove();

                    // Update the materialsArray to keep it in sync
                    const index = materialsArray.findIndex(material => material.master_quotation_material_id === materialId);
                    if (index !== -1) {
                        materialsArray.splice(index, 1); // Remove the deleted material from the array
                    }

                    // Re-render the table to update totals
                    renderMaterialsTable(materialsArray, document.getElementById('materialsTableBody'));
                    alert('Material deleted successfully.');
                } else {
                    alert(data.error || 'Failed to delete material. Please try again.');
                }
            })
            .catch((error) => {
                console.error('Error deleting material:', error);
                alert('An error occurred while deleting the material. Please try again later.');
            });
    }
}

// Add event listener for the delete button
document.addEventListener('DOMContentLoaded', function () {
    const materialsTableBody = document.getElementById('materialsTableBody');

    // Render initial materials table
    const materialsArray = JSON.parse(document.getElementById('materialsData').textContent);
    renderMaterialsTable(materialsArray, materialsTableBody);

    // Handle delete button click
    materialsTableBody.addEventListener('click', function (event) {
        if (event.target.classList.contains('delete-material-button')) {
            const materialId = parseInt(event.target.getAttribute('data-material-id'), 10);
            const rowElement = event.target.closest('tr'); // Get the specific row element
            deleteMaterial(materialId, rowElement, materialsArray);
        }
    });
});
