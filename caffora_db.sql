-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2025 at 08:24 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `caffora_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `entity` enum('order','payment','menu','user') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `from_val` varchar(100) DEFAULT NULL,
  `to_val` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `qty_change` int(11) NOT NULL,
  `reason` enum('purchase','sale','adjust','waste') NOT NULL,
  `ref_type` enum('order','manual') NOT NULL DEFAULT 'order',
  `ref_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `order_id`, `amount`, `issued_at`) VALUES
(2, 40, 38000.00, '2025-10-07 13:26:29'),
(3, 41, 49000.00, '2025-10-07 13:31:12'),
(4, 42, 51000.00, '2025-10-07 13:38:26'),
(5, 43, 44000.00, '2025-10-07 13:46:54'),
(6, 44, 85000.00, '2025-10-07 13:49:02'),
(7, 45, 64000.00, '2025-10-07 14:00:48'),
(8, 46, 165000.00, '2025-10-07 14:12:21'),
(9, 47, 44000.00, '2025-10-07 14:36:14'),
(10, 48, 27000.00, '2025-10-07 14:46:30'),
(11, 49, 27000.00, '2025-10-07 16:01:08'),
(12, 50, 44000.00, '2025-10-08 04:50:51'),
(13, 51, 55000.00, '2025-10-08 05:19:49'),
(14, 52, 133000.00, '2025-10-09 04:00:58'),
(15, 53, 78000.00, '2025-10-09 06:08:50');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `stock_status` enum('Ready','Sold Out') DEFAULT 'Ready',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL,
  `cogs` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `name`, `category`, `image`, `price`, `stock_status`, `created_at`, `category_id`, `cogs`) VALUES
