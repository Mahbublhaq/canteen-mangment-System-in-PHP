-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2024 at 03:52 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` varchar(50) NOT NULL CHECK (`role` = 'admin'),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`, `phone`, `role`, `created_at`) VALUES
(1, 'admin@gmail.com', '$2y$10$Sw5q.tN6ari2Qu4aCp9AROXjrxmieS2DKfr6RACA4ZifoDQftyzKy', '01601337085', 'admin', '2024-11-18 13:52:36');

-- --------------------------------------------------------

--
-- Table structure for table `contact_queries`
--

CREATE TABLE `contact_queries` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `problem` enum('Deposit Problem','Food Problem','Delivery Problem','Packing Problem','Other') NOT NULL,
  `details` text DEFAULT NULL,
  `query` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(13, 'Mahabub', 'mahabubpanti@gmail.com', '$2y$10$2c58Xv/WJ.fx3Sdz4WrYlOS0E3PKj5u6jJC9IYyYEZglfKbGh1LRq', '01601337085', 'Khagan, Asulia', '2024-11-23 08:13:51');

-- --------------------------------------------------------

--
-- Table structure for table `deposit_history`
--

CREATE TABLE `deposit_history` (
  `id` int(11) NOT NULL,
  `meal_id` int(11) NOT NULL,
  `deposit_amount` decimal(10,2) NOT NULL,
  `deposit_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deposit_history`
--

INSERT INTO `deposit_history` (`id`, `meal_id`, `deposit_amount`, `deposit_date`) VALUES
(2, 13, 0.00, '2024-11-23 19:34:29'),
(3, 13, 0.00, '2024-11-23 19:36:53');

-- --------------------------------------------------------

--
-- Table structure for table `meal`
--

CREATE TABLE `meal` (
  `id` int(11) NOT NULL,
  `meal_id` int(11) NOT NULL,
  `lunch_meal` tinyint(1) DEFAULT 0,
  `dinner_meal` tinyint(1) DEFAULT 0,
  `deposit` decimal(10,2) DEFAULT 0.00,
  `meal_price` decimal(10,2) DEFAULT NULL,
  `remain_balance` decimal(10,2) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal`
--

INSERT INTO `meal` (`id`, `meal_id`, `lunch_meal`, `dinner_meal`, `deposit`, `meal_price`, `remain_balance`, `active`, `created_at`) VALUES
(3, 13, 0, 0, 2000.00, NULL, 2000.00, 1, '2024-11-23 13:36:53'),
(4, 13, 0, 1, 0.00, 60.00, 1940.00, 1, '2024-11-23 13:37:59');

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
(1, 1, 'Mahbubul Huq', 'Computer Science', 2000.00, '1925102005', 'mahbubulhaq.cu.05@gmail.com', '2024-11-05'),
(43, 13, 'Mahabub', 'Computer Science', 1940.00, '1925102000', 'mahabubpanti@gmail.com', '2024-11-23');

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

CREATE TABLE `offers` (
  `id` int(11) NOT NULL,
  `offer_name` varchar(255) NOT NULL,
  `discount_code` varchar(50) NOT NULL,
  `discount_amount` int(11) NOT NULL,
  `expiry_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offers`
--

INSERT INTO `offers` (`id`, `offer_name`, `discount_code`, `discount_amount`, `expiry_date`) VALUES
(3, 'Hot Offer ', 'Mahabub', 100, '2024-11-25'),
(4, 'Hot Offer ', 'SEVASE', 50, '2024-11-24');

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

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `order_details`, `total_cost`, `created_at`) VALUES
(2, 13, '[\"Dinner Meal*1 BDT 60.00\"]', 60.00, '2024-11-23 13:37:59');

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
(8, 'Meal', 'Lunch Meal 1', 'Rice+Chicken+Vegitable+Dal', 60.00, '../uploads/meal2.png', 1),
(9, 'Meal', 'Lunch Meal', 'Rice+Vegitable+Dim+Dal', 60.00, '../uploads/Meal3.jpg', 1),
(10, 'Meal', 'Dinner Meal', 'Rice+Vegitable +Finsh Cary+Dal', 60.00, '../uploads/Meal3.jpg', 1),
(11, 'Hot Offer', 'Kacchi Biryani', 'Mutton Kacchi Biryani', 200.00, '../uploads/kassi1.jpg', 1),
(12, 'Hot Offer', 'Chicken Biryani', 'Chicken Biryani Half Plate', 100.00, '../uploads/chickenBirany.jpeg', 1),
(13, 'Hot Offer', 'Ilish Mas', 'illish fish fry + Rice +Dal', 150.00, '../uploads/fishfry.jpeg', 1),
(14, 'Hot Offer', 'Ilish Mas', 'Sorisa illish + Rice +Dal', 200.00, '../uploads/ilish.jpeg', 1),
(15, 'Hot Offer', ' Vorta ', '10 Pod er Vorta+Vat+Dal', 100.00, '../uploads/vorta.jpeg', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `contact_queries`
--
ALTER TABLE `contact_queries`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `meal_id` (`meal_id`);

--
-- Indexes for table `meal`
--
ALTER TABLE `meal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meal_id` (`meal_id`);

--
-- Indexes for table `meal_registration`
--
ALTER TABLE `meal_registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `varsity_id` (`varsity_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_queries`
--
ALTER TABLE `contact_queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `deposit_history`
--
ALTER TABLE `deposit_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `meal`
--
ALTER TABLE `meal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `meal_registration`
--
ALTER TABLE `meal_registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `offers`
--
ALTER TABLE `offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deposit_history`
--
ALTER TABLE `deposit_history`
  ADD CONSTRAINT `deposit_history_ibfk_1` FOREIGN KEY (`meal_id`) REFERENCES `meal_registration` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `meal`
--
ALTER TABLE `meal`
  ADD CONSTRAINT `meal_ibfk_1` FOREIGN KEY (`meal_id`) REFERENCES `meal_registration` (`customer_id`) ON DELETE CASCADE;

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
