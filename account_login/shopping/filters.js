document.addEventListener("DOMContentLoaded", function () {
    const typeFilter = document.getElementById("typeFilter");
    const unitFilter = document.getElementById("unitFilter");
    const makeFilter = document.getElementById("makeFilter");
    const materialTableBody = document.getElementById("material-table-body");

    // Function to update the material table based on filter data
    function updateMaterialTable(data) {
        materialTableBody.innerHTML = ""; // Clear the table body

        // Populate the table with filtered materials
        data.forEach(material => {
            const row = `
                <tr>
                    <td>${material.name}</td>
                    <td>${material.description}</td>
                    <td>${material.material_type}</td>
                    <td>${material.material_unit}</td>
                    <td>${material.material_make}</td>
                    <td>${material.hsn_code}</td>
                    <td>${material.hsn_percentage}</td>
                    <td>
                        <input 
                            type="number" 
                            class="form-control quantity-input" 
                            min="1" 
                            value="" 
                            data-add-btn-id="add-btn-${material.id}" 
                        />
                    </td>
                    <td>
                        <button 
                            id="add-btn-${material.id}"
                            class="btn btn-success btn-sm add-to-cart-btn" 
                            data-name="${material.name}" 
                            data-description="${material.description}" 
                            data-type="${material.material_type}" 
                            data-unit="${material.material_unit}" 
                            data-make="${material.material_make}" 
                            data-hsn="${material.hsn_code}" 
                            data-hsn-percent="${material.hsn_percentage}"
                            data-quantity<="0"
                            disabled
                        >
                            Add to Cart
                        </button>
                    </td>
                </tr>
            `;
            materialTableBody.insertAdjacentHTML("beforeend", row);
        });
    }

    // Event listener for each filter dropdown
    const filters = [typeFilter, unitFilter, makeFilter];
    filters.forEach(filter => {
        filter.addEventListener("change", function () {
            const typeId = typeFilter.value;
            const unitId = unitFilter.value;
            const makeId = makeFilter.value;

            // Send AJAX request to fetch filtered data
            fetch("myshop.php", { // Replace "myshop.php" with your actual PHP endpoint
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `type_id=${typeId}&unit_id=${unitId}&make_id=${makeId}`
            })
                .then(response => response.json())
                .then(data => {
                    updateMaterialTable(data);
                })
                .catch(error => console.error("Error fetching filtered materials:", error));
        });
    });
});
