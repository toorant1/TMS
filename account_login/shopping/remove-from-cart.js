document.addEventListener("DOMContentLoaded", function () {
    const cartTableBody = document.getElementById("cart-table-body");
    const cart = [];

    // Handle delete button click in cart
    document.body.addEventListener("click", function (event) {
        if (event.target.classList.contains("delete-from-cart-btn")) {
            const id = parseInt(event.target.dataset.id);
            const index = cart.findIndex(item => item.id === id);
            if (index !== -1) {
                cart.splice(index, 1);
                updateCartTable();
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
