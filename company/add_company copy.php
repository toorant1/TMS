<?php
require_once '../database/db_connection.php';
session_start(); // Ensure session is started

$message = "";

// Check if the user ID is available in the session
if (!isset($_SESSION['master_userid'])) {
    die("Error: User is not logged in.");
}

$master_userid = $_SESSION['master_userid']; // Retrieve the user ID from the session

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'company_name', 'address', 'state', 'city', 'pincode', 'country', 
        'phone', 'mobile', 'email', 'website', 'currency', 'pan', 
        'gst', 'tds', 'msme', 'other1', 'other2', 'other3', 
        'bank', 'branch', 'ifsc', 'account_no'
    ];

    // Prepare SQL placeholders
    $placeholders = implode(',', array_fill(0, count($fields), '?'));

    // SQL query
    $sql = "INSERT INTO master_company (master_userid, " . implode(',', $fields) . ") VALUES (?, $placeholders)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters dynamically
        $types = str_repeat('s', count($fields)); // Assuming all fields are strings
        $data = array_map(fn($field) => $_POST[$field] ?? null, $fields);
        $params = array_merge([$master_userid], $data); // Combine master_userid and form data
        $stmt->bind_param('i' . $types, ...$params);

        // Execute the query
        if ($stmt->execute()) {
            $message = "Company added successfully!";
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
    <title>Add New Company</title>
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
    <h2 class="form-title text-center mb-4 p-3 bg-primary text-white rounded">Create New Company</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row g-3">
            <div class="col-12">
                <label for="company_name" class="form-label">Company Name</label>
                <input type="text" name="company_name" id="company_name" class="form-control" required>
            </div>
            <div class="col-12">
                <label for="address" class="form-label">Address</label>
                <textarea name="address" id="address" class="form-control" rows="2" required></textarea>
            </div>

            <div class="col-md-2">
                <label for="pincode" class="form-label">Pincode</label>
                <input type="text" name="pincode" id="pincode" class="form-control" required>
            </div>
            
            <div class="col-md-4">
                <label for="city" class="form-label">City</label>
                <input type="text" name="city" id="city" class="form-control" >
            </div>
            <div class="col-md-6">
                <label for="district" class="form-label">District</label>
                <input type="text" id="district" class="form-control" >
            </div>

            <div class="col-md-6">
                <label for="state" class="form-label">State</label>
                <input type="text" name="state" id="state" class="form-control" readonly tabindex = "-1">
            </div>

            <div class="col-md-6">
                <label for="country" class="form-label">Country</label>
                <input type="text" name="country" id="country" class="form-control" readonly tabindex = "-1">
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
                echo <<<HTML
                <div class="col-md-6">
                    <label for="$name" class="form-label">$label</label>
                    <input type="text" name="$name" id="$name" class="form-control">
                </div>
                HTML;
            }
            ?>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="dashboard.php" class="btn btn-secondary btn-back">Back to Dashboard</a>
            <button type="submit" class="btn btn-success btn-submit">Add Company</button>
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
                        document.getElementById('state').value = "";
                        document.getElementById('city').value = "";
                        document.getElementById('country').value = "India";
                        document.getElementById('district').value = "";
                    }
                })
                .catch(error => {
                    console.error('Error fetching PIN code details:', error);
                    alert('Failed to fetch details for the entered PIN code.');
                });
        }
    });
</script>
</body>
</html>

<?php
$conn->close();
?>
