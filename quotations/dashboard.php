<?php
require_once '../database/db_connection.php'; // Update with your DB connection file

session_start();
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];

// Default Date Filter
$toDate = date('Y-m-d'); // Today's date
$fromDate = date('Y-m-01', strtotime('-3 months')); // First day of the month three months ago

// Override default dates with user input (if provided)
if (isset($_GET['from_date']) && isset($_GET['to_date'])) {
    $fromDate = $_GET['from_date'];
    $toDate = $_GET['to_date'];
}

// Search filter
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Selected company filter
$selectedCompany = isset($_GET['company']) ? $_GET['company'] : '';

// Selected status filter
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Prepare wildcard search for SQL
$searchWildcard = '%' . $search . '%';

// Append filters to queries
$companyFilter = '';
if (!empty($selectedCompany)) {
    $companyFilter = " AND mc.company_name = ? ";
}

$statusFilter = '';
if (!empty($selectedStatus)) {
    $statusFilter = " AND mqs.status_name = ? ";
}

// Fetch total quotations
$totalQuotationsQuery = "
    SELECT COUNT(*) AS total_quotations 
    FROM master_quotations mq
    JOIN account a ON mq.company_id = a.id
    JOIN master_company mc ON mq.company_id = mc.id
    JOIN master_quotations_status mqs ON mq.quotation_status_id = mqs.quotation_status_id
    WHERE mq.master_user_id = ? AND mq.quotation_date BETWEEN ? AND ? 
    AND (LOWER(a.account_name) LIKE LOWER(?)) 
    $companyFilter
    $statusFilter";
$stmt = $conn->prepare($totalQuotationsQuery);
if (!empty($selectedCompany) && !empty($selectedStatus)) {
    $stmt->bind_param("isssss", $master_userid, $fromDate, $toDate, $searchWildcard, $selectedCompany, $selectedStatus);
} elseif (!empty($selectedCompany)) {
    $stmt->bind_param("issss", $master_userid, $fromDate, $toDate, $searchWildcard, $selectedCompany);
} elseif (!empty($selectedStatus)) {
    $stmt->bind_param("issss", $master_userid, $fromDate, $toDate, $searchWildcard, $selectedStatus);
} else {
    $stmt->bind_param("isss", $master_userid, $fromDate, $toDate, $searchWildcard);
}
$stmt->execute();
$totalQuotationsResult = $stmt->get_result();
$totalQuotations = $totalQuotationsResult->fetch_assoc()['total_quotations'] ?? 0;

// Fetch quotations
$quotationsQuery = "
    SELECT 
    mq.quotation_id, 
    mq.quotation_number, 
    mq.quotation_date, 
    mc.company_name, 
    a.account_name,  
    mq.quotation_status_id,
    mqs.status_name AS quotation_status, 
    mq.quotation_valid_upto_date,
    COALESCE(SUM(mqm.quantity * mqm.unit_price), 0) AS grand_total,
    mq.quotation_token
FROM master_quotations mq
JOIN master_company mc ON mq.company_id = mc.id
JOIN account a ON a.id = mq.company_id  
JOIN master_quotations_status mqs ON mq.quotation_status_id = mqs.quotation_status_id
LEFT JOIN master_quotations_materials mqm ON mq.quotation_id = mqm.master_quotation_id
WHERE mq.master_user_id = ? 
  AND mq.quotation_date BETWEEN ? AND ?
  AND (LOWER(a.account_name) LIKE LOWER(?))
GROUP BY mq.quotation_id
ORDER BY mq.quotation_date DESC";
$stmt = $conn->prepare($quotationsQuery);
if (!empty($selectedCompany) && !empty($selectedStatus)) {
    $stmt->bind_param("isssss", $master_userid, $fromDate, $toDate, $searchWildcard, $selectedCompany, $selectedStatus);
} elseif (!empty($selectedCompany)) {
    $stmt->bind_param("issss", $master_userid, $fromDate, $toDate, $searchWildcard, $selectedCompany);
} elseif (!empty($selectedStatus)) {
    $stmt->bind_param("issss", $master_userid, $fromDate, $toDate, $searchWildcard, $selectedStatus);
} else {
    $stmt->bind_param("isss", $master_userid, $fromDate, $toDate, $searchWildcard);
}
$stmt->execute();
$quotationsResult = $stmt->get_result();

// Calculate the overall grand total
$overallGrandTotal = 0;
$quotations = [];
while ($row = $quotationsResult->fetch_assoc()) {
    $quotations[] = $row;
    $overallGrandTotal += $row['grand_total'];
}

