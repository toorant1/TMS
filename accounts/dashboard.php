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

// Fetch summary data
$totalAccountsQuery = "SELECT COUNT(*) AS total_accounts FROM account WHERE master_user_id = $master_userid";
$totalAccountsResult = $conn->query($totalAccountsQuery);
$totalAccounts = ($totalAccountsResult->fetch_assoc())['total_accounts'] ?? 0;

// Fetch accounts by state
$accountsByStateQuery = "SELECT state, COUNT(*) AS count FROM account WHERE master_user_id = $master_userid GROUP BY state ORDER BY count DESC";
$accountsByStateResult = $conn->query($accountsByStateQuery);
$accountsByState = [];
while ($row = $accountsByStateResult->fetch_assoc()) {
    $accountsByState[] = $row;
}

// Fetch accounts by city
$accountsByCityQuery = "SELECT city, COUNT(*) AS count FROM account WHERE master_user_id = $master_userid GROUP BY city ORDER BY count DESC";
$accountsByCityResult = $conn->query($accountsByCityQuery);
$accountsByCity = [];
while ($row = $accountsByCityResult->fetch_assoc()) {
    $accountsByCity[] = $row;
}

// Fetch existing accounts
// Fetch existing accounts
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT id, account_name, address, email, mobile, token 
          FROM account 
          WHERE master_user_id = $master_userid";

// Add search condition if search query is provided
if (!empty($search_query)) {
    $search_query = $conn->real_escape_string($search_query);
    $query .= " AND (
        LOWER(account_name) LIKE LOWER('%$search_query%') OR
        LOWER(address) LIKE LOWER('%$search_query%') OR
        LOWER(state) LIKE LOWER('%$search_query%') OR
        LOWER(district) LIKE LOWER('%$search_query%') OR
        LOWER(city) LIKE LOWER('%$search_query%') OR
        LOWER(pincode) LIKE LOWER('%$search_query%') OR
        LOWER(country) LIKE LOWER('%$search_query%') OR
        LOWER(account_type) LIKE LOWER('%$search_query%') OR
        LOWER(mobile) LIKE LOWER('%$search_query%') OR
        LOWER(email) LIKE LOWER('%$search_query%') OR
        LOWER(remark) LIKE LOWER('%$search_query%') OR
        LOWER(gst) LIKE LOWER('%$search_query%') OR
        LOWER(pan) LIKE LOWER('%$search_query%') OR
        LOWER(tan) LIKE LOWER('%$search_query%') OR
        LOWER(msme) LIKE LOWER('%$search_query%') OR
        LOWER(bank_name) LIKE LOWER('%$search_query%') OR
        LOWER(branch) LIKE LOWER('%$search_query%') OR
        LOWER(ifsc) LIKE LOWER('%$search_query%') OR
        LOWER(account_no) LIKE LOWER('%$search_query%')
    )";
}

// Add ordering
$query .= " ORDER BY account_name ASC";

$result = $conn->query($query);

// Check if query was successful
if ($result === false) {
    die("Error executing query: " . $conn->error); // Show detailed error if query fails
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
</head>
<body>
<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="container mt-5" style="padding-top: 10px;">
    <?php include('../headers/header_buttons.php'); ?> <!-- Include the header file here -->
    <h1 class="text-center mb-4">Accounts Dashboard</h1>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Accounts</h5>
                    <p class="card-text display-4"><?= $totalAccounts; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Most Popular State</h5>
                    <p class="card-text display-6">
                        <?= $accountsByState[0]['state'] ?? 'N/A'; ?> (<?= $accountsByState[0]['count'] ?? 0; ?>)
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Search Results</h5>
                    <p class="card-text display-6"><?= $result->num_rows; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box and Create New Account Button -->
    <div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex w-75">
        <input type="text" name="search" class="form-control me-2" placeholder="Search" value="<?= htmlspecialchars($search_query); ?>">
        <button type="submit" class="btn btn-primary me-2">Search</button>
        <a href="?" class="btn btn-secondary">Reset</a> <!-- Reset Button -->
    </form>
    <a href="add_account.php" class="btn btn-primary">Create New Account</a>
</div>



    <!-- Table to Display Existing Accounts -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th hidden>ID</th>
                    <th>Account Name</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Mobile</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td hidden><?= htmlspecialchars($row['id']); ?></td>
                            <td><a href="show.php?account_id=<?= urlencode($row['id']); ?>&token=<?= urlencode($row['token']); ?>"><?= htmlspecialchars($row['account_name']); ?></a></td>
                            <td><?= htmlspecialchars($row['address']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td><?= htmlspecialchars($row['mobile']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No Accounts Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Data for the Accounts by State Chart
const accountsByStateData = {
    labels: <?= json_encode(array_column($accountsByState, 'state')); ?>,
    datasets: [{
        label: 'Accounts by State',
        data: <?= json_encode(array_column($accountsByState, 'count')); ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
    }]
};

// Initialize the Accounts by State Chart
const accountsByStateCtx = document.getElementById('accountsByStateChart').getContext('2d');
new Chart(accountsByStateCtx, {
    type: 'bar',
    data: accountsByStateData,
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        }
    }
});

// Data for the Accounts by City Chart
const accountsByCityData = {
    labels: <?= json_encode(array_column($accountsByCity, 'city')); ?>,
    datasets: [{
        label: 'Accounts by City',
        data: <?= json_encode(array_column($accountsByCity, 'count')); ?>,
        backgroundColor: 'rgba(255, 99, 132, 0.6)',
        borderColor: 'rgba(255, 99, 132, 1)',
        borderWidth: 1
    }]
};

// Initialize the Accounts by City Chart
const accountsByCityCtx = document.getElementById('accountsByCityChart').getContext('2d');
new Chart(accountsByCityCtx, {
    type: 'pie',
    data: accountsByCityData,
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
