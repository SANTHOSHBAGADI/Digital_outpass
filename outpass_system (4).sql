-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2025 at 05:55 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `outpass_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--
-- Creation: Mar 26, 2025 at 04:47 AM
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `admin_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `department` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `admins`:
--

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `admin_id`, `name`, `email`, `password`, `phone`, `department`, `profile_picture`, `created_at`, `status`) VALUES
(4, 'CSE001', 'BAGADI SANTHOSH KUMAR', 'santhoshbagadi44@gmail.com', '$2y$10$m2b5S5zqJWq1frRyRfz0Nus2E23jJA.f/42NP5legT3RTqCRoj5yu', '8886371219', 'CSE', 'uploads/1742981298_IMG_20221108_181239_502.jpg', '2025-03-26 08:54:20', 'active'),
(9, 'ECE001', 'PRAVALIKA R', 'pravalika@gmail.com', '$2y$10$/8xubDh0RX85.rrSwpKL0epa.P3FQ2tj5.9WWuxvPQPCKrGtVLLRK', '0888637121', 'ECE', NULL, '2025-04-10 14:23:44', 'active'),
(10, 'MEC001', 'PRASANNA ', 'pavan@gmail.com', '$2y$10$pxmIovoWMUqgIXgRYkpC3.DHWvXr9LMCdIYLyy..iN0uROO5jIeMC', '9888637121', 'MECH', NULL, '2025-04-10 14:50:01', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `log_book`
--
-- Creation: Mar 22, 2025 at 05:38 PM
--

CREATE TABLE `log_book` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `verify_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `log_book`:
--   `request_id`
--       `outpass_requests` -> `id`
--   `student_id`
--       `students` -> `student_id`
--

--
-- Dumping data for table `log_book`
--

INSERT INTO `log_book` (`id`, `request_id`, `student_id`, `check_in_time`, `check_out_time`, `verify_time`) VALUES
(32, 59, '21W61A0506', '2025-04-18 17:09:36', NULL, '2025-04-14 07:36:02'),
(33, 61, '21W61A0505', '2025-04-18 17:09:38', '2025-04-14 19:42:38', '2025-04-14 19:42:39'),
(34, 62, '21W61A0506', '2025-04-18 17:09:40', '2025-04-18 17:09:28', '2025-04-18 17:09:30'),
(35, 63, '21W61A0505', '2025-04-25 15:30:32', '2025-04-25 15:30:25', '2025-04-25 15:30:27');

-- --------------------------------------------------------

--
-- Table structure for table `managers`
--
-- Creation: Apr 10, 2025 at 07:47 AM
--

CREATE TABLE `managers` (
  `manager_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `managers`:
--

--
-- Dumping data for table `managers`
--

INSERT INTO `managers` (`manager_id`, `name`, `email`, `password`, `phone`, `created_at`) VALUES
('M001', 'santhu', '21w61a0506@srisivani.com', '$2y$10$4a2vZcqM83cjEnNBio0BmelmKtbVDrwfeMkDCnNvu1EBs7gHh8iJm', '1234567890', '2025-04-10 13:30:05');

-- --------------------------------------------------------

--
-- Table structure for table `otp_resets`
--
-- Creation: Apr 05, 2025 at 06:43 PM
--

CREATE TABLE `otp_resets` (
  `email` varchar(100) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_type` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `otp_resets`:
--

--
-- Dumping data for table `otp_resets`
--

INSERT INTO `otp_resets` (`email`, `otp`, `expires`, `user_type`, `created_at`) VALUES
('santhoshbagadi44@gmail.com', '345190', '2025-04-10 10:25:25', '', '2025-04-10 13:45:25');

-- --------------------------------------------------------

--
-- Table structure for table `outpass_requests`
--
-- Creation: Mar 20, 2025 at 09:52 AM
--

CREATE TABLE `outpass_requests` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `request_date` datetime NOT NULL,
  `purpose` text NOT NULL,
  `out_date` datetime NOT NULL,
  `in_date` datetime NOT NULL,
  `status` enum('pending','approved','rejected','verified') DEFAULT 'pending',
  `admin_comment` text DEFAULT NULL,
  `security_verified_at` datetime DEFAULT NULL,
  `parent_notified` tinyint(1) DEFAULT 0,
  `student_notified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `outpass_requests`:
--   `student_id`
--       `students` -> `student_id`
--

--
-- Dumping data for table `outpass_requests`
--

INSERT INTO `outpass_requests` (`id`, `student_id`, `request_date`, `purpose`, `out_date`, `in_date`, `status`, `admin_comment`, `security_verified_at`, `parent_notified`, `student_notified`, `created_at`) VALUES
(57, '21W61A0506', '2025-04-10 16:07:23', 'jvd amount', '2025-04-11 19:37:00', '2025-04-12 19:37:00', 'verified', '', '2025-04-10 16:13:16', 0, 0, '2025-04-10 14:07:23'),
(58, '21W61A0590', '2025-04-10 16:26:12', 'jvd amount', '2025-04-11 19:56:00', '2025-04-12 19:56:00', 'verified', '', '2025-04-10 16:28:55', 0, 0, '2025-04-10 14:26:12'),
(59, '21W61A0506', '2025-04-14 07:32:21', 'jvd amount', '2025-04-14 11:01:00', '2025-04-14 16:02:00', 'verified', '', '2025-04-14 07:36:02', 0, 0, '2025-04-14 05:32:21'),
(61, '21W61A0505', '2025-04-14 19:41:15', 'jvd amount', '2025-04-15 23:11:00', '2025-04-16 23:11:00', 'verified', '', '2025-04-14 19:42:39', 0, 0, '2025-04-14 17:41:15'),
(62, '21W61A0506', '2025-04-18 17:06:56', 'jvd amount', '2025-04-18 20:36:00', '2025-04-19 20:36:00', 'verified', '', '2025-04-18 17:09:30', 0, 0, '2025-04-18 15:06:56'),
(63, '21W61A0505', '2025-04-25 15:29:12', 'pain', '2025-04-26 18:59:00', '2025-05-03 18:59:00', 'verified', '', '2025-04-25 15:30:27', 0, 0, '2025-04-25 13:29:12');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--
-- Creation: Apr 05, 2025 at 04:28 PM
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `user_type` enum('student','admin','security') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `password_resets`:
--

-- --------------------------------------------------------

--
-- Table structure for table `secret_codes`
--
-- Creation: Apr 06, 2025 at 07:23 AM
--

CREATE TABLE `secret_codes` (
  `id` int(11) NOT NULL,
  `code_type` varchar(50) NOT NULL,
  `code_value` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `secret_codes`:
--

-- --------------------------------------------------------

--
-- Table structure for table `security`
--
-- Creation: Mar 26, 2025 at 05:12 AM
--

CREATE TABLE `security` (
  `id` int(11) NOT NULL,
  `security_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `security`:
--

--
-- Dumping data for table `security`
--

INSERT INTO `security` (`id`, `security_id`, `name`, `email`, `password`, `phone`, `profile_picture`, `created_at`, `status`) VALUES
(1, 'SEC001', 'santhu', 'santhoshbagadi44@gmail.com', '$2y$10$xr7N7sWNFRrM6ndaBQwgYe7Ihu4UhdRC5iWloU30caHoqhuDIyvc2', '9966014512', 'uploads/1744551744_1695560778588.jpg', '2025-03-26 05:16:17', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--
-- Creation: Mar 26, 2025 at 03:04 AM
--

CREATE TABLE `students` (
  `student_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(50) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `parent_name` varchar(100) NOT NULL,
  `parent_phone` varchar(15) NOT NULL,
  `parent_email` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `students`:
--

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `email`, `password`, `department`, `phone`, `parent_name`, `parent_phone`, `parent_email`, `status`) VALUES
('21W61A0505', 'V YAMUNAVATHI', 'yamuna@gmail.com', '$2y$10$EkTdrRE.yU3OJFtmmQmfnuLCxKVapp2MZGmAk1lnUA7AkAEDcFeUO', 'CSE', '2345678901', 'yamuna v ', '0989761234', NULL, 'Approved'),
('21W61A0506', 'BAGADI SANTHOSH KUMAR', 'santhoshbagadi44@gmail.com', '$2y$10$3xniSKH.krCrnQIJ/jSqxOs2Vh998RdLtCs6agV6hYf.FsZ4ahrk6', 'CSE', '8886371219', 'chanra shekar rao .B', '7601084578', NULL, 'Approved'),
('21W61A0507', 'Balivada sravya', 'sravya@gmail.com', '$2y$10$fDUVXl2JvVGXKi/bgUFmleEJMJEXtuhMlJwvWswfk54hrYOfmoatG', 'CSE', '8179298778', 'B MADHAVA', '8143487047', 'madava@gmail.com', 'Approved'),
('21W61A0590', 'BAVANI V', 'bavani@gmail.com', '$2y$10$pjgFTTlAvwxNbw3I1bFX4uxdyvZd2mzusrWRb.YKkeXm0JCI2xQ8C', 'ECE', '9966014511', 'k govindha naidu', '8889671214', NULL, 'Approved'),
('21W61A0591', 'PAVAN SAI', 'pavan@gmail.com', '$2y$10$OsOg2qLc53Pg/CnirwDabebJZBdxtQt7.HSCBB5BWUQAzlLQVflx.', 'CSE', '9876789543', 'k govindha naidu', '2344322345', NULL, 'Approved');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `log_book`
--
ALTER TABLE `log_book`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `managers`
--
ALTER TABLE `managers`
  ADD PRIMARY KEY (`manager_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_manager_email` (`email`);

--
-- Indexes for table `otp_resets`
--
ALTER TABLE `otp_resets`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `outpass_requests`
--
ALTER TABLE `outpass_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `secret_codes`
--
ALTER TABLE `secret_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `security`
--
ALTER TABLE `security`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `security_id` (`security_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `log_book`
--
ALTER TABLE `log_book`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `outpass_requests`
--
ALTER TABLE `outpass_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `secret_codes`
--
ALTER TABLE `secret_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security`
--
ALTER TABLE `security`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `log_book`
--
ALTER TABLE `log_book`
  ADD CONSTRAINT `log_book_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `outpass_requests` (`id`),
  ADD CONSTRAINT `log_book_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `outpass_requests`
--
ALTER TABLE `outpass_requests`
  ADD CONSTRAINT `outpass_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
