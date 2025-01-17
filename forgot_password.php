<?php
include 'database/db_connection.php'; // Include the database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Use Composer autoload

// Function to send email
function sendEmail($toEmail, $subject, $message) {
    global $conn;

    // Fetch SMTP configuration from the database
    $sql = "SELECT smtp_host, smtp_port, smtp_user, smtp_password, smtp_status FROM master_email_configuration WHERE smtp_status = 1 LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $config = $result->fetch_assoc();

        $mail = new PHPMailer(true);
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'];
            $mail->Password = $config['smtp_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['smtp_port'];

            // Email content
            $mail->setFrom($config['smtp_user'], 'Password Reset');
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            // Send email
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false; // Return false if email sending fails
        }
    } else {
        return false; // Return false if no SMTP configuration is found
    }
}

// Handle AJAX email verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_check'])) {
    $email = $_POST['email'];

    // Check if the email exists in the database
    $sql = "SELECT * FROM master_user_registration WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = ['exists' => false];
    if ($result->num_rows > 0) {
        $response['exists'] = true;
    }

    echo json_encode($response);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_check'])) {
    $email = $_POST['email'];

    // Generate reset token and OTP
    $reset_token = bin2hex(random_bytes(16));
    $otp = rand(100000, 999999);

    // Update the database with the reset token and OTP
    $sql = "UPDATE master_user_registration SET token = ?, token_otp = ?, token_otp_status = 0 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $reset_token, $otp, $email);
    $stmt->execute();

    // Email content
    $reset_link = "localhost/account/reset_password.php?token=$reset_token";
    $subject = "Password Reset Request";
    $message = "Dear User,<br><br>You requested to reset your password. Click the link below to reset it:<br><a href='$reset_link'>$reset_link</a><br><br>Your OTP is: <strong>$otp</strong><br><br>If you did not request this, please ignore this email.";

    // Send the email
    if (sendEmail($email, $subject, $message)) {
        $resetMessage = '<div class="alert alert-success">A password reset link has been sent to your email.</div>';
    } else {
        $resetMessage = '<div class="alert alert-danger">Failed to send the email. Please try again later.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
    </style>
</head>
<body>
<div class="card shadow-sm">
    <div class="card-header text-white text-center" style="background: #007bff;">
        <h5>Forgot Password</h5>
    </div>
    <div class="card-body">
        <?php if (isset($resetMessage) && $resetMessage): ?>
            <?= $resetMessage ?>
        <?php endif; ?>
        <form id="forgotPasswordForm" method="post" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Enter Your Registered Email</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email" autocomplete="off">
                <small id="emailFeedback" class="text-danger"></small>
            </div>
            <div class="row">
                <div class="col-6">
                    <button type="button" class="btn btn-primary w-100" id="submitButton">Send Reset Link</button>
                </div>
                <div class="col-6">
                    <a href="javascript:history.back()" class="btn btn-secondary w-100">Back</a>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('submitButton').addEventListener('click', function () {
        const email = document.getElementById('email').value;
        const feedback = document.getElementById('emailFeedback');
        const form = document.getElementById('forgotPasswordForm');

        if (email === '') {
            feedback.textContent = 'Please enter an email address.';
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true); // Same page
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.exists) {
                    // Submit the form if email is valid
                    form.submit();
                } else {
                    feedback.textContent = response.message;
                    feedback.classList.add('text-danger');
                }
            }
        };
        xhr.send('ajax_check=1&email=' + encodeURIComponent(email));
    });
</script>
</body>
</html>
