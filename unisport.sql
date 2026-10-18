-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 18, 2026 at 09:29 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `unisport`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(12) DEFAULT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `field_id` int(10) UNSIGNED NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `participants` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `purpose` varchar(100) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `code`, `user_id`, `field_id`, `booking_date`, `start_time`, `end_time`, `participants`, `purpose`, `notes`, `status`, `created_at`) VALUES
(1, 'BK0001', 5, 1, '2026-10-11', '20:00:00', '22:00:00', 10, 'ฝึกซ้อม', NULL, 'completed', '2026-10-10 18:11:23'),
(2, 'BK0002', 5, 4, '2026-10-14', '11:00:00', '13:00:00', 1, 'จัดกิจกรรม', NULL, 'completed', '2026-10-10 18:28:54'),
(3, 'BK0003', 5, 2, '2026-10-11', '08:00:00', '10:00:00', 7, 'แข่งขัน', NULL, 'completed', '2026-10-10 18:40:17');

-- --------------------------------------------------------

--
-- Table structure for table `fields`
--

CREATE TABLE `fields` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(80) NOT NULL,
  `sport_type` varchar(40) NOT NULL,
  `location` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `facilities` varchar(255) NOT NULL DEFAULT '',
  `status` enum('available','unavailable','maintenance') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fields`
--

INSERT INTO `fields` (`id`, `code`, `name`, `sport_type`, `location`, `description`, `image_url`, `facilities`, `status`, `created_at`) VALUES
(1, 'F001', 'สนามฟุตบอล 1', 'ฟุตบอล', 'อาคารกีฬากลาง', 'สนามหญ้าจริง ขนาดมาตรฐาน เหมาะสำหรับการแข่งขันและกิจกรรมต่างๆ', 'https://images.unsplash.com/photo-1575361204480-aadea25e6e68?w=800', 'ห้องน้ำ,ที่จอดรถ,ไฟส่องสว่าง,อุปกรณ์กีฬา', 'available', '2026-10-10 16:54:32'),
(2, 'F002', 'สนามบาสเกตบอล', 'บาสเกตบอล', 'โรงยิม 1', 'สนามในร่ม พื้นไม้มาตรฐาน', 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800', 'ห้องน้ำ,ไฟส่องสว่าง', 'available', '2026-10-10 16:54:32'),
(3, 'F003', 'สนามวอลเลย์บอล', 'วอลเลย์บอล', 'โรงยิม 2', 'สนามในร่ม พร้อมตาข่ายมาตรฐาน', 'https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?w=800', 'ห้องน้ำ,ที่จอดรถ,อุปกรณ์กีฬา', 'maintenance', '2026-10-10 16:54:32'),
(4, 'F004', 'สนามเทนนิส', 'เทนนิส', 'ลานกีฬากลางแจ้ง', 'สนามฮาร์ดคอร์ต 2 คอร์ต', 'https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?w=800', 'ที่จอดรถ,ไฟส่องสว่าง', 'available', '2026-10-10 16:54:32'),
(5, 'F005', 'สนามแบดมินตัน', 'แบดมินตัน', 'โรงยิม 3', 'สนามในร่ม 4 คอร์ต', 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800', 'ห้องน้ำ', 'available', '2026-10-10 16:54:32'),
(12, 'F006', 'สนามเปตอง', 'อื่นๆ', 'ลานหน้าโรงยิม', 'สนามหินพร้อมขนาดสนามพอดี', 'uploads/fields/4545b669591e0506b7b18d5c.jpg', 'ที่จอดรถ,อุปกรณ์กีฬา', 'available', '2026-10-10 18:34:28'),
(13, 'F007', 'สนามกรีฑา', 'อื่นๆ', 'สเตเดี่ยม', 'สนามกรีฑาแบบมาตรฐาน', 'uploads/fields/0fa0b67bde673b2edb3559ac.jpg', 'ห้องน้ำ,ที่จอดรถ', 'available', '2026-10-12 12:41:02');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(45) NOT NULL,
  `identity` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip`, `identity`, `created_at`) VALUES
