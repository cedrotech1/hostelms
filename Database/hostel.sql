-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2025 at 11:40 PM
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
-- Database: `hostel`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `regnumber` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'pending',
  `slep` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `regnumber`, `room_id`, `status`, `slep`, `created_at`, `updated_at`) VALUES
(9, 20231016, 102, 'pending', '', '2025-05-25 14:55:56', '2025-05-25 14:55:56'),
(10, 20231031, 102, 'pending', '', '2025-05-25 14:57:03', '2025-02-25 15:57:03'),
(16, 20231001, 91, 'pending', '', '2025-05-26 10:25:58', '2025-05-26 10:25:58'),
(18, 20231007, 11, 'approved', '20231007_1748288060.png', '2025-05-26 19:32:50', '2025-05-26 19:35:22'),
(19, 20231026, 103, 'approved', '20231026_1748288655.png', '2025-05-26 19:43:08', '2025-05-26 19:44:40');

-- --------------------------------------------------------

--
-- Table structure for table `campuses`
--

CREATE TABLE `campuses` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campuses`
--

INSERT INTO `campuses` (`id`, `name`) VALUES
(1, 'huye'),
(2, 'nyagatare'),
(3, 'kigali'),
(4, 'remera');

-- --------------------------------------------------------

--
-- Table structure for table `excel`
--

CREATE TABLE `excel` (
  `id` int(11) NOT NULL,
  `regnumber` int(11) NOT NULL,
  `campus` int(11) DEFAULT NULL,
  `college` int(11) DEFAULT NULL,
  `sirname` int(11) DEFAULT NULL,
  `lastname` int(11) NOT NULL,
  `school` int(11) DEFAULT NULL,
  `program` int(11) DEFAULT NULL,
  `yearofstudy` int(11) DEFAULT NULL,
  `email` int(11) DEFAULT NULL,
  `gender` int(11) DEFAULT NULL,
  `nid` int(11) DEFAULT NULL,
  `phone` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `excel`
--

INSERT INTO `excel` (`id`, `regnumber`, `campus`, `college`, `sirname`, `lastname`, `school`, `program`, `yearofstudy`, `email`, `gender`, `nid`, `phone`) VALUES
(1, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);

-- --------------------------------------------------------

--
-- Table structure for table `hostels`
--

CREATE TABLE `hostels` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `campus_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hostels`
--

INSERT INTO `hostels` (`id`, `name`, `campus_id`) VALUES
(1, 'Cambog', 1),
(2, 'Nyarutarama', 1),
(3, 'Titanic', 1),
(4, 'Bengazi1', 1),
(5, 'Kiza', 1),
(6, 'Remera', 3),
(7, 'Gikondo', 3),
(8, 'Kacyiru', 3),
(9, 'Nyagatare Main', 2),
(10, 'Nyagatare Annex', 2),
(11, 'remera-house', 4);

-- --------------------------------------------------------

--
-- Table structure for table `hostel_attributes`
--

CREATE TABLE `hostel_attributes` (
  `id` int(11) NOT NULL,
  `hostel_id` int(11) DEFAULT NULL,
  `attribute_key` varchar(50) DEFAULT NULL,
  `attribute_value` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hostel_attributes`
--

INSERT INTO `hostel_attributes` (`id`, `hostel_id`, `attribute_key`, `attribute_value`) VALUES
(16, 3, 'yearofstudy', '3'),
(25, 2, 'yearofstudy', '3'),
(26, 1, 'gender', 'M'),
(32, 4, 'gender', 'male');

-- --------------------------------------------------------

--
-- Table structure for table `info`
--

