-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2026 at 09:09 AM
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
  `password` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`) VALUES
(1, 'admin@example.com', 'admin123');

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
('INV-20260312161857', 250601, '2026-03-12 15:18:57', 0.00, 0.00, 0.00);

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
(1, 'Logo Design', 'Professional logo creation for businesses and brands', 'Graphic Design', '2 weeks', 5000.00, '2026-02-15 12:00:03'),
(2, 'Social Media Graphics', 'Facebook, Instagram, Twitter banner and post designs', 'Graphic Design', '1 month', 8000.00, '2026-02-15 12:00:03'),
(3, 'Video Editing', 'Professional video editing and post-production', 'Video Editing', '2 months', 12000.00, '2026-02-15 12:00:03'),
(4, 'Motion Graphics', 'Animated videos and explainer videos', 'Video Editing', '3 months', 18000.00, '2026-02-15 12:00:03'),
(5, 'Social Media Management', 'Complete social media strategy and management', 'Social Media Marketing', '3 months', 15000.00, '2026-02-15 12:00:03'),
(6, 'Content Creation', 'Social media content planning and creation', 'Social Media Marketing', '2 months', 10000.00, '2026-02-15 12:00:03'),
(7, 'SEO Optimization', 'Search Engine Optimization for websites', 'Digital Marketing', '4 months', 20000.00, '2026-02-15 12:00:03'),
(9, 'Microsoft Word', 'Advanced document formatting and templates', 'Office Application', '1 month', 3000.00, '2026-02-15 12:00:03'),
(10, 'Microsoft Excel', 'Data analysis, formulas, and charts', 'Office Application', '2 months', 5000.00, '2026-02-15 12:00:03'),
(11, 'Microsoft PowerPoint', 'Professional presentation design', 'Office Application', '1 month', 4000.00, '2026-02-15 12:00:03'),
(12, 'Adobe Photoshop', 'Photo editing and manipulation', 'Graphic Design', '3 months', 15000.00, '2026-02-15 12:00:03'),
(13, 'Adobe Illustrator', 'Vector graphics and illustration', 'Graphic Design', '3 months', 16000.00, '2026-02-15 12:00:03'),
(14, 'Premiere Pro', 'Professional video editing software training', 'Video Editing', '3 months', 18000.00, '2026-02-15 12:00:03');

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
(260101, 'Fatema jahan maliha ', 'Abul Hossain Faisal', 'Sahina Akter Shanta ', '2007-04-13', 'Shuth khailkur,National University-1704 Gazipur Cirty Corporation, Gazipur ', 'Shuth khailkur,National University-1704 Gazipur Cirty Corporation, Gazipur ', 'Birth ID', 'NO', 'fatemajahanmaliha88@gmail.com', 'Graphic Design', 'ongoing', 0, '2026-01-16', '2026-01-16', '2026-03-16', 'Gazipur', 'Gazipur', 10000.00, 5000.00, 'IMG-20260101-WA0019 - Fatema Jahan Maliha.jpg', '2026-01-16 05:40:42', '2026-03-12 10:41:43', '01715547152', 'Female', 'Single', 'Student', 'Islam', 'Bangladesh');

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
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_no`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
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
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

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
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=260302;

--
-- AUTO_INCREMENT for table `student_routine`
--
ALTER TABLE `student_routine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

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
