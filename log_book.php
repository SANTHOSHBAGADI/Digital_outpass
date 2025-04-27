<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'security') {
    header("Location: index.php");
    exit();
}
require_once 'db_connect.php';

// Enable error logging for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check if the log_book table exists, and create it if it doesn't
$check_table_exists = "SHOW TABLES LIKE 'log_book'";
$check_table_result = mysqli_query($conn, $check_table_exists);
if (mysqli_num_rows($check_table_result) == 0) {
    $create_table_query = "
        CREATE TABLE log_book (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id INT NOT NULL,
            student_id VARCHAR(50) NOT NULL,
            verify_time DATETIME NULL,
            check_out_time DATETIME NULL,
            check_in_time DATETIME NULL,
            FOREIGN KEY (request_id) REFERENCES outpass_requests(id),
            FOREIGN KEY (student_id) REFERENCES students(student_id)
        )";
    if (!mysqli_query($conn, $create_table_query)) {
        die("Failed to create log_book table: " . mysqli_error($conn));
    }
}

// Verify the log_book table structure for required columns
$required_columns = ['check_out_time', 'verify_time', 'check_in_time'];
foreach ($required_columns as $column) {
    $check_column_query = "SHOW COLUMNS FROM log_book LIKE '$column'";
    $check_column_result = mysqli_query($conn, $check_column_query);
    if (mysqli_num_rows($check_column_result) == 0) {
        $alter_table_query = "ALTER TABLE log_book ADD COLUMN $column DATETIME NULL";
        if (!mysqli_query($conn, $alter_table_query)) {
            die("Failed to add $column column to log_book table: " . mysqli_error($conn));
        }
    }
}

// Fetch the security personnel's details (name, email, phone, profile picture, created_at, status)
$query = "SELECT name, email, phone, profile_picture, created_at, status 
          FROM security 
          WHERE security_id = ? AND status = 'active'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$security = mysqli_fetch_assoc($result);

if (!$security) {
    // If the security personnel is not found or is inactive, log them out
    session_destroy();
    header("Location: index.php");
    exit();
}

$security_name = $security['name'] ?? 'Security Officer';
$security_email = $security['email'] ?? 'N/A';
$security_phone = $security['phone'] ?? 'N/A';
$security_profile_picture = $security['profile_picture'] ?? 'https://via.placeholder.com/80';
$security_created_at = $security['created_at'];
$security_status = $security['status'];

// Handle check-in actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_in'])) {
    $request_id = $_POST['request_id'];
    $student_id = $_POST['student_id'];
    $timestamp = date('Y-m-d H:i:s');

    // Check if a log entry exists for this request
    $query = "SELECT COUNT(*) FROM log_book WHERE request_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($count > 0) {
        // Update the existing log entry
        $log_query = "UPDATE log_book SET check_in_time = ? WHERE request_id = ?";
        $log_stmt = mysqli_prepare($conn, $log_query);
        mysqli_stmt_bind_param($log_stmt, "si", $timestamp, $request_id);
    } else {
        // Insert a new log entry
        $log_query = "INSERT INTO log_book (request_id, student_id, check_in_time) VALUES (?, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_query);
        mysqli_stmt_bind_param($log_stmt, "iss", $request_id, $student_id, $timestamp);
    }

    if (mysqli_stmt_execute($log_stmt)) {
        $success = "Check In recorded successfully.";
    } else {
        $error = "Failed to record Check In: " . mysqli_error($conn);
    }
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Handle search for log entries
$log_search = '';
if (isset($_POST['log_search'])) {
    $log_search = trim($_POST['log_search']);
}

// Count total log entries for pagination
$count_query = "SELECT COUNT(*) 
                FROM log_book l 
                JOIN students s ON l.student_id = s.student_id 
                JOIN outpass_requests r ON l.request_id = r.id";
if (!empty($log_search)) {
    $count_query .= " WHERE l.student_id LIKE ?";
}
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($log_search)) {
    $search_term = "%$log_search%";
    mysqli_stmt_bind_param($count_stmt, "s", $search_term);
}
mysqli_stmt_execute($count_stmt);
mysqli_stmt_bind_result($count_stmt, $total_records);
mysqli_stmt_fetch($count_stmt);
mysqli_stmt_close($count_stmt);

$total_pages = ceil($total_records / $records_per_page);

// Fetch log entries with pagination
$query = "SELECT l.*, s.name, r.purpose, r.out_date, r.in_date 
          FROM log_book l 
          JOIN students s ON l.student_id = s.student_id 
          JOIN outpass_requests r ON l.request_id = r.id";
