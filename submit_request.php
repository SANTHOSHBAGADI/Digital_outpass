<?php
session_start();
include 'db_connect.php'; // Your database connection file
include 'send_email.php'; // Include the email sending function (PHPMailer files are already included in send_email.php)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming the student is logged in and their ID is stored in the session
    $student_id = $_SESSION['user_id'];
    $purpose = $_POST['purpose'];
    $out_date = $_POST['out_date'];
    $in_date = $_POST['in_date'];

    // Insert the request into the database
    $query = "INSERT INTO outpass_requests (student_id, purpose, out_date, in_date, status) VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isss', $student_id, $purpose, $out_date, $in_date);
    $stmt->execute();

    // Get the student's email and name (assuming you have a users table)
    $query = "SELECT email, name FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $student_email = $student['email'];
    $student_name = $student['name'];

    // Get the admin's email (assuming admins have a role of 'admin' in the users table)
    $query = "SELECT email FROM users WHERE role = 'admin' LIMIT 1";
    $result = $conn->query($query);
    $admin = $result->fetch_assoc();
    $admin_email = $admin['email'];

    // Send email to admin
    $subject = "New Outpass Request Submitted by $student_name";
    $body = "
        <h2>New Outpass Request</h2>
        <p><strong>Student Name:</strong> $student_name</p>
        <p><strong>Purpose:</strong> $purpose</p>
        <p><strong>Out Date:</strong> $out_date</p>
        <p><strong>In Date:</strong> $in_date</p>
        <p>Please review the request in the Outpass Management System.</p>
    ";
    if (sendEmail($admin_email, $subject, $body)) {
        $_SESSION['success'] = "Request submitted successfully! Admin has been notified.";
    } else {
        $_SESSION['error'] = "Request submitted, but failed to notify admin via email.";
    }

    // Redirect back to the student dashboard or home page
    header("Location: student_dashboard.php");
    exit();
}
?>