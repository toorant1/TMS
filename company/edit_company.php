<?php
require_once '../database/db_connection.php';
session_start(); // Ensure session is started

$message = "";

// Check if the user ID is available in the session
if (!isset($_SESSION['master_userid'])) {
    die("Error: User is not logged in.");
}

$master_userid = $_SESSION['master_userid']; // Retrieve the user ID from the session
$company_id = $_GET['company_id']; // Get company ID from the URL
$company_token = $_GET['token']; // Get company token from the URL

// Fetch company data from the database based on company_id or token
$sql = "SELECT * FROM master_company WHERE id = ? AND token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $company_id, $company_token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $company = $result->fetch_assoc();
} else {
    die("Error: Company not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // List of fields to update
    $fields = [
        'company_name', 'address', 'state', 'city', 'pincode', 'country', 
        'phone', 'mobile', 'email', 'website', 'currency', 'pan', 
        'gst', 'tds', 'msme', 'other1', 'other2', 'other3', 
        'bank', 'branch', 'ifsc', 'account_no', 'district' // Include district field here
    ];

    // Prepare SQL placeholders for the update
    $setClauses = [];
    foreach ($fields as $field) {
        $setClauses[] = "$field = ?";
    }
    $sql = "UPDATE master_company SET " . implode(', ', $setClauses) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters dynamically
        $types = str_repeat('s', count($fields)) . 'i'; // Adding 'i' for the company_id
        $data = array_map(fn($field) => $_POST[$field] ?? null, $fields);
        $params = array_merge($data, [$company_id]); // Combine form data and company_id
        $stmt->bind_param($types, ...$params);

        // Execute the query
        if ($stmt->execute()) {
            $message = "Company updated successfully!";
        } else {
            $message = "Error executing query: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Company</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .form-container { max-width: 700px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .form-title { color: #333; }
        .form-label { font-weight: bold; }
        .btn-submit { background: #28a745; border: none; }
        .btn-submit:hover { background: #218838; }
        .btn-back { color: #fff; }
    </style>
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="form-container">
<div class="dashboard-header text-center mb-4">
        <h1 class="text-white fw-bold">
            <i class="bi bi-building"></i> Update Company Data
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

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row g-3">
            <div class="col-12">
                <label for="company_name" class="form-label">Company Name</label>
                <input type="text" name="company_name" id="company_name" class="form-control" value="<?= htmlspecialchars($company['company_name']) ?>" required>
            </div>
            <div class="col-12">
                <label for="address" class="form-label">Address</label>
                <textarea name="address" id="address" class="form-control" rows="2" required><?= htmlspecialchars($company['address']) ?></textarea>
            </div>

            <div class="col-md-2">
                <label for="pincode" class="form-label">Pincode</label>
                <input type="text" name="pincode" id="pincode" class="form-control" value="<?= htmlspecialchars($company['pincode']) ?>" required>
            </div>
            
            <div class="col-md-4">
                <label for="city" class="form-label">City</label>
                <input type="text" name="city" id="city" class="form-control" value="<?= htmlspecialchars($company['city']) ?>">
            </div>
            <div class="col-md-6">
                <label for="district" class="form-label">District</label>
                <input type="text" name="district" id="district" class="form-control" value="<?= htmlspecialchars($company['district']) ?>" required>
            </div>

            <div class="col-md-6">
                <label for="state" class="form-label">State</label>
                <input type="text" name="state" id="state" class="form-control" value="<?= htmlspecialchars($company['state']) ?>" readonly tabindex="-1">
            </div>

            <div class="col-md-6">
                <label for="country" class="form-label">Country</label>
                <input type="text" name="country" id="country" class="form-control" value="<?= htmlspecialchars($company['country']) ?>" readonly tabindex="-1">
            </div>

            <!-- Remaining fields -->
            <?php
            $fields = [
                "phone" => "Phone", "mobile" => "Mobile", "email" => "Email", "website" => "Website",
                "currency" => "Currency", "pan" => "PAN", "gst" => "GST", "tds" => "TDS",
                "msme" => "MSME", "other1" => "Other1", "other2" => "Other2", "other3" => "Other3",
                "bank" => "Bank", "branch" => "Branch", "ifsc" => "IFSC", "account_no" => "Account Number"
            ];

            foreach ($fields as $name => $label) {
                $value = htmlspecialchars($company[$name]);
                echo <<<HTML
                <div class="col-md-6">
                    <label for="$name" class="form-label">$label</label>
                    <input type="text" name="$name" id="$name" class="form-control" value="$value">
                </div>
                HTML;
            }
            ?>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="dashboard.php" class="btn btn-secondary btn-back">Back to Dashboard</a>
            <button type="submit" class="btn btn-success btn-submit">Update Company</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('pincode').addEventListener('input', function () {
        const pincode = this.value;
        if (pincode.length === 6) {
            fetch(`https://api.postalpincode.in/pincode/${pincode}`)
                .then(response => response.json())
                .then(data => {
                    if (data[0].Status === 'Success') {
                        const details = data[0].PostOffice[0];
                        document.getElementById('state').value = details.State;
                        document.getElementById('city').value = details.Name;
                        document.getElementById('country').value = "India";
                        document.getElementById('district').value = details.District;
                    } else {
                        alert('Invalid PIN code');
                        document.getElementById('state').value = '';
                        document.getElementById('city').value = '';
                        document.getElementById('district').value = '';
                    }
                })
                .catch(err => {
                    alert('Error fetching data');
                    document.getElementById('state').value = '';
                    document.getElementById('city').value = '';
                    document.getElementById('district').value = '';
                });
        }
    });
</script>

</body>
</html>