if (!empty($log_search)) {
    $query .= " WHERE l.student_id LIKE ?";
}
$query .= " LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
if (!empty($log_search)) {
    $search_term = "%$log_search%";
    mysqli_stmt_bind_param($stmt, "sii", $search_term, $records_per_page, $offset);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $records_per_page, $offset);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Book</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
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
        .log-entry {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .log-entry:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .log-entry p {
            margin: 5px 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .log-entry form {
            display: inline-block;
            margin-right: 10px;
        }
        .log-entry button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .log-entry button[name="check_in"] {
            background-color: #28a745;
            color: #fff;
        }
        .log-entry button[name="check_in"]:hover {
            background-color: #218838;
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
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination a {
            padding: 10px 15px;
            margin: 0 5px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .pagination a:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        .pagination a.disabled {
            background-color: #ccc;
            pointer-events: none;
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
        /* Profile Modal */
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
        .modal-content input[type="tel"],
        .modal-content input[type="file"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            width: 100%;
        }
        .modal-content .info {
            font-size: 14px;
            color: #555;
            margin: 5px 0;
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
            .search-container {
                flex-direction: column;
                padding: 15px;
            }
            .search-container input, .search-container button {
                width: 100%;
                border-radius: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div class="loader" id="loader">
        <i class="fas fa-spinner"></i>
    </div>

    <!-- Hamburger Menu for Mobile -->
    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="profile" onclick="openProfileModal()">
            <img src="<?php echo htmlspecialchars($security_profile_picture); ?>" alt="Profile">
            <h3><?php echo htmlspecialchars($security_name); ?></h3>
            <p>ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
        </div>
        <a href="security_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Profile Modal -->
    <div class="modal" id="profileModal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeProfileModal()"><i class="fas fa-times"></i></span>
            <h2>Edit Profile</h2>
            <form method="POST" enctype="multipart/form-data">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($security_name); ?>" required>
                
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($security_email); ?>" required>
                
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($security_phone); ?>">
                
                <label for="profile_picture">Profile Picture:</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                
                <p class="info"><strong>Account Created:</strong> <?php echo htmlspecialchars($security_created_at); ?></p>
                <p class="info"><strong>Status:</strong> <?php echo htmlspecialchars($security_status); ?></p>
                
                <button type="submit" name="update_profile">Update Profile</button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h1><i class="fas fa-book"></i> Log Book</h1>
            
            <?php 
            if (isset($error)) echo "<p class='error'><i class='fas fa-exclamation-circle'></i> " . htmlspecialchars($error) . "</p>";
            if (isset($success)) echo "<p class='success'><i class='fas fa-check-circle'></i> " . htmlspecialchars($success) . "</p>";
            ?>

            <h2>Log Entries</h2>
            <form method="POST" class="search-form">
                <div class="search-container">
                    <input type="text" name="log_search" placeholder="Search by Student ID" value="<?php echo htmlspecialchars($log_search); ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
            <?php
            if (!$result) {
                echo "<p class='error'><i class='fas fa-exclamation-circle'></i> Error fetching log entries: " . mysqli_error($conn) . "</p>";
            } else {
                if (mysqli_num_rows($result) == 0) {
                    echo "<p>No log entries found.</p>";
                } else {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<div class='log-entry'>";
                        echo "<p><i class='fas fa-user'></i> Student: " . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['student_id']) . ")</p>";
                        echo "<p><i class='fas fa-comment'></i> Purpose: " . htmlspecialchars($row['purpose']) . "</p>";
                        echo "<p><i class='fas fa-calendar-alt'></i> Out: " . htmlspecialchars($row['out_date']) . " | In: " . htmlspecialchars($row['in_date']) . "</p>";
                        echo "<p><i class='fas fa-clock'></i> Verify Time: " . ($row['verify_time'] ? htmlspecialchars($row['verify_time']) : 'N/A') . "</p>";
                        echo "<p><i class='fas fa-sign-out-alt'></i> Check Out: " . ($row['check_out_time'] ? htmlspecialchars($row['check_out_time']) : 'N/A') . "</p>";
                        echo "<p><i class='fas fa-sign-in-alt'></i> Check In: " . ($row['check_in_time'] ? htmlspecialchars($row['check_in_time']) : 'N/A') . "</p>";
                        if (!$row['check_in_time']) {
                            ?>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                <button type="submit" name="check_in" value="check_in"><i class="fas fa-sign-in-alt"></i> Check In</button>
                            </form>
                            <?php
                        }
                        echo "</div>";
                    }
                }
            }
            ?>

            <!-- Pagination Links -->
            <div class="pagination">
                <?php
                $prev_page = $page - 1;
                $next_page = $page + 1;
                ?>
                <a href="?page=<?php echo $prev_page; ?>" class="<?php echo $page <= 1 ? 'disabled' : ''; ?>">Previous</a>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a href="?page=<?php echo $next_page; ?>" class="<?php echo $page >= $total_pages ? 'disabled' : ''; ?>">Next</a>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <div class="back-to-top" onclick="scrollToTop()"><i class="fas fa-arrow-up"></i></div>

    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Profile Modal
        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'flex';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('profileModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Hide Loader
        window.addEventListener('load', () => {
            document.getElementById('loader').style.display = 'none';
        });

        // Back to Top
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
    </script>
</body>
</html>
