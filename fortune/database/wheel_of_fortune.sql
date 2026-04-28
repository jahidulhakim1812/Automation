-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2026 at 05:32 PM
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
(3, 'admin', 'admin123', NULL, '2026-03-27 15:22:16');

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

-- --------------------------------------------------------

--
-- Table structure for table `segments`
--

CREATE TABLE `segments` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `segments`
--

INSERT INTO `segments` (`id`, `label`, `color`) VALUES
(1, '10 % Discount', '#36a14b'),
(2, '0 % Discount', '#f9844a'),
(3, '15% Discount', '#f9c74f'),
(4, 'Full Free', '#90be6d'),
(5, '5 % Discount', '#43aa8b'),
(6, '50% Discount', '#4d908e'),
(7, '25 % Discount', '#577590'),
(8, 'Spin Again', '#9B5DE5');

-- --------------------------------------------------------

--
-- Table structure for table `spins`
--

CREATE TABLE `spins` (
  `id` int(11) NOT NULL,
  `segment_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `prize_label` varchar(200) DEFAULT NULL,
  `spin_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spins`
--

INSERT INTO `spins` (`id`, `segment_id`, `name`, `mobile`, `prize_label`, `spin_time`) VALUES
(2, 2, 'jahid', '01957288639', 'Free Coffee', '2026-04-26 21:17:10'),
(3, 2, 'jgghh', '8464564564', 'Free Coffee', '2026-04-26 21:19:51'),
(5, 7, 'shaharia', '0155525464', '25 % Discount', '2026-04-26 22:26:48'),
(6, 5, 'gfgasg', '6464654564165', '5 % Discount', '2026-04-26 22:28:44'),
(7, 5, 'bulbul', '01980983018', '5 % Discount', '2026-04-27 18:07:53'),
(8, 6, 'jahdi', '3473674343', '50% Discount', '2026-04-27 20:23:46');

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
(10, 9, '5% OFF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 17:24:08'),
(11, 14, 'QUICK SAVINGS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 21:13:47'),
(12, 7, 'GRAND PRIZE!', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 22:37:03'),
(13, 10, 'SMALL DISCOUNT', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 22:37:09'),
(14, 6, 'TECH HUB', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 22:37:25'),
(15, 8, '20% SUPER DEALS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 22:37:33'),
(16, 3, 'TESTHUB', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 22:37:36'),
(17, 9, '5% OFF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 19:32:31'),
(18, 13, 'POPULAR 10% ITEMS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 18:37:39'),
(19, 13, 'POPULAR 10% ITEMS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 18:50:06'),
(20, 6, 'TECH HUB', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 18:50:12'),
(21, 13, 'POPULAR 10% ITEMS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 18:50:27'),
(22, 12, 'START FINISH', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 18:50:32');

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
(1, 'ggg', '#fff36b', 1, '2026-03-27 08:33:38'),
(2, 'ffffff', '#4ecdc4', 0, '2026-03-27 08:33:38'),
(3, 'TESTHUB', '#45B7D1', 2, '2026-03-27 08:33:38'),
(4, '100% OFF', '#96CEB4', 3, '2026-03-27 08:33:38'),
(5, '50% OFF', '#FFEAA7', 4, '2026-03-27 08:33:38'),
(6, 'TECH HUB', '#DDA0DD', 5, '2026-03-27 08:33:38'),
(7, 'GRAND PRIZE!', '#FFB347', 6, '2026-03-27 08:33:38'),
(8, '20% SUPER DEALS', '#FF6F61', 7, '2026-03-27 08:33:38'),
(9, '5% OFF', '#6B5B95', 8, '2026-03-27 08:33:38'),
(10, 'SMALL DISCOUNT', '#88B04B', 9, '2026-03-27 08:33:38'),
(11, 'CIRCLE RUN&SAVE!', '#F7CAC9', 10, '2026-03-27 08:33:38'),
(12, 'START FINISH', '#92A8D1', 11, '2026-03-27 08:33:38'),
(13, 'POPULAR 10% ITEMS', '#F4A261', 12, '2026-03-27 08:33:38'),
(14, 'QUICK SAVINGS', '#E9C46A', 13, '2026-03-27 08:33:38');

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
-- Indexes for table `segments`
--
ALTER TABLE `segments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `spins`
--
ALTER TABLE `spins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile_unique` (`mobile`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `segments`
--
ALTER TABLE `segments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `spins`
--
ALTER TABLE `spins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `spin_logs`
--
ALTER TABLE `spin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

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