// Fetch company-wise grand total
$companyWiseGrandTotalQuery = "
    SELECT 
        mc.company_name, 
        COUNT(DISTINCT mq.quotation_id) AS quotation_count,
        COALESCE(SUM(mqm.quantity * mqm.unit_price), 0) AS company_grand_total
    FROM master_quotations mq
    JOIN master_company mc ON mq.company_id = mc.id
    LEFT JOIN master_quotations_materials mqm ON mq.quotation_id = mqm.master_quotation_id
    JOIN account a ON mc.id = a.id
    WHERE mq.master_user_id = ? AND mq.quotation_date BETWEEN ? AND ? 
    AND (LOWER(a.account_name) LIKE LOWER(?))
    GROUP BY mc.company_name
    ORDER BY company_grand_total DESC";
$stmt = $conn->prepare($companyWiseGrandTotalQuery);
$stmt->bind_param("isss", $master_userid, $fromDate, $toDate, $searchWildcard);
$stmt->execute();
$companyWiseResult = $stmt->get_result();
$companyWiseTotals = [];
while ($row = $companyWiseResult->fetch_assoc()) {
    $companyWiseTotals[] = $row;
}

// Fetch status-wise grand total
$statusWiseGrandTotalQuery = "
    SELECT 
        mqs.status_name AS quotation_status, 
        COUNT(DISTINCT mq.quotation_id) AS quotation_count,
        COALESCE(SUM(mqm.quantity * mqm.unit_price), 0) AS status_grand_total
    FROM master_quotations mq
    JOIN master_quotations_status mqs ON mq.quotation_status_id = mqs.quotation_status_id
    LEFT JOIN master_quotations_materials mqm ON mq.quotation_id = mqm.master_quotation_id
    JOIN account a ON mq.company_id = a.id
    WHERE mq.master_user_id = ? AND mq.quotation_date BETWEEN ? AND ? 
    AND (LOWER(a.account_name) LIKE LOWER(?))
    GROUP BY mqs.status_name
    ORDER BY status_grand_total DESC";
$stmt = $conn->prepare($statusWiseGrandTotalQuery);
$stmt->bind_param("isss", $master_userid, $fromDate, $toDate, $searchWildcard);
$stmt->execute();
$statusWiseResult = $stmt->get_result();
$statusWiseTotals = [];
while ($row = $statusWiseResult->fetch_assoc()) {
    $statusWiseTotals[] = $row;
}

// Calculate totals
$totalCompanyGrandTotal = array_sum(array_column($companyWiseTotals, 'company_grand_total'));
$totalStatusGrandTotal = array_sum(array_column($statusWiseTotals, 'status_grand_total'));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotations Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-title {
            font-size: 1rem;
        }
        .table th, .table td {
            font-size: 0.9rem; /* Adjust font size for smaller screens */
        }
        @media (max-width: 768px) {
            .form-label {
                font-size: 0.8rem; /* Reduce label size */
            }
            .btn {
                font-size: 0.8rem; /* Reduce button font size */
            }
        }
    </style>
</head>
<body>
<?php include('../headers/header.php'); ?>

<div class="container mt-3">
<h1 class="text-center my-4 pt-5">Quotations Dashboard</h1>


<?php if (!empty($selectedCompany)): ?>
    <div class="alert alert-success text-center">
        Showing data for company: <strong><?= htmlspecialchars($selectedCompany); ?></strong>
    </div>
<?php endif; ?>

<?php if (!empty($selectedStatus)): ?>
    <div class="alert alert-success text-center">
        Showing data for status: <strong><?= htmlspecialchars($selectedStatus); ?></strong>
    </div>
<?php endif; ?>


 <!-- Create New Quotation Button -->
 <div class="d-flex justify-content-end mb-3">
    <button id="createQuotationButton" class="btn btn-primary">Create New Quotation</button>

    </div>
    
    <script>
        $(document).ready(function () {
    $('#createQuotationButton').on('click', function () {
        // Retrieve internal_id and token dynamically
        const internalID = "<?= htmlspecialchars($internal_id); ?>";
        const token = "<?= htmlspecialchars($token); ?>";

        // Make an Ajax request to fetch the form
        $.ajax({
            url: 'quotation_create.php',
            type: 'GET',
            data: {
                internal_id: internalID,
                token: token
            },
            success: function (response) {
                // Load the response into the dynamic form section
                $('#dynamicFormSection').html(response).slideDown();
            },
            error: function (xhr, status, error) {
                console.error('Error loading form:', error);
                alert('Failed to load the form. Please try again.');
            }
        });
    });
});

        </script>

    <?php if (!empty($selectedCompany)): ?>
        <div class="alert alert-success text-center">
            Showing data for company: <strong><?= htmlspecialchars($selectedCompany); ?></strong>
        </div>
    <?php endif; ?>

    <?php if (!empty($selectedStatus)): ?>
        <div class="alert alert-success text-center">
            Showing data for status: <strong><?= htmlspecialchars($selectedStatus); ?></strong>
        </div>
    <?php endif; ?>

    <!-- Filters -->
