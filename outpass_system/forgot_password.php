<?php
require_once 'db_connect.php';
require_once 'send_email.php'; // PHPMailer functionality

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Validation
    if (empty($email)) {
        $error = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email exists in any user table
        $tables = ['students', 'admins', 'security', 'managers']; // Added 'managers'
        $user_found = false;
        $user_type = '';
        $user_id_field = '';

        foreach ($tables as $table) {
            $id_field = ($table === 'security') ? 'security_id' : substr($table, 0, -1) . '_id'; // e.g., manager_id
            $query = "SELECT $id_field FROM $table WHERE email = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $user_found = true;
                $user_type = ($table === 'security') ? 'security' : substr($table, 0, -1); // e.g., 'manager'
                $user_id_field = $id_field;
                break;
            }
            mysqli_stmt_close($stmt);
        }

        if (!$user_found) {
            $error = "No account found with this email";
        } else {
            // Generate a 6-digit OTP
            $otp = sprintf("%06d", mt_rand(0, 999999));
            $expires = date("Y-m-d H:i:s", strtotime('+10 minutes')); // OTP expires in 10 minutes

            // Store OTP in the database
            $query = "INSERT INTO otp_resets (email, otp, expires, user_type) VALUES (?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE otp = ?, expires = ?, user_type = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssss", $email, $otp, $expires, $user_type, $otp, $expires, $user_type);

            if (mysqli_stmt_execute($stmt)) {
                // Send OTP via email
                $subject = "Your Password Reset OTP";
                $body = "
                    <h2>Password Reset OTP</h2>
                    <p>Your OTP for password reset is: <strong>$otp</strong></p>
                    <p>This OTP is valid for 10 minutes. Use it to reset your password.</p>
                    <p>If you did not request this, please ignore this email.</p>
                ";

                if (sendEmail($email, $subject, $body)) {
                    $success = "An OTP has been sent to your email. Please check and enter it on the next page.";
                    header("Refresh: 2; URL=verify_otp.php?email=" . urlencode($email)); // Redirect after 2 seconds
                } else {
                    $success = "OTP generated but email sending failed. For testing, your OTP is: $otp";
                }
            } else {
                $error = "Failed to generate OTP. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
 
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-key"></i> Forgot Password</h1>
        
        <?php 
        if (isset($error)) echo "<p class='error'>$error</p>";
        if (isset($success)) echo "<p class='success'>$success</p>";
        ?>

        <form method="POST" action="forgot_password.php">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit"><i class="fas fa-paper-plane"></i> Send OTP</button>
        </form>
        
        <a href="index.php">Back to Login</a>
    </div>
</body>
</html>