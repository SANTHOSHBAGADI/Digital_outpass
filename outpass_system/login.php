<?php
require_once 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $password = $_POST['password'];

    // Validate user type
    $valid_types = ['student', 'admin', 'security', 'manager'];
    if (!in_array($type, $valid_types)) {
        $_SESSION['error'] = "Invalid user type";
        header("Location: index.php");
        exit();
    }

    // Determine the table and ID field based on user type
    $table = ($type === 'security') ? 'security' : $type . 's';
    $id_field = $type . '_id';

    // For students, include the status in the query
    if ($type === 'student') {
        $query = "SELECT $id_field, password, status FROM $table WHERE $id_field = ?";
    } else {
        $query = "SELECT $id_field, password FROM $table WHERE $id_field = ?";
    }

    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                // Check status for students
                if ($type === 'student') {
                    if ($row['status'] !== 'Approved') {
                        $_SESSION['error'] = "Your registration is not yet approved";
                        header('Location: index.php');
                        exit();
                    }
                }
                // If status is Approved (or not applicable), proceed with login
                $_SESSION['user_id'] = $row[$id_field];
                $_SESSION['user_type'] = $type;
                
                // Redirect based on user type
                switch($type) {
                    case 'student':
                        header('Location: student_dashboard.php');
                        break;
                    case 'admin':
                        header('Location: admin_dashboard.php');
                        break;
                    case 'security':
                        header('Location: security_dashboard.php');
                        break;
                    case 'manager':
                        header('Location: manager_dashboard.php');
                        break;
                }
                exit();
            } else {
                $_SESSION['error'] = "Invalid credentials";
            }
        } else {
            $_SESSION['error'] = "User not found";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Database error occurred";
    }
    
    header('Location: index.php');
    exit();
}
?>