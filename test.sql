-- phpMyAdmin SQL Dump
-- ฉบับสมบูรณ์ (Full Stack BMS Database)
-- รองรับระบบ: Login, Profile, Categories, Devices (Modbus), Data Logging

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- 1. สร้างฐานข้อมูลใหม่ (ถ้ายังไม่มี) และเลือกใช้งาน
CREATE DATABASE IF NOT EXISTS `cyberss_iot` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cyberss_iot`;

-- --------------------------------------------------------

-- 2. สร้างตาราง `users` (ระบบบัญชีผู้ใช้งานและรูปโปรไฟล์)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT 'default_avatar.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มข้อมูล Admin เริ่มต้น
INSERT INTO `users` (`id`, `username`, `password`, `profile_pic`, `created_at`) VALUES
(1, 'admin', 'admin', 'default_avatar.png', current_timestamp());

-- --------------------------------------------------------

-- 3. สร้างตาราง `categories` (หมวดหมู่อุปกรณ์)
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มข้อมูลหมวดหมู่เริ่มต้น
INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Power Meter'),
(2, 'HVAC / Air Con'),
(3, 'Water Sensor');

-- --------------------------------------------------------

-- 4. สร้างตาราง `devices` (จัดการอุปกรณ์และตั้งค่า Modbus)
DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(150) NOT NULL,
  `image` varchar(255) DEFAULT 'default.png',
  `ip_address` varchar(50) NOT NULL,
  `port` int(11) DEFAULT 502,
  `slave_id` int(11) DEFAULT 1,
  `status` varchar(20) DEFAULT 'Offline',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มข้อมูลอุปกรณ์ตัวอย่าง 2 ตัว
INSERT INTO `devices` (`id`, `category_id`, `name`, `image`, `ip_address`, `port`, `slave_id`, `status`, `created_at`) VALUES
(1, 1, 'Main Meter (MDB)', 'default.png', '192.168.1.254', 502, 1, 'Online', current_timestamp()),
(2, 2, 'Chiller Plant A', 'default.png', '192.168.1.255', 502, 1, 'Offline', current_timestamp());

-- --------------------------------------------------------

-- 5. สร้างตาราง `current_meter` (บันทึกประวัติการดึงข้อมูลรายอุปกรณ์)
DROP TABLE IF EXISTS `current_meter`;
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

-- การตั้งค่า Indexes (คีย์หลักและคีย์ค้นหา)
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

ALTER TABLE `current_meter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

-- --------------------------------------------------------

-- การตั้งค่า AUTO_INCREMENT (ให้ ID รันตัวเลขลำดับอัตโนมัติ)
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `current_meter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;