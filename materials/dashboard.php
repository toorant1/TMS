<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];

// Fetch existing materials with their material types, makes, and units
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "
    SELECT 
        mm.id, mm.name, mm.description, mm.hsn_code, mm.hsn_percentage, 
        mt.material_type, mk.make, mu.unit_name, mm.token, mm.status
    FROM 
        master_materials mm
    LEFT JOIN 
        master_materials_type mt ON mm.material_type = mt.id
    LEFT JOIN 
        master_materials_make mk ON mm.make = mk.id
    LEFT JOIN 
        master_materials_unit mu ON mm.unit = mu.id
    WHERE 
        mm.master_user_id = ?";

// Add search condition if search query is provided
if (!empty($search_query)) {
    $query .= " AND (
        mm.name LIKE ? OR
        mm.description LIKE ? OR
        mm.hsn_code LIKE ? OR
        mt.material_type LIKE ? OR
        mk.make LIKE ? OR
        mu.unit_name LIKE ?
    )";
}

$stmt = $conn->prepare($query);
if (!empty($search_query)) {
    $search_query = '%' . $search_query . '%';
    $stmt->bind_param("issssss", $master_userid, $search_query, $search_query, $search_query, $search_query, $search_query, $search_query);
} else {
    $stmt->bind_param("i", $master_userid);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("Error executing query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->
<div class="container mt-5">
    <h1 class="text-center mb-4">Materials Dashboard</h1>

    <!-- Search Box and Create New Material Button in the Same Row -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Search Box -->
        <form method="GET" class="d-flex w-75">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by any field" value="<?= htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <!-- Create New Material Button -->
        <a href="add_material.php" class="btn btn-primary">Create New Material</a>
    </div>

    <!-- Table to Display Existing Materials -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th hidden>ID</th>
                    <th>Material Name</th>
                    <th>Description</th>
                    <th>HSN/SAC Code</th>
                    <th>HSN %</th>
                    <th>Material Type</th>
                    <th>Make</th>
                    <th>Unit</th>
                    <th>Status</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td hidden><?= htmlspecialchars($row['id']); ?></td>
                            <!-- Create a link for the material name that passes material_id and token -->
                            <td><a href="show.php?material_id=<?= urlencode($row['id']); ?>&token=<?= urlencode($row['token']); ?>"><?= htmlspecialchars($row['name']); ?></a></td>
                            <td><?= htmlspecialchars($row['description']); ?></td>
                            <td><?= htmlspecialchars($row['hsn_code']); ?></td>
                            <td><?= htmlspecialchars($row['hsn_percentage']); ?>%</td>
                            <td><?= htmlspecialchars($row['material_type']); ?></td>
                            <td><?= htmlspecialchars($row['make']); ?></td>
                            <td><?= htmlspecialchars($row['unit_name']); ?></td>
                            <td><?= $row['status'] == 1 ? 'Active' : 'Inactive'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">No Materials Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Display Total Count of Materials -->
    <div class="text-start mt-3">
        <strong>Total Records: <?= $result->num_rows; ?></strong>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
