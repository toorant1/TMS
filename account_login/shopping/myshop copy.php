<?php
session_start();

require_once __DIR__ . '../../../database/config.php';
require_once __DIR__ . '../../../database/helpers.php';
require_once __DIR__. '../../../database/db_connection.php'; // Include database connection file

// Fetch material types
$query_material_types = "SELECT id, material_type FROM master_materials_type";
$stmt_material_types = $conn->prepare($query_material_types);
$stmt_material_types->execute();
$material_types_result = $stmt_material_types->get_result();

// Fetch material makes
$query_material_makes = "SELECT id, make FROM master_materials_make";
$stmt_material_makes = $conn->prepare($query_material_makes);
$stmt_material_makes->execute();
$material_makes_result = $stmt_material_makes->get_result();

// Fetch material units
$query_material_units = "SELECT id, unit_name FROM master_materials_unit";
$stmt_material_units = $conn->prepare($query_material_units);
$stmt_material_units->execute();
$material_units_result = $stmt_material_units->get_result();

// Handle AJAX request for filtering materials
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($_POST['type_id'])) {
        $conditions[] = "m.material_type = ?";
        $params[] = $_POST['type_id'];
        $types .= 'i';
    }

    if (!empty($_POST['unit_id'])) {
        $conditions[] = "m.unit = ?";
        $params[] = $_POST['unit_id'];
        $types .= 'i';
    }

    if (!empty($_POST['make_id'])) {
        $conditions[] = "m.make = ?";
        $params[] = $_POST['make_id'];
        $types .= 'i';
    }

    $query = "
        SELECT 
            m.id,
            m.internal_code,
            m.name,
            m.description,
            mm.make AS material_make,
            mu.unit_name AS material_unit,
            mt.material_type AS material_type,
            m.hsn_code,
            m.hsn_percentage,
            m.status
        FROM 
            master_materials m
        LEFT JOIN master_materials_make mm ON m.make = mm.id
        LEFT JOIN master_materials_unit mu ON m.unit = mu.id
        LEFT JOIN master_materials_type mt ON m.material_type = mt.id
    ";

    if (!empty($conditions)) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $stmt = $conn->prepare($query);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $materials = [];
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }

    echo json_encode($materials);
    exit();
}

// Fetch all materials for initial load
$query_materials = "
    SELECT 
        m.id,
        m.internal_code,
        m.name,
        m.description,
        mm.make AS material_make,
        mu.unit_name AS material_unit,
        mt.material_type AS material_type,
        m.hsn_code,
        m.hsn_percentage,
        m.status
    FROM 
        master_materials m
    LEFT JOIN master_materials_make mm ON m.make = mm.id
    LEFT JOIN master_materials_unit mu ON m.unit = mu.id
    LEFT JOIN master_materials_type mt ON m.material_type = mt.id
