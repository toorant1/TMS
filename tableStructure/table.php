<?php
require_once '../database/db_connection.php'; // Include your database connection file

// Fetch all tables from the database
$query_tables = "SHOW TABLES";
$result_tables = $conn->query($query_tables);

if ($result_tables === false) {
    die("Error fetching tables: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Tables Structure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center mb-4">Database Tables Structure</h1>

    <?php if ($result_tables->num_rows > 0): ?>
        <?php while ($row = $result_tables->fetch_assoc()): ?>
            <?php 
            $table_name = $row[array_keys($row)[0]]; // Get table name
            $query_desc = "DESC `$table_name`"; // Get the structure of the table
            $result_desc = $conn->query($query_desc);

            if ($result_desc === false) {
                echo "<div class='alert alert-danger'>Error fetching structure for table: " . htmlspecialchars($table_name) . "</div>";
                continue;
            }
            ?>
            <h2 class="mb-4 text-primary">Table: <?= htmlspecialchars($table_name); ?></h2>
            <div class="table-responsive mb-5">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($desc_row = $result_desc->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($table_name); ?>.<?= htmlspecialchars($desc_row['Field']); ?></td>
                            <td><?= htmlspecialchars($desc_row['Type']); ?></td>
                            <td><?= htmlspecialchars($desc_row['Null']); ?></td>
                            <td><?= htmlspecialchars($desc_row['Key']); ?></td>
                            <td><?= htmlspecialchars($desc_row['Default']); ?></td>
                            <td><?= htmlspecialchars($desc_row['Extra']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-warning text-center">No tables found in the database.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
