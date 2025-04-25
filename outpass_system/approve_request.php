<?php
session_start();
include 'db_connect.php'; // Your database connection file
include 'send_email.php'; // Include the email sending function (PHPMailer files are already included in send_email.php)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming the admin is logged in and their ID is stored in the session
    $request_id = $_POST['request_id'];
    $status = $_POST['status']; // 'approved' or 'rejected'
    $comments = $_POST['comments'];

    // Update the request status in the database
    $query = "UPDATE outpass_requests SET status = ?, comments = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssi', $status, $comments, $request_id);
    $stmt->execute();

    // Get the student's email and name
    $query = "SELECT u.email, u.name 
              FROM outpass_requests r 
              JOIN users u ON r.student_id = u.id 
              WHERE r.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $student_email = $student['email'];
    $student_name = $student['name'];

    // Send email to student
    $subject = "Outpass Request $status";
    $body = "
        <h2>Outpass Request Update</h2>
        <p>Dear $student_name,</p>
        <p>Your outpass request has been <strong>$status</strong>.</p>
        <p><strong>Admin Comments:</strong> $comments</p>
        <p>Check the Outpass Management System for more details.</p>
    ";
    if (sendEmail($student_email, $subject, $body)) {
        $_SESSION['success'] = "Request $status successfully! Student has been notified.";
    } else {
        $_SESSION['error'] = "Request $status, but failed to notify student via email.";
    }

    // Redirect back to the admin dashboard
    header("Location: admin_dashboard.php");
    exit();
}
?>