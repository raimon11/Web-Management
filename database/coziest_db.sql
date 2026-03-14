-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2026 at 08:50 AM
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
-- Database: `coziest_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `created_at`) VALUES
(1, 'Stickers', '2026-03-12 22:09:14'),
(2, 'Mug', '2026-03-12 22:09:14'),
(3, 'Polaroid', '2026-03-12 22:09:14'),
(4, 'Bag', '2026-03-12 22:09:14'),
(5, 'Banner', '2026-03-12 22:09:14'),
(6, 'Glass Tumbler', '2026-03-12 22:09:14'),
(7, 'Bouquet', '2026-03-12 22:09:14'),
(8, 'Brush', '2026-03-12 22:09:14'),
(9, 'Calendar', '2026-03-12 22:09:14'),
(10, 'Caps', '2026-03-12 22:09:14'),
(11, 'Gift Box', '2026-03-12 22:09:14'),
(12, 'Katinko', '2026-03-12 22:09:14'),
(13, 'Keychain Laminated', '2026-03-12 22:09:14'),
(14, 'Keychain Landscape', '2026-03-12 22:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `product_id`, `quantity`, `created_at`) VALUES
(1, 1, 1, '2026-03-14 07:39:29'),
(2, 2, 94, '2026-03-12 14:04:59'),
(3, 3, 110, '2026-03-12 14:04:59'),
(4, 4, 80, '2026-03-12 14:04:59'),
(5, 5, 60, '2026-03-12 14:04:59'),
(6, 6, 45, '2026-03-12 14:04:59'),
(7, 7, 50, '2026-03-12 14:04:59'),
(8, 8, 55, '2026-03-12 14:04:59'),
(9, 9, 200, '2026-03-12 14:04:59'),
(10, 10, 150, '2026-03-12 14:04:59'),
(11, 11, 170, '2026-03-12 14:04:59'),
(12, 12, 40, '2026-03-12 14:04:59'),
(13, 13, 35, '2026-03-12 14:04:59'),
(14, 14, 30, '2026-03-12 14:04:59'),
(15, 15, 5, '2026-03-14 07:29:02'),
(16, 16, 18, '2026-03-12 14:04:59'),
(17, 17, 25, '2026-03-12 14:04:59'),
(18, 18, 70, '2026-03-12 14:04:59'),
(19, 19, 65, '2026-03-12 14:04:59'),
(20, 20, 60, '2026-03-12 14:04:59'),
(21, 21, 15, '2026-03-12 14:04:59'),
(22, 22, 20, '2026-03-12 14:04:59'),
(23, 23, 18, '2026-03-12 14:04:59'),
(24, 24, 90, '2026-03-12 14:04:59'),
(25, 25, 85, '2026-03-12 14:04:59'),
(26, 26, 80, '2026-03-12 14:04:59'),
(27, 27, 45, '2026-03-12 14:04:59'),
(28, 28, 40, '2026-03-12 14:04:59'),
(29, 29, 50, '2026-03-12 14:04:59'),
(30, 30, 60, '2026-03-12 14:04:59'),
(31, 31, 55, '2026-03-12 14:04:59'),
(32, 32, 50, '2026-03-12 14:04:59'),
(33, 33, 100, '2026-03-12 14:04:59'),
(34, 34, 120, '2026-03-12 14:04:59'),
(35, 35, 90, '2026-03-12 14:04:59'),
(36, 36, 70, '2026-03-12 14:04:59'),
(37, 37, 65, '2026-03-12 14:04:59'),
(38, 38, 75, '2026-03-12 14:04:59'),
(39, 39, 150, '2026-03-12 14:04:59'),
(40, 40, 130, '2026-03-12 14:04:59'),
(41, 41, 140, '2026-03-12 14:04:59'),
(42, 42, 95, '2026-03-12 14:04:59'),
(43, 43, 0, '2026-03-14 05:12:11'),
(44, 44, 90, '2026-03-12 14:04:59');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `image_url` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `order_details_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `delivery_address` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL,
  `proof_of_payment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `order_number`, `status`, `order_details_id`, `total_amount`, `payment_method`, `delivery_address`, `created_at`, `updated_at`, `proof_of_payment`) VALUES
(1, 1, 'ORD-1773462837', 'APPROVED', 0, 800.00, 'ONLINE', '134 J. Test Streeet', '2026-03-14 12:33:57', '2026-03-14 12:35:25', 'uploads/proof_of_payment/1773462837_Screenshot 2025-07-06 193317.png'),
(2, 1, 'ORD-1773464003', 'APPROVED', 0, 200.00, 'ONLINE', 'N/A', '2026-03-14 12:53:23', '2026-03-14 12:53:34', 'uploads/proof_of_payment/1773464003_Screenshot 2025-07-06 193327.png'),
(3, 1, 'ORD-1773464034', 'DECLINED', 0, 100.00, 'ONLINE', 'N/A', '2026-03-14 12:53:54', '2026-03-14 12:54:01', 'uploads/proof_of_payment/1773464034_Screenshot 2025-07-13 224150.png'),
(4, 1, 'ORD-1773464054', 'APPROVED', 0, 100.00, 'ONLINE', 'N/A', '2026-03-14 12:54:14', '2026-03-14 15:28:34', 'uploads/proof_of_payment/1773464054_Screenshot 2025-07-18 181007.png'),
(5, 1, 'ORD-1773467578', 'APPROVED', 0, 200.00, 'ONLINE', '123 Test Street Caloocan City', '2026-03-14 13:52:58', '2026-03-14 15:28:33', 'uploads/proof_of_payment/1773467578_Screenshot 2025-07-18 181821.png');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `created_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(1, 1, 1, 8, 100, 2147483647),
(2, 2, 2, 1, 100, 2147483647),
(3, 2, 1, 1, 100, 2147483647),
(4, 3, 1, 1, 100, 2147483647),
(5, 4, 1, 1, 100, 2147483647),
(6, 5, 1, 2, 100, 2147483647);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `picture` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `price`, `picture`) VALUES
(1, 1, 'Anime Sticker Pack', 100, './assets/logo.png'),
(2, 1, 'Kpop Sticker Pack', 100, './assets/logo.png'),
(3, 1, 'Cute Animal Stickers', 90, './assets/logo.png'),
(4, 1, 'Laptop Stickers Set', 120, './assets/logo.png'),
(5, 2, 'Custom Photo Mug', 120, './assets/logo.png'),
(6, 2, 'Couple Mug Set', 200, './assets/logo.png'),
(7, 2, 'Motivational Quote Mug', 130, './assets/logo.png'),
(8, 2, 'Minimalist Coffee Mug', 110, './assets/logo.png'),
(9, 3, 'Polaroid Photo Print', 20, './assets/logo.png'),
(10, 3, 'Polaroid Memory Pack', 80, './assets/logo.png'),
(11, 3, 'Vintage Polaroid Set', 60, './assets/logo.png'),
(12, 4, 'Canvas Tote Bag', 250, './assets/logo.png'),
(13, 4, 'Custom Printed Tote Bag', 280, './assets/logo.png'),
(14, 4, 'Minimalist Eco Bag', 230, './assets/logo.png'),
(15, 5, 'Birthday Banner', 400, './assets/logo.png'),
(16, 5, 'Welcome Banner', 350, './assets/logo.png'),
(17, 5, 'Event Celebration Banner', 420, './assets/logo.png'),
(18, 6, 'Custom Glass Tumbler', 100, './assets/logo.png'),
(19, 6, 'Minimalist Glass Tumbler', 110, './assets/logo.png'),
(20, 6, 'Frosted Glass Tumbler', 120, './assets/logo.png'),
(21, 7, 'Mini Flower Bouquet', 300, './assets/logo.png'),
(22, 7, 'Rose Bouquet', 350, './assets/logo.png'),
(23, 7, 'Graduation Bouquet', 320, './assets/logo.png'),
(24, 8, 'Makeup Brush Set', 80, './assets/logo.png'),
(25, 8, 'Soft Blush Brush', 70, './assets/logo.png'),
(26, 8, 'Foundation Brush', 75, './assets/logo.png'),
(27, 9, 'Desk Calendar', 220, './assets/logo.png'),
(28, 9, 'Photo Calendar', 240, './assets/logo.png'),
(29, 9, 'Minimalist Wall Calendar', 200, './assets/logo.png'),
(30, 10, 'Custom Printed Cap', 200, './assets/logo.png'),
(31, 10, 'Streetwear Cap', 210, './assets/logo.png'),
(32, 10, 'Minimalist Black Cap', 190, './assets/logo.png'),
(33, 11, 'Small Gift Box', 30, './assets/logo.png'),
(34, 11, 'Luxury Gift Box', 80, './assets/logo.png'),
(35, 11, 'Surprise Gift Box', 50, './assets/logo.png'),
(36, 12, 'Katinko Set', 150, './assets/logo.png'),
(37, 12, 'Katinko Oil', 120, './assets/logo.png'),
(38, 12, 'Katinko Relief Balm', 140, './assets/logo.png'),
(39, 13, 'Photo Keychain Laminated', 30, './assets/logo.png'),
(40, 13, 'Couple Photo Keychain', 35, './assets/logo.png'),
(41, 13, 'Custom Name Keychain', 40, './assets/logo.png'),
(42, 14, 'Landscape Photo Keychain', 50, './assets/logo.png'),
(43, 14, 'Scenery Keychain', 55, 'uploads/products/product_1773470026_9748.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `delivery_address` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `role` varchar(10) NOT NULL DEFAULT 'User'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `email`, `delivery_address`, `password`, `is_verified`, `created_at`, `role`) VALUES
(1, 'Jasper', 'David', 'Macaraeg', 'jasper@gmail.com', '123 Test Street Caloocan City', 'test1234', 0, '2026-03-08 22:16:51', 'Admin'),
(2, 'Ethan', 'Test', 'Cunanan', 'ethan@gmail.com', '1504 Test Street Caloocan City', 'test1234', 0, '2026-03-08 22:36:46', 'User'),
(3, 'Raine', 'White', 'Green', 'sdada@gmail.com', '1234 Goku Test Street Caloocan City', 'test1234', 0, '2026-03-13 19:52:50', 'Admin'),
(4, 'Gerry', 'Darwin', 'Green', 'gerry@gmail.com', '130 K Street Caloocan City', 'test1234', 0, '2026-03-14 07:49:46', 'User'),
(5, 'Cherry', 'Grey', 'Cong', 'cherry@gmail.com', '1234 White Lady Street', 'test1234', 0, '2026-03-14 07:51:19', 'User');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
