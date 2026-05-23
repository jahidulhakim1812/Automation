-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2026 at 05:23 PM
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
-- Database: `freelancing`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`, `profile_image`, `name`) VALUES
(1, '22303070@iubat.edu', 'admin123', '1776431037_22303070_iubat_edu.jpg', 'Jahid');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_email` varchar(100) NOT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `admin_email`, `admin_name`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, '22303070@iubat.edu', '', 'Student Added', 'Added new student ID: , Name: , Course: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:15:28'),
(2, '22303070@iubat.edu', '', 'Student Added', 'Added new student ID: , Name: , Course: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:15:30'),
(3, '22303070@iubat.edu', '', 'Student Added', 'Added new student ID: , Name: , Course: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:15:31'),
(4, '22303070@iubat.edu', '', 'Student Added', 'Added new student ID: , Name: , Course: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:15:34'),
(5, '22303070@iubat.edu', '', 'Student Added', 'Added new student ID: , Name: , Course: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:15:41');

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('background_image', 'uploads/banner.jpg', '2026-04-18 13:30:35'),
('dark_mode', '1', '2026-04-18 13:27:04'),
('sidebar_labels', '{\"dashboard\":\"\",\"account\":\"\",\"account_overview\":\"\",\"account_report\":\"\",\"change_password\":\"\",\"student_info\":\"\",\"add_student\":\"\",\"total_student_list\":\"\",\"student_form\":\"\",\"course_complete\":\"\",\"course_incomplete\":\"\",\"ongoing\":\"\",\"customers\":\"\",\"add_customer\":\"\",\"customer_list\":\"\",\"services\":\"\",\"manage_services\":\"\",\"assign_service\":\"\",\"delete\":\"\",\"report\":\"\",\"payment\":\"\",\"pos_invoice\":\"\",\"invoice_list\":\"\",\"print_invoice\":\"\",\"verify_invoice\":\"\",\"add_payment\":\"\",\"due_payment_list\":\"\",\"attendance\":\"\",\"take_attendance\":\"\",\"attendance_report\":\"\",\"certificate\":\"\",\"upload_certificate\":\"\",\"view_certificate\":\"\",\"video\":\"\",\"upload_video\":\"\",\"view_videos\":\"\",\"routine\":\"\"}', '2026-04-17 17:24:12');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `certificate_file` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `student_id`, `certificate_file`, `uploaded_at`) VALUES
(1, 250402, 'yeasin-hossain.jpg', '2025-09-11 05:04:27');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`, `created_at`) VALUES
(2, 'Rekha Akter', 'elitewearboutique.bd@gmail.com', '01609189529', 'boardbazar,khailkur38no woard', '2026-04-17 15:06:47'),
(3, 'Md.Ramjan Ali', 'alihairwig.bd@gmail.com', '01920-899031', 'Holdin no: 343/A,Sarker Bari,Uttar Khan,(Helal Market)., Dhaka, Bangladesh, 1230', '2026-04-18 14:53:07');

-- --------------------------------------------------------

--
-- Table structure for table `customer_services`
--

CREATE TABLE `customer_services` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `assigned_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `category`, `amount`, `expense_date`, `description`, `receipt_file`, `created_by`, `created_at`) VALUES
(1, 'Rent', 22500.00, '2026-04-18', 'June,July,August,September,October,November,December,January,February', NULL, '22303070@iubat.edu', '2026-04-18 13:29:07'),
(2, 'Wifi BIll', 5000.00, '2026-04-18', 'June,July,August,September,October,November,December,January,February,March', NULL, '22303070@iubat.edu', '2026-04-18 14:36:54'),
(3, 'Marketing', 1500.00, '2026-04-18', 'Logo light setup and other things', NULL, '22303070@iubat.edu', '2026-04-18 14:37:35'),
(4, 'Office Supplies', 5000.00, '2026-04-18', 'Chair', NULL, '22303070@iubat.edu', '2026-04-18 14:41:03'),
(5, 'Electritic items', 1500.00, '2026-04-18', 'Keyboard,Mouse', NULL, '22303070@iubat.edu', '2026-04-18 14:42:14'),
(6, 'Office Supplies', 8000.00, '2026-04-18', 'chair,table ,sofa', NULL, '22303070@iubat.edu', '2026-04-18 14:43:06'),
(7, 'Electritic items', 2000.00, '2026-04-18', 'power supply and motherboard service', NULL, '22303070@iubat.edu', '2026-04-18 14:43:31'),
(8, 'Electritic items', 1200.00, '2026-04-18', 'Monitor', NULL, '22303070@iubat.edu', '2026-04-18 14:43:45'),
(9, 'Office Supplies', 1500.00, '2026-04-18', 'Office Mat', NULL, '22303070@iubat.edu', '2026-04-18 14:44:26'),
(10, 'Electritic items', 18000.00, '2026-04-18', 'Printer', NULL, '22303070@iubat.edu', '2026-04-18 14:44:48'),
(11, 'Marketing', 1500.00, '2026-04-18', 'Decoration banner', NULL, '22303070@iubat.edu', '2026-04-18 14:45:07'),
(12, 'Marketing', 500.00, '2026-04-18', 'X stand', NULL, '22303070@iubat.edu', '2026-04-18 14:45:30'),
(13, 'Office expense', 40000.00, '2026-04-18', 'For 10 month', NULL, '22303070@iubat.edu', '2026-04-18 14:46:14'),
(14, 'Marketing', 2000.00, '2026-04-18', 'other item', NULL, '22303070@iubat.edu', '2026-04-18 14:46:39'),
(15, 'Dollor Coast', 3000.00, '2026-04-21', 'Facebook ads campaign for the Elite Wear Boutique $20  campaign 3000', NULL, '22303070@iubat.edu', '2026-04-21 15:30:06');

