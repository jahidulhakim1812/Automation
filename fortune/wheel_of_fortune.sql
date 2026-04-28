-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 12:31 PM
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
-- Database: `wheel_of_fortune`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(2, 'admin', 'admin12345', 'mdjhk19@gmail.com', '2026-03-27 08:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `admin_id`, `token`, `code`, `expires_at`, `used`, `created_at`) VALUES
(1, 2, '76e2c5a43228209ed73dde1948b01ad7989c57d00ecfbe325653d372a9f04671', NULL, '2026-03-27 11:02:24', 0, '2026-03-27 09:02:24'),
(2, 2, '47d9f1b425d1415c60e30a662079546f254d596f0a6a3fa4f96b6aeea3b7cb78', NULL, '2026-03-27 11:06:41', 0, '2026-03-27 09:06:41'),
(3, 2, '95b341b44c95e6628d282b20a37737d4407353482219a25b0355ad9f6f37baeb', NULL, '2026-03-27 11:06:46', 0, '2026-03-27 09:06:46'),
(4, 2, '5553ed1ea293e30214656ef9b1d7e1439be9fc25f926c934e3f9b488bffd9824', NULL, '2026-03-27 13:10:48', 0, '2026-03-27 11:10:48'),
(5, 2, '21fb71735f9af58cb39c715f1f97b938', '989650', '2026-03-27 12:31:06', 0, '2026-03-27 11:16:06'),
(6, 2, 'eefed0f48314b7549cba268ded503afe', '938957', '2026-03-27 12:31:17', 0, '2026-03-27 11:16:17'),
(7, 2, '4ec253d6b3965ca5dd336718f353fb2e', '957497', '2026-03-27 12:32:17', 0, '2026-03-27 11:17:17'),
(8, 2, '0047286965ae3a2c6c37137b62120d2d', '377229', '2026-03-27 12:33:21', 0, '2026-03-27 11:18:21'),
(9, 2, '38c39439559445cb2d8035a4bf622ddc', '891064', '2026-03-27 12:34:38', 0, '2026-03-27 11:19:38'),
(10, 2, '6c6c88b713cccf6afb8432c9a279ed68', '281662', '2026-03-27 12:36:27', 0, '2026-03-27 11:21:27'),
(11, 2, '372033e35783e14f42d5ceceed886e30', '208715', '2026-03-27 12:40:32', 0, '2026-03-27 11:25:32');

-- --------------------------------------------------------

--
-- Table structure for table `spin_logs`
--

CREATE TABLE `spin_logs` (
  `id` int(11) NOT NULL,
  `segment_id` int(11) NOT NULL COMMENT 'Foreign key to wheel_segments',
  `prize_label` varchar(100) NOT NULL COMMENT 'Snapshot of prize text at spin time',
  `ip_address` varchar(45) NOT NULL COMMENT 'User IP address',
  `user_agent` text DEFAULT NULL COMMENT 'Browser user agent',
  `spin_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spin_logs`
--

INSERT INTO `spin_logs` (`id`, `segment_id`, `prize_label`, `ip_address`, `user_agent`, `spin_time`) VALUES
(1, 11, 'CIRCLE RUN&SAVE!', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 14:34:13'),
(2, 9, '5% OFF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 14:34:16'),
(3, 14, 'QUICK SAVINGS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 14:34:20'),
(4, 12, 'START FINISH', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 14:34:23'),
(5, 10, 'SMALL DISCOUNT', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 14:34:25'),
(6, 6, 'TECH HUB', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 17:19:13'),
(7, 3, 'TESTHUB', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 17:24:00'),
(8, 5, '50% OFF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 17:24:03'),
(9, 13, 'POPULAR 10% ITEMS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 17:24:05'),
(10, 9, '5% OFF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 17:24:08');

-- --------------------------------------------------------

--
-- Table structure for table `wheel_segments`
--

CREATE TABLE `wheel_segments` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL COMMENT 'Text shown on the wheel',
  `color` varchar(7) NOT NULL DEFAULT '#CCCCCC' COMMENT 'Hex color code',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Order around the wheel (lower = earlier)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wheel_segments`
--

INSERT INTO `wheel_segments` (`id`, `label`, `color`, `sort_order`, `created_at`) VALUES
(1, 'SPRINT', '#FF6B6B', 1, '2026-03-27 08:33:38'),
(2, 'FASTAPPAREL', '#4ECDC4', 2, '2026-03-27 08:33:38'),
(3, 'TESTHUB', '#45B7D1', 3, '2026-03-27 08:33:38'),
(4, '100% OFF', '#96CEB4', 4, '2026-03-27 08:33:38'),
(5, '50% OFF', '#FFEAA7', 5, '2026-03-27 08:33:38'),
(6, 'TECH HUB', '#DDA0DD', 6, '2026-03-27 08:33:38'),
(7, 'GRAND PRIZE!', '#FFB347', 7, '2026-03-27 08:33:38'),
(8, '20% SUPER DEALS', '#FF6F61', 8, '2026-03-27 08:33:38'),
(9, '5% OFF', '#6B5B95', 9, '2026-03-27 08:33:38'),
(10, 'SMALL DISCOUNT', '#88B04B', 10, '2026-03-27 08:33:38'),
(11, 'CIRCLE RUN&SAVE!', '#F7CAC9', 11, '2026-03-27 08:33:38'),
(12, 'START FINISH', '#92A8D1', 12, '2026-03-27 08:33:38'),
(13, 'POPULAR 10% ITEMS', '#F4A261', 13, '2026-03-27 08:33:38'),
(14, 'QUICK SAVINGS', '#E9C46A', 14, '2026-03-27 08:33:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `spin_logs`
--
ALTER TABLE `spin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `segment_id` (`segment_id`);

--
-- Indexes for table `wheel_segments`
--
ALTER TABLE `wheel_segments`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `spin_logs`
--
ALTER TABLE `spin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wheel_segments`
--
ALTER TABLE `wheel_segments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spin_logs`
--
ALTER TABLE `spin_logs`
  ADD CONSTRAINT `spin_logs_ibfk_1` FOREIGN KEY (`segment_id`) REFERENCES `wheel_segments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
