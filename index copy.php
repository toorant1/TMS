<?php
include 'database/db_connection.php'; // Include the database connection


// Initialize message variables
$registrationMessage = "";
$loginMessage = "";

// Handle Registration Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $user_id = trim($_POST['user_id']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);

    // Validate email format
    if (!filter_var($user_id, FILTER_VALIDATE_EMAIL)) {
        $registrationMessage = '<div class="alert alert-danger">Invalid Email ID. Please enter a valid email.</div>';
    } 
    // Validate password criteria
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $password)) {
        $registrationMessage = '<div class="alert alert-danger">Password must be at least 8 characters long, contain at least one uppercase letter, one lowercase letter, and one number.</div>';
    } 
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $registrationMessage = '<div class="alert alert-danger">Passwords do not match. Please try again.</div>';
    } else {
        // Check if the email is already registered
        $check_sql = "SELECT * FROM master_user_registration WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $registrationMessage = '<div class="alert alert-danger">Error: Email ID already registered. Please use a different email. <br> Please <a href="#login" class="text-primary">Login</a> now.</div>';
        } else {
            // Proceed with registration
            $token = bin2hex(random_bytes(16)); // Generate unique token
            $token_otp = rand(100000, 999999); // Generate 6-digit OTP
            $token_otp_status = 0;
            $lic_status = 0;
            $lic_key = bin2hex(random_bytes(8)); // Generate license key

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $sql = "INSERT INTO master_user_registration 
                    (user_id, password, fname, lname, token, token_otp, token_otp_status, lic_status, lic_key)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssiisi", $user_id, $hashedPassword, $fname, $lname, $token, $token_otp, $token_otp_status, $lic_status, $lic_key);

            if ($stmt->execute()) {
                $registrationMessage = '<div class="alert alert-success">Registration successful! <br> Please <a href="#login" class="text-primary">Login</a> now.</div>';
                // Include the organisation_default_data_entry.php script after successful registration
                include 'organisation_default_data_entry.php';
            } else {
                $registrationMessage = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
            }

            $stmt->close();
        }

        $check_stmt->close();
    }
}
// Handle Registration Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $token = bin2hex(random_bytes(16)); // Generate unique token
    $token_otp = rand(100000, 999999); // Generate 6-digit OTP
    $token_otp_status = 0;
    $lic_status = 0;
    $lic_key = bin2hex(random_bytes(8)); // Generate license key

    // Check if the user_id (email) already exists
    $check_sql = "SELECT * FROM master_user_registration WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $registrationMessage = '<div class="alert alert-danger">Error: Email ID already registered. Please use a different email. <br> Please <a href="#login" class="switch-to-login text-primary">Login</a> Now.</div>';
    } else {
        // Proceed with registration
        $sql = "INSERT INTO master_user_registration 
                (user_id, password, fname, lname, token, token_otp, token_otp_status, lic_status, lic_key)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssiisi", $user_id, $password, $fname, $lname, $token, $token_otp, $token_otp_status, $lic_status, $lic_key);

        if ($stmt->execute()) {
            $registrationMessage = 'Registration successful! <br> Please <a href="#login" class="switch-to-login text-primary">Login</a> Now.';
            // Include the organisation_default_data_entry.php script after successful registration
            include 'organisation_default_data_entry.php';

        } else {
            $registrationMessage = "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $check_stmt->close();
}

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user_id = $_POST['login_user_id'];
    $password = $_POST['login_password'];

    $sql = "SELECT * FROM master_user_registration WHERE user_id = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Start a session and store the user information
        session_start();
        $user = $result->fetch_assoc();
        
        $_SESSION['user_id'] = $user_id; // Store 'id' as 'master_userid'
        $_SESSION['master_userid'] = $user['id']; // Store 'id' as 'master_userid'
        $_SESSION['token'] = $user['token']; // Store the token from the database

        // Redirect to dashboard
        header("Location: dashboard.php");
        exit; // Ensure no further code is executed
    } else {
        $loginMessage = "Invalid credentials. Please try again.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration & Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom Media Queries for responsiveness */
        @media (max-width: 768px) {
            .card {
                margin-top: 20px;
            }
            .card-header h4 {
                font-size: 1.25rem;
            }
            .nav-tabs {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .form-control {
                font-size: 0.875rem;
            }
            .btn {
                font-size: 1rem;
            }
            .card-header h4 {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab" aria-controls="register" aria-selected="true">Register</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="false">Login</button>
        </li>
    </ul>
    <div class="tab-content" id="myTabContent">
        <!-- Registration Tab -->
        <div class="tab-pane fade show active" id="register" role="tabpanel" aria-labelledby="register-tab">
            <div class="card shadow-sm mt-6">
                <div class="card-header bg-primary text-white text-center">
                    <h4>User Registration</h4>
                </div>
                <div class="card-body">
                    <?php if ($registrationMessage): ?>
                        <div class="alert alert-info"><?= $registrationMessage ?></div>
                    <?php endif; ?>
                    <form id="registrationForm" method="post" action="">
                        <input type="hidden" name="register" value="1">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">User ID</label>
                            <input type="text" class="form-control" id="user_id" name="user_id" required placeholder="Enter your Email ID" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Enter Strong Password" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Re-enter Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Re-Enter Password" autocomplete="off">
                            <small id="passwordHelp" class="text-danger"></small>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="fname" name="fname" placeholder="Enter First Name" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label for="lname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lname" name="lname" placeholder="Enter Last Name" autocomplete="off">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Login Tab -->
        <div class="tab-pane fade" id="login" role="tabpanel" aria-labelledby="login-tab">
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-primary text-white text-center">
                    <h4>User Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($loginMessage): ?>
                        <div class="alert alert-info"><?= $loginMessage ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <input type="hidden" name="login" value="1">
                        <div class="mb-3">
                            <label for="login_user_id" class="form-label">User ID</label>
                            <input type="text" class="form-control" id="login_user_id" name="login_user_id" required placeholder="Enter your User ID" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="login_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="login_password" name="login_password" required placeholder="Enter your Password" autocomplete="off">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