CREATE TABLE `info` (
  `id` int(11) NOT NULL,
  `regnumber` varchar(100) NOT NULL,
  `campus` varchar(100) DEFAULT NULL,
  `college` varchar(100) DEFAULT NULL,
  `names` varchar(255) DEFAULT NULL,
  `school` varchar(100) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `yearofstudy` varchar(100) DEFAULT NULL,
  `email` varchar(30) NOT NULL,
  `gender` varchar(30) NOT NULL,
  `nid` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `token` varchar(300) NOT NULL,
  `status` varchar(30) NOT NULL,
  `code` int(11) NOT NULL,
  `current_application` varchar(20) NOT NULL,
  `password` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `info`
--

INSERT INTO `info` (`id`, `regnumber`, `campus`, `college`, `names`, `school`, `program`, `yearofstudy`, `email`, `gender`, `nid`, `phone`, `token`, `status`, `code`, `current_application`, `password`) VALUES
(1, '20231001', 'nyagatare', 'Education', 'Alice Kabera', 'School of Law', 'Finance', '2', 'alice.kabera@example.com', 'female', '1002003001', '0795641504', '', 'active', 0, '', '$2y$10$PwBiuykHk1/oqiLpCMiq1eYxHYkLi0IxJ9yfjcsGpKUBlByf23fd2'),
(2, '20231002', 'kigali', 'Education', 'John Otieno', 'School of Med.', 'Public Health', '2', 'john.otieno@example.com', 'female', '1002003002', '0785301081', '', 'active', 0, '', '$2y$10$IdNHSNbp9J/GjwDxx0VPRuwBeXW61jfNsr8wPFbxQyXlKGD3aC.UC'),
(3, '20231006', 'nyagatare', 'Business', 'Clara Kamau', 'School of Med.', 'Education', '3', 'clara.kamau@example.com', 'female', '1002003006', '0782464799', '', 'active', 0, '', '$2y$10$5rHLUvi63fXk3FZ.BNPTSukCKozOGOtKVc4G2Vr1Fnuwb94bqdl4S'),
(4, '20231007', 'huye', 'Business', 'Clara Mutoni', 'School of Arts', 'Law', '3', 'clara.mutoni@example.com', 'female', '1002003007', '0721686167', '', 'active', 0, 'rejected', '$2y$10$j0bqJd.uNyfu2Kip.rngC.t0u3hUGPrLkfnOaS44wqJWFVGHeBGgW'),
(5, '20231008', 'huye', 'Medicine', 'David Kabera', 'School of Bus.', 'Law', '3', 'david.kabera@example.com', 'female', '1002003008', '0788609666', '', 'active', 0, '', '$2y$10$5rTnVzPUGmDR5fykwKWqzukxuApt/pUdgWGZnDHTfNx82JlqikrKq'),
(6, '20231016', 'huye', 'Business', 'David Kamau', 'School of Eng.', 'Law', '1', 'david.kamau@example.com', 'male', '1002003016', '0785061937', '', 'active', 0, '', ''),
(7, '20231018', 'nyagatare', 'Arts & Humanities', 'Frank Habimana', 'School of Arts', 'Finance', '1', 'frank.habimana@example.com', 'female', '1002003018', '0782611321', '', 'active', 0, '', ''),
(8, '20231019', 'kigali', 'Education', 'Grace Kamau', 'School of Arts', 'Nursing', '2', 'grace.kamau@example.com', 'male', '1002003019', '0791613457', '', 'active', 0, '', ''),
(9, '20231020', 'kigali', 'Science', 'Grace Mutoni', 'School of Law', 'Education', '3', 'grace.mutoni@example.com', 'female', '1002003020', '0786223285', '', 'active', 0, '', ''),
(10, '20231023', 'kigali', 'Engineering', 'Grace Mugisha', 'School of Sci.', 'Education', '4', 'grace.mugisha@example.com', 'male', '1002003023', '0789485140', '', 'active', 0, '', ''),
(11, '20231024', 'nyagatare', 'Law', 'David Niyonzima', 'School of Arts', 'Nursing', '2', 'david.niyonzima@example.com', 'male', '1002003024', '0793862345', '', 'active', 0, '', ''),
(12, '20231026', 'remera', 'Law', 'Grace Uwimana', 'School of Law', 'Civil Engineering', '1', 'grace.uwimana@example.com', 'female', '1002003026', '0789946753', '', 'active', 0, '', '$2y$10$h4HsMjZoLEkjevyoUfgGPOs/vBYiFKmCKvHQvPop99t.JZBg24p2i'),
(13, '20231027', 'kigali', 'Arts & Humanities', 'Brian Niyonzima', 'School of Sci.', 'Physics', '2', 'brian.niyonzima@example.com', 'female', '1002003027', '0785301203', '', 'active', 0, '', ''),
(14, '20231028', 'nyagatare', 'Arts & Humanities', 'Grace Mugisha', 'School of Educ.', 'Nursing', '2', 'grace.mugisha@example.com', 'female', '1002003028', '0783788369', '', 'active', 0, '', ''),
(15, '20231030', 'kigali', 'Medicine', 'Clara Mukamana', 'School of Med.', 'Computer Science', '4', 'clara.mukamana@example.com', 'male', '1002003030', '0785817768', '', 'active', 0, '', ''),
(16, '20231031', 'huye', 'Medicine', 'Eva Uwimana', 'School of Sci.', 'Literature', '3', 'eva.uwimana@example.com', 'female', '1002003031', '0799839689', '', 'active', 0, '', ''),
(17, '20231034', 'kigali', 'Science', 'Irene Mukamana', 'School of Bus.', 'Literature', '3', 'irene.mukamana@example.com', 'male', '1002003034', '0792444270', '', 'active', 0, '', ''),
(18, '20231035', 'huye', 'Arts & Humanities', 'Henry Mukamana', 'School of Law', 'Civil Engineering', '1', 'henry.mukamana@example.com', 'male', '1002003035', '0780550685', '', 'active', 0, '', ''),
(19, '20231039', 'huye', 'Law', 'Eva Niyonzima', 'School of Eng.', 'Literature', '3', 'eva.niyonzima@example.com', 'male', '1002003039', '0785543089', '', 'active', 0, '', ''),
(20, '20231040', 'nyagatare', 'Science', 'Henry Uwimana', 'School of Eng.', 'Nursing', '1', 'henry.uwimana@example.com', 'female', '1002003040', '0798511495', '', 'active', 0, '', ''),
(21, '20231041', 'kigali', 'Medicine', 'John Otieno', 'School of Arts', 'Finance', '2', 'john.otieno@example.com', 'male', '1002003041', '0798950175', '', 'active', 0, '', ''),
(22, '20231043', 'nyagatare', 'Education', 'Grace Otieno', 'School of Educ.', 'Finance', '3', 'grace.otieno@example.com', 'male', '1002003043', '0783066131', '', 'active', 0, '', ''),
(23, '20231044', 'kigali', 'Medicine', 'Clara Kamau', 'School of Arts', 'Literature', '4', 'clara.kamau@example.com', 'female', '1002003044', '0782182943', '', 'active', 0, '', ''),
(24, '20231047', 'kigali', 'Education', 'Eva Niyonzima', 'School of Eng.', 'Civil Engineering', '4', 'eva.niyonzima@example.com', 'female', '1002003047', '0783239229', '', 'active', 0, '', ''),
(25, '20231048', 'huye', 'Science', 'Grace Kabera', 'School of Educ.', 'Physics', '4', 'grace.kabera@example.com', 'female', '1002003048', '0781346179', '', 'active', 0, '', ''),
(26, '20231050', 'nyagatare', 'Arts & Humanities', 'Frank Habimana', 'School of Sci.', 'Education', '2', 'frank.habimana@example.com', 'male', '1002003050', '0780192295', '', 'active', 0, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `content`, `created_at`) VALUES
(1, 'yes', '2025-05-23 17:19:59'),
(2, 'yes', '2025-05-23 17:20:16'),
(3, 'yes', '2025-05-23 17:21:37');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_code` varchar(50) NOT NULL,
  `number_of_beds` int(11) NOT NULL,
  `hostel_id` int(11) NOT NULL,
  `remain` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_code`, `number_of_beds`, `hostel_id`, `remain`) VALUES
