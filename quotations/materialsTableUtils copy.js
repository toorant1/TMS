// materialsTableUtils.js

/**
 * Renders the materials table dynamically.
 * @param {Array} materialsArray - Array of material objects.
 * @param {HTMLElement} materialsTableBody - Table body element where rows will be inserted.
 */
function renderMaterialsTable(materialsArray, materialsTableBody) {
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
                <td class="basic-total">${basicTotal.toFixed(2)}</td>
                <td>${material.hsn_code}</td>
                <td>${material.hsn_percentage}%</td>
                <td class="hsn-total">${hsnTotal.toFixed(2)}</td>
                <td class="grand-total">${grandTotal.toFixed(2)}</td>
                <td><input type="text" class="form-control remark-input" value="${material.master_quotation_materials_remark || ''}"></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm delete-material-button" data-material-id="${material.master_quotation_material_id}">
                        Delete
                    </button>
                </td>
            </tr>
        `;
        materialsTableBody.insertAdjacentHTML('beforeend', row);
    });
}

// Export the function to make it available for import
export { renderMaterialsTable };
