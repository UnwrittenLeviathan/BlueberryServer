-- phpMyAdmin SQL Dump
-- version 5.0.4deb2+deb11u2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 03, 2025 at 07:48 AM
-- Server version: 10.5.28-MariaDB-0+deb11u2
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `esp32-data`
--

-- --------------------------------------------------------

--
-- Table structure for table `food`
--

CREATE TABLE `food` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `total_servings` decimal(10,2) DEFAULT NULL,
  `serving_size` decimal(10,2) DEFAULT NULL,
  `serving_unit` varchar(255) DEFAULT NULL,
  `Calories` decimal(10,2) DEFAULT NULL,
  `Total_Fat` decimal(10,2) DEFAULT NULL,
  `Saturated_Fat` decimal(10,2) DEFAULT NULL,
  `Trans_Fat` decimal(10,2) DEFAULT NULL,
  `Cholesterol` decimal(10,2) DEFAULT NULL,
  `Sodium` decimal(10,2) DEFAULT NULL,
  `Total_Carbohydrate` decimal(10,2) DEFAULT NULL,
  `Dietary_Fiber` decimal(10,2) DEFAULT NULL,
  `Sugars` decimal(10,2) DEFAULT NULL,
  `Added_Sugars` decimal(10,2) DEFAULT NULL,
  `Protein` decimal(10,2) DEFAULT NULL,
  `Vitamin_D` decimal(10,2) DEFAULT NULL,
  `Calcium` decimal(10,2) DEFAULT NULL,
  `Iron` decimal(10,2) DEFAULT NULL,
  `Potassium` decimal(10,2) DEFAULT NULL,
  `Vitamin_A` decimal(10,2) DEFAULT NULL,
  `Vitamin_C` decimal(10,2) DEFAULT NULL,
  `Vitamin_E` decimal(10,2) DEFAULT NULL,
  `Vitamin_K` decimal(10,2) DEFAULT NULL,
  `Thiamin` decimal(10,2) DEFAULT NULL,
  `Riboflavin` decimal(10,2) DEFAULT NULL,
  `Niacin` decimal(10,2) DEFAULT NULL,
  `Vitamin_B6` decimal(10,2) DEFAULT NULL,
  `Folate_Folic_Acid` decimal(10,2) DEFAULT NULL,
  `Vitamin_B12` decimal(10,2) DEFAULT NULL,
  `Biotin` decimal(10,2) DEFAULT NULL,
  `Phosphorus` decimal(10,2) DEFAULT NULL,
  `Iodine` decimal(10,2) DEFAULT NULL,
  `Magnesium` decimal(10,2) DEFAULT NULL,
  `Zinc` decimal(10,2) DEFAULT NULL,
  `Copper` decimal(10,2) DEFAULT NULL,
  `Manganese` decimal(10,2) DEFAULT NULL,
  `Chloride` decimal(10,2) DEFAULT NULL,
  `Chromium` decimal(10,2) DEFAULT NULL,
  `Molybdenum` decimal(10,2) DEFAULT NULL,
  `Choline` decimal(10,2) DEFAULT NULL,
  `Pantothenic_Acid` decimal(10,2) DEFAULT NULL,
  `Selenium` decimal(10,2) DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `html_link` varchar(255) DEFAULT NULL,
  `edited_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `food_recipe`
--

CREATE TABLE `food_recipe` (
  `id` int(11) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `fridge_id` int(11) DEFAULT -1,
  `amount` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fridge`
--

CREATE TABLE `fridge` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instruction`
--

CREATE TABLE `instruction` (
  `id` int(11) NOT NULL,
  `step` int(11) DEFAULT NULL,
  `instruction` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instruction_recipe`
--

CREATE TABLE `instruction_recipe` (
  `id` int(11) NOT NULL,
  `recipe_id` int(11) DEFAULT NULL,
  `instruction_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recipe`
--

CREATE TABLE `recipe` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `servings` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temperature`
--

CREATE TABLE `temperature` (
  `id` int(64) NOT NULL,
  `device_name` varchar(32) NOT NULL,
  `temperature` varchar(32) NOT NULL,
  `units` varchar(32) NOT NULL DEFAULT 'F',
  `timestamp` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `food`
--
ALTER TABLE `food`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_recipe`
--
ALTER TABLE `food_recipe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `fridge_id` (`fridge_id`);

--
-- Indexes for table `fridge`
--
ALTER TABLE `fridge`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instruction`
--
ALTER TABLE `instruction`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instruction_recipe`
--
ALTER TABLE `instruction_recipe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`),
  ADD KEY `instruction_id` (`instruction_id`);

--
-- Indexes for table `recipe`
--
ALTER TABLE `recipe`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `temperature`
--
ALTER TABLE `temperature`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `timestamp` (`timestamp`),
  ADD KEY `id` (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `food`
--
ALTER TABLE `food`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temperature`
--
ALTER TABLE `temperature`
  MODIFY `id` int(64) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `food_recipe`
--
ALTER TABLE `food_recipe`
  ADD CONSTRAINT `food_recipe_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`id`),
  ADD CONSTRAINT `food_recipe_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `food` (`id`),
  ADD CONSTRAINT `food_recipe_ibfk_3` FOREIGN KEY (`fridge_id`) REFERENCES `fridge` (`id`);

--
-- Constraints for table `instruction_recipe`
--
ALTER TABLE `instruction_recipe`
  ADD CONSTRAINT `instruction_recipe_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`id`),
  ADD CONSTRAINT `instruction_recipe_ibfk_2` FOREIGN KEY (`instruction_id`) REFERENCES `instruction` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
