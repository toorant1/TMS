<?php
require_once '../database/db_connection.php';
session_start();

$message = "";

// Check if user is logged in
if (!isset($_SESSION['master_userid'])) {
    header('Location: ../index.php');
    exit();
}

$master_userid = $_SESSION['master_userid'];

// Ensure the user_id and token are provided in the URL
if (isset($_GET['user_id']) && isset($_GET['token'])) {
    $user_id = $_GET['user_id'];
    $token = $_GET['token'];

    // Fetch user details from the database
    $query = "SELECT id, name, sex, address, state, district, city, pincode, mobile, email FROM master_users WHERE id = ? AND token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("User not found or invalid token.");
    }

    $user = $result->fetch_assoc();
} else {
    die("User ID or token not provided.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    function validate_input($data) {
        return htmlspecialchars(trim($data));
    }

    $name = validate_input($_POST['name']);
    $sex = validate_input($_POST['sex']);
    $address = validate_input($_POST['address']);
    $state = validate_input($_POST['state']);
    $district = validate_input($_POST['district']);
    $city = validate_input($_POST['city']);
    $pincode = validate_input($_POST['pincode']);
    $mobile = validate_input($_POST['mobile']);
    $email = validate_input($_POST['email']);

    // Update query
    $update_query = "UPDATE master_users SET name = ?, sex = ?, address = ?, state = ?, district = ?, city = ?, pincode = ?, mobile = ?, email = ?, updated_on = NOW(), updated_by = ? WHERE id = ? AND token = ?";
    
    $stmt = $conn->prepare($update_query);
    if ($stmt) {
        $stmt->bind_param("sssssssssiis", $name, $sex, $address, $state, $district, $city, $pincode, $mobile, $email, $master_userid, $user_id, $token);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>User details updated successfully!</div>";
        } else {
            error_log("Database error: " . $stmt->error);
            $message = "<div class='alert alert-danger'>An error occurred while updating the user. Please try again.</div>";
        }
        $stmt->close();
    } else {
        error_log("Database error: " . $conn->error);
        $message = "<div class='alert alert-danger'>An error occurred. Please try again.</div>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <script>
        function fetchLocation() {
            let pincode = document.getElementById("pincode").value;
            if (pincode.length === 6) {  
                fetch(`https://api.postalpincode.in/pincode/${pincode}`)
                .then(response => response.json())
                .then(data => {
                    if (data[0].Status === "Success") {
                        document.getElementById("state").value = data[0].PostOffice[0].State;
                        document.getElementById("district").value = data[0].PostOffice[0].District;
                        document.getElementById("city").value = data[0].PostOffice[0].Block || data[0].PostOffice[0].Name;
                    } else {
                        alert("Invalid Pincode! Please enter a valid one.");
                    }
                })
                .catch(error => console.error("Error fetching data:", error));
            }
        }
    </script>

    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 700px;
            margin: auto;
        }
        .form-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
        }
        .form-label {
            font-weight: 500;
        }
        .btn-primary {
            background: #007bff;
            border: none;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>

<div class="container mt-5">
    <?php include('../headers/header.php'); include('../headers/header_buttons.php'); ?>

    <div class="form-container">
        <div class="form-card">
            <h3 class="text-center text-white p-3 rounded shadow-lg" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                <i class="bi bi-pencil-square me-2"></i> Edit User Details
            </h3>

            <?php if (!empty($message)) echo $message; ?>

            <form method="POST" action="" autocomplete="off">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-person"></i> Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-gender-ambiguous"></i> Sex</label>
                        <select name="sex" class="form-control" required>
                            <option value="Male" <?= ($user['sex'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?= ($user['sex'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?= ($user['sex'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-geo-alt"></i> Address</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address']); ?>" required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label"><i class="bi bi-pin-map"></i> Pincode</label>
                        <input type="text" id="pincode" name="pincode" class="form-control" value="<?= htmlspecialchars($user['pincode']); ?>" required onblur="fetchLocation()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="bi bi-buildings"></i> City</label>
                        <input type="text" id="city" name="city" class="form-control" value="<?= htmlspecialchars($user['city']); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="bi bi-map"></i> District</label>
                        <input type="text" id="district" name="district" class="form-control" value="<?= htmlspecialchars($user['district']); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="bi bi-globe"></i> State</label>
                        <input type="text" id="state" name="state" class="form-control" value="<?= htmlspecialchars($user['state']); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
    <label class="form-label"><i class="bi bi-phone"></i> Mobile</label>
    <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($user['mobile']); ?>" required>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label"><i class="bi bi-envelope"></i> Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label"><i class="bi bi-key"></i> Password (Leave blank to keep)</label>
        <input type="password" name="password" class="form-control">
    </div>
</div>




<div class="d-flex justify-content-between mt-4">
    <!-- Back Button -->
    <button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
    <i class="bi bi-arrow-left"></i> Back
</button>

    <div>
        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
    </div>
               
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
