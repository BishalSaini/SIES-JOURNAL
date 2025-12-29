-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2025 at 06:26 AM
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
-- Database: `sies-journal2`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `subject` varchar(500) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_downloads`
--

CREATE TABLE `journal_downloads` (
  `id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `college_name` varchar(500) NOT NULL,
  `course` varchar(255) NOT NULL,
  `year_of_study` varchar(50) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `purpose_of_download` text DEFAULT NULL,
  `download_type` enum('journal','article') DEFAULT 'journal',
  `download_item_id` varchar(100) DEFAULT NULL,
  `download_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manuscript_submissions`
--

CREATE TABLE `manuscript_submissions` (
  `id` int(11) NOT NULL,
  `author_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `affiliation` text NOT NULL,
  `manuscript_title` text NOT NULL,
  `abstract` text NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','under_review','accepted','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_downloads`
--

CREATE TABLE `student_downloads` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `college_name` varchar(150) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year_of_study` varchar(20) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `purpose_of_download` text DEFAULT NULL,
  `download_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `pdf_name` varchar(100) DEFAULT 'SIES_Journal_Volume_1.pdf'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `journal_downloads`
--
ALTER TABLE `journal_downloads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `manuscript_submissions`
--
ALTER TABLE `manuscript_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_submission_date` (`submission_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_downloads`
--
ALTER TABLE `student_downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_download_date` (`download_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_downloads`
--
ALTER TABLE `journal_downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `manuscript_submissions`
--
ALTER TABLE `manuscript_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_downloads`
--
ALTER TABLE `student_downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
