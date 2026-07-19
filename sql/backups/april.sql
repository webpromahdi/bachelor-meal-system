-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 07:52 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(24, '2026-04-05', 'Other/Vegetables', 'other', 150.00, 2),
(25, '2026-04-06', 'Chicken/Meat', 'chicken', 1035.00, 7),
(26, '2026-04-06', 'Fish/Seafood', 'fish', 200.00, 7),
(27, '2026-04-06', 'Dim/Egg', 'dim', 130.00, 7),
(28, '2026-04-06', 'Other/Vegetables', 'other', 1365.00, 7),
(29, '2026-04-14', 'Chicken/Meat', 'chicken', 750.00, 4),
(30, '2026-04-14', 'Fish/Seafood', 'fish', 795.00, 4),
(31, '2026-04-14', 'Dim/Egg', 'dim', 30.00, 4),
(32, '2026-04-14', 'Other/Vegetables', 'other', 1300.00, 4),
(38, '2026-04-16', 'Chicken/Meat', 'chicken', 550.00, 1),
(39, '2026-04-16', 'Fish/Seafood', 'fish', 480.00, 1),
(40, '2026-04-16', 'Dim/Egg', 'dim', 20.00, 1),
(41, '2026-04-16', 'Other/Vegetables', 'other', 1215.00, 1),
(42, '2026-04-16', 'Special Meal', 'special', 620.00, 1),
(44, '2026-04-16', 'Rice (Chal)', 'rice', 500.00, 5),
(46, '2026-04-16', 'Rice (Chal)', 'rice', 500.00, 3),
(47, '2026-04-17', 'Other/Vegetables', 'other', 95.00, 1),
(48, '2026-04-17', 'Special Meal', 'special', 65.00, 1),
(49, '2026-04-16', 'Other/Vegetables', 'other', 10.00, 6),
(50, '2026-04-16', 'Rice (Chal)', 'rice', 1000.00, 2),
(51, '2026-04-25', 'Chicken/Meat', 'chicken', 820.00, 3),
(52, '2026-04-25', 'Fish/Seafood', 'fish', 300.00, 3),
(53, '2026-04-25', 'Other/Vegetables', 'other', 1140.00, 3),
(58, '2026-04-21', 'Chicken/Meat', 'chicken', 350.00, 6),
(59, '2026-04-21', 'Fish/Seafood', 'fish', 370.00, 6),
(60, '2026-04-21', 'Dim/Egg', 'dim', 85.00, 6),
(61, '2026-04-21', 'Other/Vegetables', 'other', 895.00, 6),
(65, '2026-04-30', 'Chicken/Meat', 'chicken', 300.00, 5),
(66, '2026-04-30', 'Dim/Egg', 'dim', 80.00, 5),
(67, '2026-04-30', 'Other/Vegetables', 'other', 470.00, 5);

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
(97, '2026-04-03', 6, 'dinner', 'dim', 0),
(98, '2026-04-06', 1, 'lunch', 'fish', 0),
(99, '2026-04-06', 1, 'dinner', 'dim', 0),
(100, '2026-04-06', 7, 'lunch', 'fish', 0),
(101, '2026-04-06', 7, 'dinner', 'dim', 0),
(102, '2026-04-06', 5, 'dinner', 'dim', 0),
(103, '2026-04-06', 2, 'lunch', 'fish', 0),
(104, '2026-04-06', 2, 'dinner', 'dim', 0),
(105, '2026-04-06', 4, 'lunch', 'fish', 0),
(106, '2026-04-06', 4, 'dinner', 'dim', 0),
(107, '2026-04-06', 3, 'lunch', 'fish', 0),
(108, '2026-04-06', 3, 'dinner', 'dim', 0),
(109, '2026-04-07', 1, 'lunch', 'chicken', 0),
(110, '2026-04-07', 7, 'lunch', 'chicken', 0),
(111, '2026-04-07', 2, 'lunch', 'chicken', 0),
(112, '2026-04-07', 4, 'lunch', 'chicken', 0),
(113, '2026-04-07', 3, 'lunch', 'chicken', 0),
(114, '2026-04-08', 1, 'lunch', 'chicken', 0),
(115, '2026-04-08', 1, 'dinner', 'dim', 0),
(116, '2026-04-08', 7, 'dinner', 'dim', 0),
(117, '2026-04-08', 5, 'lunch', 'chicken', 0),
(118, '2026-04-08', 5, 'dinner', 'dim', 0),
(119, '2026-04-08', 2, 'lunch', 'chicken', 0),
(120, '2026-04-08', 2, 'dinner', 'dim', 0),
(121, '2026-04-08', 4, 'lunch', 'chicken', 0),
(122, '2026-04-08', 3, 'lunch', 'chicken', 0),
(123, '2026-04-08', 3, 'dinner', 'dim', 0),
(124, '2026-04-08', 6, 'lunch', 'chicken', 0),
(125, '2026-04-08', 6, 'dinner', 'dim', 0),
(126, '2026-04-09', 1, 'lunch', 'chicken', 0),
(127, '2026-04-09', 1, 'dinner', 'other', 0),
(128, '2026-04-09', 7, 'lunch', 'chicken', 0),
(129, '2026-04-09', 7, 'dinner', 'other', 0),
(130, '2026-04-09', 5, 'lunch', 'chicken', 0),
(131, '2026-04-09', 5, 'dinner', 'other', 0),
(132, '2026-04-09', 2, 'lunch', 'chicken', 0),
(133, '2026-04-09', 2, 'dinner', 'other', 0),
(134, '2026-04-09', 4, 'lunch', 'chicken', 0),
(135, '2026-04-09', 4, 'dinner', 'other', 0),
(136, '2026-04-09', 3, 'lunch', 'chicken', 0),
(137, '2026-04-09', 3, 'dinner', 'other', 0),
(138, '2026-04-09', 6, 'lunch', 'chicken', 0),
(139, '2026-04-09', 6, 'dinner', 'other', 0),
(140, '2026-04-10', 1, 'lunch', 'chicken', 0),
(141, '2026-04-10', 1, 'dinner', 'dim', 0),
(142, '2026-04-10', 7, 'lunch', 'chicken', 0),
(143, '2026-04-10', 7, 'dinner', 'dim', 0),
(144, '2026-04-10', 5, 'lunch', 'chicken', 0),
(145, '2026-04-10', 5, 'dinner', 'dim', 0),
(146, '2026-04-10', 2, 'lunch', 'chicken', 0),
(147, '2026-04-10', 2, 'dinner', 'dim', 0),
(148, '2026-04-10', 4, 'lunch', 'chicken', 0),
(149, '2026-04-10', 4, 'dinner', 'dim', 0),
(150, '2026-04-10', 3, 'lunch', 'chicken', 0),
(151, '2026-04-10', 3, 'dinner', 'dim', 0),
(152, '2026-04-10', 6, 'lunch', 'chicken', 0),
(153, '2026-04-10', 6, 'dinner', 'dim', 0),
(159, '2026-04-11', 7, 'dinner', 'other', 0),
(160, '2026-04-11', 5, 'dinner', 'other', 0),
(161, '2026-04-11', 4, 'dinner', 'other', 0),
(162, '2026-04-11', 6, 'dinner', 'other', 0),
(163, '2026-04-12', 1, 'lunch', 'chicken', 0),
(164, '2026-04-12', 1, 'dinner', 'fish', 0),
(165, '2026-04-12', 7, 'lunch', 'chicken', 0),
(166, '2026-04-12', 7, 'dinner', 'fish', 0),
(167, '2026-04-12', 5, 'lunch', 'chicken', 0),
(168, '2026-04-12', 5, 'dinner', 'fish', 0),
(169, '2026-04-12', 2, 'lunch', 'chicken', 0),
(170, '2026-04-12', 2, 'dinner', 'fish', 0),
(171, '2026-04-12', 4, 'lunch', 'chicken', 0),
(172, '2026-04-12', 4, 'dinner', 'fish', 0),
(173, '2026-04-12', 3, 'lunch', 'chicken', 0),
(174, '2026-04-12', 6, 'lunch', 'chicken', 0),
(175, '2026-04-12', 6, 'dinner', 'fish', 0),
(190, '2026-04-13', 1, 'lunch', 'chicken', 0),
(191, '2026-04-13', 1, 'dinner', 'other', 0),
(192, '2026-04-13', 7, 'lunch', 'chicken', 0),
(193, '2026-04-13', 7, 'dinner', 'other', 0),
(194, '2026-04-13', 5, 'lunch', 'chicken', 0),
(195, '2026-04-13', 5, 'dinner', 'other', 0),
(196, '2026-04-13', 2, 'lunch', 'chicken', 0),
(197, '2026-04-13', 2, 'dinner', 'other', 0),
(198, '2026-04-13', 4, 'lunch', 'chicken', 0),
(199, '2026-04-13', 4, 'dinner', 'other', 0),
(200, '2026-04-13', 3, 'lunch', 'chicken', 0),
(201, '2026-04-13', 3, 'dinner', 'other', 0),
(202, '2026-04-13', 6, 'lunch', 'chicken', 0),
(203, '2026-04-13', 6, 'dinner', 'other', 0),
(204, '2026-04-14', 1, 'lunch', 'fish', 0),
(205, '2026-04-14', 1, 'dinner', 'fish', 0),
(206, '2026-04-14', 7, 'lunch', 'fish', 0),
(207, '2026-04-14', 7, 'dinner', 'fish', 0),
(208, '2026-04-14', 5, 'lunch', 'fish', 0),
(209, '2026-04-14', 5, 'dinner', 'fish', 0),
(210, '2026-04-14', 2, 'lunch', 'fish', 0),
(211, '2026-04-14', 2, 'dinner', 'fish', 0),
(212, '2026-04-14', 4, 'lunch', 'fish', 0),
(213, '2026-04-14', 4, 'dinner', 'fish', 0),
(214, '2026-04-14', 3, 'lunch', 'fish', 0),
(215, '2026-04-14', 3, 'dinner', 'fish', 0),
(216, '2026-04-14', 6, 'lunch', 'fish', 0),
(217, '2026-04-14', 6, 'dinner', 'fish', 0),
(232, '2026-04-15', 1, 'lunch', 'chicken', 0),
(233, '2026-04-15', 7, 'lunch', 'chicken', 0),
(234, '2026-04-15', 5, 'lunch', 'chicken', 0),
(235, '2026-04-15', 2, 'lunch', 'chicken', 0),
(236, '2026-04-15', 4, 'lunch', 'chicken', 0),
(237, '2026-04-15', 3, 'lunch', 'chicken', 0),
(238, '2026-04-15', 6, 'lunch', 'chicken', 0),
(239, '2026-04-16', 1, 'lunch', 'chicken', 0),
(240, '2026-04-16', 1, 'dinner', 'fish', 0),
(241, '2026-04-16', 7, 'lunch', 'chicken', 0),
(242, '2026-04-16', 7, 'dinner', 'fish', 0),
(243, '2026-04-16', 5, 'lunch', 'chicken', 0),
(244, '2026-04-16', 5, 'dinner', 'fish', 0),
(245, '2026-04-16', 2, 'lunch', 'chicken', 0),
(246, '2026-04-16', 2, 'dinner', 'fish', 0),
(247, '2026-04-16', 4, 'lunch', 'chicken', 0),
(248, '2026-04-16', 4, 'dinner', 'fish', 0),
(249, '2026-04-16', 3, 'lunch', 'chicken', 0),
(250, '2026-04-16', 3, 'dinner', 'fish', 0),
(251, '2026-04-16', 6, 'lunch', 'chicken', 0),
(252, '2026-04-16', 6, 'dinner', 'fish', 0),
(267, '2026-04-17', 1, 'lunch', 'special', 0),
(268, '2026-04-17', 1, 'dinner', 'other', 0),
(269, '2026-04-17', 7, 'lunch', 'special', 0),
(270, '2026-04-17', 7, 'dinner', 'other', 0),
(271, '2026-04-17', 5, 'lunch', 'special', 0),
(272, '2026-04-17', 5, 'dinner', 'other', 0),
(273, '2026-04-17', 2, 'lunch', 'special', 0),
(274, '2026-04-17', 2, 'dinner', 'other', 0),
(275, '2026-04-17', 4, 'lunch', 'special', 0),
(276, '2026-04-17', 4, 'dinner', 'other', 0),
(277, '2026-04-17', 3, 'lunch', 'special', 0),
(278, '2026-04-17', 3, 'dinner', 'other', 0),
(279, '2026-04-17', 6, 'lunch', 'special', 0),
(280, '2026-04-17', 6, 'dinner', 'other', 0),
(309, '2026-04-18', 1, 'lunch', 'chicken', 0),
(310, '2026-04-18', 1, 'dinner', 'fish', 0),
(311, '2026-04-18', 7, 'lunch', 'chicken', 0),
(312, '2026-04-18', 7, 'dinner', 'fish', 0),
(313, '2026-04-18', 5, 'lunch', 'chicken', 0),
(314, '2026-04-18', 5, 'dinner', 'fish', 0),
(315, '2026-04-18', 2, 'lunch', 'chicken', 0),
(316, '2026-04-18', 2, 'dinner', 'fish', 0),
(317, '2026-04-18', 4, 'lunch', 'chicken', 0),
(318, '2026-04-18', 4, 'dinner', 'fish', 0),
(319, '2026-04-18', 3, 'dinner', 'fish', 0),
(320, '2026-04-18', 6, 'lunch', 'chicken', 0),
(321, '2026-04-18', 6, 'dinner', 'fish', 0),
(385, '2026-04-20', 7, 'lunch', 'fish', 0),
(386, '2026-04-20', 7, 'dinner', 'other', 0),
(387, '2026-04-20', 5, 'lunch', 'fish', 0),
(388, '2026-04-20', 5, 'dinner', 'other', 0),
(389, '2026-04-20', 2, 'lunch', 'fish', 0),
(390, '2026-04-20', 4, 'lunch', 'fish', 0),
(391, '2026-04-20', 4, 'dinner', 'other', 0),
(392, '2026-04-20', 3, 'lunch', 'fish', 0),
(393, '2026-04-20', 3, 'dinner', 'other', 0),
(394, '2026-04-20', 6, 'lunch', 'fish', 0),
(395, '2026-04-20', 6, 'dinner', 'other', 0),
(410, '2026-04-19', 1, 'lunch', 'chicken', 0),
(411, '2026-04-19', 1, 'dinner', 'fish', 0),
(412, '2026-04-19', 7, 'lunch', 'chicken', 0),
(413, '2026-04-19', 7, 'dinner', 'fish', 0),
(414, '2026-04-19', 5, 'lunch', 'chicken', 0),
(415, '2026-04-19', 5, 'dinner', 'fish', 0),
(416, '2026-04-19', 2, 'lunch', 'chicken', 0),
(417, '2026-04-19', 2, 'dinner', 'fish', 0),
(418, '2026-04-19', 4, 'lunch', 'chicken', 0),
(419, '2026-04-19', 4, 'dinner', 'fish', 0),
(420, '2026-04-19', 3, 'lunch', 'chicken', 0),
(421, '2026-04-19', 3, 'dinner', 'fish', 0),
(422, '2026-04-19', 6, 'lunch', 'chicken', 0),
(423, '2026-04-19', 6, 'dinner', 'fish', 0),
(490, '2026-04-22', 1, 'dinner', 'other', 0),
(491, '2026-04-22', 7, 'lunch', 'chicken', 0),
(492, '2026-04-22', 7, 'dinner', 'other', 0),
(493, '2026-04-22', 5, 'lunch', 'chicken', 0),
(494, '2026-04-22', 5, 'dinner', 'other', 0),
(495, '2026-04-22', 2, 'dinner', 'other', 0),
(496, '2026-04-22', 4, 'lunch', 'chicken', 0),
(497, '2026-04-22', 4, 'dinner', 'other', 0),
(498, '2026-04-22', 3, 'lunch', 'chicken', 0),
(499, '2026-04-22', 3, 'dinner', 'other', 0),
(500, '2026-04-22', 6, 'lunch', 'chicken', 0),
(501, '2026-04-22', 6, 'dinner', 'other', 0),
(538, '2026-04-23', 1, 'lunch', 'fish', 0),
(539, '2026-04-23', 1, 'dinner', 'other', 0),
(540, '2026-04-23', 7, 'lunch', 'fish', 0),
(541, '2026-04-23', 7, 'dinner', 'other', 0),
(542, '2026-04-23', 5, 'lunch', 'fish', 0),
(543, '2026-04-23', 5, 'dinner', 'other', 0),
(544, '2026-04-23', 2, 'lunch', 'fish', 0),
(545, '2026-04-23', 2, 'dinner', 'other', 0),
(546, '2026-04-23', 4, 'lunch', 'fish', 0),
(547, '2026-04-23', 4, 'dinner', 'other', 0),
(548, '2026-04-23', 3, 'lunch', 'fish', 0),
(549, '2026-04-23', 3, 'dinner', 'other', 0),
(576, '2026-04-24', 1, 'lunch', 'chicken', 0),
(577, '2026-04-24', 1, 'dinner', 'other', 0),
(578, '2026-04-24', 7, 'lunch', 'chicken', 0),
(579, '2026-04-24', 7, 'dinner', 'other', 0),
(580, '2026-04-24', 5, 'lunch', 'chicken', 0),
(581, '2026-04-24', 5, 'dinner', 'other', 0),
(582, '2026-04-24', 2, 'lunch', 'chicken', 0),
(583, '2026-04-24', 2, 'dinner', 'other', 0),
(584, '2026-04-24', 4, 'lunch', 'chicken', 0),
(585, '2026-04-24', 4, 'dinner', 'other', 0),
(586, '2026-04-24', 3, 'lunch', 'chicken', 0),
(587, '2026-04-24', 3, 'dinner', 'other', 0),
(588, '2026-04-25', 1, 'lunch', 'fish', 0),
(589, '2026-04-25', 1, 'dinner', 'chicken', 0),
(590, '2026-04-25', 7, 'lunch', 'fish', 0),
(591, '2026-04-25', 7, 'dinner', 'chicken', 0),
(592, '2026-04-25', 5, 'lunch', 'fish', 0),
(593, '2026-04-25', 5, 'dinner', 'chicken', 0),
(594, '2026-04-25', 2, 'lunch', 'fish', 0),
(595, '2026-04-25', 4, 'lunch', 'fish', 0),
(596, '2026-04-25', 4, 'dinner', 'chicken', 0),
(597, '2026-04-25', 3, 'lunch', 'fish', 0),
(598, '2026-04-25', 3, 'dinner', 'chicken', 0),
(599, '2026-04-26', 1, 'lunch', 'fish', 0),
(600, '2026-04-26', 1, 'dinner', 'chicken', 0),
(601, '2026-04-26', 7, 'lunch', 'fish', 0),
(602, '2026-04-26', 7, 'dinner', 'chicken', 0),
(603, '2026-04-26', 5, 'lunch', 'fish', 0),
(604, '2026-04-26', 5, 'dinner', 'chicken', 0),
(605, '2026-04-26', 4, 'lunch', 'fish', 0),
(606, '2026-04-26', 4, 'dinner', 'chicken', 0),
(607, '2026-04-26', 3, 'lunch', 'fish', 0),
(608, '2026-04-26', 3, 'dinner', 'chicken', 0),
(609, '2026-04-27', 1, 'lunch', 'dim', 0),
(610, '2026-04-27', 1, 'dinner', 'chicken', 0),
(611, '2026-04-27', 7, 'lunch', 'dim', 0),
(612, '2026-04-27', 7, 'dinner', 'chicken', 0),
(613, '2026-04-27', 5, 'lunch', 'dim', 0),
(614, '2026-04-27', 5, 'dinner', 'chicken', 0),
(615, '2026-04-27', 4, 'lunch', 'dim', 0),
(616, '2026-04-27', 4, 'dinner', 'chicken', 0),
(617, '2026-04-27', 3, 'lunch', 'dim', 0),
(618, '2026-04-27', 3, 'dinner', 'chicken', 0),
(619, '2026-04-21', 1, 'dinner', 'other', 0),
(620, '2026-04-21', 7, 'lunch', 'fish', 0),
(621, '2026-04-21', 7, 'dinner', 'other', 0),
(622, '2026-04-21', 5, 'lunch', 'fish', 0),
(623, '2026-04-21', 5, 'dinner', 'other', 0),
(624, '2026-04-21', 2, 'lunch', 'fish', 0),
(625, '2026-04-21', 2, 'dinner', 'other', 0),
(626, '2026-04-21', 4, 'lunch', 'fish', 0),
(627, '2026-04-21', 4, 'dinner', 'other', 0),
(628, '2026-04-21', 3, 'lunch', 'fish', 0),
(629, '2026-04-21', 3, 'dinner', 'other', 0),
(630, '2026-04-21', 6, 'lunch', 'fish', 0),
(631, '2026-04-21', 6, 'dinner', 'other', 0),
(632, '2026-04-29', 1, 'dinner', 'chicken', 0),
(633, '2026-04-29', 7, 'dinner', 'chicken', 0),
(634, '2026-04-29', 5, 'dinner', 'chicken', 0),
(635, '2026-04-29', 5, 'dinner', 'chicken', 0),
(636, '2026-04-29', 2, 'dinner', 'chicken', 0),
(637, '2026-04-29', 4, 'dinner', 'chicken', 0),
(638, '2026-04-29', 3, 'dinner', 'chicken', 0),
(639, '2026-04-29', 6, 'dinner', 'chicken', 0),
(647, '2026-04-30', 1, 'lunch', 'dim', 0),
(648, '2026-04-30', 1, 'dinner', 'other', 0),
(649, '2026-04-30', 7, 'lunch', 'dim', 0),
(650, '2026-04-30', 7, 'dinner', 'other', 0),
(651, '2026-04-30', 5, 'lunch', 'dim', 0),
(652, '2026-04-30', 5, 'dinner', 'other', 0),
(653, '2026-04-30', 5, 'dinner', 'other', 0),
(654, '2026-04-30', 2, 'lunch', 'dim', 0),
(655, '2026-04-30', 2, 'dinner', 'other', 0),
(656, '2026-04-30', 4, 'lunch', 'dim', 0),
(657, '2026-04-30', 4, 'lunch', 'dim', 0),
(658, '2026-04-30', 4, 'dinner', 'other', 0),
(659, '2026-04-30', 3, 'lunch', 'dim', 0),
(660, '2026-04-30', 3, 'dinner', 'other', 0);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `daily_meals`
--
ALTER TABLE `daily_meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=661;

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
