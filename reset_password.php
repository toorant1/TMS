<?php
include 'database/db_connection.php'; // Include the database connection

// Initialize variables
$errorMessage = "";
$successMessage = "";
$userId = "";

// Check if the token is provided in the URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token exists in the database and is valid
    $sql = "SELECT * FROM master_user_registration WHERE token = ? AND token_otp_status = 0 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userId = $user['user_id']; // Get the user's ID for display

        // Handle password reset form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            $otp = $_POST['otp'];

            // Validate OTP
            if ($otp != $user['token_otp']) {
                $errorMessage = "Invalid OTP. Please try again.";
            } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $newPassword)) {
                $errorMessage = "Password must include at least one uppercase letter, one lowercase letter, one number, and one special character.";
            } elseif ($newPassword !== $confirmPassword) {
                $errorMessage = "Passwords do not match. Please try again.";
            } else {
                // Hash the new password
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

                // Update the password in the database and invalidate the token
                $updateSql = "UPDATE master_user_registration SET password = ?, token_otp_status = 1, token = NULL, token_otp = NULL WHERE token = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ss", $hashedPassword, $token);
                if ($updateStmt->execute()) {
                    $successMessage = "Your password has been successfully reset. You can now <a href='index.php'>login</a>.";
                } else {
                    $errorMessage = "Failed to reset your password. Please try again.";
                }
            }
        }
    } else {
        $errorMessage = "Invalid or expired token.";
    }
} else {
    $errorMessage = "Invalid request. Token is missing.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .card {
            max-width: 400px;
            width: 100%;
        }
        .card-header {
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .disabled-field {
            background-color: #e9ecef !important;
            color: #6c757d !important;
            cursor: not-allowed;
        }
        .feedback {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .feedback .valid {
            color: green;
        }
        .feedback .invalid {
            color: red;
        }
    </style>
</head>
<body>
<div class="card shadow-sm">
    <div class="card-header text-white text-center" style="background: #007bff;">
        <h5>Reset Password</h5>
    </div>
    <div class="card-body">
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= $successMessage ?></div>
        <?php else: ?>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="otp" class="form-label">OTP</label>
                    <input type="text" class="form-control" id="otp" name="otp" required placeholder="Enter OTP">
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required placeholder="Enter new password">
                    <div id="passwordCriteria" class="feedback">
                        <div id="length" class="invalid">✔️ At least 8 characters</div>
                        <div id="uppercase" class="invalid">✔️ At least one uppercase letter</div>
                        <div id="lowercase" class="invalid">✔️ At least one lowercase letter</div>
                        <div id="number" class="invalid">✔️ At least one number</div>
                        <div id="special" class="invalid">✔️ At least one special character</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                    <small id="passwordMatchFeedback" class="feedback invalid"></small>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="resetButton" disabled>Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<script>
    const passwordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const resetButton = document.getElementById('resetButton');
    const passwordMatchFeedback = document.getElementById('passwordMatchFeedback');

    const lengthCriteria = document.getElementById('length');
    const uppercaseCriteria = document.getElementById('uppercase');
    const lowercaseCriteria = document.getElementById('lowercase');
    const numberCriteria = document.getElementById('number');
    const specialCriteria = document.getElementById('special');

    function validatePassword() {
        const password = passwordInput.value;

        // Check length
        if (password.length >= 8) {
            lengthCriteria.classList.remove('invalid');
            lengthCriteria.classList.add('valid');
        } else {
            lengthCriteria.classList.remove('valid');
            lengthCriteria.classList.add('invalid');
        }

        // Check uppercase
        if (/[A-Z]/.test(password)) {
            uppercaseCriteria.classList.remove('invalid');
            uppercaseCriteria.classList.add('valid');
        } else {
            uppercaseCriteria.classList.remove('valid');
            uppercaseCriteria.classList.add('invalid');
        }

        // Check lowercase
        if (/[a-z]/.test(password)) {
            lowercaseCriteria.classList.remove('invalid');
            lowercaseCriteria.classList.add('valid');
        } else {
            lowercaseCriteria.classList.remove('valid');
            lowercaseCriteria.classList.add('invalid');
        }

        // Check number
        if (/\d/.test(password)) {
            numberCriteria.classList.remove('invalid');
            numberCriteria.classList.add('valid');
        } else {
            numberCriteria.classList.remove('valid');
            numberCriteria.classList.add('invalid');
        }

        // Check special characters
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            specialCriteria.classList.remove('invalid');
            specialCriteria.classList.add('valid');
        } else {
            specialCriteria.classList.remove('valid');
            specialCriteria.classList.add('invalid');
        }

        validatePasswordMatch();
    }

    function validatePasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (password === confirmPassword && password.length > 0) {
            passwordMatchFeedback.textContent = "Passwords match!";
            passwordMatchFeedback.classList.remove('invalid');
            passwordMatchFeedback.classList.add('valid');
            resetButton.disabled = false;
        } else {
            passwordMatchFeedback.textContent = "Passwords do not match!";
            passwordMatchFeedback.classList.remove('valid');
            passwordMatchFeedback.classList.add('invalid');
            resetButton.disabled = true;
        }
    }

    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', validatePasswordMatch);
</script>
</body>
</html>