(1, 'C100', 4, 1, 4),
(2, 'C101', 3, 1, 3),
(3, 'C102', 4, 1, 4),
(4, 'C103', 3, 1, 3),
(5, 'C104', 4, 1, 4),
(6, 'C105', 6, 1, 6),
(7, 'C106', 5, 1, 5),
(8, 'C107', 4, 1, 4),
(9, 'C108', 2, 1, 2),
(10, 'C109', 6, 1, 6),
(11, 'N100', 20, 2, 15),
(12, 'N101', 4, 2, 4),
(13, 'N102', 6, 2, 6),
(14, 'N103', 2, 2, 2),
(15, 'N104', 4, 2, 4),
(16, 'N105', 4, 2, 4),
(17, 'N106', 5, 2, 5),
(18, 'N107', 3, 2, 3),
(19, 'N108', 5, 2, 5),
(20, 'N109', 2, 2, 2),
(21, 'T100', 5, 3, 5),
(22, 'T101', 2, 3, 2),
(23, 'T102', 2, 3, 2),
(24, 'T103', 3, 3, 3),
(25, 'T104', 5, 3, 5),
(26, 'T105', 4, 3, 4),
(27, 'T106', 5, 3, 5),
(28, 'T107', 6, 3, 6),
(29, 'T108', 3, 3, 3),
(30, 'T109', 6, 3, 6),
(31, 'B100', 4, 4, 4),
(32, 'B101', 4, 4, 4),
(33, 'B102', 5, 4, 5),
(34, 'B103', 5, 4, 5),
(35, 'B104', 5, 4, 5),
(36, 'B105', 2, 4, 2),
(37, 'B106', 6, 4, 6),
(38, 'B107', 5, 4, 5),
(39, 'B108', 6, 4, 6),
(40, 'B109', 2, 4, 2),
(41, 'K100', 6, 5, 4),
(42, 'K101', 2, 5, 2),
(43, 'K102', 2, 5, 2),
(44, 'K103', 3, 5, 3),
(45, 'K104', 3, 5, 3),
(46, 'K105', 3, 5, 3),
(47, 'K106', 3, 5, 3),
(48, 'K107', 2, 5, 2),
(49, 'K108', 3, 5, 3),
(50, 'K109', 4, 5, 4),
(51, 'R100', 3, 6, 2),
(52, 'R101', 2, 6, 2),
(53, 'R102', 6, 6, 6),
(54, 'R103', 6, 6, 6),
(55, 'R104', 5, 6, 5),
(56, 'R105', 4, 6, 4),
(57, 'R106', 3, 6, 3),
(58, 'R107', 4, 6, 4),
(59, 'R108', 4, 6, 4),
(60, 'R109', 4, 6, 4),
(61, 'G100', 6, 7, 6),
(62, 'G101', 4, 7, 4),
(63, 'G102', 2, 7, 2),
(64, 'G103', 4, 7, 4),
(65, 'G104', 5, 7, 5),
(66, 'G105', 4, 7, 4),
(67, 'G106', 3, 7, 3),
(68, 'G107', 6, 7, 6),
(69, 'G108', 6, 7, 6),
(70, 'G109', 2, 7, 2),
(71, 'K100', 6, 8, 6),
(72, 'K101', 6, 8, 6),
(73, 'K102', 2, 8, 2),
(74, 'K103', 5, 8, 5),
(75, 'K104', 4, 8, 4),
(76, 'K105', 4, 8, 4),
(77, 'K106', 6, 8, 6),
(78, 'K107', 6, 8, 6),
(79, 'K108', 6, 8, 6),
(80, 'K109', 5, 8, 5),
(81, 'N100', 4, 9, 4),
(82, 'N101', 6, 9, 6),
(83, 'N102', 6, 9, 6),
(84, 'N103', 4, 9, 4),
(85, 'N104', 5, 9, 5),
(86, 'N105', 5, 9, 5),
(87, 'N106', 3, 9, 3),
(88, 'N107', 4, 9, 4),
(89, 'N108', 2, 9, 2),
(90, 'N109', 2, 9, 2),
(91, 'N100', 6, 10, 5),
(92, 'N101', 4, 10, 4),
(93, 'N102', 3, 10, 3),
(94, 'N103', 6, 10, 6),
(95, 'N104', 6, 10, 6),
(96, 'N105', 2, 10, 2),
(97, 'N106', 6, 10, 6),
(98, 'N107', 5, 10, 5),
(99, 'N108', 5, 10, 5),
(100, 'N109', 2, 10, 2),
(101, 'T0012', 2, 3, 0),
(102, 'R001', 2, 2, 0),
(103, '001', 3, 11, 2),
(104, '002', 2, 11, 2);

