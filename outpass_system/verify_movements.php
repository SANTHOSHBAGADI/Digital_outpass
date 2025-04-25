<?php
session_start();
include 'db_connect.php'; // Your database connection file
include 'send_email.php'; // Include the email sending function (PHPMailer files are already included in send_email.php)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming the security personnel is logged in
    $request_id = $_POST['request_id'];
    $movement_type = $_POST['movement_type']; // 'check-in' or 'check-out'
    $timestamp = date('Y-m-d H:i:s');

    // Log the movement in the database (assuming you have a movements table)
    $query = "INSERT INTO movements (request_id, movement_type, timestamp) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iss', $request_id, $movement_type, $timestamp);
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

    // Get the admin's email
    $query = "SELECT email FROM users WHERE role = 'admin' LIMIT 1";
    $result = $conn->query($query);
    $admin = $result->fetch_assoc();
    $admin_email = $admin['email'];

    // Send email to student
    $subject = "Movement Verified: $movement_type";
    $body = "
        <h2>Movement Verification</h2>
        <p>Dear $student_name,</p>
        <p>Your $movement_type has been verified by security at $timestamp.</p>
        <p>Check the Outpass Management System for more details.</p>
    ";
    sendEmail($student_email, $subject, $body);

    // Send email to admin
    $subject = "Student Movement Verified: $student_name";
    $body = "
        <h2>Student Movement Update</h2>
        <p><strong>Student Name:</strong> $student_name</p>
        <p><strong>Movement Type:</strong> $movement_type</p>
        <p><strong>Timestamp:</strong> $timestamp</p>
        <p>Check the Outpass Management System for more details.</p>
    ";
    sendEmail($admin_email, $subject, $body);

    // Redirect back to the security dashboard
    header("Location: security_dashboard.php");
    exit();
}
?>