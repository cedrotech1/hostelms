-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2025 at 02:18 PM
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
  `ReceptNumber` varchar(100) NOT NULL,
  `Date_of_payment` varchar(100) NOT NULL,
  `createdby` varchar(10) NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `regnumber`, `room_id`, `status`, `slep`, `ReceptNumber`, `Date_of_payment`, `createdby`, `created_at`, `updated_at`) VALUES
(1, 20231003, 7, 'pending', '', '', '', 'student', '2025-06-24 11:13:21', '2025-06-24 11:13:21');

-- --------------------------------------------------------

--
-- Table structure for table `campuses`
--

CREATE TABLE `campuses` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campuses`
--

INSERT INTO `campuses` (`id`, `name`, `createdBy`, `updatedBy`, `createdAt`, `updatedAt`) VALUES
(1, 'huye', '1', '1', '2025-06-24 12:11:44', '2025-06-24 12:12:34'),
(2, 'gikondo', '1', '1', '2025-06-24 12:11:44', '2025-06-24 12:12:34'),
(3, 'nyarugenge', '1', '1', '2025-06-24 12:11:44', '2025-06-24 12:12:34'),
(4, 'remera', '1', '1', '2025-06-24 12:11:44', '2025-06-24 12:12:34'),
(5, 'busogo', '1', '1', '2025-06-24 12:11:44', '2025-06-24 12:12:34'),
(6, 'nyagatare', '1', '1', '2025-06-24 12:11:44', '2025-06-24 12:12:34'),
(7, 'rukara', '1', '1', '2025-06-24 12:11:44', '2025-06-24 12:12:34');

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
  `building_code` varchar(100) NOT NULL,
  `othernames` varchar(200) NOT NULL,
  `campus_id` int(11) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `year` varchar(10) NOT NULL,
  `college` varchar(100) NOT NULL,
  `school` varchar(255) NOT NULL,
  `intake` varchar(100) NOT NULL,
  `disability` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(100) NOT NULL DEFAULT 'draft',
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hostels`
--

INSERT INTO `hostels` (`id`, `name`, `building_code`, `othernames`, `campus_id`, `gender`, `year`, `college`, `school`, `intake`, `disability`, `status`, `createdBy`, `updatedBy`, `createdAt`, `updatedAt`) VALUES
(6, 'remera-boys', 'B001', 'Block A', 4, 'M', '1,3', '', '', '', 0, 'published', '3', '1', '2025-06-24 10:58:02', '2025-06-24 12:15:04'),
(7, 'remera-girls', 'B002', 'Block B', 4, 'F', '2', '', '', '', 0, 'published', '3', '3', '2025-06-24 10:58:02', '2025-06-24 12:15:09'),
(8, 'KAMBOG', 'B002', 'UI', 1, 'M', '3', '', '', '', 0, 'published', '1', '1', '2025-06-24 11:11:11', '2025-06-24 11:11:17');

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
  `intake` varchar(30) NOT NULL,
  `disability` int(11) NOT NULL DEFAULT 0,
  `yearofstudy` varchar(100) DEFAULT NULL,
  `email` varchar(30) NOT NULL,
  `gender` varchar(30) NOT NULL,
  `nid` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `token` varchar(300) NOT NULL,
  `status` varchar(30) NOT NULL,
  `code` int(11) NOT NULL,
  `current_application` varchar(20) NOT NULL,
  `password` varchar(200) NOT NULL DEFAULT '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `info`
--

