-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2026 at 05:33 PM
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
-- Database: `bachelor_meal_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `bazar_items`
--

CREATE TABLE `bazar_items` (
  `id` int(11) NOT NULL,
  `bazar_date` date NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` enum('chicken','fish','dim','other','rice','special') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bazar_items`
--

INSERT INTO `bazar_items` (`id`, `bazar_date`, `item_name`, `category`, `amount`, `paid_by`) VALUES
(9, '2026-04-02', 'Chicken/Meat', 'chicken', 260.00, 2),
(10, '2026-04-02', 'Fish/Seafood', 'fish', 760.00, 2),
(11, '2026-04-02', 'Dim/Egg', 'dim', 60.00, 2),
(12, '2026-04-02', 'Other/Vegetables', 'other', 1570.00, 2),
(13, '2026-04-02', 'Special Meal', 'special', 640.00, 2),
(14, '2026-04-02', 'Rice (Chal)', 'rice', 110.00, 2),
(21, '2026-04-03', 'Rice (Chal)', 'rice', 560.00, 1),
(22, '2026-04-03', 'Rice (Chal)', 'rice', 560.00, 7),
(23, '2026-04-03', 'Rice (Chal)', 'rice', 560.00, 4),
(24, '2026-04-05', 'Other/Vegetables', 'other', 150.00, 2);

-- --------------------------------------------------------

--
-- Table structure for table `daily_meals`
--

CREATE TABLE `daily_meals` (
  `id` int(11) NOT NULL,
  `meal_date` date NOT NULL,
  `person_id` int(11) NOT NULL,
  `session` enum('lunch','dinner') NOT NULL,
  `meal_type` enum('chicken','fish','dim','other','special') NOT NULL,
  `guest_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_meals`
--

INSERT INTO `daily_meals` (`id`, `meal_date`, `person_id`, `session`, `meal_type`, `guest_count`) VALUES
(13, '2026-04-04', 1, 'lunch', 'fish', 0),
(14, '2026-04-04', 1, 'dinner', 'other', 0),
(15, '2026-04-04', 7, 'lunch', 'fish', 0),
(16, '2026-04-04', 7, 'dinner', 'other', 0),
(17, '2026-04-04', 5, 'lunch', 'fish', 0),
(18, '2026-04-04', 5, 'dinner', 'other', 0),
(19, '2026-04-04', 2, 'lunch', 'fish', 0),
(20, '2026-04-04', 2, 'dinner', 'other', 0),
(21, '2026-04-04', 4, 'lunch', 'fish', 0),
(22, '2026-04-04', 4, 'dinner', 'other', 0),
(23, '2026-04-04', 3, 'lunch', 'fish', 0),
(24, '2026-04-04', 3, 'dinner', 'other', 0),
(25, '2026-04-04', 6, 'lunch', 'fish', 0),
(26, '2026-04-04', 6, 'dinner', 'other', 0),
(38, '2026-04-02', 1, 'lunch', 'fish', 0),
(39, '2026-04-02', 1, 'dinner', 'chicken', 0),
(40, '2026-04-02', 7, 'lunch', 'fish', 0),
(41, '2026-04-02', 7, 'dinner', 'chicken', 0),
(42, '2026-04-02', 5, 'lunch', 'fish', 0),
(43, '2026-04-02', 2, 'lunch', 'fish', 0),
(44, '2026-04-02', 2, 'dinner', 'chicken', 0),
(45, '2026-04-02', 4, 'lunch', 'fish', 0),
(46, '2026-04-02', 4, 'dinner', 'chicken', 0),
(47, '2026-04-02', 3, 'lunch', 'fish', 0),
(48, '2026-04-02', 3, 'dinner', 'chicken', 0),
(63, '2026-04-05', 1, 'lunch', 'chicken', 0),
(64, '2026-04-05', 1, 'dinner', 'fish', 0),
(65, '2026-04-05', 7, 'lunch', 'chicken', 0),
(66, '2026-04-05', 7, 'dinner', 'fish', 0),
(67, '2026-04-05', 2, 'lunch', 'chicken', 0),
(68, '2026-04-05', 2, 'dinner', 'fish', 0),
(69, '2026-04-05', 4, 'lunch', 'chicken', 0),
(70, '2026-04-05', 4, 'dinner', 'fish', 0),
(71, '2026-04-05', 3, 'lunch', 'chicken', 0),
(72, '2026-04-05', 3, 'dinner', 'fish', 0),
(73, '2026-04-05', 6, 'lunch', 'chicken', 0),
(74, '2026-04-05', 6, 'dinner', 'fish', 0),
(87, '2026-04-03', 1, 'lunch', 'special', 0),
(88, '2026-04-03', 1, 'dinner', 'dim', 0),
(89, '2026-04-03', 7, 'lunch', 'special', 0),
(90, '2026-04-03', 7, 'dinner', 'dim', 0),
(91, '2026-04-03', 2, 'lunch', 'special', 0),
(92, '2026-04-03', 2, 'dinner', 'dim', 0),
(93, '2026-04-03', 4, 'lunch', 'special', 0),
(94, '2026-04-03', 4, 'dinner', 'dim', 0),
(95, '2026-04-03', 3, 'lunch', 'special', 0),
(96, '2026-04-03', 3, 'dinner', 'dim', 0),
(97, '2026-04-03', 6, 'dinner', 'dim', 0);

-- --------------------------------------------------------

--
-- Table structure for table `persons`
--

CREATE TABLE `persons` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `persons`
--

INSERT INTO `persons` (`id`, `name`) VALUES
(1, 'Mahdi'),
(2, 'Rafi'),
(3, 'Riyad'),
(4, 'Rakib'),
(5, 'Munna'),
(6, 'Sohel'),
(7, 'Munjil');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bazar_items`
--
ALTER TABLE `bazar_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paid_by` (`paid_by`);

--
-- Indexes for table `daily_meals`
--
ALTER TABLE `daily_meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `person_id` (`person_id`);

--
-- Indexes for table `persons`
--
ALTER TABLE `persons`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bazar_items`
--
ALTER TABLE `bazar_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `daily_meals`
--
ALTER TABLE `daily_meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `persons`
--
ALTER TABLE `persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bazar_items`
--
ALTER TABLE `bazar_items`
  ADD CONSTRAINT `bazar_items_ibfk_1` FOREIGN KEY (`paid_by`) REFERENCES `persons` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_meals`
--
ALTER TABLE `daily_meals`
  ADD CONSTRAINT `daily_meals_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
