<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'student') {
    header("Location: index.php");
    exit();
}
require_once 'db_connect.php';

// Handle cancellation request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_request'])) {
    $request_id = $_POST['request_id'];
    
    $query = "DELETE FROM outpass_requests WHERE id = ? AND student_id = ? AND status = 'pending'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $request_id, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt) && mysqli_affected_rows($conn) > 0) {
        $success = "Outpass request cancelled successfully.";
    } else {
        $error = "Failed to cancel request or request is not pending.";
    }
}

// Check if student has already made a request today
$today = date('Y-m-d');
$query = "SELECT COUNT(*) as request_count FROM outpass_requests WHERE student_id = ? AND DATE(request_date) = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $_SESSION['user_id'], $today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$request_count = mysqli_fetch_assoc($result)['request_count'];

// Fetch student details including parent information
$query = "SELECT parent_name, parent_phone, parent_email FROM students WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
    <h1><i class="fas fa-user-graduate"></i>welcome <?php echo htmlspecialchars($_SESSION['user_id']); ?>!</h1>
        <?php 
        if (isset($error)) echo "<p class='error'>$error</p>";
        if (isset($success)) echo "<p class='success'>$success</p>";
        if (isset($_GET['success'])) echo "<p class='success'>" . htmlspecialchars($_GET['success']) . "</p>";
        ?>
        <h2>Request Outpass</h2>
        <?php if ($request_count > 0): ?>
            <p class='error'>You have already submitted an outpass request today. Only one request per day is allowed.</p>
        <?php else: ?>
            <form action="request_outpass.php" method="POST">
                <input type="text" name="student_id" value="<?php echo $_SESSION['user_id']; ?>" readonly>
                <input type="text" name="purpose" placeholder="Purpose" required>
                <input type="datetime-local" name="out_date" required>
                <input type="datetime-local" name="in_date" required>
                <input type="text" name="parent_phone" value="<?php echo $student['parent_phone']; ?>" readonly>
                <button type="submit"><i class="fas fa-plus-circle"></i>Request Outpass</button>
            </form>
        <?php endif; ?>

        <h2>Your Requests</h2>
        <?php
        $query = "SELECT * FROM outpass_requests WHERE student_id = ? ORDER BY request_date DESC";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 0) {
            echo "<p></p>";
        } else {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<div class='request'>";
                echo "<p><i class='fas fa-id-card'></i> Student ID: " . htmlspecialchars($row['student_id']) . "</p>";
                echo "<p><i class='fas fa-comment'></i> Purpose: " . htmlspecialchars($row['purpose']) . "</p>";
                echo "<p><i class='fas fa-calendar-alt'></i> Out: " . htmlspecialchars($row['out_date']) . " | In: " . htmlspecialchars($row['in_date']) . "</p>";
                echo "<p><i class='fas fa-user'></i> Parent Name: " . htmlspecialchars($student['parent_name'] ?? 'N/A') . "</p>";
                echo "<p><i class='fas fa-phone'></i> Parent Phone: " . htmlspecialchars($student['parent_phone'] ?? 'N/A') . "</p>";
                if (!empty($student['parent_email'])) {
                    echo "<p><i class='fas fa-envelope'></i> Parent Email: " . htmlspecialchars($student['parent_email']) . "</p>";
                }
                echo "<p><i class='fas fa-info-circle'></i> Status: " . htmlspecialchars($row['status']) . "</p>";
                if ($row['admin_comment']) {
                    echo "<p><i class='fas fa-comment-dots'></i> Comment: " . htmlspecialchars($row['admin_comment']) . "</p>";
                }
                if ($row['security_verified_at']) { 
                    echo "<p><i class='fas fa-check-circle'></i> Verified at: " . htmlspecialchars($row['security_verified_at']) . "</p>";
                }
                
                if ($row['status'] == 'pending') {
                    echo "<form method='POST' onsubmit='return confirm(\"Are you sure you want to cancel this request?\");'>";
                    echo "<input type='hidden' name='request_id' value='" . $row['id'] . "'>";
                    echo "<button type='submit' name='cancel_request'><i class='fas fa-times-circle'></i> Cancel Request</button>";
                    echo "</form>";
                }
                
                echo "</div>";
            }
        }
        ?>
        <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>
    <script src="js/script.js"></script>
</body>
</html>