<?php
// Include database connection
require_once '../database/db_connection.php'; // Update with your DB connection file

session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable for master_userid
$master_userid = $_SESSION['master_userid'];

// Ensure the company_id and token are provided in the URL
if (isset($_GET['company_id']) && isset($_GET['token'])) {
    $company_id = $_GET['company_id'];
    $token = $_GET['token'];

    // Fetch company details from the database
    $query = "SELECT id, company_name, address, state, district, city, pincode, country, phone, mobile, email, website, currency, pan, gst, tds, msme, other1, other2, other3, bank, branch, ifsc, account_no, create_on, update_on, update_by, token 
              FROM master_company WHERE id = ? AND token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $company_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the company exists
    if ($result->num_rows === 0) {
        die("Company not found or invalid token.");
    }

    $company = $result->fetch_assoc();
} else {
    die("Company ID or token not provided.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include('../headers/header.php'); ?> <!-- Include the header file here -->

    <div class="container mt-5">
        <h1 class="text-center mb-4" style="background: linear-gradient(360deg, #1f4037, #99f2c8); color: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); font-size: 2.5rem; font-weight: bold;">
            Company Details
        </h1>


        <!-- Table inside a Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Company Master Data : <?= htmlspecialchars($company['company_name']); ?>
                    <a href="edit_company.php?company_id=<?= urlencode($company['id']); ?>&token=<?= urlencode($company['token']); ?>" class="btn btn-warning">Edit Company Details</a>
                    <!-- Generate PDF Button -->
                    <a href="company_pdf.php?company_id=<?= urlencode($company['id']); ?>&token=<?= urlencode($company['token']); ?>"
                        class="btn btn-danger" target="_blank">Generate PDF</a>
                    <a href="dashboard.php" class="btn btn-primary">Back</a>
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Company Name</strong></td>
                            <td><?= htmlspecialchars($company['company_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Address</strong></td>
                            <td><?= htmlspecialchars($company['address']); ?></td>
                        </tr>

                        <tr>
                            <td><strong>City</strong></td>
                            <td><?= htmlspecialchars($company['city']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>District</strong></td>
                            <td><?= htmlspecialchars($company['district']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>State</strong></td>
                            <td><?= htmlspecialchars($company['state']); ?></td>
                        </tr>

                        <tr>
                            <td><strong>Pincode</strong></td>
                            <td><?= htmlspecialchars($company['pincode']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Country</strong></td>
                            <td><?= htmlspecialchars($company['country']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email</strong></td>
                            <td><?= htmlspecialchars($company['email']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Phone</strong></td>
                            <td><?= htmlspecialchars($company['phone']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Mobile</strong></td>
                            <td><?= htmlspecialchars($company['mobile']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Website</strong></td>
                            <td><?= htmlspecialchars($company['website']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Currency</strong></td>
                            <td><?= htmlspecialchars($company['currency']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>PAN</strong></td>
                            <td><?= htmlspecialchars($company['pan']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>GST</strong></td>
                            <td><?= htmlspecialchars($company['gst']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>TDS</strong></td>
                            <td><?= htmlspecialchars($company['tds']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>MSME</strong></td>
                            <td><?= htmlspecialchars($company['msme']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Other 1</strong></td>
                            <td><?= htmlspecialchars($company['other1']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Other 2</strong></td>
                            <td><?= htmlspecialchars($company['other2']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Other 3</strong></td>
                            <td><?= htmlspecialchars($company['other3']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Bank</strong></td>
                            <td><?= htmlspecialchars($company['bank']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Branch</strong></td>
                            <td><?= htmlspecialchars($company['branch']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>IFSC</strong></td>
                            <td><?= htmlspecialchars($company['ifsc']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Account No</strong></td>
                            <td><?= htmlspecialchars($company['account_no']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Created On</strong></td>
                            <td><?= htmlspecialchars($company['create_on']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Last Updated On</strong></td>
                            <td><?= htmlspecialchars($company['update_on']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Updated By</strong></td>
                            <td><?= htmlspecialchars($company['update_by']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Button to Edit Company -->

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
// Close database connection
$conn->close();
?>