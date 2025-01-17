
import { renderMaterialsTable } from 'materialsTableUtils.js';

function attachBlurEventListeners(materialsArray) {
    // Attach blur event to quantity, unit price, and remark inputs
    document.querySelectorAll('.quantity-input, .price-input, .remark-input').forEach((input) => {
        input.addEventListener('blur', function () {
            const rowElement = this.closest('tr');
            const materialId = parseInt(rowElement.getAttribute('data-material-id'), 10);

            if (!materialId) {
                alert('Invalid material ID.');
                return;
            }

            // Determine the field being updated
            let fieldName;
            if (this.classList.contains('quantity-input')) {
                fieldName = 'quantity';
            } else if (this.classList.contains('price-input')) {
                fieldName = 'unit_price';
            } else if (this.classList.contains('remark-input')) {
                fieldName = 'master_quotation_materials_remark';
            }

            const fieldValue = this.value.trim();

            // Validate the input values
            if ((fieldName === 'quantity' || fieldName === 'unit_price') && (isNaN(fieldValue) || fieldValue <= 0)) {
                alert(`${fieldName} must be a valid number greater than 0.`);
                this.focus();
                return;
            }

            // Trigger the update query
            updateMaterial(materialId, fieldName, fieldValue, materialsArray);
        });
    });
}

// Call this function after rendering the materials table
renderMaterialsTable(materialsArray, materialsTableBody) ;
