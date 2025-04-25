<?php
require_once 'db_connect.php';

$email = isset($_GET['email']) ? mysqli_real_escape_string($conn, $_GET['email']) : null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp']) && isset($_POST['email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $otp = mysqli_real_escape_string($conn, $_POST['otp']);
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Verify OTP
    $query = "SELECT expires, user_type FROM otp_resets WHERE email = ? AND otp = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $otp);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        $error = "Invalid OTP. Please check and try again.";
    } else {
        $row = mysqli_fetch_assoc($result);
        $expires = $row['expires'];
        $user_type = $row['user_type'];

        if (strtotime($expires) < time()) {
            $error = "This OTP has expired. Please request a new one.";
        } else {
            // Update password in the appropriate table based on user_type
            $table = ($user_type === 'security') ? 'security' : $user_type . 's';
            $id_field = $user_type . '_id';

            $update_query = "UPDATE $table SET password = ? WHERE email = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ss", $new_password, $email);
            
            if (mysqli_stmt_execute($stmt) && mysqli_affected_rows($conn) > 0) {
                // Delete the OTP after successful reset
                $delete_query = "DELETE FROM otp_resets WHERE email = ?";
                $stmt = mysqli_prepare($conn, $delete_query);
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);

                $success = "Password reset successfully! <a href='index.php'>Click here to login</a>.";
            } else {
                $error = "Failed to reset password. No matching user found or database error.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
 
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-shield-alt"></i> Verify OTP</h1>

        <?php 
        if (isset($error)) echo "<p class='error'>$error</p>";
        if (isset($success)) echo "<p class='success'>$success</p>";
        if (!$email) echo "<p class='error'>No email provided. Please start from the forgot password page.</p>";
        ?>

        <?php if ($email && !isset($success)): ?>
        <form method="POST" action="verify_otp.php">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="text" name="otp" placeholder="Enter OTP" required maxlength="6">
            <input type="password" name="password" placeholder="Enter new password" required>
            <button type="submit"><i class="fas fa-check"></i> Verify & Reset</button>
        </form>
        <?php endif; ?>

        <a href="forgot_password.php">Request a new OTP</a> | <a href="index.php">Back to Login</a>
    </div>
</body>
</html>