(20, 'chickenthai', 'food', 'uploads/menu/m_1759549134_1908.jpg', 26000.00, 'Ready', '2025-10-04 03:38:54', NULL, NULL),
(21, 'es', 'drink', 'uploads/menu/m_1759549526_5477.jpg', 6000.00, 'Ready', '2025-10-04 03:45:26', NULL, NULL),
(22, 'Pound cake', 'pastry', 'uploads/menu/m_1759754380_2771.jpg', 49000.00, 'Ready', '2025-10-06 12:39:40', NULL, NULL),
(23, 'Nasi goreng Bali', 'food', 'uploads/menu/m_1759754418_9633.jpg', 44000.00, 'Ready', '2025-10-06 12:40:18', NULL, NULL),
(24, 'chiffon', 'pastry', 'uploads/menu/m_1759810812_5410.jpg', 145000.00, 'Ready', '2025-10-07 04:20:12', NULL, NULL),
(25, 'basque cheesecake', 'drink', 'uploads/menu/m_1759810844_3127.jpg', 45000.00, 'Ready', '2025-10-07 04:20:44', NULL, NULL),
(26, 'lazy lemon', 'drink', 'uploads/menu/m_1759810877_5614.jpg', 20000.00, 'Ready', '2025-10-07 04:21:17', NULL, NULL),
(27, 'korean', 'food', 'uploads/menu/m_1759810963_4489.jpg', 38000.00, 'Ready', '2025-10-07 04:22:43', NULL, NULL),
(28, 'croissant', 'pastry', 'uploads/menu/m_1759811015_8062.jpg', 27000.00, 'Ready', '2025-10-07 04:23:35', NULL, NULL),
(29, 'matcha cheesecake', 'pastry', 'uploads/menu/m_1759811076_2295.jpg', 44000.00, 'Ready', '2025-10-07 04:24:36', NULL, NULL),
(30, 'salmon toast', 'food', 'uploads/menu/m_1759811191_6905.jpg', 78000.00, 'Ready', '2025-10-07 04:26:31', NULL, NULL),
(31, 'avocado toast', 'food', 'uploads/menu/m_1759811219_2947.jpg', 55000.00, 'Ready', '2025-10-07 04:26:59', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `service_type` enum('dine_in','take_away') NOT NULL DEFAULT 'dine_in',
  `table_no` varchar(10) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL,
  `order_status` enum('new','processing','ready','completed','cancelled') NOT NULL DEFAULT 'new',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','bank_transfer','qris','ewallet') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `invoice_no`, `customer_name`, `service_type`, `table_no`, `total`, `order_status`, `payment_status`, `payment_method`, `created_at`, `updated_at`) VALUES
(38, NULL, 'INV-003', 'lisa', 'dine_in', '04', 47000.00, 'completed', 'paid', 'bank_transfer', '2025-10-07 13:06:13', '2025-10-08 05:29:58'),
(39, NULL, 'INV-002', 'lisa', 'dine_in', '11', 122000.00, 'completed', 'paid', 'qris', '2025-10-07 13:09:44', '2025-10-08 05:03:58'),
(40, NULL, 'INV-003', 'lisa', 'take_away', '', 38000.00, 'completed', 'paid', 'bank_transfer', '2025-10-07 13:26:29', '2025-10-08 05:29:54'),
(41, NULL, 'INV-004', 'jiso', 'dine_in', '12', 49000.00, 'completed', 'paid', 'cash', '2025-10-07 13:31:12', '2025-10-08 05:29:52'),
(42, NULL, 'INV-005', 'alex', 'dine_in', '11', 51000.00, 'completed', 'paid', 'cash', '2025-10-07 13:38:26', '2025-10-08 05:29:47'),
(43, 30, 'INV-006', 'felix', 'dine_in', '11', 44000.00, 'completed', 'paid', 'cash', '2025-10-07 13:46:54', '2025-10-07 13:48:15'),
(44, 30, 'INV-007', 'joy', 'take_away', '', 85000.00, 'completed', 'paid', 'qris', '2025-10-07 13:49:02', '2025-10-08 05:03:44'),
(45, 30, 'INV-008', 'jack', 'dine_in', '11', 64000.00, 'completed', 'paid', 'qris', '2025-10-07 14:00:48', '2025-10-07 14:13:37'),
(46, 30, 'INV-009', 'charlos', 'take_away', '', 165000.00, 'completed', 'paid', 'bank_transfer', '2025-10-07 14:12:21', '2025-10-08 05:29:44'),
(47, 30, 'INV-010', 'lisa', 'take_away', '', 44000.00, 'completed', 'paid', 'qris', '2025-10-07 14:36:14', '2025-10-08 05:03:37'),
(48, 30, 'INV-011', 'cakra', 'take_away', '', 27000.00, 'completed', 'paid', 'cash', '2025-10-07 14:46:30', '2025-10-08 05:03:35'),
(49, 30, 'INV-012', 'bryan', 'take_away', '', 27000.00, 'completed', 'paid', 'qris', '2025-10-07 16:01:08', '2025-10-07 16:01:23'),
(50, 30, 'INV-013', 'lisa', 'dine_in', '12', 44000.00, 'completed', 'paid', 'bank_transfer', '2025-10-08 04:50:51', '2025-10-08 05:29:36'),
(51, 30, 'INV-014', 'lisa', 'take_away', '', 55000.00, 'completed', 'paid', 'cash', '2025-10-08 05:19:49', '2025-10-08 05:33:59'),
(52, 30, 'INV-015', 'ronaldo messi', 'take_away', '', 133000.00, 'completed', 'paid', 'bank_transfer', '2025-10-09 04:00:58', '2025-10-09 04:01:29'),
(53, 30, 'INV-016', 'maria', 'take_away', '', 78000.00, 'completed', 'paid', 'qris', '2025-10-09 06:08:50', '2025-10-09 06:09:30');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `price` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cogs_unit` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_id`, `qty`, `price`, `discount`, `cogs_unit`, `created_at`) VALUES
(3, 40, 27, 1, 38000.00, 0.00, NULL, '2025-10-07 13:26:29'),
(4, 41, 22, 1, 49000.00, 0.00, NULL, '2025-10-07 13:31:12'),
(5, 42, 21, 1, 6000.00, 0.00, NULL, '2025-10-07 13:38:26'),
(6, 42, 25, 1, 45000.00, 0.00, NULL, '2025-10-07 13:38:26'),
(7, 43, 29, 1, 44000.00, 0.00, NULL, '2025-10-07 13:46:54'),
(8, 44, 28, 1, 27000.00, 0.00, NULL, '2025-10-07 13:49:02'),
(9, 44, 27, 1, 38000.00, 0.00, NULL, '2025-10-07 13:49:02'),
(10, 44, 26, 1, 20000.00, 0.00, NULL, '2025-10-07 13:49:02'),
(11, 45, 23, 1, 44000.00, 0.00, NULL, '2025-10-07 14:00:48'),
(12, 45, 26, 1, 20000.00, 0.00, NULL, '2025-10-07 14:00:48'),
(13, 46, 24, 1, 145000.00, 0.00, NULL, '2025-10-07 14:12:21'),
(14, 46, 26, 1, 20000.00, 0.00, NULL, '2025-10-07 14:12:21'),
(15, 47, 29, 1, 44000.00, 0.00, NULL, '2025-10-07 14:36:14'),
(16, 48, 28, 1, 27000.00, 0.00, NULL, '2025-10-07 14:46:30'),
(17, 49, 28, 1, 27000.00, 0.00, NULL, '2025-10-07 16:01:08'),
(18, 50, 29, 1, 44000.00, 0.00, NULL, '2025-10-08 04:50:51'),
(19, 51, 31, 1, 55000.00, 0.00, NULL, '2025-10-08 05:19:49'),
(20, 52, 31, 1, 55000.00, 0.00, NULL, '2025-10-09 04:00:58'),
(21, 52, 30, 1, 78000.00, 0.00, NULL, '2025-10-09 04:00:58'),
(22, 53, 30, 1, 78000.00, 0.00, NULL, '2025-10-09 06:08:50');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `method` enum('cash','qris','bank_transfer','ewallet') NOT NULL,
  `status` enum('pending','paid','failed','refunded','overdue') NOT NULL DEFAULT 'pending',
  `amount_gross` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_net` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `status` enum('pending','active') DEFAULT 'pending',
  `role` enum('admin','customer','karyawan') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `otp`, `otp_expires_at`, `status`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin Caffora', 'admncaffora@gmail.com', '$2y$10$S5XiTHROSObbeH4ysAu3lOsGtDpmZsuKIT9pJvhahLjIihV3G.U8m', NULL, NULL, 'active', 'admin', '2025-09-27 09:34:04', '2025-09-29 17:08:00'),
(2, 'uroh', 'umansyuroh@gmail.com', '$2y$10$38JMoZiUBB/iUdSkxmASGuNekAglhyYMg9vIwhkCwg4E1HUgWDyK.', NULL, NULL, 'active', 'karyawan', '2025-09-27 10:49:01', '2025-09-30 10:57:06'),
(3, 'jennie', 'fatinalusiana@gmail.com', '$2y$10$EP0/NqrdB5XV0EX1a0twEOjB292gDnpW2iUE9hXT4IQ.duKV9akyS', NULL, NULL, 'active', 'customer', '2025-09-29 14:05:32', '2025-10-06 16:28:11'),
(16, 'annisaMP', 'fauziaisyanti@gmail.com', '$2y$10$T9Ga8uZCTbcVY45xTJeP2uNBNXvc4JXKeAGnAEWNMqN4VU3K.qdvu', NULL, NULL, 'active', 'customer', '2025-10-01 03:49:52', '2025-10-01 03:50:14'),
(29, 'ayaa', 'himansyuroh@gmail.com', '$2y$10$0SbVGNcI4K/Nkjk2zqKLguyufsDywIExzHKy9MGY7f5Rpzu83uLWa', '255537', '2025-10-05 17:04:16', 'active', 'customer', '2025-10-05 09:49:22', '2025-10-06 13:32:59'),
(30, 'lisa', 'skyynepzeo@gmail.com', '$2y$10$VF.NWgPOacW8aOdh6IrLF.HWDuZrARbFWmM.t9GRlJOsXHzF43NBy', NULL, NULL, 'active', 'customer', '2025-10-07 04:39:35', '2025-10-07 04:40:55');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_daily_revenue`
-- (See below for the actual view)
--
CREATE TABLE `v_daily_revenue` (
`d` date
,`revenue` decimal(34,2)
,`orders` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_margin`
-- (See below for the actual view)
--
CREATE TABLE `v_margin` (
`gross_sales` decimal(44,2)
,`cogs` decimal(44,2)
,`gross_margin` decimal(45,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_top_menu_30d`
-- (See below for the actual view)
--
CREATE TABLE `v_top_menu_30d` (
`id` int(11)
,`name` varchar(150)
,`qty` decimal(32,0)
,`total` decimal(44,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_daily_revenue`
--
DROP TABLE IF EXISTS `v_daily_revenue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_daily_revenue`  AS SELECT cast(`p`.`paid_at` as date) AS `d`, sum(`p`.`amount_net`) AS `revenue`, count(distinct `p`.`order_id`) AS `orders` FROM `payments` AS `p` WHERE `p`.`status` = 'paid' AND `p`.`paid_at` is not null GROUP BY cast(`p`.`paid_at` as date) ;

-- --------------------------------------------------------

--
-- Structure for view `v_margin`
--
DROP TABLE IF EXISTS `v_margin`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_margin`  AS SELECT sum(`oi`.`qty` * `oi`.`price`) AS `gross_sales`, sum(`oi`.`qty` * coalesce(`oi`.`cogs_unit`,0)) AS `cogs`, sum(`oi`.`qty` * `oi`.`price`) - sum(`oi`.`qty` * coalesce(`oi`.`cogs_unit`,0)) AS `gross_margin` FROM (`order_items` `oi` join `payments` `p` on(`p`.`order_id` = `oi`.`order_id` and `p`.`status` = 'paid')) ;

-- --------------------------------------------------------

--
-- Structure for view `v_top_menu_30d`
--
DROP TABLE IF EXISTS `v_top_menu_30d`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_top_menu_30d`  AS SELECT `m`.`id` AS `id`, `m`.`name` AS `name`, sum(`oi`.`qty`) AS `qty`, sum(`oi`.`qty` * `oi`.`price`) AS `total` FROM ((`order_items` `oi` join `menu` `m` on(`m`.`id` = `oi`.`menu_id`)) join `payments` `p` on(`p`.`order_id` = `oi`.`order_id` and `p`.`status` = 'paid' and `p`.`paid_at` >= curdate() - interval 30 day)) GROUP BY `m`.`id`, `m`.`name` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_entity` (`entity`,`entity_id`,`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inv_menu` (`menu_id`,`created_at`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoices_order_id` (`order_id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_menu_cat` (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_oi_order` (`order_id`),
  ADD KEY `fk_oi_menu` (`menu_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pay_order` (`order_id`),
  ADD KEY `idx_pay_status` (`status`,`paid_at`),
  ADD KEY `idx_pay_order` (`order_id`),
  ADD KEY `idx_pay_status_paidat` (`status`,`paid_at`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ref_order` (`order_id`),
  ADD KEY `idx_ref_pay` (`payment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_otp_exp` (`otp_expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `fk_inv_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_inv_order_id_cascade` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_invoices_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `fk_menu_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pay_order_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pay_order_c1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `fk_ref_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ref_pay` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
