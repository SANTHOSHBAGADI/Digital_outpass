<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'student') {
    header("Location: index.php");
    exit();
}
require_once 'db_connect.php';
require_once 'send_email.php'; // Include PHPMailer functionality

$current_date = date('Y-m-d'); // Current date (March 25, 2025)

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $purpose = isset($_POST['purpose']) ? mysqli_real_escape_string($conn, $_POST['purpose']) : '';
    $out_date = isset($_POST['out_date']) ? $_POST['out_date'] : '';
    $in_date = isset($_POST['in_date']) ? $_POST['in_date'] : '';
    $parent_phone = isset($_POST['parent_phone']) ? mysqli_real_escape_string($conn, $_POST['parent_phone']) : '';
    $student_id = $_SESSION['user_id'];
    $request_date = date('Y-m-d H:i:s');

    if (empty($purpose) || empty($out_date) || empty($in_date) || empty($parent_phone)) {
        $error = "All fields are required";
    } elseif (strtotime($out_date) < strtotime($current_date)) {
        $error = "Out date cannot be in the past. Please select today or a future date.";
    } elseif (strtotime($out_date) >= strtotime($in_date)) {
        $error = "Out date must be before in date";
    } elseif (!preg_match("/^[0-9]{10}$/", $parent_phone)) {
        $error = "Parent phone must be a valid 10-digit number";
    } else {
        $query = "INSERT INTO outpass_requests (student_id, request_date, purpose, out_date, in_date) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $student_id, $request_date, $purpose, $out_date, $in_date);
        
        if (mysqli_stmt_execute($stmt)) {
            // Get the student's email and name
            $query = "SELECT email, name FROM students WHERE student_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            $student_email = $student['email'];
            $student_name = $student['name'];

            // Get the admin's email
            $query = "SELECT email FROM admins LIMIT 1";
            $result = $conn->query($query);
            $success_message = "Request submitted successfully";
            if ($result && $result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                $admin_email = $admin['email'];

                // Send email to admin
                $subject = "New Outpass Request Submitted by $student_name";
                $body = "
                    <h2>New Outpass Request</h2>
                    <p><strong>Student Name:</strong> $student_name</p>
                    <p><strong>Student ID:</strong> $student_id</p>
                    <p><strong>Purpose:</strong> $purpose</p>
                    <p><strong>Out Date:</strong> $out_date</p>
                    <p><strong>In Date:</strong> $in_date</p>
                    <p><strong>Request Date:</strong> $request_date</p>
                    <p>Please review the request in the Outpass Management System.</p>
                ";
                $email_result = sendEmail($admin_email, $subject, $body);
                if(is_array($email_result)) {                if ($email_result['success']) { // Line 66
                    $success_message .= " Admin has been notified.";
                } else {
                    $success_message .= " Failed to notify admin via email: " . $email_result['error']; // Line 69
                }
              } 
            } else {
                $success_message .= " No admin found to notify.";
            }

            // Send confirmation email to student
            $subject = "Outpass Request Submitted - Outpass Management System";
            $body = "
                <h2>Outpass Request Confirmation</h2>
                <p>Dear $student_name,</p>
                <p>Your outpass request has been submitted successfully and is awaiting admin approval.</p>
                <p><strong>Purpose:</strong> $purpose</p>
                <p><strong>Out Date:</strong> $out_date</p>
                <p><strong>In Date:</strong> $in_date</p>
                <p><strong>Request Date:</strong> $request_date</p>
                <p>You will be notified once your request is approved or rejected.</p>
            ";
            $email_result = sendEmail($student_email, $subject, $body);
            if (is_array($email_result)) {
            if (!$email_result['success']) { // Line 88
                $success_message .= " Failed to send confirmation email to student: " . $email_result['error']; // Line 89
            }
        }
            header("Location: student_dashboard.php?success=" . urlencode($success_message));
            exit();
        } else {
            $error = "Error submitting request";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Outpass</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('background.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            margin: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input, textarea {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        textarea {
            resize: vertical;
            height: 100px;
        }
        button {
            padding: 10px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #218838;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .back-btn:hover {
            background-color: #218838;
        }
        .error {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Request Outpass</h2>
        <?php if (isset($error)) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
        <form method="POST" action="request_outpass.php">
            <textarea name="purpose" placeholder="Purpose of Outpass" required></textarea>
            <input type="date" name="out_date" min="<?php echo $current_date; ?>" required>
            <input type="date" name="in_date" required>
            <input type="text" name="parent_phone" placeholder="Parent Phone (10 digits)" pattern="[0-9]{10}" required>
            <button type="submit"><i class="fas fa-paper-plane"></i> Submit Request</button>
        </form>
        <a href="student_dashboard.php" class="back-btn">Back to Dashboard</a>
    </div>
</body>
</html>