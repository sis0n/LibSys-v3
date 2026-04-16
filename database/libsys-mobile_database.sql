-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2026 at 02:58 PM
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
-- Database: `libsys-mobile`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `first_scan_at` datetime NOT NULL,
  `last_scan_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_number` varchar(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT '',
  `year_level` int(11) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `course_id` int(11) DEFAULT 0,
  `method` enum('qr','manual') DEFAULT 'qr',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `resource` varchar(50) DEFAULT NULL,
  `resource_id` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_log`
--

CREATE TABLE `backup_log` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `size` decimal(10,2) DEFAULT 0.00,
  `created_by` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `accession_number` varchar(50) NOT NULL,
  `call_number` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `book_place` varchar(150) DEFAULT NULL,
  `book_publisher` varchar(150) DEFAULT NULL,
  `campus_id` int(11) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `book_edition` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `book_isbn` varchar(50) DEFAULT NULL,
  `book_supplementary` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `availability` enum('available','borrowed','damaged','lost') DEFAULT 'available',
  `borrowing_duration_override` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `cover` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `borrow_transactions`
--

CREATE TABLE `borrow_transactions` (
  `transaction_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `collateral_id` int(11) DEFAULT NULL,
  `transaction_code` varchar(50) DEFAULT NULL,
  `qrcode` varchar(255) DEFAULT NULL,
  `borrowed_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `status` enum('pending','borrowed','returned','overdue','expired') DEFAULT 'pending',
  `method` enum('manual','qr') NOT NULL DEFAULT 'qr',
  `librarian_id` int(11) DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `borrow_transaction_items`
--

CREATE TABLE `borrow_transaction_items` (
  `item_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `book_id` int(11) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `status` enum('pending','borrowed','returned','damaged','lost','expired') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_delete_requests`
--

CREATE TABLE `bulk_delete_requests` (
  `request_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `approver_id` int(11) DEFAULT NULL,
  `entity_type` enum('books','equipments') NOT NULL,
  `status` enum('pending','approved','rejected','executed','expired') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `decided_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_delete_request_items`
--

CREATE TABLE `bulk_delete_request_items` (
  `item_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campuses`
--

CREATE TABLE `campuses` (
  `campus_id` int(11) NOT NULL,
  `campus_code` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `campus_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campuses`
--

INSERT INTO `campuses` (`campus_id`, `campus_code`, `is_active`, `campus_name`, `created_at`) VALUES
(1, 'SOUTH-MAINCAMPUS', 1, 'South - Main Campus', '2026-03-26 11:28:19'),
(2, 'NORTH-CONGRESSCAMPUS', 1, 'North - Congress Campus', '2026-03-26 11:28:19'),
(3, 'NORTH-CAMARINCAMPUS', 1, 'North - Camarin Campus', '2026-03-26 11:28:19'),
(4, 'NORTH-ENGINEERINGCAMPUS', 1, 'North - Engineering Campus', '2026-03-27 02:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `book_id` int(11) NOT NULL,
  `added_at` datetime DEFAULT current_timestamp(),
  `checkout_token` varchar(255) DEFAULT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collaterals`
--

CREATE TABLE `collaterals` (
  `collateral_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collaterals`
--

INSERT INTO `collaterals` (`collateral_id`, `name`, `created_at`) VALUES
(1, 'School ID', '2026-02-28 11:22:27'),
(2, 'Library ID', '2026-02-28 11:22:27'),
(3, 'Driver\'s License', '2026-02-28 11:22:27'),
(4, 'National ID', '2026-02-28 11:24:54');

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `college_id` int(11) NOT NULL,
  `college_code` varchar(10) NOT NULL,
  `college_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`college_id`, `college_code`, `college_name`) VALUES
(1, 'CBA', 'College of Business and Accountancy'),
(2, 'CCJE', 'College of Criminal Justice Education'),
(3, 'COE', 'College of Education'),
(4, 'COEngr', 'College of Engineering'),
(5, 'LAW', 'College of Law'),
(6, 'CLAS', 'College of Liberal Arts and Sciences'),
(7, 'GS', 'Graduate School');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_title` varchar(255) NOT NULL,
  `college_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_title`, `college_id`) VALUES
(1, 'BSA', 'Bachelor of Science in Accountancy', 1),
(2, 'BSAIS', 'Bachelor of Science in Accounting Information Systems', 1),
(3, 'BSBA FMGT', 'BSBA Major in Financial Management', 1),
(4, 'BSBA HRM', 'BSBA Major in Human Resource Management', 1),
(5, 'BSBA MKTG', 'BSBA Major in Marketing Management', 1),
(6, 'BS ENTREP', 'Bachelor of Science in Entrepreneurship', 1),
(7, 'BS CRIM', 'Bachelor of Science in Criminology', 2),
(8, 'BSISM', 'Bachelor of Science in Industrial Security Management', 2),
(9, 'BECED', 'Bachelor of Early Childhood Education', 3),
(10, 'BSE ENG', 'Bachelor of Secondary Education Major in English', 3),
(11, 'BSE ENG-CHI', 'Bachelor of Secondary Education Major in English with Additional Chinese Language and Pedagogy Courses', 3),
(12, 'BSE SCI', 'Bachelor of Secondary Education Major in Science', 3),
(13, 'BTLED HE', 'Bachelor of Technology and Livelihood Education Major in Home Economics', 3),
(14, 'CPE', 'Certificate in Professional Education', 3),
(15, 'BS CPE', 'Bachelor of Science in Computer Engineering', 4),
(16, 'BS ECE', 'Bachelor of Science in Electronics Engineering', 4),
(17, 'BS EE', 'Bachelor of Science in Electrical Engineering', 4),
(18, 'BS IE', 'Bachelor of Science in Industrial Engineering', 4),
(19, 'LAW', 'Bachelor of Laws / Juris Doctor', 5),
(20, 'ABBS', 'Bachelor of Arts in Behavioral Sciences', 6),
(21, 'BA COMM', 'Bachelor of Arts in Communication', 6),
(22, 'BA POS', 'Bachelor of Arts in Political Science', 6),
(23, 'BS MATH', 'Bachelor of Science in Mathematics', 6),
(24, 'BS PSY', 'Bachelor of Science in Psychology', 6),
(25, 'BSIS', 'Bachelor of Science in Information Systems', 6),
(26, 'BSIT', 'Bachelor of Science in Information Technology', 6),
(27, 'BSCS', 'Bachelor of Science in Computer Science', 6),
(28, 'BSEMC', 'Bachelor of Science in Entertainment and Multimedia Computing', 6),
(29, 'BSOAD', 'Bachelor of Science in Office Administration', 6),
(30, 'BSSW', 'Bachelor of Science in Social Work', 6),
(31, 'BSTM', 'Bachelor of Science in Tourism Management', 6),
(32, 'BSHM', 'Bachelor of Science in Hospitality Management', 6),
(33, 'DPA', 'Doctor in Public Administration', 7),
(34, 'BPA', 'Bachelor of Public Administration', 7),
(35, 'BPA ECGE', 'Bachelor of Public Administration – Evening Class for Govt. Employees', 7),
(36, 'MAED', 'Master of Arts in Education Major in Educational Management', 7),
(37, 'MAT-EG', 'Master of Arts in Teaching in the Early Grades', 7),
(38, 'MATS', 'Master of Arts in Teaching Science', 7),
(39, 'MBA', 'Master in Business Administration', 7);

-- --------------------------------------------------------

--
-- Table structure for table `deleted_books`
--

CREATE TABLE `deleted_books` (
  `deleted_book_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `accession_number` varchar(50) NOT NULL,
  `call_number` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `book_place` varchar(150) DEFAULT NULL,
  `book_publisher` varchar(150) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `book_edition` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `book_isbn` varchar(50) DEFAULT NULL,
  `book_supplementary` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `availability` enum('available','borrowed') DEFAULT 'available',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `cover` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_students`
--

CREATE TABLE `deleted_students` (
  `student_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_number` varchar(20) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `section` varchar(5) DEFAULT NULL,
  `status` enum('enrolled','dropped','transferred') DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_users`
--

CREATE TABLE `deleted_users` (
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` datetime DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('superadmin','admin','librarian','student','scanner') DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipments`
--

CREATE TABLE `equipments` (
  `equipment_id` int(11) NOT NULL,
  `equipment_name` varchar(255) NOT NULL,
  `campus_id` int(11) DEFAULT NULL,
  `asset_tag` varchar(50) DEFAULT NULL,
  `status` enum('available','borrowed','damaged','maintenance','lost') NOT NULL DEFAULT 'available',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `unique_faculty_id` varchar(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `college_id` int(11) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `profile_updated` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `guest_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_policies`
--

CREATE TABLE `library_policies` (
  `policy_id` int(11) NOT NULL,
  `campus_id` int(11) NOT NULL,
  `role` enum('student','faculty','staff','equipment') NOT NULL,
  `max_books` int(11) DEFAULT 5,
  `borrow_duration_days` int(11) DEFAULT 7,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `library_policies`
--

INSERT INTO `library_policies` (`policy_id`, `campus_id`, `role`, `max_books`, `borrow_duration_days`, `updated_at`) VALUES
(1, 1, 'student', 10, 2, '2026-03-30 04:52:52'),
(2, 1, 'faculty', 2, 3, '2026-03-30 04:50:39'),
(3, 1, 'staff', 1, 1, '2026-03-30 04:30:07'),
(4, 1, 'equipment', 5, 0, '2026-03-30 04:30:07'),
(6, 2, 'student', 10, 1, '2026-03-30 04:31:19'),
(7, 2, 'faculty', 2, 2, '2026-03-30 04:31:19'),
(8, 2, 'staff', 1, 1, '2026-03-30 04:31:19'),
(9, 2, 'equipment', 5, 0, '2026-03-30 04:31:19'),
(13, 3, 'student', 10, 1, '2026-03-30 04:31:19'),
(14, 3, 'faculty', 2, 1, '2026-03-30 04:50:57'),
(15, 3, 'staff', 1, 1, '2026-03-30 04:31:19'),
(16, 3, 'equipment', 5, 0, '2026-03-30 04:31:19'),
(20, 4, 'student', 10, 1, '2026-03-30 04:31:19'),
(21, 4, 'faculty', 2, 2, '2026-03-30 04:31:19'),
(22, 4, 'staff', 1, 1, '2026-03-30 04:31:19'),
(23, 4, 'equipment', 5, 0, '2026-03-30 04:31:19');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_01_28_132028_create_oauth_auth_codes_table', 1),
(2, '2026_01_28_132029_create_oauth_access_tokens_table', 1),
(3, '2026_01_28_132030_create_oauth_refresh_tokens_table', 1),
(4, '2026_01_28_132031_create_oauth_clients_table', 1),
(5, '2026_01_28_132032_create_oauth_device_codes_table', 1),
(6, '2026_01_28_133225_create_oauth_auth_codes_table', 2),
(7, '2026_01_28_133226_create_oauth_access_tokens_table', 2),
(8, '2026_01_28_133227_create_oauth_refresh_tokens_table', 2),
(9, '2026_01_28_133228_create_oauth_clients_table', 2),
(10, '2026_01_28_133607_create_oauth_auth_codes_table', 3),
(11, '2026_01_28_133608_create_oauth_access_tokens_table', 3),
(12, '2026_01_28_133609_create_oauth_refresh_tokens_table', 3),
(13, '2026_01_28_133610_create_oauth_clients_table', 3),
(14, '2026_01_28_133611_create_oauth_device_codes_table', 3),
(15, '2026_01_28_134504_create_oauth_auth_codes_table', 4),
(16, '2026_01_28_134505_create_oauth_access_tokens_table', 4),
(17, '2026_01_28_134506_create_oauth_refresh_tokens_table', 4),
(18, '2026_01_28_134507_create_oauth_clients_table', 4),
(19, '2026_01_28_134508_create_oauth_device_codes_table', 4),
(20, '2026_01_28_141753_create_oauth_auth_codes_table', 5),
(21, '2026_01_28_141754_create_oauth_access_tokens_table', 5),
(22, '2026_01_28_141755_create_oauth_refresh_tokens_table', 5),
(23, '2026_01_28_141756_create_oauth_clients_table', 5),
(24, '2026_01_28_141757_create_oauth_device_codes_table', 5),
(25, '2026_01_28_142404_create_cache_table', 6),
(26, '2026_01_28_152710_create_oauth_auth_codes_table', 7),
(27, '2026_01_28_152711_create_oauth_access_tokens_table', 7),
(28, '2026_01_28_152712_create_oauth_refresh_tokens_table', 7),
(29, '2026_01_28_152713_create_oauth_clients_table', 7),
(30, '2026_01_28_152714_create_oauth_device_codes_table', 7),
(31, '2026_03_01_121558_add_qrcode_to_borrow_transactions_table', 8);

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL,
  `borrowing_item_id` int(11) NOT NULL,
  `recipient_user_id` int(11) NOT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_access_tokens`
--

CREATE TABLE `oauth_access_tokens` (
  `id` char(80) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` char(36) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_access_tokens`
--

INSERT INTO `oauth_access_tokens` (`id`, `user_id`, `client_id`, `name`, `scopes`, `revoked`, `created_at`, `updated_at`, `expires_at`) VALUES
('00f9449232350090a22804db249e58d8255b11171aab04d1f69030b9012ab9e00eec749710706599', 30280, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-02-18 06:16:28', '2026-02-18 06:16:29', '2027-02-18 14:16:28'),
('05e78fead76702c889528217a61b3daefad8eb3736d0890f9985375d1f3d9431681233b6f97105c1', 30282, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-02-16 22:57:21', '2026-02-16 22:57:21', '2027-02-17 06:57:21'),
('22b5603b302fb5d36867821d581713324ca3c7716ab7848e2170d8efaeb879636303fefd2597491e', 27005, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-02-04 20:46:08', '2026-02-04 20:46:08', '2027-02-05 04:46:08'),
('2609bf873e30f8ec91013b8cc15e0f7ba2b26a19d053180718122860a3c5b8fa1ca32aaab396e7f3', 26154, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-02-04 20:45:28', '2026-02-04 20:45:28', '2027-02-05 04:45:28'),
('3a063adfad394a534f8c9581f7f12da0290c29f63bd4cfb87ebba60fe34f04cc67352b73c21af836', 30289, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-02 04:59:00', '2026-03-02 04:59:00', '2027-03-02 12:59:00'),
('414d31a794e1cad132e75fa066c7799e416899e1e35b3cdf7cf2991e046e815cdd533dad0bd8019c', 1, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-02-13 05:45:48', '2026-02-13 05:45:48', '2027-02-13 13:45:48'),
('41e00b0aca6c388c399d65f3d1343d817cf65957adfa85bee84955549b16cb6edfdeadd2e4324477', 35345, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-02 05:00:05', '2026-03-02 05:00:05', '2027-03-02 13:00:05'),
('4565758be6e5d588db658e9acc95e1837692849327a94b8a8434439be292d0b2dee7132b4939db3c', 120053, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-03 22:03:20', '2026-03-03 22:03:20', '2027-03-04 06:03:20'),
('5f4f1ddefff4aeb95a183ca9a07be1d940d8a58efd19d2c18c63fb22340ae34677e8af41a94e7e31', 117028, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-03 05:08:41', '2026-03-03 05:08:41', '2027-03-03 13:08:41'),
('7609a8af5f1aed7822b184c45572abf533a12ee7cb90d4683915da8ce1cf92f7c20df0c8929bb17c', 117031, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-16 06:12:19', '2026-03-16 06:12:19', '2027-03-16 14:12:19'),
('777d213b3ccaf6089dff29752e0631cc6ccfd6b86f067eddfe14b3e8f96b3479c54f6890e5aead02', 117212, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-05 19:58:45', '2026-03-05 19:58:45', '2027-03-06 03:58:45'),
('7e20b03240a415226dc519ec5d515bb1d5a72c5148dfac79d1480a6a32af222032e69b65f8867536', 105360, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-03 06:59:28', '2026-03-03 06:59:28', '2027-03-03 14:59:28'),
('8e82d06974e3b8a37167266327047c1d64ee015a5cb184620ddf880e51e6fb5f1e3c5c9b8c1a7b4b', 105540, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-03 04:20:35', '2026-03-03 04:20:35', '2027-03-03 12:20:35'),
('92d85bc549bc0faee23a3987ff4ccca3430ea6d0e55dcb83511263e26bb4a1b8924268a0096e3f77', 25227, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 1, '2026-02-16 22:21:44', '2026-02-16 22:24:34', '2027-02-17 06:21:44'),
('97b385d7d17cc043160d5e9fe35bdf1b9e4fff46ab3ec059fa42a2a9d87b9f8393be4be7457f8bde', 25221, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-02-04 20:31:12', '2026-02-04 20:31:12', '2027-02-05 04:31:12'),
('9e840547ebeb3011231311d55cd2e331aa693a40e6bd60805dbfd67719d3bda38f922f54b21fd901', 117030, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-03 06:55:32', '2026-03-03 06:55:32', '2027-03-03 14:55:32'),
('a3952460ac2572ec67ded149db56c45989fc5e778ce1f3fa78c303832614795fb6d4e7b529b04363', 32509, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-01 02:51:15', '2026-03-01 02:51:15', '2027-03-01 10:51:15'),
('a4acd3bac415342f21dca7d0dc0196d6318a14a9c7c34b412c5358e3264b89e967781193474d9bd2', 122348, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-05 19:52:52', '2026-03-05 19:52:52', '2027-03-06 03:52:52'),
('b727bd5a737b92cd103b4335dc3876b7765fb48e60df45d363d0e56537cd0bf528fa8abfaf997223', 123801, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-05 19:52:42', '2026-03-05 19:52:42', '2027-03-06 03:52:42'),
('b9265d652f3dec18b5e9b6a64e3e8d9bd91ca949140cbe95f4d8a92766ede3959020c53b2814cf9b', 25229, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-02-18 05:45:20', '2026-02-18 05:45:20', '2027-02-18 13:45:20'),
('b9b724c78dfd23542469c442fc0b36913ec18e6c7bc65e032ccc2fd0a5b4b57639527d0db1039907', 128699, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-03 22:04:51', '2026-03-03 22:04:51', '2027-03-04 06:04:51'),
('bb24bb7de3526a02e71cc748e49ccfee5f3525e47c129dce0f089b10c9505581f94672a53f739730', 30274, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-02-16 22:24:48', '2026-02-16 22:24:48', '2027-02-17 06:24:48'),
('c0870616dda697d9e4b8c72a63ca9daed4a232d2af7b830dea989fe159c41ce93097aeb70041143e', 58686, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-05 05:58:33', '2026-03-05 05:58:33', '2027-03-05 13:58:33'),
('c7331906d8ee0cba38f57ac2b6edf7c601776a963eceb183ffb9d8da24393b9434fd3d268fccbb27', 26995, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-02-18 06:46:05', '2026-02-18 06:46:05', '2027-02-18 14:46:05'),
('dc648586e2118a1ad1bc0ce8743e9ded25965f7b083b599ee7fe98cf538f39e62987fa02b6289c27', 32071, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-02 23:44:21', '2026-03-02 23:44:21', '2027-03-03 07:44:21'),
('f553ca8bb824d514d16a2c20657f76f3f5b27ccc276874ced080a8203b3d8a45eb2beb0dbe8cdd61', 32061, '019c0537-f2ad-7316-ac1b-42f7e7156c86', 'AuthToken', '[]', 0, '2026-03-01 04:18:48', '2026-03-01 04:18:48', '2027-03-01 12:18:48');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_auth_codes`
--

CREATE TABLE `oauth_auth_codes` (
  `id` char(80) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` char(36) NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_clients`
--

CREATE TABLE `oauth_clients` (
  `id` char(36) NOT NULL,
  `owner_type` varchar(255) DEFAULT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `secret` varchar(255) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `redirect_uris` text NOT NULL,
  `grant_types` text NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_clients`
--

INSERT INTO `oauth_clients` (`id`, `owner_type`, `owner_id`, `name`, `secret`, `provider`, `redirect_uris`, `grant_types`, `revoked`, `created_at`, `updated_at`) VALUES
('019c0537-f2ad-7316-ac1b-42f7e7156c86', NULL, NULL, 'Laravel', '$2y$12$7V9PfEGkxb0sVXIcHbcbUuHteJUAWzavayUqmnUvwiruRX79YyKyC', 'users', '[]', '[\"personal_access\"]', 0, '2026-01-28 07:27:58', '2026-01-28 07:27:58'),
('019c0539-4136-7208-94ed-70301f000667', NULL, NULL, 'BackendClient', '$2y$12$CxqW/wXePH9xazAr1tdjs.Tzs9rgi.e3zzrCskhoxhD11gnal2OCu', 'users', '[]', '[\"password\",\"refresh_token\"]', 0, '2026-01-28 07:29:24', '2026-01-28 07:29:24');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_device_codes`
--

CREATE TABLE `oauth_device_codes` (
  `id` char(80) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` char(36) NOT NULL,
  `user_code` char(8) NOT NULL,
  `scopes` text NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `user_approved_at` datetime DEFAULT NULL,
  `last_polled_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_refresh_tokens`
--

CREATE TABLE `oauth_refresh_tokens` (
  `id` char(80) NOT NULL,
  `access_token_id` char(80) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(10) NOT NULL COMMENT 'This stores the 6-digit OTP',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `action` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `report_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `generated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `profile_updated` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_number` varchar(20) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `status` enum('enrolled','dropped','transferred') DEFAULT 'enrolled',
  `profile_updated` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit_profile` tinyint(1) DEFAULT 0,
  `registration_form` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `campus_id` int(11) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('superadmin','admin','librarian','scanner','student','faculty','staff') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_module_permissions`
--

CREATE TABLE `user_module_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_name` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_date` (`user_id`,`date`),
  ADD KEY `idx_attendance_date` (`created_at`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`user_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_attendance_timestamp` (`timestamp`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_audit_created_at` (`created_at`),
  ADD KEY `idx_audit_action` (`action`);

--
-- Indexes for table `backup_log`
--
ALTER TABLE `backup_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `unique_accession_campus` (`accession_number`,`campus_id`),
  ADD KEY `fk_books_campus` (`campus_id`),
  ADD KEY `idx_book_is_active` (`is_active`),
  ADD KEY `idx_book_availability` (`availability`);

--
-- Indexes for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `fk_borrow_student` (`student_id`),
  ADD KEY `fk_borrow_faculty` (`faculty_id`),
  ADD KEY `fk_borrow_staff` (`staff_id`),
  ADD KEY `fk_borrow_guest` (`guest_id`),
  ADD KEY `fk_borrow_transactions_collateral` (`collateral_id`),
  ADD KEY `idx_borrowed_at` (`borrowed_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_librarian_id` (`librarian_id`);

--
-- Indexes for table `borrow_transaction_items`
--
ALTER TABLE `borrow_transaction_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `borrow_transaction_items_ibfk_2` (`book_id`),
  ADD KEY `idx_borrow_equipment_id` (`equipment_id`),
  ADD KEY `idx_item_status` (`status`),
  ADD KEY `idx_returned_at` (`returned_at`);

--
-- Indexes for table `bulk_delete_requests`
--
ALTER TABLE `bulk_delete_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `approver_id` (`approver_id`);

--
-- Indexes for table `bulk_delete_request_items`
--
ALTER TABLE `bulk_delete_request_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `campuses`
--
ALTER TABLE `campuses`
  ADD PRIMARY KEY (`campus_id`),
  ADD UNIQUE KEY `campus_code` (`campus_code`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `carts_ibfk_2` (`book_id`),
  ADD KEY `fk_faculty_id` (`faculty_id`),
  ADD KEY `fk_carts_user` (`user_id`);

--
-- Indexes for table `collaterals`
--
ALTER TABLE `collaterals`
  ADD PRIMARY KEY (`collateral_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`college_id`),
  ADD UNIQUE KEY `college_code` (`college_code`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `deleted_books`
--
ALTER TABLE `deleted_books`
  ADD PRIMARY KEY (`deleted_book_id`),
  ADD KEY `idx_original_book_id` (`book_id`),
  ADD KEY `idx_deleted_accession` (`accession_number`);

--
-- Indexes for table `deleted_students`
--
ALTER TABLE `deleted_students`
  ADD KEY `deleted_by` (`deleted_by`);

--
-- Indexes for table `deleted_users`
--
ALTER TABLE `deleted_users`
  ADD KEY `deleted_by` (`deleted_by`);

--
-- Indexes for table `equipments`
--
ALTER TABLE `equipments`
  ADD PRIMARY KEY (`equipment_id`),
  ADD UNIQUE KEY `asset_tag` (`asset_tag`),
  ADD KEY `fk_equipment_campus` (`campus_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `unique_faculty_id` (`unique_faculty_id`),
  ADD KEY `fk_user` (`user_id`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`guest_id`);

--
-- Indexes for table `library_policies`
--
ALTER TABLE `library_policies`
  ADD PRIMARY KEY (`policy_id`),
  ADD UNIQUE KEY `campus_role_unique` (`campus_id`,`role`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrowing_item_id` (`borrowing_item_id`),
  ADD KEY `idx_notif_sent_at` (`sent_at`);

--
-- Indexes for table `oauth_access_tokens`
--
ALTER TABLE `oauth_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_access_tokens_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_auth_codes`
--
ALTER TABLE `oauth_auth_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_auth_codes_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_clients_owner_type_owner_id_index` (`owner_type`,`owner_id`);

--
-- Indexes for table `oauth_device_codes`
--
ALTER TABLE `oauth_device_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `oauth_device_codes_user_code_unique` (`user_code`),
  ADD KEY `oauth_device_codes_user_id_index` (`user_id`),
  ADD KEY `oauth_device_codes_client_id_index` (`client_id`);

--
-- Indexes for table `oauth_refresh_tokens`
--
ALTER TABLE `oauth_refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_staff_status` (`status`),
  ADD KEY `idx_staff_position` (`position`),
  ADD KEY `fk_staff_deleted_by` (`deleted_by`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `student_number_2` (`student_number`),
  ADD KEY `fk_student_course` (`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `fk_user_campus` (`campus_id`),
  ADD KEY `idx_user_role` (`role`),
  ADD KEY `idx_user_is_active` (`is_active`);

--
-- Indexes for table `user_module_permissions`
--
ALTER TABLE `user_module_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1117;

--
-- AUTO_INCREMENT for table `backup_log`
--
ALTER TABLE `backup_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130698;

--
-- AUTO_INCREMENT for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- AUTO_INCREMENT for table `borrow_transaction_items`
--
ALTER TABLE `borrow_transaction_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `bulk_delete_requests`
--
ALTER TABLE `bulk_delete_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bulk_delete_request_items`
--
ALTER TABLE `bulk_delete_request_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `campuses`
--
ALTER TABLE `campuses`
  MODIFY `campus_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `collaterals`
--
ALTER TABLE `collaterals`
  MODIFY `collateral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `college_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `deleted_books`
--
ALTER TABLE `deleted_books`
  MODIFY `deleted_book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `equipments`
--
ALTER TABLE `equipments`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `library_policies`
--
ALTER TABLE `library_policies`
  MODIFY `policy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=266827;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=431775;

--
-- AUTO_INCREMENT for table `user_module_permissions`
--
ALTER TABLE `user_module_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=646;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `fk_books_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`) ON DELETE SET NULL;

--
-- Constraints for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  ADD CONSTRAINT `borrow_transactions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `fk_borrow_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`),
  ADD CONSTRAINT `fk_borrow_guest` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
  ADD CONSTRAINT `fk_borrow_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `fk_borrow_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `fk_borrow_transactions_collateral` FOREIGN KEY (`collateral_id`) REFERENCES `collaterals` (`collateral_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `borrow_transaction_items`
--
ALTER TABLE `borrow_transaction_items`
  ADD CONSTRAINT `borrow_transaction_items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `borrow_transactions` (`transaction_id`),
  ADD CONSTRAINT `borrow_transaction_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `fk_borrow_equipment_id` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`equipment_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `bulk_delete_requests`
--
ALTER TABLE `bulk_delete_requests`
  ADD CONSTRAINT `bulk_delete_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bulk_delete_requests_ibfk_2` FOREIGN KEY (`approver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `bulk_delete_request_items`
--
ALTER TABLE `bulk_delete_request_items`
  ADD CONSTRAINT `bulk_delete_request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `bulk_delete_requests` (`request_id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_faculty_id` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`);

--
-- Constraints for table `deleted_students`
--
ALTER TABLE `deleted_students`
  ADD CONSTRAINT `deleted_students_ibfk_1` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `deleted_users`
--
ALTER TABLE `deleted_users`
  ADD CONSTRAINT `deleted_users_ibfk_1` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `equipments`
--
ALTER TABLE `equipments`
  ADD CONSTRAINT `fk_equipment_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`);

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `library_policies`
--
ALTER TABLE `library_policies`
  ADD CONSTRAINT `fk_policy_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD CONSTRAINT `notification_logs_ibfk_1` FOREIGN KEY (`borrowing_item_id`) REFERENCES `borrow_transaction_items` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `fk_staff_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_student_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`);

--
-- Constraints for table `user_module_permissions`
--
ALTER TABLE `user_module_permissions`
  ADD CONSTRAINT `fk_user_module_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
