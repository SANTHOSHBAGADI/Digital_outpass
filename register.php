<?php
require_once 'db_connect.php';
require_once 'send_email.php';

$departments = ['CSE', 'ECE', 'MECH', 'CIVIL', 'EEE'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $parent_name = mysqli_real_escape_string($conn, $_POST['parent_name']);
    $parent_phone = mysqli_real_escape_string($conn, $_POST['parent_phone']);
    $parent_email = !empty($_POST['parent_email']) ? mysqli_real_escape_string($conn, $_POST['parent_email']) : null;

    $password_pattern = "/^(?=.*[A-Z])(?=.*[!@#$%^&*])(?=.{8,})/";

    // Validation
    if (empty($id) || empty($name) || empty($email) || empty($password) || empty($phone)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error = "Invalid phone number (10 digits required)";
    } elseif (empty($department) || empty($parent_name) || empty($parent_phone)) {
        $error = "Department, parent name, and parent phone are required";
    } elseif (!empty($parent_email) && !filter_var($parent_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid parent email format";
    } elseif (!preg_match($password_pattern, $password)) {
        $error = "Password must be at least 8 characters long and contain at least one uppercase letter and one special character (!@#$%^&*)";
    } else {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $table = 'students';
        $id_field = 'student_id';
        $check_query = "SELECT * FROM $table WHERE $id_field = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "ss", $id, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "ID or Email already exists";
        } else {
            $status = 'Pending';
            $query = "INSERT INTO $table ($id_field, name, email, password, department, phone, parent_name, parent_phone, parent_email, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssssssssss", $id, $name, $email, $password, $department, $phone, $parent_name, $parent_phone, $parent_email, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $admin_query = "SELECT email FROM admins LIMIT 1";
                $admin_result = mysqli_query($conn, $admin_query);
                if ($admin_result && mysqli_num_rows($admin_result) > 0) {
                    $admin = mysqli_fetch_assoc($admin_result);
                    $admin_email = $admin['email'];

                    $subject = "New Student Registration Request - $name";
                    $body = "
                        <h2>New Student Registration Request</h2>
                        <p><strong>Student ID:</strong> $id</p>
                        <p><strong>Name:</strong> $name</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Department:</strong> $department</p>
                        <p><strong>Phone:</strong> $phone</p>
                        <p><strong>Parent Name:</strong> $parent_name</p>
                        <p><strong>Parent Phone:</strong> $parent_phone</p>
                        <p><strong>Parent Email:</strong> " . ($parent_email ?: 'N/A') . "</p>
                        <p>Please review and approve this registration in the Outpass Management System.</p>
                    ";
                    if (sendEmail($admin_email, $subject, $body)) {
                        $success = "Registration submitted! Awaiting admin approval. Admin has been notified.";
                    } else {
                        $success = "Registration submitted! Awaiting admin approval. Failed to notify admin via email.";
                    }
                } else {
                    $success = "Registration submitted! Awaiting admin approval. No admin found to notify.";
                }
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-user-plus"></i> Student Registration</h1>
        
        <?php 
        if (isset($error)) echo "<p class='error'>$error</p>";
        if (isset($success)) echo "<p class='success'>$success</p>";
        ?>

        <form method="POST" action="register.php" onsubmit="return validateForm()">
            <input type="hidden" name="type" value="student">
            <input type="text" name="id" placeholder="Student ID" required>
            <input type="text" name="name" placeholder="Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" id="password" placeholder="Password (min 8 chars, 1 uppercase, 1 special)" required>
            <input type="text" name="phone" placeholder="Phone (10 digits)" required>
            <select name="department" required>
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="parent_name" placeholder="Parent Name" required>
            <input type="text" name="parent_phone" placeholder="Parent Phone (10 digits)" required>
            <input type="email" name="parent_email" placeholder="Parent Email (optional)">

            <button type="submit"><i class="fas fa-user-plus"></i> Register</button>
        </form>
        
        <a href="index.php">Back to Login</a>
    </div>
    <script>
        function validateForm() {
            const password = document.getElementById('password').value;
            const passwordPattern = /^(?=.*[A-Z])(?=.*[!@#$%^&*])(?=.{8,})/;
            
            if (!passwordPattern.test(password)) {
                alert('Password must be at least 8 characters long and contain at least one uppercase letter and one special character (!@#$%^&*)');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>