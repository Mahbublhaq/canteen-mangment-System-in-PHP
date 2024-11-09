-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 09, 2024 at 09:24 AM
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
-- Database: `canteen_mangment_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_name`, `email`, `password`, `phone`, `address`, `created_at`) VALUES
(1, 'Mahbubul Huq', 'mahbubulhaq.cu.05@gmail.com', '$2y$10$0bGpIgvSKHHyanaCxyNK3OHAqKxKmrrrYJcLeSav6oB12RLwOGe7O', '01601337085', 'Mirpur 14', '2024-11-01 06:29:01'),
(2, 'Alim', 'abdulalimcse14@gmail.com', '$2y$10$A4DaZqDU5bOCfLELGG9HDOdu6.DNbe6Rb..OBIyki.DTBvrLCQ7mS', '01718337085', 'Khagan, Asulia', '2024-11-07 16:27:54'),
(7, 'Mr X', 'mahabubpanti@gmail.com', '$2y$10$pNeIuoztLydQ/R2Q9hsy8eihS1aU.nrMw5SoED8YlKtiZWdpYn2Om', '01601337085', 'Khagan, Asulia', '2024-11-08 06:39:51');

-- --------------------------------------------------------

--
-- Table structure for table `deposit_history`
--

CREATE TABLE `deposit_history` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `deposit_amount` decimal(10,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deposit_history`
--

INSERT INTO `deposit_history` (`id`, `customer_id`, `deposit_amount`, `transaction_date`) VALUES
(1, 1, 500.00, '2024-11-09 08:08:14'),
(2, 1, 20.00, '2024-11-09 08:10:16');

-- --------------------------------------------------------

--
-- Table structure for table `meal`
--

CREATE TABLE `meal` (
  `id` int(11) NOT NULL,
  `meal_id` int(11) NOT NULL,
  `lunch_meal` tinyint(1) DEFAULT 0,
  `dinner_meal` tinyint(1) DEFAULT 0,
  `deposit` decimal(10,2) DEFAULT NULL,
  `meal_price` decimal(10,2) DEFAULT NULL,
  `remain_balance` decimal(10,2) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lunch_quantity` int(11) DEFAULT 0,
  `dinner_quantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal`
--

INSERT INTO `meal` (`id`, `meal_id`, `lunch_meal`, `dinner_meal`, `deposit`, `meal_price`, `remain_balance`, `active`, `created_at`, `lunch_quantity`, `dinner_quantity`) VALUES
(1, 1, 0, 0, 1900.00, NULL, 1900.00, 1, '2024-11-09 07:59:20', 0, 0),
(2, 1, 0, 0, 2000.00, NULL, 2000.00, 1, '2024-11-09 07:59:39', 0, 0),
(3, 1, 0, 0, 2500.00, NULL, 2500.00, 1, '2024-11-09 08:08:14', 0, 0),
(4, 1, 1, 0, NULL, 120.00, 2080.00, 1, '2024-11-09 08:09:17', 2, 0),
(5, 1, 0, 1, NULL, 300.00, 2080.00, 1, '2024-11-09 08:09:17', 0, 5),
(6, 1, 0, 0, 2100.00, NULL, 2100.00, 1, '2024-11-09 08:10:16', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `meal_registration`
--

CREATE TABLE `meal_registration` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `deposit` decimal(10,2) DEFAULT 100.00,
  `varsity_id` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `meal_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_registration`
--

INSERT INTO `meal_registration` (`id`, `customer_id`, `name`, `department`, `deposit`, `varsity_id`, `email`, `meal_date`) VALUES
(1, 1, 'Mahbubul Huq', 'Computer Science', 2100.00, '1925102005', 'mahbubulhaq.cu.05@gmail.com', '2024-11-05'),
(3, 7, 'Mr X', 'Computer Science', 2000.00, '1925102002', 'mahabubpanti@gmail.com', '2024-11-09');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`order_details`)),
  `total_cost` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `catagorey` varchar(100) DEFAULT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_details` varchar(200) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `Active` int(10) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `catagorey`, `product_name`, `product_details`, `price`, `product_image`, `Active`) VALUES
(5, 'Combo', 'Combo 1', 'Fride Rice + Cegitable + Cary+ Drinks', 250.00, '../uploads/Combo1.jpeg', 1),
(6, 'Combo', 'Combo 2', 'Fride Rice + Vegitable +Chicken Fry+ Drinks', 300.00, '../uploads/Combo2.jpg', 1),
(7, 'Combo', 'Student Combo', 'Fride Rice + Vegitable +Chicken Cary+ Drinks', 200.00, '../uploads/student_combo.jpeg', 1),
(8, 'Meal', 'Lunch Meal', 'Rice+Chicken+Vegitable+Dal', 60.00, '../uploads/meal2.png', 1),
(9, 'Meal', 'Lunch Meal', 'Rice+Vegitable+Dim+Dal', 60.00, '../uploads/Meal3.jpg', 1),
(10, 'Meal', 'Dinner Meal', 'Rice+Vegitable +Finsh Cary+Dal', 60.00, '../uploads/Meal3.jpg', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `deposit_history`
--
ALTER TABLE `deposit_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `meal`
--
ALTER TABLE `meal`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meal_registration`
--
ALTER TABLE `meal_registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `varsity_id` (`varsity_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `deposit_history`
--
ALTER TABLE `deposit_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `meal`
--
ALTER TABLE `meal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `meal_registration`
--
ALTER TABLE `meal_registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deposit_history`
--
ALTER TABLE `deposit_history`
  ADD CONSTRAINT `deposit_history_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `meal_registration` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `meal_registration`
--
ALTER TABLE `meal_registration`
  ADD CONSTRAINT `meal_registration_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
