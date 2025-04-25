<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}
require_once 'db_connect.php';

// Fetch admin details
$query = "SELECT name, email, department, profile_picture FROM admins WHERE admin_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);
$admin_name = $admin['name'] ?? 'Admin';
$admin_email = $admin['email'] ?? 'N/A';
$admin_department = $admin['department'];
$admin_profile_picture = $admin['profile_picture'] ?? 'https://via.placeholder.com/80';

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $profile_picture = $admin_profile_picture;

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_dir = 'uploads/';
        $file_name = time() . '_' . basename($_FILES['profile_picture']['name']);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_type, $allowed_types) && $_FILES['profile_picture']['size'] <= 5000000) {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $profile_picture = $target_file;
                if ($admin_profile_picture && $admin_profile_picture != 'https://via.placeholder.com/80' && file_exists($admin_profile_picture)) {
                    unlink($admin_profile_picture);
                }
            } else {
                $error = "Failed to upload profile picture.";
            }
        } else {
            $error = "Invalid file type or size. Only JPG, PNG, GIF files under 5MB are allowed.";
        }
    }

    $query = "UPDATE admins SET name = ?, email = ?, profile_picture = ? WHERE admin_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $new_name, $new_email, $profile_picture, $_SESSION['user_id']);
    if (mysqli_stmt_execute($stmt)) {
        $success = "Profile updated successfully.";
        $admin_name = $new_name;
        $admin_email = $new_email;
        $admin_profile_picture = $profile_picture;
    } else {
        $error = "Failed to update profile.";
    }
}

// Handle student account deletion (Updated with manual deletion of related log_book entries)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    
    // Begin transaction to ensure atomicity
    mysqli_begin_transaction($conn);
    try {
        // First, delete related outpass_requests (which will cascade to log_book due to ON DELETE CASCADE)
        $delete_requests_query = "DELETE FROM outpass_requests WHERE student_id = ? AND EXISTS (SELECT 1 FROM students WHERE student_id = ? AND department = ?)";
        $stmt_requests = mysqli_prepare($conn, $delete_requests_query);
        mysqli_stmt_bind_param($stmt_requests, "sss", $student_id, $student_id, $admin_department);
        if (!mysqli_stmt_execute($stmt_requests)) {
            throw new Exception("Failed to delete related outpass requests: " . mysqli_error($conn));
        }

        // Then, delete the student
        $delete_student_query = "DELETE FROM students WHERE student_id = ? AND department = ?";
        $stmt_student = mysqli_prepare($conn, $delete_student_query);
        mysqli_stmt_bind_param($stmt_student, "ss", $student_id, $admin_department);
        if (!mysqli_stmt_execute($stmt_student)) {
            throw new Exception("Failed to delete student account: " . mysqli_error($conn));
        }

        // Commit transaction
        mysqli_commit($conn);
        $success = "Student account and related records deleted successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = $e->getMessage();
        // Log the error for debugging
        error_log("Student deletion error for student_id $student_id: " . $e->getMessage(), 0);
    }
}

// Fetch summary statistics
$query = "SELECT 
            SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
          FROM outpass_requests r
          JOIN students s ON r.student_id = s.student_id
          WHERE s.department = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $admin_department);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);
$pending_count = $stats['pending_count'] ?? 0;
$approved_count = $stats['approved_count'] ?? 0;
$rejected_count = $stats['rejected_count'] ?? 0;

// Handle approve/reject actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $comment = $_POST['comment'] ?? '';
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    
    $query = "UPDATE outpass_requests SET status = ?, admin_comment = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $status, $comment, $request_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $query = "SELECT s.email, s.name, r.purpose, r.out_date, r.in_date 
                 FROM outpass_requests r 
                 JOIN students s ON r.student_id = s.student_id 
                 WHERE r.id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $request_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($result);
        
        if ($student) {
            require_once 'send_email.php';
            $student_email = $student['email'];
            $student_name = $student['name'];
            $purpose = $student['purpose'];
            $out_date = $student['out_date'];
            $in_date = $student['in_date'];
            
            $subject = "Outpass Request $status - $purpose";
            $body = "
                <h2>Outpass Request Update</h2>
                <p>Dear $student_name,</p>
                <p>Your outpass request has been <strong>$status</strong>.</p>
                <p><strong>Request Details:</strong></p>
                <ul>
                    <li><strong>Purpose:</strong> $purpose</li>
                    <li><strong>Out Date:</strong> $out_date</li>
                    <li><strong>In Date:</strong> $in_date</li>
                </ul>
                <p><strong>Admin Comments:</strong> $comment</p>
                <p>Please log in to the Outpass Management System for more details.</p>
            ";
            
            if (sendEmail($student_email, $subject, $body)) {
                $success = "Request $status successfully! Notification sent to student.";
            } else {
                $success = "Request $status, but failed to send email notification.";
            }
        }
    } else {
        $error = "Failed to update request.";
    }
}