";
$stmt_materials = $conn->prepare($query_materials);
$stmt_materials->execute();
$materials_result = $stmt_materials->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        table {
            table-layout: fixed;
            width: 100%;
        }

        th, td {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>

<body class="bg-light">

    <?php include("../header.php"); ?>

    <div class="container mt-5">
        <div class="alert alert-primary text-center" role="alert">
            <h1 class="display-5">Materials Management</h1>
            <p class="mb-0">Manage and filter materials based on type, unit, or make.</p>
        </div>
    </div>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Filter Materials</h2>
        <div class="row mb-4">
            <div class="col-md-4">
                <label for="typeFilter" class="form-label">Material Type</label>
                <select id="typeFilter" class="form-select">
                    <option value="">All</option>
                    <?php while ($row = $material_types_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['id']); ?>"><?= htmlspecialchars($row['material_type']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4" hidden>
                <label for="unitFilter" class="form-label">Material Unit</label>
                <select id="unitFilter" class="form-select">
                    <option value="">All</option>
                    <?php while ($row = $material_units_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['id']); ?>"><?= htmlspecialchars($row['unit_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="makeFilter" class="form-label">Material Make</label>
                <select id="makeFilter" class="form-select">
                    <option value="">All</option>
                    <?php while ($row = $material_makes_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['id']); ?>"><?= htmlspecialchars($row['make']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="container">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 15%;">Material Name</th>
                    <th style="width: 20%;">Description</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 10%;">Unit</th>
                    <th style="width: 10%;">Make</th>
                    <th style="width: 10%;">HSN Code</th>
                    <th style="width: 10%;">HSN %</th>
                    <th style="width: 10%;">Quantity</th>
                    <th style="width: 10%;">Action</th>
                </tr>
            </thead>
            <tbody id="material-table-body">
                <?php while ($row = $materials_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= htmlspecialchars($row['description']); ?></td>
                        <td><?= htmlspecialchars($row['material_type']); ?></td>
                        <td><?= htmlspecialchars($row['material_unit']); ?></td>
                        <td><?= htmlspecialchars($row['material_make']); ?></td>
                        <td><?= htmlspecialchars($row['hsn_code']); ?></td>
                        <td><?= htmlspecialchars($row['hsn_percentage']); ?></td>
                        <td>
                            <input 
                                type="number" 
                                class="form-control quantity-input" 
                                min="1" 
                                value="" 
                                data-add-btn-id="add-btn-<?= htmlspecialchars($row['id']); ?>"
                            />
                        </td>
                        <td>
                            <button 
                                id="add-btn-<?= htmlspecialchars($row['id']); ?>"
                                class="btn btn-success btn-sm add-to-cart-btn" 
                                data-name="<?= htmlspecialchars($row['name']); ?>" 
                                data-description="<?= htmlspecialchars($row['description']); ?>" 
                                data-type="<?= htmlspecialchars($row['material_type']); ?>" 
                                data-unit="<?= htmlspecialchars($row['material_unit']); ?>" 
                                data-make="<?= htmlspecialchars($row['material_make']); ?>" 
                                data-hsn="<?= htmlspecialchars($row['hsn_code']); ?>" 
                                data-hsn-percent="<?= htmlspecialchars($row['hsn_percentage']); ?>"
                                data-quantity="1"
                                disabled
                            >
                                Add to Cart
                            </button>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Cart</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 15%;">Material Name</th>
                    <th style="width: 20%;">Description</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 10%;">Unit</th>
                    <th style="width: 10%;">Make</th>
                    <th style="width: 10%;">HSN Code</th>
                    <th style="width: 10%;">HSN %</th>
                    <th style="width: 10%;">Quantity</th>
                </tr>
            </thead>
            <tbody id="cart-table-body">
                <!-- Cart items will be dynamically added here -->
            </tbody>
        </table>
    </div>


    <script>
        document.addEventListener("DOMContentLoaded", function () {
    const typeFilter = document.getElementById("typeFilter");
    const unitFilter = document.getElementById("unitFilter");
    const makeFilter = document.getElementById("makeFilter");
    const materialTableBody = document.getElementById("material-table-body");
    const cartTableBody = document.getElementById("cart-table-body");
    const cart = [];

    // Add event listener for "Add to Cart" buttons
    document.body.addEventListener("click", function (event) {
        if (event.target.classList.contains("add-to-cart-btn")) {
            const material = {
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
    });

    // Enable or disable Add to Cart button based on quantity
    document.body.addEventListener("input", function (event) {
        if (event.target.classList.contains("quantity-input")) {
            const quantity = parseInt(event.target.value);
            const addButtonId = event.target.dataset.addBtnId;
            const addButton = document.getElementById(addButtonId);

            if (quantity >= 1) {
                addButton.disabled = false;
                addButton.setAttribute("data-quantity", quantity);
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
                </tr>
            `;
            cartTableBody.insertAdjacentHTML("beforeend", row);
        });
    }

    // Filter materials dynamically
    const filters = [typeFilter, unitFilter, makeFilter];
    filters.forEach(filter => {
        filter.addEventListener("change", function () {
            const typeId = typeFilter.value;
            const unitId = unitFilter.value;
            const makeId = makeFilter.value;

            fetch("<?= $_SERVER['PHP_SELF']; ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `type_id=${typeId}&unit_id=${unitId}&make_id=${makeId}`
            })
            .then(response => response.json())
            .then(data => {
                // Clear the current table body
                materialTableBody.innerHTML = "";

                // Populate table with filtered materials
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
                                    data-quantity="1"
                                    disabled
                                >
                                    Add to Cart
                                </button>
                            </td>
                        </tr>
                    `;
                    materialTableBody.insertAdjacentHTML("beforeend", row);
                });
            })
            .catch(error => console.error("Error:", error));
        });
    });
});
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