(1, '::1', '406859025@yru.ac.th', '2026-10-10 17:06:22'),
(2, '::1', '406859025@yru.ac.th', '2026-10-10 17:06:31'),
(3, '::1', 'reset:406859025@yru.ac.th', '2026-10-10 17:06:43'),
(4, '::1', '406859025@yru.ac.th', '2026-10-10 17:07:27'),
(5, '::1', 'reset:406859025@yru.ac.th', '2026-10-10 17:07:46'),
(6, '::1', 'reset:406859025@yru.ac.th', '2026-10-10 17:19:15'),
(7, '::1', '406859025@yru.ac.th', '2026-10-10 17:19:44'),
(8, '::1', '406859025@yru.ac.th', '2026-10-10 17:20:14'),
(9, '::1', '406859025@yru.ac.th', '2026-10-10 17:20:25'),
(10, '::1', '406859025@yru.ac.th', '2026-10-10 17:21:27'),
(11, '::1', '406859025@yru.ac.th', '2026-10-10 17:21:35'),
(12, '::1', 'reset:406859025@yru.ac.th', '2026-10-10 17:21:46'),
(13, '::1', '406859025@yru.ac.th', '2026-10-10 17:22:02'),
(14, '::1', '406859025@yru.ac.th', '2026-10-10 17:32:17'),
(15, '::1', '406859025@yru.ac.th', '2026-10-10 17:32:25'),
(16, '::1', '406859025@yru.ac.th', '2026-10-10 17:32:31'),
(17, '::1', 'reset:406859025@yru.ac.th', '2026-10-10 17:32:43'),
(18, '::1', 'reset:406859025@yru.ac.th', '2026-10-10 17:33:43'),
(19, '::1', '406859025@yru.ac.th', '2026-10-10 17:34:19'),
(20, '::1', 'reset:406859025@yru.ac.th', '2026-10-10 17:34:33'),
(21, '::1', '406859025@yru.ac.th', '2026-10-10 17:36:30'),
(22, '::1', '406859025@yru.ac.th', '2026-10-10 17:49:00'),
(23, '::1', '406859025@yru.ac.th', '2026-10-10 17:49:11'),
(24, '::1', '406859025@yru.ac.th', '2026-10-10 17:49:18'),
(25, '::1', '406859025@yru.ac.th', '2026-10-10 18:01:48'),
(26, '::1', '406859025@yru.ac.th', '2026-10-10 18:11:53'),
(27, '::1', 'admin@unisport.local', '2026-10-10 18:13:51'),
(28, '::1', 'admin@unisport.local', '2026-10-10 18:13:56'),
(29, '::1', '406859025@yru.ac.th', '2026-10-11 13:20:58');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `field_id` int(10) UNSIGNED DEFAULT NULL,
  `problem_type` varchar(60) NOT NULL,
  `details` varchar(1000) DEFAULT NULL,
  `found_date` date DEFAULT NULL,
  `found_time` time DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `status` enum('open','in_progress','resolved') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `field_id`, `problem_type`, `details`, `found_date`, `found_time`, `photo_url`, `status`, `created_at`) VALUES
(1, 5, 1, 'ไฟฟ้า/แสงสว่าง', NULL, '2026-10-11', '06:10:00', 'uploads/reports/160772cce0d69c91ee0315a4.jpg', 'resolved', '2026-10-10 18:10:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `member_code` varchar(30) DEFAULT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL,
  `username` varchar(30) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `member_code`, `first_name`, `last_name`, `email`, `username`, `phone`, `avatar_url`, `password_hash`, `role`, `created_at`) VALUES
(1, '025', 'ผู้ดูแล', 'ระบบ', 'admin@unisport.local', 'admin', NULL, NULL, '$2y$10$vtRMH5/jzEcQxqrGBdaENezpkTA2oSwo.2D5uWRXLb7O4uMN8HK/e', 'admin', '2026-10-10 16:54:32'),
(2, '6500001', 'สมาชิก', 'ทดลอง', 'student@unisport.local', 'student', NULL, NULL, '$2y$10$ndfekZnvveo8Fk9LOCcpfu9BgqiMOUv1E9qerxyclnR.i3I/jLe4G', 'user', '2026-10-10 16:54:32'),
(5, '406859025', 'หุสนา', 'กามะ', '406859025@yru.ac.th', 'HusnaKama', '0808714733', 'uploads/avatars/cda6986c2c79fc179edade42.jpg', '$2y$10$ITA4twHp2cZuyo2TXWzE/.J37lJcQn1E2F1qHVOZECWBbmzmMRMhu', 'user', '2026-10-10 17:50:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_bookings_code` (`code`),
  ADD KEY `idx_bookings_slot` (`field_id`,`booking_date`,`status`),
  ADD KEY `idx_bookings_user` (`user_id`,`booking_date`);

--
-- Indexes for table `fields`
--
ALTER TABLE `fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fields_code` (`code`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attempt_ip` (`ip`,`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reset_token` (`token_hash`),
  ADD KEY `fk_reset_user` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_status` (`status`,`created_at`),
  ADD KEY `fk_reports_user` (`user_id`),
  ADD KEY `fk_reports_field` (`field_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fields`
--
ALTER TABLE `fields`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