// Handle search for past requests and student management
$search_query = '';
$show_past_requests = false;
$show_students = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $search_query = trim($_POST['search']);
    $show_past_requests = true;
}
if (isset($_GET['manage_students'])) {
    $show_students = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .table-container {
            margin: 20px 0;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
        }
        .action-buttons button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #dc3545;
            color: white;
            transition: all 0.3s ease;
        }
        .action-buttons button:hover {
            background-color: #c82333;
            transform: scale(1.05);
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }
        .loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loader i {
            font-size: 40px;
            color: #28a745;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            color: #fff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
        }
        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
            cursor: pointer;
        }
        .sidebar .profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 2px solid #28a745;
            transition: transform 0.3s ease;
        }
        .sidebar .profile img:hover {
            transform: scale(1.1);
        }
        .sidebar .profile h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .sidebar .profile p {
            font-size: 12px;
            color: #b0b0b0;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #fff;
            padding: 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .sidebar a i {
            margin-right: 10px;
        }
        .sidebar a:hover {
            background-color: #28a745;
            transform: translateX(5px);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        h1, h2 {
            text-align: center;
            color: #2c3e50;
        }
        h1 {
            margin-bottom: 20px;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        h2 {
            margin-top: 30px;
            margin-bottom: 20px;
            font-size: 22px;
        }
        .summary {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        .summary-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            flex: 1;
            min-width: 200px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .summary-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .summary-box h3 {
            margin: 0;
            color: #555;
            font-size: 16px;
        }
        .summary-box p {
            font-size: 24px;
            margin: 5px 0 0;
            color: #2c3e50;
        }
        .request {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .request:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .request p {
            margin: 5px 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .request form {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .request textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
        }
        .request button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .request button[name="action"][value="approve"] {
            background-color: #28a745;
            color: #fff;
        }
        .request button[name="action"][value="approve"]:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        .request button[name="action"][value="reject"] {
            background-color: #dc3545;
            color: #fff;
        }
        .request button[name="action"][value="reject"]:hover {
            background-color: #c82333;
            transform: scale(1.05);
        }
        .search-form {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
        }
        .search-container {
            display: flex;
            gap: 10px;
            width: 100%;
            max-width: 500px;
            background: #f1f1f1;
            padding: 10px;
            border-radius: 25px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .search-container input {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            background: transparent;
            outline: none;
        }
        .search-container button {
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .search-container button:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        .success, .error {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: #fff;
            padding: 10px 15px;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            transition: all 0.3s ease;
        }
        .back-to-top:hover {
            background: #218838;
            transform: scale(1.1);
        }
        .hamburger {
            display: none;
            font-size: 24px;
            background: none;
            border: none;
            color: #2c3e50;
            cursor: pointer;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            0% { transform: translateY(-50px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        .modal-content h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #2c3e50;
        }
        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .modal-content label {
            font-size: 14px;
            color: #555;
        }
        .modal-content input[type="text"],
        .modal-content input[type="email"],
        .modal-content input[type="file"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            width: 100%;
        }
        .modal-content button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .modal-content button[type="submit"] {
            background-color: #28a745;
            color: #fff;
        }
        .modal-content button[type="submit"]:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        .modal-content .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .modal-content .close-btn:hover {
            background: #c82333;
        }
        .pending-search-container {
            max-width: 400px;
            margin: 0 auto 20px auto;
        }
        .pending-search-container .search-container {
            padding: 8px 15px;
        }
        .pending-search-container input {
            font-size: 13px;
        }
        .pending-search-container button {
            padding: 8px 15px;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            .hamburger {
                display: block;
            }
            .summary {
                flex-direction: column;
            }
            .summary-box {
                margin: 10px 0;
            }
            .search-container {
                flex-direction: column;
                padding: 15px;
            }
            .search-container input, .search-container button {
                width: 100%;
                border-radius: 5px;
            }
            .request form {
                flex-direction: column;
            }
            .request button {
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="loader" id="loader">
        <i class="fas fa-spinner"></i>
    </div>

    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

    <div class="sidebar" id="sidebar">
        <div class="profile" onclick="openProfileModal()">
            <img src="<?php echo htmlspecialchars($admin_profile_picture); ?>" alt="Profile">
            <h3><?php echo htmlspecialchars($admin_name); ?></h3>
            <p>Dept: <?php echo htmlspecialchars($admin_department); ?></p>
        </div>
        <a href="admin_review_registrations.php"><i class="fas fa-users"></i> Review Registrations</a>
        <a href="?manage_students=1"><i class="fas fa-user-graduate"></i> Manage Students</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="modal" id="profileModal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeProfileModal()"><i class="fas fa-times"></i></span>
            <h2>Edit Profile</h2>
            <form method="POST" enctype="multipart/form-data">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin_name); ?>" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                <label for="profile_picture">Profile Picture:</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                <button type="submit" name="update_profile">Update Profile</button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <h1><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></h1>
            <?php 
            if (isset($error)) echo "<p class='error'><i class='fas fa-exclamation-circle'></i> " . htmlspecialchars($error) . "</p>";
            if (isset($success)) echo "<p class='success'><i class='fas fa-check-circle'></i> " . htmlspecialchars($success) . "</p>";
            ?>

            <?php if ($show_students): ?>
                <h2>Manage Student Accounts</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $students_query = "SELECT * FROM students WHERE department = ?";
                            $stmt = mysqli_prepare($conn, $students_query);
                            mysqli_stmt_bind_param($stmt, "s", $admin_department);
                            mysqli_stmt_execute($stmt);
                            $students_result = mysqli_stmt_get_result($stmt);
                            
                            while ($student = mysqli_fetch_assoc($students_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($student['status']); ?></td>
                                    <td class="action-buttons">
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this student account? This will also delete all related outpass requests and log entries.');">
                                            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                            <button type="submit" name="delete_student">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div class="summary">
                    <div class="summary-box">
                        <h3>Pending Requests</h3>
                        <p><?php echo $pending_count; ?></p>
                    </div>
                    <div class="summary-box">
                        <h3>Approved Requests</h3>
                        <p><?php echo $approved_count; ?></p>
                    </div>
                    <div class="summary-box">
                        <h3>Rejected Requests</h3>
                        <p><?php echo $rejected_count; ?></p>
                    </div>
                </div>

                <h2>Pending Requests</h2>
                <div class="pending-search-container">
                    <div class="search-container">
                        <input type="text" id="pendingSearch" placeholder="Search pending requests..." onkeyup="filterPendingRequests()">
                        <button type="button"><i class="fas fa-search"></i></button>
                    </div>
                </div>

                <div id="pendingRequestsContainer">
                    <?php
                    $query = "SELECT r.*, s.name, s.department, s.parent_name, s.parent_phone, s.parent_email 
                             FROM outpass_requests r 
                             JOIN students s ON r.student_id = s.student_id 
                             WHERE r.status = 'pending' AND s.department = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "s", $admin_department);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if (mysqli_num_rows($result) == 0) {
                        echo "<p>No pending requests found.</p>";
                    } else {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<div class='request pending-request'>";
                            echo "<p><i class='fas fa-user'></i> Student: " . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['student_id']) . ")</p>";
                            echo "<p><i class='fas fa-building'></i> Department: " . htmlspecialchars($row['department']) . "</p>";
                            echo "<p><i class='fas fa-comment'></i> Purpose: " . htmlspecialchars($row['purpose']) . "</p>";
                            echo "<p><i class='fas fa-calendar-alt'></i> Out: " . htmlspecialchars($row['out_date']) . " | In: " . htmlspecialchars($row['in_date']) . "</p>";
                            echo "<p><i class='fas fa-user'></i> Parent Name: " . htmlspecialchars($row['parent_name'] ?? 'N/A') . "</p>";
                            echo "<p><i class='fas fa-phone'></i> Parent Phone: " . htmlspecialchars($row['parent_phone'] ?? 'N/A') . "</p>";
                            if (!empty($row['parent_email'])) {
                                echo "<p><i class='fas fa-envelope'></i> Parent Email: " . htmlspecialchars($row['parent_email']) . "</p>";
                            }
                            ?>
                            <form method="POST">
                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                <textarea name="comment" placeholder="Add a comment (optional)"></textarea>
                                <button type="submit" name="action" value="approve"><i class="fas fa-check"></i> Approve</button>
                                <button type="submit" name="action" value="reject"><i class="fas fa-times"></i> Reject</button>
                            </form>
                            <?php
                            echo "</div>";
                        }
                    }
                    ?>
                </div>

                <h2>Past Requests</h2>
                <form class="search-form" method="POST">
                    <div class="search-container">
                        <input type="text" name="search" placeholder="Search by student name, ID, or purpose..." value="<?php echo htmlspecialchars($search_query); ?>" required>
                        <button type="submit"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>

                <?php
                if (!$show_past_requests) {
                    echo "<p>Please search to view past requests.</p>";
                } else {
                    $query = "SELECT r.*, s.name, s.department, s.parent_name, s.parent_phone, s.parent_email 
                             FROM outpass_requests r 
                             JOIN students s ON r.student_id = s.student_id 
                             WHERE r.status != 'pending' AND s.department = ?";
                    if (!empty($search_query)) {
                        $query .= " AND (s.name LIKE ? OR s.student_id LIKE ? OR r.purpose LIKE ?)";
                        $search_param = "%$search_query%";
                    }
                    $stmt = mysqli_prepare($conn, $query);
                    if (!empty($search_query)) {
                        mysqli_stmt_bind_param($stmt, "ssss", $admin_department, $search_param, $search_param, $search_param);
                    } else {
                        mysqli_stmt_bind_param($stmt, "s", $admin_department);
                    }
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($result) == 0) {
                        echo "<p>No past requests found for your search.</p>";
                    } else {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<div class='request'>";
                            echo "<p><i class='fas fa-user'></i> Student: " . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['student_id']) . ")</p>";
                            echo "<p><i class='fas fa-building'></i> Department: " . htmlspecialchars($row['department']) . "</p>";
                            echo "<p><i class='fas fa-comment'></i> Purpose: " . htmlspecialchars($row['purpose']) . "</p>";
                            echo "<p><i class='fas fa-calendar-alt'></i> Out: " . htmlspecialchars($row['out_date']) . " | In: " . htmlspecialchars($row['in_date']) . "</p>";
                            echo "<p><i class='fas fa-user'></i> Parent Name: " . htmlspecialchars($row['parent_name'] ?? 'N/A') . "</p>";
                            echo "<p><i class='fas fa-phone'></i> Parent Phone: " . htmlspecialchars($row['parent_phone'] ?? 'N/A') . "</p>";
                            if (!empty($row['parent_email'])) {
                                echo "<p><i class='fas fa-envelope'></i> Parent Email: " . htmlspecialchars($row['parent_email']) . "</p>";
                            }
                            echo "<p><i class='fas fa-info-circle'></i> Status: " . htmlspecialchars($row['status']) . "</p>";
                            echo "<p><i class='fas fa-comment-dots'></i> Comment: " . htmlspecialchars($row['admin_comment'] ?? 'None') . "</p>";
                            if ($row['security_verified_at']) {
                                echo "<p><i class='fas fa-check-circle'></i> Verified at: " . htmlspecialchars($row['security_verified_at']) . "</p>";
                            }
                            echo "</div>";
                        }
                    }
                }
                ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="back-to-top" onclick="scrollToTop()"><i class="fas fa-arrow-up"></i></div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'flex';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('profileModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        window.addEventListener('load', () => {
            document.getElementById('loader').style.display = 'none';
        });

        window.addEventListener('scroll', () => {
            const backToTop = document.querySelector('.back-to-top');
            if (window.scrollY > 300) {
                backToTop.style.display = 'block';
            } else {
                backToTop.style.display = 'none';
            }
        });

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function filterPendingRequests() {
            const input = document.getElementById('pendingSearch');
            const filter = input.value.toLowerCase();
            const requests = document.querySelectorAll('.pending-request');

            requests.forEach(request => {
                const text = request.textContent || request.innerText;
                if (text.toLowerCase().includes(filter)) {
                    request.style.display = "";
                } else {
                    request.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>