-- --------------------------------------------------------

--
-- Table structure for table `generated_certificates`
--

CREATE TABLE `generated_certificates` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `issue_date` date NOT NULL,
  `course_name` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_no` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invoice_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoice_no`, `student_id`, `invoice_date`, `total_amount`, `paid_amount`, `due_amount`) VALUES
('11', 250804, '2025-09-12 15:58:55', 0.00, 0.00, 0.00),
('INV-20250911093018', 250801, '2025-09-11 07:30:18', 0.00, 0.00, 0.00),
('INV-20250915160137', 250701, '2025-09-15 14:01:37', 0.00, 0.00, 0.00),
('INV-20250918145133', 250803, '2025-09-18 12:51:33', 0.00, 0.00, 0.00),
('INV-20250918145218', 250803, '2025-09-18 12:52:18', 0.00, 0.00, 0.00),
('INV-20250925183602', 250802, '2025-09-25 16:36:02', 0.00, 0.00, 0.00),
('INV-20250925183612', 250802, '2025-09-25 16:36:12', 0.00, 0.00, 0.00),
('INV-20251005075149', 250803, '2025-10-05 05:51:49', 0.00, 0.00, 0.00),
('INV-20251016141121', 250601, '2025-10-16 12:11:21', 0.00, 0.00, 0.00),
('INV-20251130164734', 250601, '2025-11-30 15:47:34', 0.00, 0.00, 0.00),
('INV-20251229111047', 250803, '2025-12-29 10:10:47', 0.00, 0.00, 0.00),
('INV-20260109062855', 251201, '2026-01-09 05:28:55', 0.00, 0.00, 0.00),
('INV-20260129072601', 250601, '2026-01-29 06:26:01', 0.00, 0.00, 0.00),
('INV-20260207155139', 251201, '2026-02-07 14:51:39', 0.00, 0.00, 0.00),
('INV-20260216072504', 260101, '2026-02-16 06:25:04', 0.00, 0.00, 0.00),
('INV-20260223145834', 251201, '2026-02-23 13:58:34', 0.00, 0.00, 0.00),
('INV-20260227135125', 251201, '2026-02-27 12:51:25', 0.00, 0.00, 0.00),
('INV-20260301121533', 260101, '2026-03-01 11:15:33', 0.00, 0.00, 0.00),
('INV-20260312114240', 260101, '2026-03-12 10:42:40', 0.00, 0.00, 0.00),
('INV-20260312161857', 250601, '2026-03-12 15:18:57', 0.00, 0.00, 0.00),
('INV-20260417093434', 250702, '2026-04-17 07:34:34', 0.00, 0.00, 0.00),
('INV-20260417093457', 250401, '2026-04-17 07:34:57', 0.00, 0.00, 0.00),
('INV-20260515175653', 260501, '2026-05-15 15:56:53', 0.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `invoices_new`
--

CREATE TABLE `invoices_new` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(20) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `invoice_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` enum('unpaid','paid','partial','cancelled') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices_new`
--

INSERT INTO `invoices_new` (`id`, `invoice_number`, `customer_id`, `invoice_date`, `due_date`, `subtotal`, `discount`, `total`, `paid_amount`, `notes`, `status`, `created_at`) VALUES
(11, 'INV-20260417-189', 2, '2026-04-17', '2026-04-24', 5000.00, 0.00, 5000.00, 5000.00, '', 'paid', '2026-04-17 15:51:37'),
(12, 'INV-20260418-268', 3, '2026-04-18', '2026-04-25', 65000.00, 2500.00, 62500.00, 60000.00, '', 'partial', '2026-04-18 14:54:23'),
(25, 'INV-20260421-543', 2, '2026-04-21', '2026-04-28', 4500.00, 0.00, 4500.00, 4500.00, '', 'paid', '2026-04-21 15:25:56'),
(26, 'INV-20260507-721', 2, '2026-05-07', '2026-05-14', 10000.00, 0.00, 10000.00, 4000.00, '', 'partial', '2026-05-07 16:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `service_id`, `description`, `qty`, `unit_price`, `total`) VALUES
(4, 11, 44, 'Facebook page setup master package', 1, 3500.00, 3500.00),
(5, 11, 43, 'TIN Certificate', 1, 1500.00, 1500.00),
(6, 12, 46, 'Website Design', 1, 15000.00, 15000.00),
(7, 12, 45, 'Custom software Design', 1, 50000.00, 50000.00),
(20, 25, NULL, 'Boosting Service: $20.00 USD @ rate 150.00 = 3000.00 BDT + service charge 1500.00 BDT', 1, 4500.00, 4500.00),
(21, 26, NULL, 'Boosting Service: $69.00 USD @ rate 135.00 = 9315.00 BDT + service charge 685.00 BDT', 1, 10000.00, 10000.00);

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('Admin','User') NOT NULL,
  `token` varchar(255) NOT NULL,
  `reset_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `role`, `token`, `reset_code`, `expires_at`, `created_at`) VALUES
