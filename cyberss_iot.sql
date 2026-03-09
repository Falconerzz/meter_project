-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2026 at 11:27 AM
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
-- Database: `cyberss_iot`
--

-- --------------------------------------------------------

--
-- Table structure for table `alert_logs`
--

CREATE TABLE `alert_logs` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alert_logs`
--

INSERT INTO `alert_logs` (`id`, `device_id`, `message`, `created_at`) VALUES
(1, 6, 'สัญญาณกลับมาออนไลน์ (Back Online)', '2026-03-09 10:20:41'),
(2, 6, 'อุปกรณ์ขาดการติดต่อ (Offline)', '2026-03-09 10:22:16'),
(3, 6, 'สัญญาณกลับมาออนไลน์ (Back Online)', '2026-03-09 10:23:41'),
(4, 6, 'อุปกรณ์ขาดการติดต่อ (Offline)', '2026-03-09 10:23:55'),
(5, 3, 'สัญญาณกลับมาออนไลน์ (Back Online)', '2026-03-09 10:24:32');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#3b82f6'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `color`) VALUES
(1, 'Power Meter', '#3b82f6'),
(2, 'HVAC / Air Con', '#f59e0b'),
(3, 'Water Sensor', '#06b6d4');

-- --------------------------------------------------------

--
-- Table structure for table `current_meter`
--

CREATE TABLE `current_meter` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `v1` float DEFAULT NULL,
  `v2` float DEFAULT NULL,
  `v3` float DEFAULT NULL,
  `a1` float DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(150) NOT NULL,
  `location` varchar(255) DEFAULT 'ไม่ระบุ',
  `image` varchar(255) DEFAULT 'default.png',
  `ip_address` varchar(50) NOT NULL,
  `port` int(11) DEFAULT 502,
  `slave_id` int(11) DEFAULT 1,
  `status` varchar(20) DEFAULT 'Offline',
  `mode` varchar(10) NOT NULL DEFAULT 'mock',
  `alert_min` float DEFAULT 0,
  `alert_max` float DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `category_id`, `name`, `location`, `image`, `ip_address`, `port`, `slave_id`, `status`, `mode`, `alert_min`, `alert_max`, `is_active`, `created_at`, `last_seen`) VALUES
(1, 1, 'Main Meter (MDB)', 'ไม่ระบุ', 'default.png', '192.168.1.254', 502, 1, 'Offline', 'real', 0, 0, 0, '2026-03-06 03:00:00', NULL),
(2, 2, 'Chiller Plant A', 'ไม่ระบุ', 'default.png', '192.168.1.255', 502, 1, 'Offline', 'real', 0, 0, 0, '2026-03-06 03:00:00', NULL),
(3, 1, 'meter mpr-45s', 'ไม่ระบุ', 'dev_1772772564_828.jpg', '192.168.2.254', 502, 1, 'Online', 'mock', 0, 0, 0, '2026-03-06 03:51:23', '2026-03-09 10:24:46'),
(5, 1, 'เทสมิเตอร์', 'ชั้น 2', 'https://cdn-icons-png.flaticon.com/512/2833/2833778.png', '192.168.50.254', 502, 1, 'Online', 'mock', 0, 0, 0, '2026-03-09 09:02:24', '2026-03-09 10:24:46'),
(6, 2, 'aaa', 'AAAjj', 'https://cdn-icons-png.flaticon.com/512/15271/15271114.png', '192.168.75.60', 502, 20, 'Offline', 'real', 0, 0, 1, '2026-03-09 10:20:23', '2026-03-09 10:23:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT 'default_avatar.png',
  `telegram_token` varchar(255) DEFAULT NULL,
  `telegram_chat_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `profile_pic`, `telegram_token`, `telegram_chat_id`, `created_at`) VALUES
(1, 'adming', 'admin', 'user_1_1772768482.png', '8738827547:AAHpMvEWeAWywYn0qe8KuHhkjm4NAiw6KA0', '6979743598', '2026-03-06 03:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alert_logs`
--
ALTER TABLE `alert_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `current_meter`
--
ALTER TABLE `current_meter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alert_logs`
--
ALTER TABLE `alert_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `current_meter`
--
ALTER TABLE `current_meter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
