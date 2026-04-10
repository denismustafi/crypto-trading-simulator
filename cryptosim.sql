-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 07:40 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cryptosim`
--

-- --------------------------------------------------------

--
-- Table structure for table `holdings`
--

CREATE TABLE `holdings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `coin` varchar(20) NOT NULL,
  `amount` decimal(20,8) DEFAULT 0.00000000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `holdings`
--

INSERT INTO `holdings` (`id`, `user_id`, `coin`, `amount`) VALUES
(1, 3, 'bitcoin', 0.06850148),
(4, 3, 'solana', 0.00944975),
(16, 4, 'ethereum', 0.44904702),
(18, 4, 'bitcoin', 0.03445117),
(19, 4, 'solana', 17.80724473),
(20, 4, 'dogecoin', 21374.39225120);

-- --------------------------------------------------------

--
-- Table structure for table `limit_orders`
--

CREATE TABLE `limit_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `coin` varchar(20) NOT NULL,
  `type` enum('buy','sell') NOT NULL,
  `amount_usd` decimal(15,2) NOT NULL,
  `target_price` decimal(15,2) NOT NULL,
  `status` enum('pending','completed','canceled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `limit_orders`
--

INSERT INTO `limit_orders` (`id`, `user_id`, `coin`, `type`, `amount_usd`, `target_price`, `status`, `created_at`) VALUES
(1, 4, 'bitcoin', 'buy', 500.00, 73000.00, 'completed', '2026-04-10 16:20:23'),
(2, 4, 'bitcoin', 'buy', 500.00, 73000.00, 'completed', '2026-04-10 16:20:34');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `coin` varchar(20) NOT NULL,
  `type` enum('buy','sell') NOT NULL,
  `amount_usd` decimal(15,2) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `coins` decimal(20,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `coin`, `type`, `amount_usd`, `price`, `coins`, `created_at`) VALUES
(1, 3, 'bitcoin', 'buy', 50.00, 72733.45, 0.00068744, '2026-04-10 15:39:13'),
(2, 3, 'bitcoin', 'buy', 500.00, 72733.45, 0.00687442, '2026-04-10 15:39:17'),
(3, 3, 'bitcoin', 'buy', 9000.00, 72733.45, 0.12373949, '2026-04-10 15:39:27'),
(4, 3, 'solana', 'buy', 50.00, 84.74, 0.59006115, '2026-04-10 15:41:49'),
(5, 3, 'solana', 'buy', 50.00, 84.74, 0.59006115, '2026-04-10 15:41:51'),
(6, 3, 'solana', 'buy', 50.00, 84.74, 0.59006115, '2026-04-10 15:41:52'),
(7, 3, 'solana', 'buy', 50.00, 84.74, 0.59006115, '2026-04-10 15:41:52'),
(8, 3, 'solana', 'buy', 50.00, 84.74, 0.59006115, '2026-04-10 15:41:53'),
(9, 3, 'solana', 'buy', 5.00, 84.74, 0.05900611, '2026-04-10 15:42:11'),
(10, 3, 'solana', 'buy', 50.00, 84.74, 0.59006115, '2026-04-10 15:42:12'),
(11, 3, 'solana', 'buy', 100.00, 84.74, 1.18012229, '2026-04-10 15:42:14'),
(12, 3, 'solana', 'sell', 50.00, 84.74, 0.59006115, '2026-04-10 15:42:24'),
(13, 3, 'solana', 'buy', 50.00, 84.74, 0.59006115, '2026-04-10 15:42:26'),
(14, 3, 'solana', 'sell', 50.00, 84.74, 0.59006115, '2026-04-10 15:42:29'),
(15, 3, 'solana', 'buy', 50.00, 84.74, 0.59006115, '2026-04-10 15:42:31'),
(16, 3, 'solana', 'sell', 50.00, 84.74, 0.59006115, '2026-04-10 15:42:32'),
(17, 3, 'solana', 'sell', 100.00, 84.74, 1.18012229, '2026-04-10 15:42:42'),
(18, 3, 'solana', 'sell', 100.00, 84.74, 1.18012229, '2026-04-10 15:42:43'),
(19, 3, 'bitcoin', 'buy', 50.00, 72665.09, 0.00068809, '2026-04-10 15:43:10'),
(20, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:15'),
(21, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:17'),
(22, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:18'),
(23, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:19'),
(24, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:19'),
(25, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:19'),
(26, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:20'),
(27, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:20'),
(28, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:20'),
(29, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:21'),
(30, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:21'),
(31, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:21'),
(32, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:21'),
(33, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:21'),
(34, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:21'),
(35, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:22'),
(36, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:22'),
(37, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:22'),
(38, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:22'),
(39, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:22'),
(40, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:23'),
(41, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:23'),
(42, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:23'),
(43, 3, 'bitcoin', 'sell', 245.00, 72717.20, 0.00336922, '2026-04-10 15:46:23'),
(44, 3, 'bitcoin', 'sell', 500.00, 72717.20, 0.00687595, '2026-04-10 15:46:34'),
(45, 3, 'bitcoin', 'sell', 500.00, 72717.20, 0.00687595, '2026-04-10 15:46:35'),
(46, 3, 'bitcoin', 'sell', 500.00, 72717.20, 0.00687595, '2026-04-10 15:46:36'),
(47, 3, 'bitcoin', 'sell', 500.00, 72717.20, 0.00687595, '2026-04-10 15:46:36'),
(48, 3, 'bitcoin', 'sell', 500.00, 72717.20, 0.00687595, '2026-04-10 15:46:37'),
(49, 3, 'bitcoin', 'sell', 500.00, 72717.20, 0.00687595, '2026-04-10 15:46:37'),
(50, 3, 'bitcoin', 'sell', 500.00, 72717.20, 0.00687595, '2026-04-10 15:46:37'),
(51, 3, 'bitcoin', 'sell', 50.00, 72717.20, 0.00068760, '2026-04-10 15:46:43'),
(52, 3, 'bitcoin', 'sell', 50.00, 72717.20, 0.00068760, '2026-04-10 15:46:43'),
(53, 3, 'bitcoin', 'sell', 50.00, 72717.20, 0.00068760, '2026-04-10 15:46:44'),
(54, 3, 'bitcoin', 'sell', 50.00, 72717.20, 0.00068760, '2026-04-10 15:46:44'),
(55, 3, 'bitcoin', 'sell', 15.00, 72717.20, 0.00020628, '2026-04-10 15:46:53'),
(56, 3, 'bitcoin', 'sell', 2.00, 72717.20, 0.00002750, '2026-04-10 15:47:02'),
(57, 3, 'bitcoin', 'buy', 9842.00, 72717.20, 0.13534625, '2026-04-10 15:47:11'),
(58, 3, 'bitcoin', 'sell', 9842.00, 72717.20, 0.13534625, '2026-04-10 15:47:26'),
(59, 3, 'solana', 'sell', 84.00, 84.63, 0.99258535, '2026-04-10 15:47:55'),
(60, 3, 'solana', 'sell', 50.00, 84.63, 0.59082462, '2026-04-10 15:48:00'),
(61, 3, 'solana', 'sell', 20.00, 84.63, 0.23632985, '2026-04-10 15:48:09'),
(62, 4, 'ethereum', 'buy', 500.00, 2230.48, 0.22416735, '2026-04-10 15:48:52'),
(63, 4, 'ethereum', 'buy', 500.00, 2223.41, 0.22487967, '2026-04-10 16:03:38'),
(64, 4, 'bitcoin', 'buy', 1500.00, 72436.00, 0.02070793, '2026-04-10 16:04:01'),
(65, 4, 'solana', 'buy', 1500.00, 84.24, 17.80724473, '2026-04-10 16:04:11'),
(66, 4, 'dogecoin', 'buy', 500.00, 0.09, 5343.59806280, '2026-04-10 16:04:28'),
(67, 4, 'dogecoin', 'buy', 500.00, 0.09, 5343.59806280, '2026-04-10 16:04:29'),
(68, 4, 'dogecoin', 'buy', 500.00, 0.09, 5343.59806280, '2026-04-10 16:04:31'),
(69, 4, 'dogecoin', 'buy', 500.00, 0.09, 5343.59806280, '2026-04-10 16:04:32'),
(70, 4, 'bitcoin', 'buy', 500.00, 72763.00, 0.00687162, '2026-04-10 16:20:51'),
(71, 4, 'bitcoin', 'buy', 500.00, 72763.00, 0.00687162, '2026-04-10 16:20:51'),
(72, 3, 'bitcoin', 'buy', 4998.00, 72975.06, 0.06848915, '2026-04-10 17:02:06'),
(73, 3, 'bitcoin', 'sell', 4998.00, 72975.06, 0.06848915, '2026-04-10 17:02:08'),
(74, 3, 'bitcoin', 'buy', 4998.00, 72975.06, 0.06848915, '2026-04-10 17:02:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `usd_balance` decimal(15,2) NOT NULL DEFAULT 10000.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `usd_balance`, `created_at`) VALUES
(1, 'denis', 'denis1@gmail.com', '$2y$10$GqPRQz0p3KKLe5nOLx7GheycoViM7XOcMJYF2/pRtII/PNfHSn8iW', 10000.00, '2026-04-04 11:30:01'),
(2, 'denis1', 'denis2@gmail.com', '$2y$10$FSZwkeBV7yQqnOmFZ7elTu/7RjY0wA2NNTK3WxFx2B.mJRjUSHUfS', 10000.00, '2026-04-04 12:00:18'),
(3, 'denis123', 'denis123@gmail.com', '$2y$10$BBiNdZQhoyRgMPWQkA3RMOyhAZ3m0JUQ4AkO6.dzJHaxKqDJHVXrC', 4998.00, '2026-04-10 15:38:41'),
(4, 'denis1337', 'denis@gmail.com', '$2y$10$c9uOzm8H6zF0.3yCPuNmze1Wb1DmvEZIHb.E9ukqKPmxDZe5XnZtG', 3000.00, '2026-04-10 15:48:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `holdings`
--
ALTER TABLE `holdings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_coin` (`user_id`,`coin`);

--
-- Indexes for table `limit_orders`
--
ALTER TABLE `limit_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `holdings`
--
ALTER TABLE `holdings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `limit_orders`
--
ALTER TABLE `limit_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `holdings`
--
ALTER TABLE `holdings`
  ADD CONSTRAINT `holdings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `limit_orders`
--
ALTER TABLE `limit_orders`
  ADD CONSTRAINT `limit_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
