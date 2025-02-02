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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize user input
    function validate_input($data) {
        return htmlspecialchars(trim($data));
    }

    $name = validate_input($_POST['name'] ?? '');
    $sex = validate_input($_POST['sex'] ?? '');
    $address = validate_input($_POST['address'] ?? '');
    $state = validate_input($_POST['state'] ?? '');
    $district = validate_input($_POST['district'] ?? '');
    $city = validate_input($_POST['city'] ?? '');
    $pincode = validate_input($_POST['pincode'] ?? '');
    $mobile = validate_input($_POST['mobile'] ?? '');
    $email = validate_input($_POST['email'] ?? '');
    $password = validate_input($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $message = "<div class='alert alert-danger'>Please fill out all required fields!</div>";
    } else {
        // Check if email is unique
        $email_check_query = "SELECT email FROM master_users WHERE email = ?";
        $stmt = $conn->prepare($email_check_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "<div class='alert alert-danger'>Error: Email is already registered.</div>";
            $stmt->close();
        } else {
            $stmt->close();

            // Generate a unique token and hash the password
            $token = uniqid('user_', true);
            $password_hashed = password_hash($password, PASSWORD_BCRYPT);
            $password_reset_token = bin2hex(random_bytes(16));
            $password_reset_token_status = 0;
            $status = 1;

            // Insert user data into the 'master_users' table
            $sql = "INSERT INTO master_users 
                    (master_user_id, token, name, sex, address, state, district, city, pincode, mobile, email, password, password_reset_token, password_reset_token_status, created_on, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("ssssssssssssssi", $master_userid, $token, $name, $sex, $address, $state, $district, $city, $pincode, $mobile, $email, $password_hashed, $password_reset_token, $password_reset_token_status, $status);

                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>User added successfully!</div>";
                } else {
                    error_log("Database error: " . $stmt->error);
                    $message = "<div class='alert alert-danger'>An error occurred while adding the user. Please try again.</div>";
                }

                $stmt->close();
            } else {
                error_log("Database error: " . $conn->error);
                $message = "<div class='alert alert-danger'>An error occurred. Please try again.</div>";
            }
        }
    }
}

$conn->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <script>
        function fetchLocation() {
            let pincode = document.getElementById("pincode").value;

            if (pincode.length === 6) {  // Fetch data only if Pincode is 6 digits
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

<script>
    // Function to make city and district editable on double-click
    function makeEditable(field) {
        field.readOnly = false;
        field.style.backgroundColor = "#ffffff"; // Set to white when editable
        field.style.cursor = "text";
    }

    // Function to revert city and district to readonly on blur
    function makeReadonly(field) {
        field.readOnly = true;
        field.style.backgroundColor = "#e9ecef"; // Set back to grey when readonly
        field.style.cursor = "not-allowed";
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
        .input-group-text {
            background-color: #e9ecef;
        }
    </style>
</head>

<body>

<div class="container mt-5">
    <?php include('../headers/header.php'); include('../headers/header_buttons.php'); ?>

    <div class="form-container">
        <div class="form-card">
        <h3 class="text-center text-white p-3 rounded shadow-lg" style="background: linear-gradient(135deg, #007bff, #0056b3);">
    <i class="bi bi-person-plus-fill me-2"></i> Add New User
</h3>


            <?php if (!empty($message)) echo $message; ?>

            <form method="POST" action="add_user.php" autocomplete="off">
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label"><i class="bi bi-person"></i> Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="Enter full name" required autocomplete="off">
        </div>
        <div class="col-md-6">
            <label class="form-label"><i class="bi bi-gender-ambiguous"></i> Sex</label>
            <select name="sex" class="form-control" required>
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label"><i class="bi bi-geo-alt"></i> Address</label>
        <input type="text" name="address" class="form-control" placeholder="Enter address" required autocomplete="off">
    </div>

    <div class="row mb-3">
    <div class="col-md-3">
        <label class="form-label"><i class="bi bi-pin-map"></i> Pincode</label>
        <input type="text" id="pincode" name="pincode" class="form-control" placeholder="Enter Pincode" required onblur="fetchLocation()" autocomplete="off">
    </div>
    <div class="col-md-3">
        <label class="form-label"><i class="bi bi-buildings"></i> City</label>
        <input type="text" id="city" name="city" class="form-control readonly-field" required readonly tabindex="-1" ondblclick="makeEditable(this)" onblur="makeReadonly(this)">
    </div>
    <div class="col-md-3">
        <label class="form-label"><i class="bi bi-map"></i> District</label>
        <input type="text" id="district" name="district" class="form-control readonly-field" required readonly tabindex="-1" ondblclick="makeEditable(this)" onblur="makeReadonly(this)">
    </div>
    <div class="col-md-3">
        <label class="form-label"><i class="bi bi-globe"></i> State</label>
        <input type="text" id="state" name="state" class="form-control readonly-field" required readonly tabindex="-1">
    </div>
</div>
<style>
    /* Grey background for readonly fields */
    .readonly-field {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
</style>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label"><i class="bi bi-phone"></i> Mobile</label>
            <input type="text" name="mobile" class="form-control" placeholder="Enter mobile number" required autocomplete="off">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label"><i class="bi bi-envelope"></i> Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter email" required autocomplete="off">
        </div>
        <div class="col-md-6">
            <label class="form-label"><i class="bi bi-key"></i> Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter password" required autocomplete="off">
        </div>
    </div>


    <div class="d-flex justify-content-between mt-4">
    <!-- Back Button -->
    <button type="button" class="btn btn-secondary" onclick="history.back()">
        <i class="bi bi-arrow-left"></i> Back
    </button>

    <div>
        <!-- Reset Button -->
        <button type="reset" class="btn btn-warning me-2">
            <i class="bi bi-arrow-clockwise"></i> Reset Form Data
        </button>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-person-check"></i> Save User Data
        </button>
    </div>
</div>


</form>




<style>
    /* Grey background for readonly fields */
    .readonly-field {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
</style>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
