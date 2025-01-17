<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable for master_userid
$master_userid = $_SESSION['master_userid'];

// Ensure the material_id and token are provided in the URL
if (isset($_GET['material_id']) && isset($_GET['token'])) {
    $material_id = $_GET['material_id'];
    $token = $_GET['token'];

    // Fetch material details from the database
    $query = "
        SELECT 
            mm.id, mm.name, mm.description, mm.hsn_code, mm.hsn_percentage, 
            mm.internal_code, mm.customer_mat_code, mm.status, 
            mt.material_type, mk.make, mu.unit_name, 
            mm.created_on, mm.updated_on
        FROM 
            master_materials mm
        LEFT JOIN 
            master_materials_type mt ON mm.material_type = mt.id
        LEFT JOIN 
            master_materials_make mk ON mm.make = mk.id
        LEFT JOIN 
            master_materials_unit mu ON mm.unit = mu.id
        WHERE 
            mm.id = ? AND mm.token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $material_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the material exists
    if ($result->num_rows === 0) {
        die("Material not found or invalid token.");
    }

    $material = $result->fetch_assoc();
} else {
    die("Material ID or token not provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { margin-bottom: 20px; }
        .table-responsive { margin-top: 20px; }
    </style>
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="container mt-4">
    <h1 class="text-center mb-4">Material Details</h1>

    <!-- Material Details Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title d-flex justify-content-between align-items-center">
                <span>Material: <?= htmlspecialchars($material['name']); ?></span>
                <div>
                    <a href="edit_material.php?material_id=<?= urlencode($material['id']); ?>&token=<?= urlencode($token); ?>" class="btn btn-warning btn-sm me-2">Edit Material</a>
                    <a href="dashboard.php" class="btn btn-primary btn-sm">Back</a>
                </div>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Attribute</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>Material Name</strong></td><td><?= htmlspecialchars($material['name']); ?></td></tr>
                        <tr><td><strong>Description</strong></td><td><?= htmlspecialchars($material['description']); ?></td></tr>
                        <tr><td><strong>Internal Code</strong></td><td><?= htmlspecialchars($material['internal_code']); ?></td></tr>
                        <tr><td><strong>OEM Material Code</strong></td><td><?= htmlspecialchars($material['customer_mat_code']); ?></td></tr>
                        <tr><td><strong>HSN/SAC Code</strong></td><td><?= htmlspecialchars($material['hsn_code']); ?></td></tr>
                        <tr><td><strong>HSN Percentage</strong></td><td><?= htmlspecialchars($material['hsn_percentage']); ?>%</td></tr>
                        <tr><td><strong>Material Type</strong></td><td><?= htmlspecialchars($material['material_type']); ?></td></tr>
                        <tr><td><strong>Make</strong></td><td><?= htmlspecialchars($material['make']); ?></td></tr>
                        <tr><td><strong>Unit</strong></td><td><?= htmlspecialchars($material['unit_name']); ?></td></tr>
                        <tr><td><strong>Status</strong></td><td><?= $material['status'] == 1 ? 'Active' : 'Inactive'; ?></td></tr>
                        <tr><td><strong>Created On</strong></td><td><?= htmlspecialchars($material['created_on']); ?></td></tr>
                        <tr><td><strong>Updated On</strong></td><td><?= htmlspecialchars($material['updated_on']); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
