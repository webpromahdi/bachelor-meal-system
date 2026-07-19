-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2026 at 04:53 PM
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
(1, '2026-01-03', 'Chicken/Meat', 'chicken', 790.00, 1),
(2, '2026-01-03', 'Fish/Seafood', 'fish', 360.00, 1),
(3, '2026-01-03', 'Dim/Egg', 'dim', 100.00, 1),
(4, '2026-01-03', 'Rice (Chal)', 'rice', 500.00, 1),
(5, '2026-01-03', 'Vegetables/Oil/Spices', 'other', 1545.00, 1),
(6, '2026-01-07', 'Chicken/Meat', 'chicken', 1055.00, 4),
(7, '2026-01-07', 'Fish/Seafood', 'fish', 240.00, 4),
(8, '2026-01-07', 'Dim/Egg', 'dim', 110.00, 4),
(9, '2026-01-07', 'Other/Vegetables', 'other', 1105.00, 4),
(10, '2026-01-07', 'Rice (Chal)', 'rice', 710.00, 4),
(11, '2026-01-14', 'Chicken/Meat', 'chicken', 750.00, 2),
(12, '2026-01-14', 'Fish/Seafood', 'fish', 270.00, 2),
(13, '2026-01-14', 'Other/Vegetables', 'other', 1635.00, 2),
(14, '2026-01-14', 'Rice (Chal)', 'rice', 500.00, 2),
(21, '2026-01-19', 'Chicken/Meat', 'chicken', 970.00, 3),
(22, '2026-01-19', 'Fish/Seafood', 'fish', 430.00, 3),
(23, '2026-01-19', 'Dim/Egg', 'dim', 60.00, 3),
(24, '2026-01-19', 'Other/Vegetables', 'other', 822.00, 3),
(25, '2026-01-19', 'Rice (Chal)', 'rice', 500.00, 3),
(30, '2026-01-23', 'Chicken/Meat', 'chicken', 640.00, 5),
(31, '2026-01-23', 'Other/Vegetables', 'other', 1040.00, 5),
(32, '2026-01-23', 'Rice (Chal)', 'rice', 640.00, 5),
(34, '2026-01-24', 'Dim/Egg', 'dim', 120.00, 5),
(35, '2026-01-25', 'Dim/Egg', 'dim', 30.00, 1),
(41, '2026-01-26', 'Chicken/Meat', 'chicken', 760.00, 6),
(42, '2026-01-26', 'Fish/Seafood', 'fish', 360.00, 6),
(43, '2026-01-26', 'Other/Vegetables', 'other', 470.00, 6),
(44, '2026-01-26', 'Special Meal', 'special', 640.00, 6),
(45, '2026-01-26', 'Rice (Chal)', 'rice', 500.00, 6),
(47, '2026-01-30', 'Chicken/Meat', 'chicken', 360.00, 7),
(48, '2026-01-30', 'Fish/Seafood', 'fish', 670.00, 7),
(49, '2026-01-30', 'Other/Vegetables', 'other', 1440.00, 7),
(50, '2026-01-30', 'Rice (Chal)', 'rice', 780.00, 7),
(57, '2026-01-31', 'Dim/Egg', 'dim', 140.00, 7),
(58, '2026-01-31', 'Other/Vegetables', 'other', 20.00, 7),
(59, '2026-01-31', 'Rice (Chal)', 'rice', 88.00, 7);

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
(49, '2026-01-03', 1, 'lunch', 'chicken', 0),
(50, '2026-01-03', 1, 'dinner', 'fish', 0),
(51, '2026-01-03', 7, 'lunch', 'chicken', 0),
(52, '2026-01-03', 7, 'dinner', 'fish', 0),
(53, '2026-01-03', 5, 'lunch', 'chicken', 0),
(54, '2026-01-03', 5, 'dinner', 'fish', 0),
(55, '2026-01-03', 4, 'lunch', 'chicken', 0),
(56, '2026-01-03', 4, 'dinner', 'fish', 0),
(57, '2026-01-03', 6, 'lunch', 'chicken', 0),
(58, '2026-01-03', 6, 'dinner', 'fish', 0),
(59, '2026-01-03', 3, 'lunch', 'chicken', 0),
(60, '2026-01-03', 3, 'dinner', 'fish', 0),
(61, '2026-01-04', 1, 'lunch', 'fish', 0),
(62, '2026-01-04', 1, 'dinner', 'chicken', 0),
(63, '2026-01-04', 7, 'lunch', 'fish', 0),
(64, '2026-01-04', 7, 'dinner', 'chicken', 0),
(65, '2026-01-04', 5, 'lunch', 'fish', 0),
(66, '2026-01-04', 5, 'dinner', 'chicken', 0),
(67, '2026-01-04', 4, 'lunch', 'fish', 0),
(68, '2026-01-04', 4, 'dinner', 'chicken', 0),
(69, '2026-01-04', 6, 'lunch', 'fish', 0),
(70, '2026-01-04', 6, 'dinner', 'chicken', 0),
(71, '2026-01-04', 6, 'dinner', 'chicken', 0),
(72, '2026-01-04', 6, 'dinner', 'chicken', 0),
(73, '2026-01-04', 3, 'lunch', 'fish', 0),
(74, '2026-01-04', 3, 'dinner', 'chicken', 0),
(75, '2026-01-05', 1, 'lunch', 'chicken', 0),
(76, '2026-01-05', 1, 'dinner', 'dim', 0),
(77, '2026-01-05', 7, 'dinner', 'dim', 0),
(78, '2026-01-05', 5, 'lunch', 'chicken', 0),
(79, '2026-01-05', 5, 'dinner', 'dim', 0),
(80, '2026-01-05', 4, 'lunch', 'chicken', 0),
(81, '2026-01-05', 6, 'dinner', 'dim', 0),
(82, '2026-01-05', 6, 'dinner', 'dim', 0),
(83, '2026-01-05', 6, 'dinner', 'dim', 0),
(84, '2026-01-05', 3, 'lunch', 'chicken', 0),
(85, '2026-01-05', 3, 'dinner', 'dim', 0),
(86, '2026-01-06', 1, 'lunch', 'chicken', 0),
(87, '2026-01-06', 1, 'dinner', 'other', 0),
(88, '2026-01-06', 7, 'lunch', 'chicken', 0),
(89, '2026-01-06', 7, 'dinner', 'other', 0),
(90, '2026-01-06', 5, 'lunch', 'chicken', 0),
(91, '2026-01-06', 5, 'dinner', 'other', 0),
(92, '2026-01-06', 4, 'lunch', 'chicken', 0),
(93, '2026-01-06', 4, 'dinner', 'other', 0),
(94, '2026-01-06', 6, 'lunch', 'chicken', 0),
(95, '2026-01-06', 6, 'dinner', 'other', 0),
(96, '2026-01-06', 3, 'lunch', 'chicken', 0),
(97, '2026-01-06', 3, 'dinner', 'other', 0),
(98, '2026-01-07', 1, 'lunch', 'dim', 0),
(99, '2026-01-07', 1, 'dinner', 'chicken', 0),
(100, '2026-01-07', 7, 'lunch', 'dim', 0),
(101, '2026-01-07', 7, 'dinner', 'chicken', 0),
(102, '2026-01-07', 5, 'lunch', 'dim', 0),
(103, '2026-01-07', 5, 'dinner', 'chicken', 0),
(104, '2026-01-07', 2, 'lunch', 'dim', 0),
(105, '2026-01-07', 2, 'dinner', 'chicken', 0),
(106, '2026-01-07', 4, 'lunch', 'dim', 0),
(107, '2026-01-07', 4, 'dinner', 'chicken', 0),
(108, '2026-01-07', 6, 'lunch', 'dim', 0),
(109, '2026-01-07', 6, 'dinner', 'chicken', 0),
(110, '2026-01-07', 3, 'dinner', 'chicken', 0),
(111, '2026-01-08', 1, 'lunch', 'dim', 0),
(112, '2026-01-08', 7, 'lunch', 'dim', 0),
(113, '2026-01-08', 5, 'lunch', 'dim', 0),
(114, '2026-01-08', 4, 'lunch', 'dim', 0),
(115, '2026-01-08', 6, 'lunch', 'dim', 0),
(116, '2026-01-08', 3, 'lunch', 'dim', 0),
(117, '2026-01-09', 1, 'lunch', 'chicken', 0),
(118, '2026-01-09', 7, 'lunch', 'chicken', 0),
(119, '2026-01-09', 7, 'dinner', 'other', 0),
(120, '2026-01-09', 7, 'dinner', 'other', 0),
(121, '2026-01-09', 4, 'lunch', 'chicken', 0),
(122, '2026-01-09', 4, 'dinner', 'other', 0),
(123, '2026-01-09', 6, 'lunch', 'chicken', 0),
(124, '2026-01-09', 3, 'dinner', 'other', 0),
(125, '2026-01-10', 1, 'lunch', 'fish', 0),
(126, '2026-01-10', 1, 'dinner', 'chicken', 0),
(127, '2026-01-10', 7, 'lunch', 'fish', 0),
(128, '2026-01-10', 7, 'dinner', 'chicken', 0),
(129, '2026-01-10', 5, 'lunch', 'fish', 0),
(130, '2026-01-10', 5, 'dinner', 'chicken', 0),
(131, '2026-01-10', 2, 'lunch', 'fish', 0),
(132, '2026-01-10', 2, 'dinner', 'chicken', 0),
(133, '2026-01-10', 4, 'lunch', 'fish', 0),
(134, '2026-01-10', 4, 'dinner', 'chicken', 0),
(135, '2026-01-10', 3, 'lunch', 'fish', 0),
(136, '2026-01-10', 3, 'dinner', 'chicken', 0),
(137, '2026-01-13', 1, 'lunch', 'chicken', 0),
(138, '2026-01-13', 1, 'dinner', 'fish', 0),
(139, '2026-01-13', 7, 'lunch', 'chicken', 0),
(140, '2026-01-13', 7, 'dinner', 'fish', 0),
(141, '2026-01-13', 5, 'lunch', 'chicken', 0),
(142, '2026-01-13', 5, 'dinner', 'fish', 0),
(143, '2026-01-13', 2, 'lunch', 'chicken', 0),
(144, '2026-01-13', 2, 'dinner', 'fish', 0),
(145, '2026-01-13', 4, 'lunch', 'chicken', 0),
(146, '2026-01-13', 4, 'dinner', 'fish', 0),
(147, '2026-01-13', 3, 'lunch', 'chicken', 0),
(148, '2026-01-13', 3, 'dinner', 'fish', 0),
(149, '2026-01-14', 1, 'lunch', 'fish', 0),
(150, '2026-01-14', 1, 'dinner', 'other', 0),
(151, '2026-01-14', 7, 'lunch', 'fish', 0),
(152, '2026-01-14', 7, 'dinner', 'other', 0),
(153, '2026-01-14', 5, 'lunch', 'fish', 0),
(154, '2026-01-14', 5, 'lunch', 'fish', 0),
(155, '2026-01-14', 5, 'dinner', 'other', 0),
(156, '2026-01-14', 5, 'dinner', 'other', 0),
(157, '2026-01-14', 2, 'lunch', 'fish', 0),
(158, '2026-01-14', 2, 'dinner', 'other', 0),
(159, '2026-01-14', 4, 'lunch', 'fish', 0),
(160, '2026-01-14', 4, 'dinner', 'other', 0),
(161, '2026-01-14', 3, 'lunch', 'fish', 0),
(162, '2026-01-14', 3, 'dinner', 'other', 0),
(163, '2026-01-15', 1, 'lunch', 'chicken', 0),
(164, '2026-01-15', 1, 'dinner', 'dim', 0),
(165, '2026-01-15', 7, 'lunch', 'chicken', 0),
(166, '2026-01-15', 7, 'lunch', 'chicken', 0),
(167, '2026-01-15', 7, 'dinner', 'dim', 0),
(168, '2026-01-15', 5, 'lunch', 'chicken', 0),
(169, '2026-01-15', 5, 'dinner', 'dim', 0),
(170, '2026-01-15', 2, 'lunch', 'chicken', 0),
(171, '2026-01-15', 2, 'dinner', 'dim', 0),
(172, '2026-01-15', 4, 'lunch', 'chicken', 0),
(173, '2026-01-15', 4, 'dinner', 'dim', 0),
(174, '2026-01-15', 3, 'lunch', 'chicken', 0),
(175, '2026-01-15', 3, 'dinner', 'dim', 0),
(176, '2026-01-16', 1, 'lunch', 'chicken', 0),
(177, '2026-01-16', 1, 'dinner', 'fish', 0),
(178, '2026-01-16', 7, 'lunch', 'chicken', 0),
(179, '2026-01-16', 7, 'dinner', 'fish', 0),
(180, '2026-01-16', 5, 'lunch', 'chicken', 0),
(181, '2026-01-16', 5, 'dinner', 'fish', 0),
(182, '2026-01-16', 2, 'lunch', 'chicken', 0),
(183, '2026-01-16', 2, 'dinner', 'fish', 0),
(184, '2026-01-16', 4, 'lunch', 'chicken', 0),
(185, '2026-01-16', 4, 'dinner', 'fish', 0),
(186, '2026-01-16', 3, 'lunch', 'chicken', 0),
(187, '2026-01-16', 3, 'dinner', 'fish', 0),
(188, '2026-01-17', 1, 'lunch', 'chicken', 0),
(189, '2026-01-17', 1, 'dinner', 'chicken', 0),
(190, '2026-01-17', 7, 'lunch', 'chicken', 0),
(191, '2026-01-17', 7, 'dinner', 'chicken', 0),
(192, '2026-01-17', 5, 'lunch', 'chicken', 0),
(193, '2026-01-17', 5, 'dinner', 'chicken', 0),
(194, '2026-01-17', 2, 'lunch', 'chicken', 0),
(195, '2026-01-17', 2, 'dinner', 'chicken', 0),
(196, '2026-01-17', 4, 'lunch', 'chicken', 0),
(197, '2026-01-17', 4, 'dinner', 'chicken', 0),
(198, '2026-01-17', 3, 'lunch', 'chicken', 0),
(199, '2026-01-17', 3, 'dinner', 'chicken', 0),
(200, '2026-01-27', 1, 'lunch', 'chicken', 0),
(201, '2026-01-27', 1, 'dinner', 'fish', 0),
(202, '2026-01-27', 7, 'lunch', 'chicken', 0),
(203, '2026-01-27', 7, 'dinner', 'fish', 0),
(204, '2026-01-27', 5, 'lunch', 'chicken', 0),
(205, '2026-01-27', 5, 'dinner', 'fish', 0),
(206, '2026-01-27', 2, 'lunch', 'chicken', 0),
(207, '2026-01-27', 4, 'lunch', 'chicken', 0),
(208, '2026-01-27', 4, 'dinner', 'fish', 0),
(209, '2026-01-27', 6, 'lunch', 'chicken', 0),
(210, '2026-01-27', 6, 'dinner', 'fish', 0),
(211, '2026-01-27', 3, 'lunch', 'chicken', 0),
(212, '2026-01-27', 3, 'dinner', 'fish', 0),
(213, '2026-01-18', 1, 'lunch', 'dim', 0),
(214, '2026-01-18', 7, 'lunch', 'dim', 0),
(215, '2026-01-18', 5, 'lunch', 'dim', 0),
(216, '2026-01-18', 2, 'lunch', 'dim', 0),
(217, '2026-01-18', 4, 'lunch', 'dim', 0),
(218, '2026-01-18', 3, 'lunch', 'dim', 0),
(219, '2026-01-19', 1, 'lunch', 'chicken', 0),
(220, '2026-01-19', 1, 'dinner', 'fish', 0),
(221, '2026-01-19', 7, 'lunch', 'chicken', 0),
(222, '2026-01-19', 7, 'dinner', 'fish', 0),
(223, '2026-01-19', 5, 'lunch', 'chicken', 0),
(224, '2026-01-19', 5, 'dinner', 'fish', 0),
(225, '2026-01-19', 2, 'lunch', 'chicken', 0),
(226, '2026-01-19', 2, 'dinner', 'fish', 0),
(227, '2026-01-19', 4, 'lunch', 'chicken', 0),
(228, '2026-01-19', 4, 'dinner', 'fish', 0),
(229, '2026-01-19', 3, 'lunch', 'chicken', 0),
(230, '2026-01-19', 3, 'dinner', 'fish', 0),
(231, '2026-01-20', 1, 'lunch', 'fish', 0),
(232, '2026-01-20', 1, 'dinner', 'chicken', 0),
(233, '2026-01-20', 7, 'lunch', 'fish', 0),
(234, '2026-01-20', 7, 'dinner', 'chicken', 0),
(235, '2026-01-20', 5, 'lunch', 'fish', 0),
(236, '2026-01-20', 5, 'dinner', 'chicken', 0),
(237, '2026-01-20', 2, 'lunch', 'fish', 0),
(238, '2026-01-20', 4, 'lunch', 'fish', 0),
(239, '2026-01-20', 4, 'dinner', 'chicken', 0),
(240, '2026-01-20', 3, 'lunch', 'fish', 0),
(241, '2026-01-20', 3, 'dinner', 'chicken', 0),
(242, '2026-01-21', 1, 'lunch', 'fish', 0),
(243, '2026-01-21', 1, 'dinner', 'chicken', 0),
(244, '2026-01-21', 7, 'lunch', 'fish', 0),
(245, '2026-01-21', 7, 'dinner', 'chicken', 0),
(246, '2026-01-21', 7, 'dinner', 'chicken', 0),
(247, '2026-01-21', 5, 'lunch', 'fish', 0),
(248, '2026-01-21', 5, 'dinner', 'chicken', 0),
(249, '2026-01-21', 2, 'lunch', 'fish', 0),
(250, '2026-01-21', 2, 'dinner', 'chicken', 0),
(251, '2026-01-21', 4, 'lunch', 'fish', 0),
(252, '2026-01-21', 4, 'dinner', 'chicken', 0),
(253, '2026-01-21', 3, 'lunch', 'fish', 0),
(254, '2026-01-21', 3, 'dinner', 'chicken', 0),
(268, '2026-01-28', 1, 'dinner', 'chicken', 0),
(269, '2026-01-28', 7, 'lunch', 'fish', 0),
(270, '2026-01-28', 7, 'dinner', 'chicken', 0),
(271, '2026-01-28', 5, 'lunch', 'fish', 0),
(272, '2026-01-28', 5, 'dinner', 'chicken', 0),
(273, '2026-01-28', 2, 'lunch', 'fish', 0),
(274, '2026-01-28', 2, 'dinner', 'chicken', 0),
(275, '2026-01-28', 4, 'lunch', 'fish', 0),
(276, '2026-01-28', 4, 'dinner', 'chicken', 0),
(277, '2026-01-28', 6, 'lunch', 'fish', 0),
(278, '2026-01-28', 6, 'dinner', 'chicken', 0),
(279, '2026-01-28', 3, 'lunch', 'fish', 0),
(280, '2026-01-28', 3, 'dinner', 'chicken', 0),
(297, '2026-01-23', 1, 'lunch', 'chicken', 0),
(298, '2026-01-23', 1, 'dinner', 'dim', 0),
(299, '2026-01-23', 7, 'lunch', 'chicken', 0),
(300, '2026-01-23', 7, 'dinner', 'dim', 0),
(301, '2026-01-23', 5, 'lunch', 'chicken', 0),
(302, '2026-01-23', 5, 'dinner', 'dim', 0),
(303, '2026-01-23', 2, 'lunch', 'chicken', 0),
(304, '2026-01-23', 2, 'dinner', 'dim', 0),
(305, '2026-01-23', 4, 'lunch', 'chicken', 0),
(306, '2026-01-23', 4, 'dinner', 'dim', 0),
(307, '2026-01-23', 3, 'lunch', 'chicken', 0),
(308, '2026-01-23', 3, 'dinner', 'dim', 0),
(309, '2026-01-24', 1, 'dinner', 'dim', 0),
(310, '2026-01-24', 7, 'dinner', 'dim', 0),
(311, '2026-01-24', 5, 'dinner', 'dim', 0),
(312, '2026-01-24', 2, 'dinner', 'dim', 0),
(313, '2026-01-24', 4, 'dinner', 'dim', 0),
(314, '2026-01-24', 3, 'dinner', 'dim', 0),
(315, '2026-01-25', 1, 'dinner', 'dim', 0),
(316, '2026-01-25', 7, 'dinner', 'dim', 0),
(317, '2026-01-25', 5, 'dinner', 'dim', 0),
(318, '2026-01-25', 4, 'dinner', 'dim', 0),
(319, '2026-01-25', 3, 'dinner', 'dim', 0),
(320, '2026-01-22', 1, 'lunch', 'chicken', 0),
(321, '2026-01-22', 1, 'dinner', 'other', 0),
(322, '2026-01-22', 7, 'lunch', 'chicken', 0),
(323, '2026-01-22', 7, 'dinner', 'other', 0),
(324, '2026-01-22', 5, 'lunch', 'chicken', 0),
(325, '2026-01-22', 5, 'dinner', 'other', 0),
(326, '2026-01-22', 2, 'lunch', 'chicken', 0),
(327, '2026-01-22', 4, 'lunch', 'chicken', 0),
(328, '2026-01-22', 4, 'dinner', 'other', 0),
(329, '2026-01-22', 3, 'lunch', 'chicken', 0),
(330, '2026-01-22', 3, 'dinner', 'other', 0),
(363, '2026-01-26', 1, 'lunch', 'special', 0),
(364, '2026-01-26', 1, 'dinner', 'other', 0),
(365, '2026-01-26', 7, 'lunch', 'special', 0),
(366, '2026-01-26', 7, 'dinner', 'other', 0),
(367, '2026-01-26', 5, 'lunch', 'special', 0),
(368, '2026-01-26', 5, 'dinner', 'other', 0),
(369, '2026-01-26', 2, 'lunch', 'special', 0),
(370, '2026-01-26', 4, 'lunch', 'special', 0),
(371, '2026-01-26', 4, 'dinner', 'other', 0),
(372, '2026-01-26', 4, 'dinner', 'other', 0),
(373, '2026-01-26', 6, 'lunch', 'special', 0),
(374, '2026-01-26', 6, 'dinner', 'other', 0),
(375, '2026-01-26', 3, 'lunch', 'special', 0),
(376, '2026-01-26', 3, 'dinner', 'other', 0),
(377, '2026-01-30', 1, 'lunch', 'chicken', 0),
(378, '2026-01-30', 1, 'dinner', 'other', 0),
(379, '2026-01-30', 7, 'lunch', 'chicken', 0),
(380, '2026-01-30', 7, 'lunch', 'chicken', 0),
(381, '2026-01-30', 7, 'dinner', 'other', 0),
(382, '2026-01-30', 7, 'dinner', 'other', 0),
(383, '2026-01-30', 5, 'lunch', 'chicken', 0),
(384, '2026-01-30', 5, 'dinner', 'other', 0),
(385, '2026-01-30', 2, 'lunch', 'chicken', 0),
(386, '2026-01-30', 2, 'dinner', 'other', 0),
(387, '2026-01-30', 4, 'lunch', 'chicken', 0),
(388, '2026-01-30', 4, 'dinner', 'other', 0),
(389, '2026-01-30', 6, 'lunch', 'chicken', 0),
(390, '2026-01-30', 6, 'dinner', 'other', 0),
(391, '2026-01-30', 3, 'lunch', 'chicken', 0),
(392, '2026-01-30', 3, 'dinner', 'other', 0),
(409, '2026-01-31', 1, 'lunch', 'fish', 0),
(410, '2026-01-31', 1, 'dinner', 'dim', 0),
(411, '2026-01-31', 7, 'lunch', 'fish', 0),
(412, '2026-01-31', 7, 'dinner', 'dim', 0),
(413, '2026-01-31', 5, 'lunch', 'fish', 0),
(414, '2026-01-31', 5, 'dinner', 'dim', 0),
(415, '2026-01-31', 2, 'lunch', 'fish', 0),
(416, '2026-01-31', 4, 'lunch', 'fish', 0),
(417, '2026-01-31', 4, 'dinner', 'dim', 0),
(418, '2026-01-31', 6, 'lunch', 'fish', 0),
(419, '2026-01-31', 6, 'lunch', 'fish', 0),
(420, '2026-01-31', 6, 'dinner', 'dim', 0),
(421, '2026-01-31', 6, 'dinner', 'dim', 0),
(422, '2026-01-31', 3, 'lunch', 'fish', 0),
(423, '2026-01-31', 3, 'dinner', 'dim', 0),
(440, '2026-01-02', 1, 'lunch', 'dim', 0),
(441, '2026-01-02', 1, 'dinner', 'other', 0),
(442, '2026-01-02', 7, 'lunch', 'dim', 0),
(443, '2026-01-02', 7, 'dinner', 'other', 0),
(444, '2026-01-02', 5, 'dinner', 'other', 0),
(445, '2026-01-02', 2, 'lunch', 'dim', 0),
(446, '2026-01-02', 2, 'dinner', 'other', 0),
(447, '2026-01-02', 4, 'lunch', 'dim', 0),
(448, '2026-01-02', 4, 'dinner', 'other', 0),
(449, '2026-01-02', 6, 'lunch', 'dim', 0),
(450, '2026-01-02', 3, 'lunch', 'dim', 0),
(451, '2026-01-02', 3, 'dinner', 'other', 0),
(452, '2026-01-01', 1, 'lunch', 'fish', 0),
(453, '2026-01-01', 1, 'dinner', 'other', 0),
(454, '2026-01-01', 7, 'lunch', 'fish', 0),
(455, '2026-01-01', 7, 'dinner', 'other', 0),
(456, '2026-01-01', 5, 'lunch', 'fish', 0),
(457, '2026-01-01', 5, 'dinner', 'other', 0),
(458, '2026-01-01', 2, 'lunch', 'fish', 0),
(459, '2026-01-01', 2, 'dinner', 'other', 0),
(460, '2026-01-01', 4, 'lunch', 'fish', 0),
(461, '2026-01-01', 4, 'dinner', 'other', 0),
(462, '2026-01-01', 6, 'lunch', 'fish', 0),
(463, '2026-01-01', 6, 'lunch', 'fish', 0),
(464, '2026-01-01', 6, 'dinner', 'other', 0),
(465, '2026-01-01', 6, 'dinner', 'other', 0),
(466, '2026-01-01', 3, 'lunch', 'fish', 0),
(467, '2026-01-01', 3, 'dinner', 'other', 0);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `daily_meals`
--
ALTER TABLE `daily_meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=468;

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
