document.addEventListener("DOMContentLoaded", function () {
    const materialTableBody = document.getElementById("material-table-body");
    const cartTableBody = document.getElementById("cart-table-body");
    const cart = [];

    // Add event listener for "Add to Cart" buttons
    document.body.addEventListener("click", function (event) {
        if (event.target.classList.contains("add-to-cart-btn")) {
            const material = {
                id: cart.length + 1, // Unique ID for each material
                name: event.target.dataset.name,
                description: event.target.dataset.description,
                type: event.target.dataset.type,
                unit: event.target.dataset.unit,
                make: event.target.dataset.make,
                hsn: event.target.dataset.hsn,
                hsnPercent: event.target.dataset.hsnPercent,
                quantity: event.target.dataset.quantity,
            };

            // Add material to cart array
            cart.push(material);

            // Update cart table
            updateCartTable();
        }

        // Handle delete button click in the cart
        if (event.target.classList.contains("delete-from-cart-btn")) {
            const id = parseInt(event.target.dataset.id, 10); // Get the ID of the item to delete
            const index = cart.findIndex(item => item.id === id);
            if (index !== -1) {
                cart.splice(index, 1); // Remove item from the cart array
                updateCartTable(); // Refresh the cart table
            }
        }
    });

    // Enable or disable Add to Cart button based on quantity input
    document.body.addEventListener("input", function (event) {
        if (event.target.classList.contains("quantity-input")) {
            const quantity = parseInt(event.target.value, 10);
            const addButtonId = event.target.dataset.addBtnId;
            const addButton = document.getElementById(addButtonId);

            if (quantity >= 1) {
                addButton.disabled = false;
                addButton.setAttribute("data-quantity", quantity); // Update the quantity data attribute
            } else {
                addButton.disabled = true;
            }
        }
    });

    function updateCartTable() {
        // Clear the current cart table body
        cartTableBody.innerHTML = "";

        // Populate table with cart items
        cart.forEach(material => {
            const row = `
                <tr>
                    <td>${material.name}</td>
                    <td>${material.description}</td>
                    <td>${material.type}</td>
                    <td>${material.unit}</td>
                    <td>${material.make}</td>
                    <td>${material.hsn}</td>
                    <td>${material.hsnPercent}</td>
                    <td>${material.quantity}</td>
                    <td>
                        <button 
                            class="btn btn-danger btn-sm delete-from-cart-btn" 
                            data-id="${material.id}" 
                        >
                            Delete
                        </button>
                    </td>
                </tr>
            `;
            cartTableBody.insertAdjacentHTML("beforeend", row);
        });
    }
});