INSERT INTO `info` (`id`, `regnumber`, `campus`, `college`, `names`, `school`, `program`, `intake`, `disability`, `yearofstudy`, `email`, `gender`, `nid`, `phone`, `token`, `status`, `code`, `current_application`, `password`) VALUES
(1, '20231001', 'huye', 'CASS', 'Cedrick hakuzimana', 'School of Journalism', 'Computer Science', 'May-2022', 1, '1', 'cedrickhakuzimana@gmail.com', 'M', '1234567890100000', '0784366616', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(2, '20231002', 'huye', 'CBE', 'patrick murisa', 'School of Business', 'Economics', 'Dec-2022', 0, '2', 'cedrotech1@gmail.com', 'M', '1234567890100001', '0783043021', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(3, '20231003', 'remera', 'CAVM', 'Alice Johnson', 'School of Veterinary Medicine', 'Agribusiness', 'May-2023', 0, '3', 'alice.johnson3@gmail.com', 'M', '1234567890100002', '0784366616', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(4, '20231004', 'huye', 'CST', 'Bob Williams', 'School of Engineering', 'Nursing', 'Dec-2023', 0, '4', 'cedrojoe@gmail.com', 'F', '1234567890100003', '0781300003', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(5, '20231005', 'gikondo', 'CMHS', 'Emily Brown', 'School of Nursing', 'Civil Engineering', 'May-2022', 0, '5', 'emily.brown5@gmail.com', 'M', '1234567890100004', '0781300004', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(6, '20231006', 'remera', 'CE', 'David Jones', 'School of Distance Learning', 'Education', 'Dec-2022', 0, '6', 'david.jones6@gmail.com', 'F', '1234567890100005', '0781300005', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(7, '20231007', 'huye', 'CASS', 'Grace Miller', 'School of Journalism', 'Veterinary Science', 'May-2023', 0, '1', 'grace.miller7@gmail.com', 'M', '1234567890100006', '0781300006', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(8, '20231008', 'gikondo', 'CBE', 'James Davis', 'School of Business', 'Accounting', 'Dec-2023', 0, '2', 'james.davis8@gmail.com', 'F', '1234567890100007', '0781300007', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(9, '20231009', 'remera', 'CAVM', 'Lucy Wilson', 'School of Veterinary Medicine', 'Medicine', 'May-2022', 0, '3', 'lucy.wilson9@gmail.com', 'M', '1234567890100008', '0781300008', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(10, '20231010', 'huye', 'CST', 'Michael Anderson', 'School of Engineering', 'Journalism', 'Dec-2022', 0, '4', 'michael.anderson10@gmail.com', 'F', '1234567890100009', '0781300009', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(11, '20231011', 'gikondo', 'CMHS', 'John Doe', 'School of Nursing', 'Computer Science', 'May-2023', 0, '5', 'john.doe11@gmail.com', 'M', '1234567890100010', '0781300010', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(12, '20231012', 'remera', 'CE', 'Jane Smith', 'School of Distance Learning', 'Economics', 'Dec-2023', 0, '6', 'jane.smith12@gmail.com', 'F', '1234567890100011', '0781300011', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(13, '20231013', 'huye', 'CASS', 'Alice Johnson', 'School of Journalism', 'Agribusiness', 'May-2022', 0, '1', 'alice.johnson13@gmail.com', 'M', '1234567890100012', '0781300012', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(14, '20231014', 'gikondo', 'CBE', 'Bob Williams', 'School of Business', 'Nursing', 'Dec-2022', 0, '2', 'bob.williams14@gmail.com', 'F', '1234567890100013', '0781300013', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(15, '20231015', 'remera', 'CAVM', 'Emily Brown', 'School of Veterinary Medicine', 'Civil Engineering', 'May-2023', 0, '3', 'emily.brown15@gmail.com', 'M', '1234567890100014', '0781300014', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(16, '20231016', 'huye', 'CST', 'David Jones', 'School of Engineering', 'Education', 'Dec-2023', 0, '4', 'david.jones16@gmail.com', 'F', '1234567890100015', '0781300015', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(17, '20231017', 'gikondo', 'CMHS', 'Grace Miller', 'School of Nursing', 'Veterinary Science', 'May-2022', 0, '5', 'grace.miller17@gmail.com', 'M', '1234567890100016', '0781300016', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(18, '20231018', 'remera', 'CE', 'James Davis', 'School of Distance Learning', 'Accounting', 'Dec-2022', 0, '6', 'james.davis18@gmail.com', 'F', '1234567890100017', '0781300017', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(19, '20231019', 'huye', 'CASS', 'Lucy Wilson', 'School of Journalism', 'Medicine', 'May-2023', 0, '1', 'lucy.wilson19@gmail.com', 'M', '1234567890100018', '0781300018', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(20, '20231020', 'gikondo', 'CBE', 'Michael Anderson', 'School of Business', 'Journalism', 'Dec-2023', 0, '2', 'michael.anderson20@gmail.com', 'F', '1234567890100019', '0781300019', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(21, '20231021', 'remera', 'CAVM', 'John Doe', 'School of Veterinary Medicine', 'Computer Science', 'May-2022', 0, '3', 'john.doe21@gmail.com', 'M', '1234567890100020', '0781300020', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(22, '20231022', 'huye', 'CST', 'Jane Smith', 'School of Engineering', 'Economics', 'Dec-2022', 0, '4', 'jane.smith22@gmail.com', 'F', '1234567890100021', '0781300021', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(23, '20231023', 'gikondo', 'CMHS', 'Alice Johnson', 'School of Nursing', 'Agribusiness', 'May-2023', 0, '5', 'alice.johnson23@gmail.com', 'M', '1234567890100022', '0781300022', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(24, '20231024', 'remera', 'CE', 'Bob Williams', 'School of Distance Learning', 'Nursing', 'Dec-2023', 0, '6', 'bob.williams24@gmail.com', 'F', '1234567890100023', '0781300023', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(25, '20231025', 'huye', 'CASS', 'Emily Brown', 'School of Journalism', 'Civil Engineering', 'May-2022', 0, '1', 'emily.brown25@gmail.com', 'M', '1234567890100024', '0781300024', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(26, '20231026', 'gikondo', 'CBE', 'David Jones', 'School of Business', 'Education', 'Dec-2022', 0, '2', 'david.jones26@gmail.com', 'F', '1234567890100025', '0781300025', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(27, '20231027', 'remera', 'CAVM', 'Grace Miller', 'School of Veterinary Medicine', 'Veterinary Science', 'May-2023', 0, '3', 'grace.miller27@gmail.com', 'M', '1234567890100026', '0781300026', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(28, '20231028', 'huye', 'CST', 'James Davis', 'School of Engineering', 'Accounting', 'Dec-2023', 1, '4', 'james.davis28@gmail.com', 'F', '1234567890100027', '0781300027', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(29, '20231029', 'gikondo', 'CMHS', 'Lucy Wilson', 'School of Nursing', 'Medicine', 'May-2022', 0, '5', 'lucy.wilson29@gmail.com', 'M', '1234567890100028', '0781300028', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(30, '20231030', 'remera', 'CE', 'Michael Anderson', 'School of Distance Learning', 'Journalism', 'Dec-2022', 0, '6', 'michael.anderson30@gmail.com', 'F', '1234567890100029', '0781300029', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(31, '20231031', 'huye', 'CASS', 'John Doe', 'School of Journalism', 'Computer Science', 'May-2023', 0, '1', 'john.doe31@gmail.com', 'M', '1234567890100030', '0781300030', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(32, '20231032', 'gikondo', 'CBE', 'Jane Smith', 'School of Business', 'Economics', 'Dec-2023', 0, '2', 'jane.smith32@gmail.com', 'F', '1234567890100031', '0781300031', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(33, '20231033', 'remera', 'CAVM', 'Alice Johnson', 'School of Veterinary Medicine', 'Agribusiness', 'May-2022', 0, '3', 'alice.johnson33@gmail.com', 'M', '1234567890100032', '0781300032', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(34, '20231034', 'huye', 'CST', 'Bob Williams', 'School of Engineering', 'Nursing', 'Dec-2022', 0, '4', 'bob.williams34@gmail.com', 'F', '1234567890100033', '0781300033', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(35, '20231035', 'gikondo', 'CMHS', 'Emily Brown', 'School of Nursing', 'Civil Engineering', 'May-2023', 0, '5', 'emily.brown35@gmail.com', 'M', '1234567890100034', '0781300034', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(36, '20231036', 'remera', 'CE', 'David Jones', 'School of Distance Learning', 'Education', 'Dec-2023', 0, '6', 'david.jones36@gmail.com', 'F', '1234567890100035', '0781300035', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(37, '20231037', 'huye', 'CASS', 'Grace Miller', 'School of Journalism', 'Veterinary Science', 'May-2022', 0, '1', 'grace.miller37@gmail.com', 'M', '1234567890100036', '0781300036', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(38, '20231038', 'gikondo', 'CBE', 'James Davis', 'School of Business', 'Accounting', 'Dec-2022', 0, '2', 'james.davis38@gmail.com', 'F', '1234567890100037', '0781300037', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(39, '20231039', 'remera', 'CAVM', 'Lucy Wilson', 'School of Veterinary Medicine', 'Medicine', 'May-2023', 0, '3', 'lucy.wilson39@gmail.com', 'M', '1234567890100038', '0781300038', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(40, '20231040', 'huye', 'CST', 'Michael Anderson', 'School of Engineering', 'Journalism', 'Dec-2023', 0, '4', 'michael.anderson40@gmail.com', 'F', '1234567890100039', '0781300039', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(41, '20231041', 'gikondo', 'CMHS', 'John Doe', 'School of Nursing', 'Computer Science', 'May-2022', 0, '5', 'john.doe41@gmail.com', 'M', '1234567890100040', '0781300040', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(42, '20231042', 'remera', 'CE', 'Jane Smith', 'School of Distance Learning', 'Economics', 'Dec-2022', 0, '6', 'jane.smith42@gmail.com', 'F', '1234567890100041', '0781300041', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(43, '20231043', 'huye', 'CASS', 'Alice Johnson', 'School of Journalism', 'Agribusiness', 'May-2023', 0, '1', 'alice.johnson43@gmail.com', 'M', '1234567890100042', '0781300042', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(44, '20231044', 'gikondo', 'CBE', 'Bob Williams', 'School of Business', 'Nursing', 'Dec-2023', 0, '2', 'bob.williams44@gmail.com', 'F', '1234567890100043', '0781300043', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(45, '20231045', 'remera', 'CAVM', 'Emily Brown', 'School of Veterinary Medicine', 'Civil Engineering', 'May-2022', 0, '3', 'emily.brown45@gmail.com', 'M', '1234567890100044', '0781300044', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(46, '20231046', 'huye', 'CST', 'David Jones', 'School of Engineering', 'Education', 'Dec-2022', 0, '4', 'david.jones46@gmail.com', 'F', '1234567890100045', '0781300045', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(47, '20231047', 'gikondo', 'CMHS', 'Grace Miller', 'School of Nursing', 'Veterinary Science', 'May-2023', 0, '5', 'grace.miller47@gmail.com', 'M', '1234567890100046', '0781300046', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(48, '20231048', 'remera', 'CE', 'James Davis', 'School of Distance Learning', 'Accounting', 'Dec-2023', 0, '6', 'james.davis48@gmail.com', 'F', '1234567890100047', '0781300047', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(49, '20231049', 'huye', 'CASS', 'Lucy Wilson', 'School of Journalism', 'Medicine', 'May-2022', 0, '1', 'lucy.wilson49@gmail.com', 'M', '1234567890100048', '0781300048', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe'),
(50, '20231050', 'gikondo', 'CBE', 'Michael Anderson', 'School of Business', 'Journalism', 'Dec-2022', 0, '2', 'michael.anderson50@gmail.com', 'F', '1234567890100049', '0781300049', '', 'active', 0, '', '$2y$10$H2514DdF9L0vw755rCurN.IeH9UqA7sXg4wlkquPRGkdCinWuzuNe');

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
  `remain` int(11) NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'published',
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_code`, `number_of_beds`, `hostel_id`, `remain`, `status`, `createdBy`, `updatedBy`, `createdAt`, `updatedAt`) VALUES
(4, 'A101', 4, 6, 4, 'published', '3', '3', '2025-06-24 10:58:02', '2025-06-24 10:58:02'),
(5, 'A102', 4, 6, 4, 'published', '3', '3', '2025-06-24 10:58:02', '2025-06-24 10:58:02'),
(6, 'B101', 2, 7, 2, 'published', '3', '3', '2025-06-24 10:58:02', '2025-06-24 10:58:02'),
(7, '7G00', 3, 8, 2, 'published', '1', '1', '2025-06-24 11:11:35', '2025-06-24 11:13:21');

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
(1, 'development', '', '', '', '', '');

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
(1, 'cedrick', 'cedrickhakuzimana@gmail.com', '0783043021', 'upload/av.png', '', 'information_modifier', '$2y$10$5OhGuQPwsrHkVzq9b91vO.KowcpwDdbpM2ZAogWii.xZf4ya0sLSK', 1, 0, 0),
(2, 'pier', 'akimana@gmail.com', '07843534345', 'assets/img/av.png', '', 'warefare', '$2y$10$R/9MVQqrOyE0X54/T3noP.OxvmRkE/wSaKffXUqgbKhIAGD0nN1dG', 1, 0, 1),
(3, 'remera', 'remera@gmail.com', '0784366616', 'assets/img/av.png', '', 'warefare', '$2y$10$kxQrvZ8/GerCl3er0SrF1OQogcEzgq2z5VNItaIxcdvPggOvxIG5q', 1, 0, 4);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `campuses`
--
ALTER TABLE `campuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `excel`
--
ALTER TABLE `excel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hostels`
--
ALTER TABLE `hostels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `hostel_attributes`
--
ALTER TABLE `hostel_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `info`
--
ALTER TABLE `info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
