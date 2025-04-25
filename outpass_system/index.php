<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Outpass Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-ticket-alt"></i> Outpass Portal</h1>
        <h2><i class="fas fa-user"></i> select your profile</h2>
        
        <?php 
        if (isset($_SESSION['error'])): ?>
            <p class="error animate__animated animate__shakeX">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </p>
        <?php endif; ?>
        
        <?php 
        if (isset($_SESSION['success'])): ?>
            <p class="success animate__animated animate__fadeIn">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </p>
        <?php endif; ?>
        
        <div class="login-options">
            <button onclick="showLogin('student')"><i class="fas fa-user-graduate"></i> Student</button>
            <button onclick="showLogin('admin')"><i class="fas fa-user-tie"></i> HOD</button>
            <button onclick="showLogin('manager')"><i class="fas fa-user-shield"></i> Admin</button>
            <button onclick="showLogin('security')"><i class="fas fa-user-lock"></i> Security</button>
        </div>

        <div id="login-form" class="login-form">
            <h2 id="login-title"></h2>
            <form id="auth-form" method="POST" action="./login.php">
                <input type="text" name="id" placeholder="Enter your ID" required>
                <input type="password" name="password" placeholder="Enter your Password" required>
                <input type="hidden" name="type" id="user-type">
                <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
                <p id="register-link" style="display: none;">Don't have an account? <a href="./register.php">Register here</a></p>
                <p>Forgot password? <a href="./forgot_password.php">Reset here</a></p>
            </form>
        </div>
    </div>
    
    <script>
        function showLogin(type) {
            const loginForm = document.getElementById('login-form');
            const loginTitle = document.getElementById('login-title');
            const userType = document.getElementById('user-type');
            const registerLink = document.getElementById('register-link');

            loginForm.style.display = 'block';
            userType.value = type;

            // Show register link only for student
            registerLink.style.display = type === 'student' ? 'block' : 'none';

            switch(type) {
                case 'student':
                    loginTitle.innerHTML = '<i class="fas fa-user-graduate"></i> Student Login';
                    break;
                case 'admin':
                    loginTitle.innerHTML = '<i class="fas fa-user-tie"></i> HOD Login';
                    break;
                case 'security':
                    loginTitle.innerHTML = '<i class="fas fa-user-lock"></i> Security Login';
                    break;
                case 'manager':
                    loginTitle.innerHTML = '<i class="fas fa-user-shield"></i> Admin Login';
                    break;
            }
        }
    </script>
</body>
</html>