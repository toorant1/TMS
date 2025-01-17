import { renderMaterialsTable } from './materialsTableUtils.js';

document.addEventListener('DOMContentLoaded', function () {
    const materialsTableBody = document.getElementById('materialsTableBody');
    let materialsArray = JSON.parse(document.getElementById('materialsData').textContent);

    function updateMaterial(materialId, updatedData) {
        fetch('update_material.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                material_id: materialId,
                ...updatedData,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // Update the local array
                    const material = materialsArray.find((mat) => mat.master_quotation_material_id === materialId);
                    if (material) {
                        material.quantity = updatedData.quantity;
                        material.unit_price = updatedData.unitPrice;
                        material.master_quotation_materials_remark = updatedData.remark;

                        // Re-render the table
                        renderMaterialsTable(materialsArray, materialsTableBody, updateMaterial, deleteMaterial);
                    }
                    alert('Material updated successfully.');
                } else {
                    alert(data.message || 'Failed to update material.');
                }
            })
            .catch((error) => console.error('Error updating material:', error));
    }

    function deleteMaterial(materialId) {
        fetch('delete_material.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ material_id: materialId }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // Remove the material from the local array
                    materialsArray = materialsArray.filter((mat) => mat.master_quotation_material_id !== materialId);

                    // Re-render the table
                    renderMaterialsTable(materialsArray, materialsTableBody, updateMaterial, deleteMaterial);
                    alert('Material deleted successfully.');
                } else {
                    alert(data.message || 'Failed to delete material.');
                }
            })
            .catch((error) => console.error('Error deleting material:', error));
    }

    // Initial rendering of the materials table
    renderMaterialsTable(materialsArray, materialsTableBody, updateMaterial, deleteMaterial);
});
