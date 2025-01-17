<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

$master_userid = $_SESSION['master_userid'];

if (isset($_GET['material_id']) && isset($_GET['token'])) {
    $material_id = $_GET['material_id'];
    $token = $_GET['token'];

    // Fetch material details from the database
    $query = "
        SELECT 
            id, name, description, internal_code, customer_mat_code, make, unit, hsn_code, hsn_percentage, 
            material_type, status, token 
        FROM 
            master_materials 
        WHERE 
            id = ? AND token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $material_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Material not found or invalid token.");
    }

    $material = $result->fetch_assoc();

    // Fetch dropdown data
    $material_types = [];
    $type_query = "SELECT id, material_type FROM master_materials_type WHERE master_user_id = 0 OR master_user_id = ?";
    $type_stmt = $conn->prepare($type_query);
    $type_stmt->bind_param("i", $master_userid);
    $type_stmt->execute();
    $type_result = $type_stmt->get_result();
    while ($row = $type_result->fetch_assoc()) {
        $material_types[] = $row;
    }

    $units = [];
    $unit_query = "SELECT id, unit_name FROM master_materials_unit WHERE status = 1";
    $unit_result = $conn->query($unit_query);
    while ($row = $unit_result->fetch_assoc()) {
        $units[] = $row;
    }

    $makes = [];
    $make_query = "SELECT id, make FROM master_materials_make WHERE master_user_id = ? AND status = 1";
    $make_stmt = $conn->prepare($make_query);
    $make_stmt->bind_param("i", $master_userid);
    $make_stmt->execute();
    $make_result = $make_stmt->get_result();
    while ($row = $make_result->fetch_assoc()) {
        $makes[] = $row;
    }

    // Handle form submission for updating material
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $description = $_POST['description'] ?? null;
        $internal_code = $_POST['internal_code'] ?? null;
        $customer_mat_code = $_POST['customer_mat_code'] ?? null;
        $make = $_POST['make'] ?? null;
        $unit = $_POST['unit'] ?? null;
        $hsn_code = $_POST['hsn_code'] ?? null;
        $hsn_percentage = $_POST['hsn_percentage'] ?? null;
        $material_type = $_POST['material_type'];
        $status = $_POST['status'];
        $updated_on = date('Y-m-d H:i:s');

        $update_query = "
            UPDATE 
                master_materials 
            SET 
                name = ?, description = ?, internal_code = ?, customer_mat_code = ?, make = ?, unit = ?, 
                hsn_code = ?, hsn_percentage = ?, material_type = ?, status = ?, updated_on = ? 
            WHERE 
                id = ? AND token = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param(
            "ssssiiisiiisi",
            $name, $description, $internal_code, $customer_mat_code, $make, $unit,
            $hsn_code, $hsn_percentage, $material_type, $status, $updated_on, $material_id, $token
        );

        if ($update_stmt->execute()) {
            header("Location: dashboard.php"); // Redirect to the dashboard after successful update
            exit;
        } else {
            $error_message = "Error updating material: " . $conn->error;
        }
    }
} else {
    die("Material ID or token not provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('../headers/header.php'); ?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Edit Material</h1>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row g-3">
            <div class="col-md-8">
                <label for="name" class="form-label">Material Name</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($material['name']); ?>" required>
            </div>
            <div class="col-md-4">
                <label for="material_type" class="form-label">Material Type</label>
                <select name="material_type" id="material_type" class="form-select">
                    <?php foreach ($material_types as $type): ?>
                        <option value="<?= $type['id']; ?>" <?= $type['id'] == $material['material_type'] ? 'selected' : ''; ?>><?= htmlspecialchars($type['material_type']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control"><?= htmlspecialchars($material['description']); ?></textarea>
            </div>
            <div class="col-md-4">
                <label for="internal_code" class="form-label">Internal Code</label>
                <input type="text" name="internal_code" id="internal_code" class="form-control" value="<?= htmlspecialchars($material['internal_code']); ?>">
            </div>
            <div class="col-md-4">
                <label for="customer_mat_code" class="form-label">OEM Material Code</label>
                <input type="text" name="customer_mat_code" id="customer_mat_code" class="form-control" value="<?= htmlspecialchars($material['customer_mat_code']); ?>">
            </div>
            <div class="col-md-4">
                <label for="make" class="form-label">Make</label>
                <div class="d-flex align-items-center">
                    <select name="make" id="make" class="form-select me-2">
                        <?php foreach ($makes as $make): ?>
                            <option value="<?= $make['id']; ?>" <?= $make['id'] == $material['make'] ? 'selected' : ''; ?>><?= htmlspecialchars($make['make']); ?></option>
                        <?php endforeach; ?>
                        <option value="add_new">Add New...</option> <!-- Add New option -->
                    </select>
                    <button hidden type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMakeModal">
                        Add New
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <label for="unit" class="form-label">Unit</label>
                <select name="unit" id="unit" class="form-select">
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= $unit['id']; ?>" <?= $unit['id'] == $material['unit'] ? 'selected' : ''; ?>><?= htmlspecialchars($unit['unit_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="hsn_code" class="form-label">HSN/SAC Code</label>
                <input type="text" name="hsn_code" id="hsn_code" class="form-control" value="<?= htmlspecialchars($material['hsn_code']); ?>">
            </div>
            <div class="col-md-4">
                <label for="hsn_percentage" class="form-label">HSN Percentage</label>
                <input type="number" name="hsn_percentage" id="hsn_percentage" class="form-control" value="<?= htmlspecialchars($material['hsn_percentage']); ?>">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="1" <?= $material['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?= $material['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="mt-4 text-center">
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
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
