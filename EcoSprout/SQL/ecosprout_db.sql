-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 16, 2026 at 10:20 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecosprout_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_inquiries`
--

DROP TABLE IF EXISTS `contact_inquiries`;
CREATE TABLE IF NOT EXISTS `contact_inquiries` (
  `inquiry_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED DEFAULT NULL,
  `full_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `inquiry_status` enum('NEW','READ','REPLIED','CLOSED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NEW',
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`inquiry_id`),
  KEY `fk_contact_inquiries_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_inquiries`
--

INSERT INTO `contact_inquiries` (`inquiry_id`, `user_id`, `full_name`, `email`, `phone`, `subject`, `message`, `inquiry_status`, `submitted_at`) VALUES
(1, NULL, 'awda', 'adaw@awda.dawd', '12123123', 'adawd', 'wdawd', 'NEW', '2026-09-16 00:38:08'),
(2, NULL, 'Kavi', 'awdawd@awd.awda', '44134', 'dawdaw', 'awdawd', 'NEW', '2026-09-16 00:46:22'),
(3, NULL, 'dawd', 'awdawd@awd.awd', 'awawd', 'dawda', 'dawdawd', 'NEW', '2026-09-16 00:47:00'),
(4, NULL, 'Kavi', 'dad@adaw.awd', '324234234', 'dawdadawdadad', 'dadawdawd', 'NEW', '2026-09-16 00:51:36'),
(5, NULL, 'Kavi', 'kavi@gmail.com', '0758839004', 'adawd', 'awdad', 'NEW', '2026-09-16 00:56:31'),
(6, NULL, 'Kavi', 'kavi@gmail.com', '0758839004', 'wdawd', 'awdawd', 'NEW', '2026-09-16 00:58:15'),
(7, NULL, 'Kavi', 'kavi@gmail.com', '0758839004', '34342', '34dawdawd', 'NEW', '2026-09-16 01:17:01'),
(8, 13, 'Oshi', 'oshi@gmail.com', '0705493847', 'about the a tool', 'Do you have this tool ?', 'NEW', '2026-09-16 08:49:31');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `event_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `venue` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EcoSprout Nursery, Kegalle',
  `capacity` smallint UNSIGNED DEFAULT NULL,
  `fee` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('UPCOMING','COMPLETED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UPCOMING',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `event_date`, `start_time`, `venue`, `capacity`, `fee`, `image_path`, `description`, `status`, `created_at`) VALUES