(4, '22303070@iubat.edu', 'Admin', '8772ec2e6d2be2e08365818fd7b99389d77cfa7c819c8b310dc8695944200dbb', '758384', '2026-04-17 12:59:33', '2026-04-17 10:44:33');

-- --------------------------------------------------------

--
-- Table structure for table `payment_history`
--

CREATE TABLE `payment_history` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routines`
--

CREATE TABLE `routines` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `day` varchar(20) NOT NULL,
  `time_slot` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `description`, `category`, `duration`, `fee`, `created_at`) VALUES
(34, 'Facebook Page Setup', 'Logo,Banner,Additional setup.Location mapping,SEO', 'Social Media Development', '3 Days', 1500.00, '2026-04-17 15:39:36'),
(39, 'Youtube Channel Setup', 'Logo,Channel Art,Additional Setup,SEO,Address verification,Number verification,Adsence verification help.', 'Social Media Development', '10  Days', 3000.00, '2026-04-17 15:44:21'),
(40, 'Logo Design', 'High quality logo \r\nFile format will be jpg,png,psd/AI.', 'Graphic Design', '2 Days', 1000.00, '2026-04-17 15:45:29'),
(41, 'Cover Design', 'High quality cover design\r\nJpg,png,AI/Psd', 'Graphic Design', '2 Days', 500.00, '2026-04-17 15:46:10'),
(42, 'Custom Flyer Design', 'Made with customer requirement\r\nfile format will be jpg,png,AI/Psd,', 'Graphic Design', '2 Days', 500.00, '2026-04-17 15:47:02'),
(43, 'TIN Certificate', 'Open TIN Account \r\nand return the yearly TIN ,customer will give the tax amount payment and the charge 100.', 'Online Work', '1 Hour', 1500.00, '2026-04-17 15:48:31'),
(44, 'Facebook page setup master package', 'logo ,cover ,additional setup,SEO,fully optimized and 10 graphic custom design for their products and  1 month support', 'Social Media Development', '1 Month', 3500.00, '2026-04-17 15:50:37'),
(45, 'Custom software Design', 'Custom software design according to customer requirement,', 'Software Development', '3 month', 50000.00, '2026-04-18 14:47:54'),
(46, 'Website Design', 'According to customer requirement', 'Web design', '2 Month', 15000.00, '2026-04-18 14:48:37');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `present_address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `id_type` enum('NID','Birth ID') NOT NULL,
  `nid_birth_id` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `course_category` enum('Web Development','Graphic Design','Digital Marketing','Freelancing','office application') NOT NULL,
  `course_status` enum('ongoing','finished','incomplete') DEFAULT 'ongoing',
  `is_blocked` tinyint(1) DEFAULT 0,
  `issue_date` date DEFAULT NULL,
  `course_start_date` date DEFAULT NULL,
  `course_end_date` date DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `course_fee` decimal(10,2) DEFAULT NULL,
  `paid_fee` decimal(10,2) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `phone_number` varchar(20) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `occupation` varchar(50) DEFAULT NULL,
  `religion` varchar(30) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `father_name`, `mother_name`, `dob`, `present_address`, `permanent_address`, `id_type`, `nid_birth_id`, `email`, `course_category`, `course_status`, `is_blocked`, `issue_date`, `course_start_date`, `course_end_date`, `city`, `district`, `course_fee`, `paid_fee`, `profile_image`, `created_at`, `last_updated`, `phone_number`, `gender`, `marital_status`, `occupation`, `religion`, `country`) VALUES
