<?php
session_start();
require_once '../../database/db_connection.php'; // Ensure your database connection file is correct

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Fetch data from the database
$query = "SELECT id, report_name, status FROM auto_generated_reports WHERE master_userid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $master_userid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Ticket Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>

    <?php include('../../headers/header.php'); ?>

    <div class="container mt-5">
        <h1 class="text-center mb-4">Create Auto Generated Report</h1>

        <!-- Date Filter Section -->
        <div class="row mb-4">
            <div class="col-md-4">
                <input type="date" class="form-control" id="reportDate">
            </div>
            <!-- Button to Navigate to Report Creation Page -->
<div class="text-end mb-3">
    <a href="report_create.php" class="btn btn-success">+ Create New Report</a>
</div>

            <div class="col-md-4">
                <button class="btn btn-primary" onclick="filterReport()">Filter</button>
                <button class="btn btn-success" onclick="exportReport()">Export</button>
            </div>
        </div>

        <!-- Ticket Report Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Report Name</th>
                        <th>Status</th>
                        <th>Report Schedule</th>
                        <th>Assigned To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Convert status tinyint to text representation
                            $statusText = ($row['status'] == 1) ? "Resolved" : "Pending";
                            $statusBadge = ($row['status'] == 1) ? "bg-success" : "bg-warning";
                            
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['report_name']}</td>
                                    <td><span class='badge $statusBadge'>{$statusText}</span></td>
                                    <td>{$row['report_schedule']}</td>
                                    <td>{$row['assigned_to']}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center'>No reports found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterReport() {
            let selectedDate = document.getElementById("reportDate").value;
            alert("Filtering report for date: " + selectedDate);
            // Implement AJAX call to fetch filtered data in the future
        }

        function exportReport() {
            alert("Exporting report...");
            // Implement export functionality in the future
        }
    </script>

</body>

</html>