(1, 'Plant Care Workshop', '2026-09-20', '10:00:00', 'EcoSprout Nursery, Kegalle', NULL, 0.00, 'Images/Events/Plant_Care_Workshop.jpg', 'Learn the basics of watering, pruning and maintaining healthy indoor and outdoor plants.', 'UPCOMING', '2026-09-15 10:10:40'),
(2, 'Home Gardening Workshop', '2026-09-27', '09:30:00', 'EcoSprout Nursery, Kegalle', NULL, 0.00, 'Images/Events/home_gardeing.jpg', 'Discover simple techniques for creating and maintaining a beautiful home garden.', 'UPCOMING', '2026-09-15 10:10:40'),
(3, 'Organic Gardening Day', '2026-10-04', '10:00:00', 'EcoSprout Nursery, Kegalle', NULL, 0.00, 'Images/Events/Organic_Gardening_Day.jpg', 'Explore organic gardening methods and environmentally friendly plant care.', 'UPCOMING', '2026-09-15 10:10:40'),
(4, 'Indoor Plant Workshop', '2026-10-11', '11:00:00', 'EcoSprout Nursery, Kegalle', NULL, 0.00, 'Images/Events/Indoor_plant_Workshop.jpeg', 'Learn how to choose, place and care for indoor plants.', 'UPCOMING', '2026-09-15 10:10:40'),
(5, 'Planting for Beginners', '2026-10-18', '09:00:00', 'EcoSprout Nursery, Kegalle', NULL, 0.00, 'Images/Events/Planting_for_Beginners.jpg', 'A beginner session covering soil preparation, planting methods and basic care.', 'UPCOMING', '2026-09-15 10:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

DROP TABLE IF EXISTS `event_registrations`;
CREATE TABLE IF NOT EXISTS `event_registrations` (
  `registration_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `attendee_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_of_people` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `registration_status` enum('REGISTERED','ATTENDED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REGISTERED',
  `registered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`registration_id`),
  KEY `fk_event_registrations_event` (`event_id`),
  KEY `fk_event_registrations_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `first_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_1` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_2` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('CARD','BANK_TRANSFER','CASH_ON_DELIVERY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` enum('PENDING','PAID','FAILED','REFUNDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `order_status` enum('PENDING','PROCESSING','OUT_FOR_DELIVERY','COMPLETED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `subtotal` decimal(10,2) UNSIGNED NOT NULL,
  `delivery_fee` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) UNSIGNED NOT NULL,
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `order_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_code` (`order_code`),
  KEY `idx_orders_user_date` (`user_id`,`order_date`),
  KEY `idx_orders_status` (`order_status`,`payment_status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_code`, `user_id`, `first_name`, `last_name`, `phone`, `email`, `address_line_1`, `address_line_2`, `city`, `postal_code`, `payment_method`, `payment_status`, `order_status`, `subtotal`, `delivery_fee`, `total_amount`, `remark`, `order_date`, `updated_at`) VALUES
(1, 'ECO20260915125330115', NULL, 'Savindu', 'Ashirwadha', '+94751354132', 'savi@gmail.com', 'Ganemulla', '', 'GAne', '50230', 'CASH_ON_DELIVERY', 'PENDING', 'PENDING', 1250.00, 0.00, 1250.00, '', '2026-09-15 12:53:30', '2026-09-15 12:53:30'),
(2, 'ECO20260915130433299', NULL, 'Savindu', 'Ashirwadha', '+94751354132', 'savi@gmail.com', 'Ganemulla', '', 'GAne', '50230', 'CASH_ON_DELIVERY', 'PENDING', 'PENDING', 1250.00, 0.00, 1250.00, '', '2026-09-15 13:04:33', '2026-09-15 13:04:33'),
(3, 'ECO20260916044843539', NULL, 'Kavi', 'perera', '0758839004', 'kavi@gmail.com', 'mailangamuwa', '', 'ganemulla', '235235', 'CASH_ON_DELIVERY', 'PENDING', 'PENDING', 1950.00, 0.00, 1950.00, 'dadadad', '2026-09-16 04:48:43', '2026-09-16 04:48:43'),
(4, 'ECO20260916073200405', 11, 'Ravi', 'perera', '0710846374', 'ravi@gmail.com', 'wallawatta', 'main road', 'Colombo', '124535', 'CASH_ON_DELIVERY', 'PENDING', 'PENDING', 750.00, 0.00, 750.00, '', '2026-09-16 07:32:00', '2026-09-16 07:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int UNSIGNED NOT NULL,
  `plant_id` int UNSIGNED DEFAULT NULL,
  `tool_id` int UNSIGNED DEFAULT NULL,
  `item_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int UNSIGNED NOT NULL,
  `unit_price` decimal(10,2) UNSIGNED NOT NULL,
  `line_total` decimal(10,2) UNSIGNED NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_plant` (`plant_id`),
  KEY `fk_order_items_tool` (`tool_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `plant_id`, `tool_id`, `item_name`, `quantity`, `unit_price`, `line_total`) VALUES
(1, 1, NULL, 1, 'Hand Trowel', 1, 1250.00, 1250.00),
(2, 2, NULL, 1, 'Hand Trowel', 1, 1250.00, 1250.00),
(3, 3, 3, NULL, 'hathawariya', 1, 100.00, 100.00),
(4, 3, NULL, 6, 'Watering Can', 1, 1850.00, 1850.00),
(5, 4, 8, NULL, 'Mint Plant', 1, 750.00, 750.00);

-- --------------------------------------------------------

--
-- Table structure for table `plants`
--

DROP TABLE IF EXISTS `plants`;
CREATE TABLE IF NOT EXISTS `plants` (
  `plant_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `plant_category_id` tinyint UNSIGNED NOT NULL,
  `plant_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT '0',
  `plant_size` enum('Small','Medium','Large') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `light_requirement` enum('Low Light','Indirect Light','Full Sunlight','Partial Shade') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pot_size` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pot_color` enum('Black','Clay','Gray','Green','White') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pet_friendly` tinyint(1) NOT NULL DEFAULT '0',
  `difficulty` enum('Easy Care','Medium Care','Advanced Care') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `air_cleaner` tinyint(1) NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`plant_id`),
  KEY `idx_plants_category_status` (`plant_category_id`,`status`),
  KEY `idx_plants_name` (`plant_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plants`
--

INSERT INTO `plants` (`plant_id`, `plant_category_id`, `plant_name`, `price`, `quantity`, `plant_size`, `light_requirement`, `pot_size`, `pot_color`, `pet_friendly`, `difficulty`, `air_cleaner`, `description`, `status`, `created_at`, `updated_at`) VALUES
(3, 4, 'hathawariya', 100.00, 9, 'Small', 'Low Light', '40', 'Green', 1, 'Easy Care', 1, 'agata hodai', 'ACTIVE', '2026-09-16 04:43:32', '2026-09-16 04:48:43'),
(4, 1, 'Snake Plant', 2500.00, 20, 'Small', 'Indirect Light', '40', 'Black', 1, 'Easy Care', 1, 'snake shape', 'ACTIVE', '2026-09-16 06:39:23', '2026-09-16 06:39:23'),
(5, 1, 'Peace Lily', 3200.00, 15, 'Medium', 'Full Sunlight', '70', 'Black', 1, 'Easy Care', 0, 'Peace lily', 'ACTIVE', '2026-09-16 06:41:08', '2026-09-16 06:41:08'),
(6, 2, 'Bougainvillea', 2800.00, 30, 'Medium', 'Full Sunlight', '60', 'Gray', 1, 'Medium Care', 1, 'redish', 'ACTIVE', '2026-09-16 06:43:04', '2026-09-16 06:43:04'),
(7, 2, 'Croton', 1800.00, 50, 'Medium', 'Full Sunlight', '100', 'White', 0, 'Easy Care', 1, 'colorful', 'ACTIVE', '2026-09-16 06:44:45', '2026-09-16 06:44:45'),
(8, 4, 'Mint Plant', 750.00, 29, 'Small', 'Low Light', '30', 'Gray', 1, 'Advanced Care', 1, 'taste good', 'ACTIVE', '2026-09-16 06:46:49', '2026-09-16 07:32:00'),
(9, 4, 'Alo vera', 1500.00, 20, 'Small', 'Indirect Light', '100', 'Green', 1, 'Easy Care', 1, 'healthy', 'ACTIVE', '2026-09-16 07:42:23', '2026-09-16 07:42:23');

-- --------------------------------------------------------

--
-- Table structure for table `plant_categories`
--

DROP TABLE IF EXISTS `plant_categories`;
CREATE TABLE IF NOT EXISTS `plant_categories` (
  `plant_category_id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`plant_category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plant_categories`
--

INSERT INTO `plant_categories` (`plant_category_id`, `category_name`) VALUES
(4, 'Edible'),
(1, 'Indoor'),
(3, 'Ornamental'),
(2, 'Outdoor');

-- --------------------------------------------------------

--
-- Table structure for table `plant_images`
--

DROP TABLE IF EXISTS `plant_images`;
CREATE TABLE IF NOT EXISTS `plant_images` (
  `plant_image_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `plant_id` int UNSIGNED NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` tinyint UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`plant_image_id`),
  KEY `fk_plant_images_plant` (`plant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plant_images`
--

INSERT INTO `plant_images` (`plant_image_id`, `plant_id`, `image_path`, `is_main`, `sort_order`) VALUES
(3, 3, 'Images/Plants/uploads/plant_25ee545cece25458b87f.jpg', 1, 1),
(4, 4, 'Images/Plants/uploads/plant_78178ccb575d317b0389.webp', 1, 1),
(5, 5, 'Images/Plants/uploads/plant_217eee608a3c1eb851d8.jpg', 1, 1),
(6, 6, 'Images/Plants/uploads/plant_45c5b0c99696da51d7e7.webp', 1, 1),
(7, 7, 'Images/Plants/uploads/plant_dbe3f4f2c506245260fb.webp', 1, 1),
(8, 8, 'Images/Plants/uploads/plant_1dc654e78f45c497cccb.jpg', 1, 1),
(9, 9, 'Images/Plants/uploads/plant_d2b31ecc86c6c954cc57.webp', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `service_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_category_id` tinyint UNSIGNED NOT NULL,
  `service_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `starting_price` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `service_duration` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_area` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`),
  KEY `fk_services_category` (`service_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_category_id`, `service_name`, `starting_price`, `service_duration`, `service_area`, `image_path`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'Garden Design', 10000.00, 'Depends on project', 'Kegalle', 'Images/Services/garden-designing.png', 'Professional planning and design for beautiful outdoor spaces.', 'ACTIVE', '2026-09-15 10:10:40', '2026-09-15 10:10:40'),
(2, 3, 'Garden Maintenance', 5000.00, '1 day', 'Kegalle', 'Images/Services/garden-maintainence.webp', 'Regular care and maintenance to keep your garden healthy.', 'ACTIVE', '2026-09-15 10:10:40', '2026-09-15 10:10:40'),
(3, 1, 'Landscaping', 15000.00, 'Depends on project', 'Kegalle', 'Images/Services/landscaping.avif', 'Complete landscaping solutions for homes and commercial spaces.', 'ACTIVE', '2026-09-15 10:10:40', '2026-09-15 10:10:40'),
(4, 7, 'Plant Consultation', 3500.00, '1 hour', 'Kegalle', 'Images/Services/plant-cultivation.webp', 'Expert advice for choosing, placing and caring for your plants.', 'ACTIVE', '2026-09-15 10:10:40', '2026-09-15 10:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

DROP TABLE IF EXISTS `service_categories`;
CREATE TABLE IF NOT EXISTS `service_categories` (
  `service_category_id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`service_category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`service_category_id`, `category_name`) VALUES
(7, 'Consultation'),
(2, 'Garden Design'),
(3, 'Garden Maintenance'),
(1, 'Landscaping'),
(5, 'Lawn Care'),
(4, 'Plant Care'),
(6, 'Tree Service');

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

DROP TABLE IF EXISTS `service_requests`;
CREATE TABLE IF NOT EXISTS `service_requests` (
  `service_request_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `customer_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `customer_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `request_status` enum('PENDING','CONFIRMED','IN_PROGRESS','COMPLETED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_request_id`),
  KEY `fk_service_requests_service` (`service_id`),
  KEY `fk_service_requests_user` (`user_id`),
  KEY `idx_service_requests_status` (`request_status`,`requested_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`service_request_id`, `service_id`, `user_id`, `customer_name`, `address`, `email`, `phone`, `preferred_date`, `customer_message`, `request_status`, `requested_at`) VALUES
(1, 1, NULL, 'Savindu Ashirwadha', 'Ganemulla, , GAne, 50230', 'savi@gmail.com', '+94751354132', NULL, '', 'PENDING', '2026-09-15 13:24:18'),
(2, 1, NULL, 'Savindu Ashirwadha', 'Ganemulla, , GAne, 50230', 'savi@gmail.com', '+94751354132', NULL, 'dgdgdfg', 'PENDING', '2026-09-15 13:24:28'),
(3, 2, 11, 'Ravi', 'wallawatta', 'ravi@gmail.com', '0710846374', '2026-09-16', 'I need a maintenance for my garden.', 'PENDING', '2026-09-16 07:35:47');

-- --------------------------------------------------------

--
-- Table structure for table `tools`
--

DROP TABLE IF EXISTS `tools`;
CREATE TABLE IF NOT EXISTS `tools` (
  `tool_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_category_id` tinyint UNSIGNED NOT NULL,
  `tool_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) UNSIGNED NOT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT '0',
  `purpose` enum('Digging','Cutting','Watering','Planting','Cleaning','Maintenance') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `material` enum('Stainless Steel','Carbon Steel','Plastic','Wood','Aluminium','Other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tool_id`),
  KEY `idx_tools_category_status` (`tool_category_id`,`status`),
  KEY `idx_tools_name` (`tool_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tools`
--

INSERT INTO `tools` (`tool_id`, `tool_category_id`, `tool_name`, `brand`, `price`, `quantity`, `purpose`, `material`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Hand Trowel', 'GreenPro', 1250.00, 18, 'Digging', 'Stainless Steel', 'A compact hand tool for digging, planting and repotting.', 'ACTIVE', '2026-09-15 10:10:40', '2026-09-16 06:49:55'),
(2, 2, 'Pruning Shears', 'GardenMax', 2350.00, 8, 'Cutting', 'Carbon Steel', 'Sharp pruning shears for trimming plants and small branches.', 'ACTIVE', '2026-09-15 10:10:40', '2026-09-15 10:10:40'),
(3, 3, 'Watering Can', 'EcoWater', 1850.00, 12, 'Watering', 'Plastic', 'A lightweight watering can for indoor and outdoor plants.', 'ACTIVE', '2026-09-15 10:10:40', '2026-09-16 06:49:49'),
(4, 1, 'Hand Trowel', 'GreenPro', 1250.00, 20, 'Digging', 'Stainless Steel', 'A compact tool for digging, planting and repotting.', 'ACTIVE', '2026-09-15 12:46:29', '2026-09-16 06:49:52'),
(5, 2, 'Pruning Shears', 'GardenMax', 2350.00, 8, 'Cutting', 'Carbon Steel', 'Sharp pruning shears for trimming plants and branches.', 'ACTIVE', '2026-09-15 12:46:29', '2026-09-16 06:49:53'),
(6, 3, 'Watering Can', 'EcoWater', 1850.00, 11, 'Watering', 'Plastic', 'A lightweight watering can for indoor and outdoor plants.', 'ACTIVE', '2026-09-15 12:46:29', '2026-09-16 06:49:56');

-- --------------------------------------------------------

--
-- Table structure for table `tool_categories`
--

DROP TABLE IF EXISTS `tool_categories`;
CREATE TABLE IF NOT EXISTS `tool_categories` (
  `tool_category_id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`tool_category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tool_categories`
--

INSERT INTO `tool_categories` (`tool_category_id`, `category_name`) VALUES
(2, 'Cutting Tools'),
(1, 'Hand Tools'),
(4, 'Planting Tools'),
(5, 'Power Tools'),
(6, 'Safety Equipment'),
(3, 'Watering Tools');

-- --------------------------------------------------------

--
-- Table structure for table `tool_images`
--

DROP TABLE IF EXISTS `tool_images`;
CREATE TABLE IF NOT EXISTS `tool_images` (
  `tool_image_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id` int UNSIGNED NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` tinyint UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`tool_image_id`),
  KEY `fk_tool_images_tool` (`tool_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tool_images`
--

INSERT INTO `tool_images` (`tool_image_id`, `tool_id`, `image_path`, `is_main`, `sort_order`) VALUES
(7, 6, 'Images/Tools/uploads/tool_4ad726c1ac18b6dcc746.webp', 1, 1),
(8, 1, 'Images/Tools/uploads/tool_822aba6d559d3b3799e7.webp', 1, 1),
(9, 5, 'Images/Tools/uploads/tool_7013a4e434bed3faa419.jpg', 1, 1),
(10, 4, 'Images/Tools/uploads/tool_fe6666811a84ff9013e3.jpg', 1, 1),
(11, 3, 'Images/Tools/uploads/tool_3dce840ef239e3ef72fc.webp', 1, 1),
(12, 2, 'Images/Tools/uploads/tool_3a33272fcf1862d550a6.webp', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alternative_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('CUSTOMER','STAFF','ADMIN') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CUSTOMER',
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `employee_code` (`employee_code`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `employee_code`, `first_name`, `last_name`, `email`, `phone`, `alternative_phone`, `address_line_1`, `address_line_2`, `city`, `postal_code`, `password_hash`, `role`, `status`, `created_at`, `updated_at`) VALUES
(5, 'ADM001', 'Ravindu', 'Bandara', 'admin@ecosprout.lk', '0777890123', '0711234567', '10 Green Avenue', NULL, 'Kegalle', '71000', '12345678', 'ADMIN', 'ACTIVE', '2026-09-16 06:20:11', '2026-09-16 06:20:11'),
(8, 'ADM002', 'Dinithi', '', 'galagedara@gmail.com', '414213424', NULL, 'kollupitiya', NULL, NULL, NULL, '$2y$10$K7CyH08yc18vR.WYapD6KubpzFyecZJpIw0tMV3HCxi1Jam8Wxhgi', 'ADMIN', 'ACTIVE', '2026-09-16 04:15:13', '2026-09-16 08:36:55'),
(9, 'EMP002', 'Kavi', 'perera', 'kavi@gmail.com', '0713436743', NULL, '135', 'ganemulla', 'gampaha', '3423532', '$2y$10$CcRAw6y4K3U.zxbeoZ2qo.4gM0VsMVo/If7kZCNxLMMTcGn3WrN.i', 'STAFF', 'ACTIVE', '2026-09-16 06:29:37', '2026-09-16 06:29:37'),
(10, 'ADM003', 'Jani', 'sada', 'jani@gmail.com', '0764235674', NULL, '154', 'batuwatta', 'Gampaha', '342345', '$2y$10$TGceziGuXRhlntfhUWhBeOLXys/qIPHhpLrrPcABNg8GhJ4U4Bzc.', 'ADMIN', 'ACTIVE', '2026-09-16 06:32:00', '2026-09-16 06:33:42'),
(11, NULL, 'Ravi', '', 'ravi@gmail.com', '0710846374', NULL, 'wallawatta', NULL, NULL, NULL, '$2y$10$qV4d6MBILtw6kXw4tIhB6OZG23pOQqg3FToEx42QNaPuOvrT/3bmq', 'CUSTOMER', 'ACTIVE', '2026-09-16 07:16:22', '2026-09-16 07:16:22'),
(12, 'EMP003', 'Yash', 'raihana', 'yash@gmail.com', '07872637453', NULL, '54', 'Gampaha', 'kiridiwela', '4231351', '$2y$10$QLe0J1eWGN3o2M0dkA8ShOvyb0sVepFEYi2H9jde/I9dmMRJW/ZyG', 'STAFF', 'ACTIVE', '2026-09-16 08:34:55', '2026-09-16 08:34:55'),
(13, NULL, 'Oshi', '', 'oshi@gmail.com', '0705493847', NULL, 'Ragama', NULL, NULL, NULL, '$2y$10$UW0qFN7ztotsj0hC3HeHQeAAWyPCkx4GYkuhCnWW9zXSFQKQiXdOq', 'CUSTOMER', 'ACTIVE', '2026-09-16 08:47:21', '2026-09-16 08:47:21');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  ADD CONSTRAINT `fk_contact_inquiries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `fk_event_registrations_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`),
  ADD CONSTRAINT `fk_event_registrations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_plant` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`),
  ADD CONSTRAINT `fk_order_items_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`tool_id`);

--
-- Constraints for table `plants`
--
ALTER TABLE `plants`
  ADD CONSTRAINT `fk_plants_category` FOREIGN KEY (`plant_category_id`) REFERENCES `plant_categories` (`plant_category_id`);

--
-- Constraints for table `plant_images`
--
ALTER TABLE `plant_images`
  ADD CONSTRAINT `fk_plant_images_plant` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_category` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`service_category_id`);

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `fk_service_requests_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`),
  ADD CONSTRAINT `fk_service_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `tools`
--
ALTER TABLE `tools`
  ADD CONSTRAINT `fk_tools_category` FOREIGN KEY (`tool_category_id`) REFERENCES `tool_categories` (`tool_category_id`);

--
-- Constraints for table `tool_images`
--
ALTER TABLE `tool_images`
  ADD CONSTRAINT `fk_tool_images_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`tool_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