(241001, 'md sujon miah', 'abdur rashid bepary', 'mst shajeda ', '2005-09-28', 'Gajipur ', 'Kurigram ', 'NID', 'NO', 'Shakilbepary005@gamil.com', 'Graphic Design', 'incomplete', 1, NULL, NULL, NULL, NULL, NULL, 10000.00, 8000.00, '8.jpeg', '2025-06-26 19:13:53', '2026-02-21 10:58:02', '01960532845', 'Male', 'Single', 'Student', 'Islam', 'Bangladesh'),
(241101, 'Md. Rayhan', 'Md. Ismail', 'Mst. Rube Akter', '2006-07-21', 'Gazipur ,bordbazar', 'Gazipur ,bordbazar', 'NID', 'NO', 'kingrayhan338@gmail.com', 'Graphic Design', 'finished', 0, NULL, NULL, NULL, NULL, NULL, 10000.00, 10000.00, '1.jpg', '2025-06-26 09:44:05', '2025-09-11 07:06:32', '01623736697', 'Male', 'Single', 'Studen', 'Islam', 'Bangladeshi'),
(241201, 'Md.Abdullah', 'Md kazimuddin', 'Rahima Akter ', '2002-11-22', 'Meghdubi,  ward -40 Gazipur City Corporation ', 'Meghdubi,  ward -40 Gazipur City Corporation ', 'NID', 'NO', 'abdullah2252002@gmail.com', 'Graphic Design', 'incomplete', 1, NULL, NULL, NULL, NULL, NULL, 15000.00, 12000.00, '2.jpg', '2025-06-26 09:49:03', '2026-02-21 10:58:51', '01996331920', 'Male', 'Single', 'Student', 'Islam', 'Bangladesh'),
(241202, 'Siyam khan', 'Mohammed ali', 'Minara Aktar ', '2007-03-26', 'Khailkur board bazar gazipur', 'Motlab north chandpur', 'NID', 'NO', 'siyamkhansiyam754@gmail.com', 'Graphic Design', 'incomplete', 1, NULL, NULL, NULL, NULL, NULL, 10000.00, 3000.00, 'BD FOOTBALL T SHIRT.jpg', '2025-06-26 19:17:38', '2026-02-21 10:58:31', '01881060377', 'Male', 'Single', 'Student', 'Islam', 'Bangladesh'),
(250201, 'Md:Jubaer Mahmud', 'Md:Khalilur Rahman', 'Mst:Mokseda begum', '2002-05-28', 'Khailkur,board bazer,gazipur ', 'Khailkur,board bazer,gazipur ', 'NID', 'NO', 'Mdakash112266@gmail.com', 'Graphic Design', 'incomplete', 1, NULL, NULL, NULL, NULL, NULL, 8000.00, 5000.00, '3.jpg', '2025-06-26 09:52:22', '2026-02-21 10:57:47', '01873331646', 'Male', 'Married', 'Driver', 'Islam', 'Bangladesh'),
(250401, 'Md:Emran Hossain', 'Ali Ahmed', 'Bilkis begum', '2002-03-10', 'Bord bazar', 'Bord bazar', 'NID', 'NO', 'emran456777@gmail.com', 'Graphic Design', 'finished', 0, NULL, NULL, NULL, NULL, NULL, 11000.00, 11000.00, '4.jpg', '2025-06-26 09:55:46', '2025-09-24 14:12:19', '01778683691', 'Male', 'Married', 'Labour', 'Islam', 'Bangladesh'),
(250402, 'Yeasin Hosin ', 'Khokon mia', 'Ayesha khatun', '2002-01-15', 'Khailkur, Gazipur ', 'Khailkur, Gazipur ', 'NID', 'NO', 'mbyasin90@gmail.com', 'Graphic Design', 'finished', 0, NULL, NULL, NULL, NULL, NULL, 10000.00, 10000.00, '5.jpg', '2025-06-26 18:53:40', '2025-09-11 07:06:05', '01302942741', 'Male', 'Single', 'Student', 'Islam', 'Bangladesh'),
(250601, 'Md.Jamil', 'MD.Nabesh Uddin', 'Mst.Amichha khatun', '1991-10-25', 'Khailkur, 38 NO ward, Gazipur City Corporation. ', 'Panchpir, Boda, Panchagarh.', 'NID', 'NO', 'ju206251@gmail.com', 'Graphic Design', 'incomplete', 1, '2025-01-01', '2025-06-03', '2025-09-03', 'Gazipur', 'Gazipur', 9000.00, 9000.00, '6.jpg', '2025-06-26 18:58:07', '2026-03-12 15:16:35', '01515206251', 'Male', 'Married', 'Service Holder', 'Islam', 'Bangladesh'),
(250602, 'Afjal Hossain', 'Amir Hossain', 'Nargis Aktar', '2000-10-10', 'Board Bazar Gazipure', 'Sultan General Hospital rood', 'NID', 'NO', 'hossainafjal1123@gmail.com', 'Graphic Design', 'incomplete', 1, NULL, NULL, NULL, NULL, NULL, 6000.00, 2000.00, '7.jpg', '2025-06-26 19:10:59', '2026-02-21 10:58:16', '01996347366', 'Male', 'Married', 'Driver', 'Islam', 'Bangladesh'),
(250701, 'Abdur Rahman Rajon', 'Abdus Sattar', 'Rohima Begum', '2009-02-02', 'South Khailkur,38 No ward,Boardbazar,Gazipur', 'Atharo Bari,Mymensingh', 'Birth ID', '20096113111118352', 'abdurrahmanrajon0@gmail.com', 'Graphic Design', 'finished', 0, '2025-07-01', '2025-07-01', '2025-10-01', 'Gazipur', 'Gazipur', 10000.00, 10000.00, 'WhatsApp Image 2025-07-01 at 7.51.20 PM.jpeg', '2025-07-01 13:56:01', '2026-02-20 15:16:17', '01935721343', 'Male', 'Single', 'Student', 'Islam', 'Bangladesh'),
(250702, 'Babli Akhter ', 'Babul Islam', 'Baby begom', '2000-10-29', 'Hidubarir  mor.  Board Bazar Gazipur ', 'Kishorganj,  Nilfamari', 'NID', '9589990879', 'sblipu050@gmail.com', 'Graphic Design', 'incomplete', 1, NULL, NULL, NULL, NULL, NULL, 12000.00, 8500.00, 'Snapchat-958891014 - Sb Lipu.jpg', '2025-07-04 13:28:42', '2026-02-21 10:57:36', '01627174002', 'Female', 'Married', 'Service Holder', 'Islam', 'Bangladesh'),
(250801, 'Md Biplob Miah', 'Md sayed Ali', 'Halima khatun', '1988-07-01', 'Pinglan,Purbachol,17 no sector,Dhaka', 'Pinglan,Purbachol,17 no sector,Dhaka', 'NID', '7766269570', 'b49066313@gmail.com', 'Graphic Design', 'ongoing', 0, NULL, NULL, NULL, NULL, NULL, 10000.00, 10000.00, 'biplob bhai.jfif', '2025-07-31 13:49:58', '2025-09-11 07:26:05', '01710100657', 'Male', 'Married', 'Businessman', 'Islam', 'Bangladesh'),
(250802, 'Md Biplob Miah', 'Md sayed Ali', 'Halima khatun', '1988-07-01', 'Pinglan,Purbachol,17 no sector,Dhaka', 'Pinglan,Purbachol,17 no sector,Dhaka', 'NID', '7766269570	', 'b4906631@gmail.com', 'Digital Marketing', 'ongoing', 0, NULL, NULL, NULL, NULL, NULL, 10000.00, 8000.00, 'biplob bhai.jfif', '2025-07-31 14:14:23', '2026-01-16 05:46:06', '01710100657', 'Male', 'Married', 'Businessman', 'Islam', 'Bangladesh'),
(250803, 'Eva Akther Eity', 'Md.Eomn Mia', 'Shireena Akter', '2012-12-08', 'Dhaka, gazipur, Board bazar ', 'Kishorgonj, Austrogram ', 'Birth ID', '2008339338076647', 'kimlinzu8@gmail.com', 'Graphic Design', 'ongoing', 0, '2025-08-08', '2025-08-08', '2025-11-08', 'Gazipur', 'Gazipur', 8000.00, 8000.00, 'IMG.png', '2025-09-11 05:12:53', '2026-02-21 10:36:18', '01819866303', 'Female', 'Single', 'Student', 'Islam', 'Bangladesh'),
(250804, 'MD.ASHIK BABU', 'Md Shafiqul Islam', 'Mst Rajia Sultana', '2005-09-05', 'Khailkur badshahmia road,kunia,borobari, Gazipur-1704', 'Dhumerkuthi,Kaunia, Rangpur', 'NID', '4672198381', 'mdazizulhakimasik@gmail.com', 'office application', 'ongoing', 0, '2025-08-08', '2025-08-08', '2025-09-08', 'Gazipur', 'Gazipur', 5000.00, 5000.00, 'IMG_20250816_203143 - Important life Asik.jpg', '2025-09-11 07:24:45', '2025-10-13 15:37:05', '01770090941', 'Male', 'Single', 'Garments worker', 'Islam', 'Bangladesh'),
(251201, 'Muhammad Rohan Islam', 'Md Hanif Mia', 'Mst Nasima Begum ', '2016-01-30', 'Khailkur,Gazipur,Dhaka ', 'Khailkur,Gazipur,Dhaka ', 'Birth ID', 'No', 'nafiup3@gmail.com', 'Graphic Design', 'ongoing', 0, '2025-12-07', '2025-12-07', '2026-03-07', 'Gazipur', 'Gazipur', 12000.00, 12000.00, 'inbound3695639844118582066 - Nafiu .p.jpg', '2025-12-08 11:32:35', '2026-02-27 12:50:46', '01889562965', 'Male', 'Single', 'Student', 'Islam', 'Bangladesh'),
(260101, 'Fatema jahan maliha ', 'Abul Hossain Faisal', 'Sahina Akter Shanta ', '2007-04-13', 'Shuth khailkur,National University-1704 Gazipur Cirty Corporation, Gazipur ', 'Shuth khailkur,National University-1704 Gazipur Cirty Corporation, Gazipur ', 'Birth ID', 'NO', 'fatemajahanmaliha88@gmail.com', 'Graphic Design', 'ongoing', 0, '2026-01-16', '2026-01-16', '2026-03-16', 'Gazipur', 'Gazipur', 10000.00, 5000.00, 'IMG-20260101-WA0019 - Fatema Jahan Maliha.jpg', '2026-01-16 05:40:42', '2026-03-12 10:41:43', '01715547152', 'Female', 'Single', 'Student', 'Islam', 'Bangladesh'),
(260501, 'Azizul Hakim Asik', 'Md.Shafiqul Islam', 'Mst. Rajia Sultana ', '2005-09-05', 'Khailkur badshahmia road,kunia,borobari, Gazipur-1704', 'Dhumerkuthi,Kaunia, Rangpur', 'NID', '4672198381', 'mdazizulhakimasik@gmail.com', 'Graphic Design', 'ongoing', 0, '2026-05-15', '2026-05-22', '2026-08-22', 'Gazipur', 'Gazipur', 10000.00, 1000.00, '1000012761.jpeg', '2026-05-15 15:34:45', '2026-05-15 15:42:52', '01767269232', 'Male', 'Single', 'Garments worker', 'Islam', 'Bangladesh');