-- --------------------------------------------------------

--
-- Table structure for table `system`
--

CREATE TABLE `system` (
  `id` int(11) NOT NULL,
  `status` varchar(100) NOT NULL,
  `exp_date` varchar(20) NOT NULL,
  `exam_validity` varchar(20) NOT NULL,
  `accademic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `allow_message` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system`
--

INSERT INTO `system` (`id`, `status`, `exp_date`, `exam_validity`, `accademic_year`, `semester`, `allow_message`) VALUES
(1, 'live', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `uploaded_files`
--

CREATE TABLE `uploaded_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_link` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uploaded_files`
--

INSERT INTO `uploaded_files` (`id`, `file_name`, `file_link`, `uploaded_at`) VALUES
(2, 'STUDENT_USER_GUIDE.pdf', 'uploads/677ff4ce281b90.53056929.pdf', '2025-01-09 16:09:50'),
(3, 'UR-FINAL-DOC.pdf', 'uploads/6785341b99d427.22586797.pdf', '2025-01-13 15:41:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `names` varchar(30) NOT NULL,
  `email` varchar(30) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `image` varchar(200) NOT NULL,
  `about` varchar(150) NOT NULL,
  `role` varchar(30) NOT NULL,
  `password` varchar(200) NOT NULL,
  `active` int(11) NOT NULL,
  `resetcode` int(11) NOT NULL,
  `campus` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `names`, `email`, `phone`, `image`, `about`, `role`, `password`, `active`, `resetcode`, `campus`) VALUES
(1, 'cedro_tech', 'cedrotech1@gmail.com', '0788308413', 'upload/icon1.png', '                                                                                                                                                      ', 'admin', '$2y$10$jiLGHmEwqW0ARK0pDQqdreaADev6mkf/pfO0ZkFx0uBqsHImhmwNG', 1, 0, 0),
(29, 'cedrick', 'cedrickhakuzimana@gmail.com', '', 'upload/av.png', '', 'information_modifier', '$2y$10$5OhGuQPwsrHkVzq9b91vO.KowcpwDdbpM2ZAogWii.xZf4ya0sLSK', 1, 0, 0),
(41, 'Ange', 'a.nduwera@ur.ac.rw', '', 'upload/av.png', '', 'admin', '$2y$10$xIc8QDddoo7PWKT2Ejtd1O2n77W.elUtKlnL9nNcTyeamddekY4o6', 1, 660120, 0),
(44, 'cedrick hakuzimana', 'cedrickhakuzimana75@gmail.com', '0784366616', 'assets/img/av.png', '', 'warefare', '$2y$10$G4MfaQibRn0UVkrJPJvEIOQ33re6/7Wzx10XA/Gas5g1Q7KTuiXRK', 1, 0, 1),
(45, 'akimana', 'akimana@gmail.com', '0784366616', 'assets/img/av.png', '', 'warefare', '$2y$10$oetnF5JR/F/4d8o57UPPfek9ogQo3nckbODaeEZdc0cG6OgthJ.su', 1, 0, 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `campuses`
--
ALTER TABLE `campuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `excel`
--
ALTER TABLE `excel`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hostels`
--
ALTER TABLE `hostels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hostel_attributes`
--
ALTER TABLE `hostel_attributes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `info`
--
ALTER TABLE `info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system`
--
ALTER TABLE `system`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `uploaded_files`
--
ALTER TABLE `uploaded_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `campuses`
--
ALTER TABLE `campuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `excel`
--
ALTER TABLE `excel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hostels`
--
ALTER TABLE `hostels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `hostel_attributes`
--
ALTER TABLE `hostel_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `info`
--
ALTER TABLE `info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `system`
--
ALTER TABLE `system`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `uploaded_files`
--
ALTER TABLE `uploaded_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
