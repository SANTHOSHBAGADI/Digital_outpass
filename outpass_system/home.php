<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outpass Management System - Home</title>
    <!-- External Libraries for Styling and Animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
            overflow-x: hidden;
        }

        /* Navigation Bar */
        .navbar {
            background: rgba(40, 167, 69, 0.9);
            padding: 15px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .navbar-logo {
            font-size: 1.8em;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .navbar-logo i {
            margin-right: 10px;
            animation: pulse 2s infinite;
        }

        .navbar-logo:hover {
            transform: scale(1.05);
        }

        .navbar-links a {
            color: #fff;
            text-decoration: none;
            margin: 0 20px;
            font-size: 1.1em;
            font-weight: 500;
            position: relative;
            transition: color 0.3s ease;
        }

        .navbar-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: #fff;
            bottom: -5px;
            left: 0;
            transition: width 0.3s ease;
        }

        .navbar-links a:hover::after {
            width: 100%;
        }

        .navbar-links a.active {
            font-weight: 600;
            border-bottom: 2px solid #fff;
        }

        /* Hero Section */
        .hero-section {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: url('passs.jpg') no-repeat center center/cover;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.7), rgba(40, 167, 69, 0.3));
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            color: #fff;
            padding: 20px;
        }

        .hero-content h1 {
            font-size: 4em;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            animation: zoomIn 1.5s ease-in-out;
        }

        .hero-content p {
            font-size: 1.5em;
            font-weight: 300;
            margin-bottom: 40px;
            animation: fadeInUp 1.5s ease-in-out 0.5s;
        }

        .hero-buttons .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.2em;
            font-weight: 500;
            margin: 0 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            animation: bounce 2s infinite;
        }

        .hero-buttons .btn:hover {
            background: #218838;
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        /* Features Section */
        .features-section {
            padding: 80px 20px;
            background: #fff;
            text-align: center;
            position: relative;
        }

        .features-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('circuit-pattern.png') repeat;
            opacity: 0.05;
            z-index: 0;
        }

        .section-title {
            font-size: 3em;
            font-weight: 700;
            margin-bottom: 50px;
            color: #28a745;
            position: relative;
            z-index: 1;
            animation: slideInDown 1s ease-in-out;
        }

        .features-grid {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .feature-card {
            background: linear-gradient(135deg, #f9f9f9, #e0e0e0);
            padding: 40px;
            border-radius: 15px;
            width: 320px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .feature-card i {
            font-size: 3em;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounce 1.5s infinite;
        }

        .feature-card h3 {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .feature-card p {
            font-size: 1em;
            color: #666;
        }

        /* Workflow Section */
        .workflow-section {
            padding: 80px 20px;
            background: linear-gradient(135deg, #f5f7fa, #e0e7ff);
            text-align: center;
        }

        .workflow-timeline {
            position: relative;
            padding: 20px 0;
            max-width: 800px;
            margin: 0 auto;
        }

        .workflow-timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: #28a745;
            transform: translateX(-50%);
        }

        .workflow-item {
            display: flex;
            align-items: center;
            margin: 40px 0;
            position: relative;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
            transition: all 0.3s ease;
        }

        .workflow-item:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .workflow-icon {
            width: 50px;
            height: 50px;
            background-color: #28a745;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 20px;
            position: absolute;
            left: -25px;
            transform: translateX(-50%);
        }

        .workflow-icon i {
            font-size: 1.5em;
            color: #fff;
            animation: rotate 3s infinite linear;
        }

        .workflow-card {
            flex: 1;
            padding-left: 40px;
        }

        .workflow-card h3 {
            font-size: 1.4em;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .workflow-card p {
            font-size: 0.95em;
            color: #666;
        }

        /* Footer */
        .landing-footer {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: #fff;
            padding: 50px 20px;
            text-align: center;
            position: relative;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 30px;
        }

        .footer-section h3 {
            font-size: 1.4em;
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            width: 50px;
            height: 2px;
            background: #fff;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
        }

        .footer-section p, .footer-section ul {
            font-size: 1em;
            font-weight: 300;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 12px;
        }

        .footer-section a {
            color: #e0e0e0;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: #fff;
        }

        .social-icons a {
            color: #fff;
            margin: 0 15px;
            font-size: 1.5em;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            color: #e0e0e0;
            transform: rotate(360deg);
        }

        .footer-bottom {
            margin-top: 30px;
            font-size: 0.9em;
            font-weight: 300;
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .back-to-top.show {
            opacity: 1;
        }

        .back-to-top:hover {
            transform: scale(1.1);
            background: #218838;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .back-to-top i {
            animation: bounce 1.5s infinite;
        }

        /* Custom Animations */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        @keyframes zoomIn {
            0% { transform: scale(0.5); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @keyframes rotate {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
           }

        @keyframes slideInDown {
            0% { transform: translateY(-100px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideInLeft {
            0% { transform: translateX(-100px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideInRight {
            0% { transform: translateX(100px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        @keyframes glow {
            0% { box-shadow: 0 0 5px rgba(40, 167, 69, 0.5); }
            50% { box-shadow: 0 0 20px rgba(40, 167, 69, 0.8); }
            100% { box-shadow: 0 0 5px rgba(40, 167, 69, 0.5); }
        }

        /* Responsive Design for Workflow Section */
        @media (max-width: 768px) {
            .workflow-timeline::before {
                left: 25px;
            }

            .workflow-item {
                width: 90%;
                margin-left: 50px;
            }

            .workflow-icon {
                left: 0;
                transform: translateX(0);
            }

            .workflow-card {
                padding-left: 60px;
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .navbar-container {
                max-width: 1000px;
            }

            .features-grid {
                gap: 40px;
            }

            .feature-card {
                width: 300px;
            }
        }

        @media (max-width: 1024px) {
            .navbar-container {
                flex-direction: column;
                gap: 15px;
            }

            .navbar-links a {
                margin: 0 10px;
                font-size: 1em;
            }

            .hero-content h1 {
                font-size: 3em;
            }

            .hero-content p {
                font-size: 1.2em;
            }

            .hero-buttons .btn {
                padding: 12px 30px;
                font-size: 1em;
            }

            .section-title {
                font-size: 2.5em;
            }

            .feature-card {
                width: 280px;
            }
        }

        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                gap: 10px;
            }

            .navbar-links {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }

            .navbar-links a {
                margin: 5px 10px;
                font-size: 0.9em;
            }

            .hero-section {
                height: 80vh;
            }

            .hero-content h1 {
                font-size: 2.5em;
            }

            .hero-content p {
                font-size: 1em;
            }

            .hero-buttons .btn {
                padding: 10px 25px;
                font-size: 0.9em;
            }

            .section-title {
                font-size: 2em;
            }

            .feature-card {
                width: 100%;
                max-width: 350px;
            }
        }

        @media (max-width: 480px) {
            .navbar-logo {
                font-size: 1.5em;
            }

            .navbar-links a {
                margin: 5px 8px;
                font-size: 0.8em;
            }

            .hero-content h1 {
                font-size: 2em;
            }

            .hero-content p {
                font-size: 0.9em;
            }

            .hero-buttons .btn {
                padding: 8px 20px;
                font-size: 0.8em;
            }

            .section-title {
                font-size: 1.8em;
            }

            .feature-card {
                padding: 20px;
            }

            .back-to-top {
                width: 50px;
                height: 50px;
                bottom: 20px;
                right: 20px;
            }

            .social-icons a {
                margin: 0 10px;
                font-size: 1.2em;
            }
        }

        @media (max-width: 360px) {
            .navbar-logo {
                font-size: 1.3em;
            }

            .navbar-links a {
                margin: 5px 6px;
                font-size: 0.7em;
            }

            .hero-content h1 {
                font-size: 1.8em;
            }

            .hero-content p {
                font-size: 0.8em;
            }

            .hero-buttons .btn {
                padding: 6px 15px;
                font-size: 0.7em;
            }

            .section-title {
                font-size: 1.5em;
            }

            .feature-card {
                padding: 15px;
            }

            .footer-section h3 {
                font-size: 1.2em;
            }

            .footer-section p, .footer-section ul {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body class="home-page">
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <h1 class="navbar-logo"><i class="fas fa-ticket-alt"></i> Outpass System</h1>
            <div class="navbar-links">
                <a href="home.php" class="active"><i class="fas fa-home"></i> Home</a>
                <a href="index.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content animate__animated animate__fadeInDown">
            <h1>Welcome to Outpass Management System</h1>
            <p>Streamline your outpass requests with ease and security.</p>
            <div class="hero-buttons">
                <a href="index.php" class="btn hero-btn"><i class="fas fa-sign-in-alt"></i> Get Started</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <h2 class="section-title animate__animated animate__slideInDown">Our Features</h2>
        <div class="features-grid">
            <div class="feature-card animate__animated animate__fadeInLeft" style="animation-delay: 0.1s;">
                <i class="fas fa-user-graduate"></i>
                <h3>For Students</h3>
                <p>Submit and track outpass requests effortlessly.</p>
            </div>
            <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                <i class="fas fa-user-tie"></i>
                <h3>For HODS</h3>
                <p>Review and manage outpass requests with ease.</p>
            </div>
            <div class="feature-card animate__animated animate__fadeInRight" style="animation-delay: 0.5s;">
                <i class="fas fa-user-lock"></i>
                <h3>For Security</h3>
                <p>Verify student movements and manage logs.</p>
            </div>
        </div>
    </section>

    <!-- Workflow Section -->
    <section class="workflow-section">
        <h2 class="section-title animate__animated animate__slideInDown">How It Works</h2>
        <div class="workflow-timeline">
            <div class="workflow-item animate__animated animate__fadeInLeft" style="animation-delay: 0.1s;">
                <div class="workflow-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="workflow-card">
                    <h3>Students</h3>
                    <p>Submit outpass requests, track their status, and manage your request efficiently.</p>
                </div>
            </div>
            <div class="workflow-item animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                <div class="workflow-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="workflow-card">
                    <h3>HOD</h3>
                    <p>Review and approve/reject outpass requests with comments for clarity.</p>
                </div>
            </div>
            <div class="workflow-item animate__animated animate__fadeInRight" style="animation-delay: 0.5s;">
                <div class="workflow-icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <div class="workflow-card">
                    <h3>Security</h3>
                    <p>Verify student movements, manage check-ins/check-outs, and view logs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="footer-container">
            <div class="footer-section animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="index.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </div>
            <div class="footer-section animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                <h3>Contact Us</h3>
                <p>Email: <a href="mailto:srisivanicollege25@gmail.com">srisivanicollege25@gmail.com</a></p>
                <p>Phone: +91 8886371219</p>
            </div>
            <div class="footer-section animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <p class="footer-bottom animate__animated animate__fadeIn" style="animation-delay: 0.7s;">© 2025 Outpass Management System. All rights reserved.</p>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="btn back-to-top"><i class="fas fa-arrow-up"></i></button>

    <!-- JavaScript for Back to Top Button -->
    <script>
        // Show/Hide Back to Top Button on Scroll
        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('back-to-top');
            if (window.scrollY > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });

        // Smooth Scroll to Top
        document.getElementById('back-to-top').addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>