-- --------------------------------------------------------

--
-- Table structure for table `student_routine`
--

CREATE TABLE `student_routine` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `instructor` varchar(100) NOT NULL,
  `computer_no` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_routine`
--

INSERT INTO `student_routine` (`id`, `student_id`, `day`, `start_time`, `end_time`, `instructor`, `computer_no`, `created_at`) VALUES
(1, 250702, 'Monday', '10:00:00', '11:00:00', 'jahid', '02', '2026-04-12 15:09:57'),
(2, 250802, 'Monday', '10:00:00', '11:00:00', '', '', '2026-04-12 15:14:28'),
(3, 250802, 'Tuesday', '00:00:00', '01:00:00', '', '', '2026-04-12 15:16:33'),
(4, 250803, 'Friday', '10:10:00', '11:10:00', '', '', '2026-04-12 15:58:09'),
(5, 250803, 'Saturday', '10:10:00', '11:10:00', '', '', '2026-04-12 15:58:24'),
(6, 250803, 'Sunday', '10:10:00', '11:10:00', '', '', '2026-04-12 15:58:24'),
(7, 250803, 'Monday', '10:10:00', '11:10:00', '', '', '2026-04-12 15:58:24'),
(8, 250803, 'Tuesday', '10:10:00', '11:10:00', '', '', '2026-04-12 15:58:24'),
(9, 250803, 'Wednesday', '10:10:00', '11:10:00', '', '', '2026-04-12 15:58:24'),
(10, 250803, 'Thursday', '10:10:00', '11:10:00', '', '', '2026-04-12 15:58:24'),
(11, 250401, 'Saturday', '12:11:00', '13:11:00', '', '', '2026-04-17 17:21:13'),
(13, 260501, 'Monday', '20:00:00', '21:00:00', '', '', '2026-05-15 15:54:26'),
(14, 260501, 'Wednesday', '20:00:00', '21:00:00', '', '', '2026-05-15 15:55:27'),
(15, 260501, 'Friday', '19:00:00', '20:00:00', '', '', '2026-05-15 15:55:51');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`) VALUES
(1, 'users@example.com', 'users123');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_services`
--
ALTER TABLE `customer_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `generated_certificates`
--
ALTER TABLE `generated_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_certificate` (`student_id`,`certificate_number`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_no`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `invoices_new`
--
ALTER TABLE `invoices_new`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_history`
--
ALTER TABLE `payment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `routines`
--
ALTER TABLE `routines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_fee` (`fee`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `student_routine`
--
ALTER TABLE `student_routine`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer_services`
--
ALTER TABLE `customer_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `generated_certificates`
--
ALTER TABLE `generated_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices_new`
--
ALTER TABLE `invoices_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_history`
--
ALTER TABLE `payment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routines`
--
ALTER TABLE `routines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=260502;

--
-- AUTO_INCREMENT for table `student_routine`
--
ALTER TABLE `student_routine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `attendance_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_services`
--
ALTER TABLE `customer_services`
  ADD CONSTRAINT `customer_services_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices_new`
--
ALTER TABLE `invoices_new`
  ADD CONSTRAINT `invoices_new_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_history`
--
ALTER TABLE `payment_history`
  ADD CONSTRAINT `payment_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `student_routine`
--
ALTER TABLE `student_routine`
  ADD CONSTRAINT `student_routine_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
