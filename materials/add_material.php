<?php
require_once '../database/db_connection.php';
session_start();

$message = "";

// Check if the user ID is available in the session
if (!isset($_SESSION['master_userid'])) {
    die("Error: User is not logged in.");
}

$master_userid = $_SESSION['master_userid'];

// Fetch Material Types
$material_types = [];
$type_query = "SELECT id, material_type FROM master_materials_type";
$type_result = $conn->query($type_query);
if ($type_result) {
    while ($row = $type_result->fetch_assoc()) {
        $material_types[] = $row;
    }
}

// Fetch Units
$units = [];
$unit_query = "SELECT id, unit_name FROM master_materials_unit WHERE status = 1"; // Fetch only active units
$unit_result = $conn->query($unit_query);
if ($unit_result) {
    while ($row = $unit_result->fetch_assoc()) {
        $units[] = $row;
    }
}

// Fetch Makes
$makes = [];
$make_query = "SELECT id, make FROM master_materials_make WHERE master_user_id = ? AND status = 1 ORDER BY make";
$make_stmt = $conn->prepare($make_query);
$make_stmt->bind_param("i", $master_userid);
$make_stmt->execute();
$make_result = $make_stmt->get_result();
if ($make_result) {
    while ($row = $make_result->fetch_assoc()) {
        $makes[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'internal_code', 'customer_mat_code', 'name', 'description', 
        'make', 'unit', 'hsn_code', 'hsn_percentage', 'material_type', 'status'
    ];

    // Generate unique tokens and timestamps
    $token = uniqid('material_', true);
    $created_on = $updated_on = date('Y-m-d H:i:s');
    $updated_by = $master_userid;

    // SQL query to insert the material data
    $sql = "INSERT INTO master_materials (master_user_id, token, created_on, updated_on, updated_by, " . implode(',', $fields) . ") 
            VALUES (?, ?, ?, ?, ?, " . implode(',', array_fill(0, count($fields), '?')) . ")";

    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $types = 'sssss' . str_repeat('s', count($fields));
        $data = array_map(fn($field) => $_POST[$field] ?? null, $fields);
        $params = array_merge([$master_userid, $token, $created_on, $updated_on, $updated_by], $data);

        if (!$stmt->bind_param($types, ...$params)) {
            throw new Exception("Error binding parameters: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }

        $message = "Material added successfully!";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .form-container { max-width: 700px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .form-title { color: #333; }
    </style>
</head>
<body>

<?php include('../headers/header.php'); ?>

<div class="form-container">
    <h2 class="form-title text-center mb-4 p-3 bg-primary text-white rounded">Add New Material</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row g-3">
            <!-- Material Details -->
            <hr style="border-width:5px">
            <div class="col-md-4">
                <label for="material_type" class="form-label">Material Type</label>
                <select name="material_type" id="material_type" class="form-select">
                    <?php foreach ($material_types as $type): ?>
                        <option value="<?= htmlspecialchars($type['id']); ?>"><?= htmlspecialchars($type['material_type']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-8">
                <label for="name" class="form-label">Material Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control"></textarea>
            </div>

            <div class="col-md-6">
                <label for="internal_code" class="form-label">Internal Code</label>
                <input type="text" name="internal_code" id="internal_code" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="customer_mat_code" class="form-label">OEM Material Code</label>
                <input type="text" name="customer_mat_code" id="customer_mat_code" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="make" class="form-label">Make</label>
                <select name="make" id="make" class="form-select">
                    <?php foreach ($makes as $make): ?>
                        <option value="<?= htmlspecialchars($make['id']); ?>"><?= htmlspecialchars($make['make']); ?></option>
                    <?php endforeach; ?>
                    <option value="add_new">Add New...</option> <!-- Add New option -->
                </select>
            </div>
            <div class="col-md-3">
                <label for="unit" class="form-label">Unit</label>
                <select name="unit" id="unit" class="form-select">
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= htmlspecialchars($unit['id']); ?>"><?= htmlspecialchars($unit['unit_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="hsn_code" class="form-label">HSN Code</label>
                <input type="text" name="hsn_code" id="hsn_code" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="hsn_percentage" class="form-label">HSN %</label>
                <input type="number" name="hsn_percentage" id="hsn_percentage" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <hr style="border-width:5px">
        </div>
        <div class="d-flex justify-content-between mt-4">
            <a href="materials_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <button type="submit" class="btn btn-success">Save Material</button>
        </div>
    </form>
</div>

<!-- Modal for Adding New Make -->
<div class="modal fade" id="addMakeModal" tabindex="-1" aria-labelledby="addMakeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMakeModalLabel">Add New Make</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addMakeForm">
                    <div class="mb-3">
                        <label for="newMake" class="form-label">Make Name</label>
                        <input type="text" name="newMake" id="newMake" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="remark" class="form-label">Remark</label>
                        <textarea name="remark" id="remark" class="form-control"></textarea>
                    </div>
                    <input type="hidden" name="master_user_id" value="<?= $master_userid; ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="saveMake" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const makeDropdown = document.getElementById("make");
    const saveMakeButton = document.getElementById("saveMake");
    const addMakeForm = document.getElementById("addMakeForm");

    makeDropdown.addEventListener("change", function () {
        if (makeDropdown.value === "add_new") {
            const modal = new bootstrap.Modal(document.getElementById("addMakeModal"));
            modal.show();
        }
    });

    saveMakeButton.addEventListener("click", function () {
        const formData = new FormData(addMakeForm);

        // Send AJAX request to add_make.php
        fetch("add_make.php", {
            method: "POST",
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const option = document.createElement("option");
                option.value = data.id;
                option.textContent = data.make;
                makeDropdown.appendChild(option);
                makeDropdown.value = data.id;
                const modal = bootstrap.Modal.getInstance(document.getElementById("addMakeModal"));
                modal.hide();
                addMakeForm.reset();
            } else {
                alert("Error adding make: " + data.message);
            }
        })
        .catch(error => {
            alert("An error occurred. Please try again.");
        });
    });
});
</script>

</body>
</html>

<?php
$conn->close();
?>
