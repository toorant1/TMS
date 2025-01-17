<?php
// Include database connection
require_once '../database/db_connection.php'; // Update with your DB connection file

session_start();
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];

// Fetch existing companies from `master_company` table
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Start building the query
$query = "SELECT id, company_name, address, email, phone, mobile, token FROM master_company WHERE master_userid = ?";
$params = [$master_userid];
$types = 'i'; // 'i' for integer parameter (master_userid)

// Add search condition if a search query is provided
if (!empty($search_query)) {
    $query .= " AND company_name LIKE ?";
    $params[] = '%' . $search_query . '%';
    $types .= 's'; // 's' for string parameter (search query)
}

$query .= " ORDER BY company_name ASC";

// Prepare the query
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// Bind parameters
$stmt->bind_param($types, ...$params);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Check if query was successful
if ($result === false) {
    die("Error executing query: " . $stmt->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->
<div class="container mt-5">
    <div class="dashboard-header text-center mb-4">
        <h1 class="text-white fw-bold">
            <i class="bi bi-building"></i> Company Dashboard
        </h1>
        <p class="text-light">Empower Your Business, Achieve Success.</p>
    </div>


<style>
    .dashboard-header {
        background: linear-gradient(360deg, green, #99f2c8); 
        padding: 15px;
        border-radius: 15px;
        color: white;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .dashboard-header h1 {
        font-size: 2.5rem;
        margin-bottom: 5px;
    }

    .dashboard-header p {
        font-size: 1.1rem;
    }

    .table {
        max-width: 100%;
        margin: auto;
    }

    .table-responsive {
        margin: auto;
    }
</style>

    <!-- Search Box and Create New Company Button in the Same Row -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <!-- Search Box -->
    <form method="GET" class="d-flex w-75">
        <input type="text" name="search" class="form-control me-2" placeholder="Search by Company Name" value="<?= htmlspecialchars($search_query); ?>">
        <button type="submit" class="btn btn-primary me-2">Search</button>
        <button type="button" class="btn btn-secondary" onclick="window.location.href = window.location.pathname;">Reset</button>


    </form>

    <!-- Create New Company Button -->
    <a href="add_company.php" class="btn btn-primary">Create New Company</a>
</div>


    <!-- Table to Display Existing Companies -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th hidden>ID</th>
                    <th>Company Name</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Mobile</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td hidden><?= htmlspecialchars($row['id']); ?></td>
                            <!-- Create a link for the company name that passes company_id and token -->
                            <td>
                                <a href="show.php?company_id=<?= urlencode($row['id']); ?>&token=<?= urlencode($row['token']); ?>" class="text-decoration-none text-danger"><strong>
                                    <?= htmlspecialchars($row['company_name']); ?>
                                </a>
                            </td>

                            <td><?= htmlspecialchars($row['address']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td><?= htmlspecialchars($row['phone']); ?></td>
                            <td><?= htmlspecialchars($row['mobile']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No Companies Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Display Total Count of Companies -->
    <div class="text-start mt-12">
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