<form method="GET" class="row g-2 mb-3 d-flex align-items-end">
    <div class="col-auto">
        <label for="from_date" class="form-label">From Date</label>
        <input type="date" class="form-control form-control-sm" id="from_date" name="from_date" value="<?= htmlspecialchars($fromDate); ?>">
    </div>
    <div class="col-auto">
        <label for="to_date" class="form-label">To Date</label>
        <input type="date" class="form-control form-control-sm" id="to_date" name="to_date" value="<?= htmlspecialchars($toDate); ?>">
    </div>
    <div class="col flex-grow-1">
        <label for="search" class="form-label">Search</label>
        <input type="text" class="form-control form-control-sm" id="search" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search by account name">
    </div>
    <div class="col-auto d-flex">
        <button type="submit" class="btn btn-outline-primary btn-sm me-2">Filter</button>
        <a href="?" class="btn btn-outline-secondary btn-sm">Reset</a>
    </div>
    
</form>

    <!-- Company-Wise and Status-Wise Grand Totals -->
    <div class="row mb-3">
        <!-- Company-Wise Grand Total -->
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Company-Wise Grand Total</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Company</th>
                                    <th class="text-center">Quotations</th>
                                    <th class="text-end">Grand Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $totalQuotationsByCompany = 0; ?>
                                <?php foreach ($companyWiseTotals as $company): ?>
                                    <?php $totalQuotationsByCompany += $company['quotation_count']; ?>
                                    <tr>
                                        <td>
                                            <a href="?from_date=<?= htmlspecialchars($fromDate); ?>&to_date=<?= htmlspecialchars($toDate); ?>&search=<?= htmlspecialchars($search); ?>&company=<?= urlencode($company['company_name']); ?>">
                                                <?= htmlspecialchars($company['company_name']); ?>
                                            </a>
                                        </td>
                                        <td class="text-center"><?= $company['quotation_count']; ?></td>
                                        <td class="text-end">₹ <?= number_format($company['company_grand_total'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <td class="text-end"><strong>Total:</strong></td>
                                    <td class="text-center"><strong><?= $totalQuotationsByCompany; ?></strong></td>
                                    <td class="text-end"><strong>₹ <?= number_format($totalCompanyGrandTotal, 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status-Wise Grand Total -->
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Status-Wise Grand Total</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Status</th>
                                    <th class="text-center">Quotations</th>
                                    <th class="text-end">Grand Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $totalQuotationsByStatus = 0; ?>
                                <?php foreach ($statusWiseTotals as $status): ?>
                                    <?php $totalQuotationsByStatus += $status['quotation_count']; ?>
                                    <tr>
                                        <td>
                                            <a href="?from_date=<?= htmlspecialchars($fromDate); ?>&to_date=<?= htmlspecialchars($toDate); ?>&search=<?= htmlspecialchars($search); ?>&status=<?= urlencode($status['quotation_status']); ?>">
                                                <?= htmlspecialchars($status['quotation_status']); ?>
                                            </a>
                                        </td>
                                        <td class="text-center"><?= $status['quotation_count']; ?></td>
                                        <td class="text-end">₹ <?= number_format($status['status_grand_total'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <td class="text-end"><strong>Total:</strong></td>
                                    <td class="text-center"><strong><?= $totalQuotationsByStatus; ?></strong></td>
                                    <td class="text-end"><strong>₹ <?= number_format($totalStatusGrandTotal, 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quotations Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Quotation Number</th>
                    <th>Quotation Date</th>
                    <th>Company Name</th>
                    <th>Account Name</th>
                    <th>Status</th>
                    <th>Valid Upto</th>
                    <th class="text-end">Basic Value Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($quotations)): ?>
                    <?php foreach ($quotations as $row): ?>
                        <tr>
                            <td>
                                <a href="quotation_view.php?quotation_id=<?= $row['quotation_id']; ?>&token=<?= htmlspecialchars($row['quotation_token']); ?>">
                                    <?= htmlspecialchars($row['quotation_number']); ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($row['quotation_date']); ?></td>
                            <td><?= htmlspecialchars($row['company_name']); ?></td>
                            <td><?= htmlspecialchars($row['account_name']); ?></td>
                            <td><?= htmlspecialchars($row['quotation_status']); ?></td>
                            <td><?= htmlspecialchars($row['quotation_valid_upto_date']); ?></td>
                            <td class="text-end">₹ <?= number_format($row['grand_total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No Quotations Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



<?php
// Close database connection
$conn->close();
?>
