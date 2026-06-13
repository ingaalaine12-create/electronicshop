-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2026 at 08:42 AM
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
-- Database: `electronicshop`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$cG2yvulH1wRb3nKbBMVitO/6fKP7/Wx1Tn47NXnLCnwbfKB4sGpP6', '2026-06-05 13:05:44');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`) VALUES
(1, 'Laptops & Workstations', 'laptops', 'Premium high-performance laptops and corporate workstations.', 'category_laptops.png'),
(2, 'Smartphones & Tablets', 'smartphones', 'Next-gen devices, smartphones, and tablets from world leaders.', 'category_phones.png'),
(3, 'Premium Audio', 'audio', 'Active noise cancelling headphones, earbuds, and premium home systems.', 'category_audio.png'),
(4, 'Wearables & Health', 'wearables', 'Smartwatches, fitness bands, and smart lifestyle accessories.', 'category_wearables.png'),
(5, 'Tech Services', 'services', 'Expert IT assistance, hardware repairs, and custom system assemblies in Kigali.', 'category_services.png');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `delivery_province` varchar(100) NOT NULL,
  `delivery_district` varchar(100) NOT NULL,
  `delivery_address` text NOT NULL,
  `payment_method` varchar(100) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tx_ref` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_name`, `customer_email`, `customer_phone`, `delivery_province`, `delivery_district`, `delivery_address`, `payment_method`, `total_amount`, `status`, `created_at`, `tx_ref`, `transaction_id`) VALUES
(1, 'ishimwe pacifique', 'ishimwepacifique0@gmail.com', '0787334843', 'Eastern Province', 'Gasabo', 'kigali', 'Card Payment', 283000.00, 'Pending', '2026-06-08 19:01:26', NULL, NULL),
(2, 'ishimwe pacifique', 'ishimwepacifique0@gmail.com', '0787334843', 'Eastern Province', 'Gasabo', '4th floor, La Bonne Adresse, KN 2 Roundabout, Kigali', 'MTN Mobile Money', 78000.00, 'Pending', '2026-06-08 19:02:35', NULL, NULL),
(3, 'ishimwe pacifique', 'ishimwepacifique0@gmail.com', '0787334843', 'Eastern Province', 'Musanze', '4th floor, La Bonne Adresse, KN 2 Roundabout, Kigali', 'Card Payment', 98000.00, 'Pending', '2026-06-08 19:08:17', NULL, NULL),
(4, 'ishimwe pacifique', 'ishimwepacifique0@gmail.com', '0787334843', 'Eastern Province', 'Nyarugenge', '4th floor, La Bonne Adresse, KN 2 Roundabout, Kigali', 'MTN Mobile Money', 28000.00, 'Pending', '2026-06-08 19:24:03', NULL, NULL),
(5, 'ishimwe pacifique', 'ishimwepacifique0@gmail.com', '0787334843', 'Eastern Province', 'Gasabo', '4th floor, La Bonne Adresse, KN 2 Roundabout, Kigali', 'Card Payment', 28000.00, 'Pending', '2026-06-08 22:21:59', NULL, NULL),
(6, 'Empiremenswear Trainer', 'ishimwepacifique0@gmail.com', '0788785765', 'Eastern Province', 'Musanze', '4th floor, La Bonne Adresse, KN 2 Roundabout, Kigali', 'Card Payment', 28000.00, 'Pending', '2026-06-08 22:28:18', NULL, NULL),
(7, 'Empiremenswear Trainer', 'ishimwepacifique0@gmail.com', '0788785765', 'Eastern Province', 'Rwamagana', '4th floor, La Bonne Adresse, KN 2 Roundabout, Kigali', 'MTN Mobile Money', 28000.00, 'Pending', '2026-06-08 22:42:12', 'kth-order-1780958532-9921', '10284817');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 5, 1, 280000.00),
(2, 2, 9, 1, 75000.00),
(3, 3, 6, 1, 95000.00),
(4, 4, 8, 1, 25000.00),
(5, 5, 8, 1, 25000.00),
(6, 6, 8, 1, 25000.00),
(7, 7, 8, 1, 25000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 10,
  `featured` tinyint(1) DEFAULT 0,
  `specs` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `price`, `image`, `description`, `stock`, `featured`, `specs`) VALUES
(1, NULL, 'ApexBook Pro 16', 'apexbook-pro-16', 1450000.00, 'product_apexbook_pro.png', 'Unleash your ultimate creative potential. The ApexBook Pro features a breathtaking 16\" Liquid Retina XDR display, powered by the ultra-efficient Zenith M3 Octa-Core Processor, 16GB of unified RAM, and a blistering-fast 512GB SSD. Crafted with a premium single-body aluminum chassis, this is the ultimate tool for developers, designers, and professionals in Rwanda.', 8, 1, '{\"Display\": \"16.2-inch Liquid Retina XDR (3024 x 1964)\", \"Processor\": \"Zenith M3 Octa-Core 3.8GHz\", \"Memory\": \"16GB LPDDR5\", \"Storage\": \"512GB NVMe SSD\", \"OS\": \"ZenithOS Pro\", \"Battery Life\": \"Up to 22 Hours\"}'),
(2, NULL, 'Horizon Ultrabook 14', 'horizon-ultrabook-14', 890000.00, 'product_horizon_ultrabook.png', 'Ultra-thin, feather-light, and engineered for those on the move. The Horizon Ultrabook 14 fits effortlessly into any briefcase. Boasting an Intel Core i5 12th Gen processor, 8GB RAM, and 256GB SSD, it balances robust multitasking capability with an extraordinary 14-hour battery life. Perfect for students and digital entrepreneurs.', 12, 0, '{\"Display\": \"14-inch Full HD IPS Anti-glare\", \"Processor\": \"Intel Core i5-1240P 4.4GHz\", \"Memory\": \"8GB LPDDR4X\", \"Storage\": \"256GB PCIe SSD\", \"OS\": \"Windows 11 Home\", \"Weight\": \"1.2 kg\"}'),
(3, NULL, 'Quantum Phone X', 'quantum-phone-x', 1100000.00, 'product_quantum_phone.png', 'Welcome to the future of mobile intelligence. The Quantum Phone X redefines visual performance with a brilliant 120Hz Dynamic AMOLED display and a state-of-the-art 108MP AI-enhanced triple camera. Powered by the high-octane Bionic-9 processor, it supports ultra-fast 5G networks, wireless charging, and has IP68 dust/water resistance.', 15, 1, '{\"Screen Size\": \"6.7-inch Dynamic AMOLED 2X 120Hz\", \"Processor\": \"Bionic-9 Octa-core 5nm\", \"Rear Camera\": \"108MP Main + 12MP Ultra-wide + 5MP Macro\", \"Front Camera\": \"32MP Dual Pixel\", \"Battery\": \"5000 mAh with 45W Fast Charging\", \"Security\": \"Under-display Ultrasonic Fingerprint\"}'),
(4, NULL, 'Nexus Alpha 5G', 'nexus-alpha-5g', 620000.00, 'product_nexus_alpha.png', 'Unbeatable speed and high-tier specifications made accessible. The Nexus Alpha 5G is built to impress, sporting a stunning 6.5\" cinematic display, smooth 90Hz refresh rate, a massive 5000mAh battery that easily stretches into a second day, and a sharp 50MP triple-camera setup to record your favorite memories.', 20, 0, '{\"Display\": \"6.5-inch IPS LCD 90Hz\", \"Processor\": \"Dimensity 700 5G\", \"RAM/Storage\": \"6GB RAM / 128GB (Expandable)\", \"Main Camera\": \"50MP Dual Camera\", \"Battery\": \"5000mAh with 18W Charger\", \"Color Options\": \"Midnight Black, Aura Blue\"}'),
(5, NULL, 'AeroFlow ANC Wireless Headphones', 'aeroflow-anc-headphones', 280000.00, 'product_aeroflow_headphones.png', 'Escape the noise and immerse yourself in pristine sound. The AeroFlow headphones feature advanced hybrid Active Noise Cancellation that filters out up to 95% of background noise. Enjoy hi-res wireless sound with deep bass, lush mids, plush memory foam earcups, and an incredible 40 hours of continuous playback with ANC enabled.', 13, 1, '{\"ANC Technology\": \"Hybrid Active Noise Cancelling\", \"Driver Size\": \"40mm Dynamic Drivers\", \"Bluetooth Version\": \"Bluetooth 5.2\", \"Battery Life\": \"40 Hrs (ANC On) / 60 Hrs (ANC Off)\", \"Charging Port\": \"USB Type-C Fast Charge (10m charge = 4h play)\", \"Audio Formats\": \"LDAC, AAC, SBC\"}'),
(6, NULL, 'SonicWave Mini Bluetooth Speaker', 'sonicwave-mini-speaker', 95000.00, 'product_sonicwave_speaker.png', 'Compact size, colossal sound. The SonicWave Mini is IPX7 waterproof and ruggedly engineered, making it the ultimate speaker for your outdoor hikes, beach visits, and pool parties. Delivering punchy 360-degree audio and dual passive bass radiators, it offers up to 15 hours of heavy, immersive music playback.', 24, 0, '{\"Output Power\": \"15 Watts RMS\", \"Waterproof Rating\": \"IPX7 certified\", \"Connectivity\": \"Bluetooth 5.0 + AUX input\", \"Battery\": \"Up to 15 hours play time\", \"Special Features\": \"TWS Stereo Pairing (connect two speakers)\", \"Dimensions\": \"90mm x 90mm x 110mm\"}'),
(7, NULL, 'Chronos Fit Smartwatch', 'chronos-fit-smartwatch', 180000.00, 'product_chronos_smartwatch.png', 'Your dedicated 24/7 personal health and lifestyle assistant. Chronos Fit incorporates a premium Always-On AMOLED touchscreen. Tracks real-time heart rate, blood oxygen levels (SpO2), stress levels, sleep stages, and features over 90 sport profiles. Syncs beautifully with iOS & Android to deliver calls, texts, and alerts.', 18, 1, '{\"Display\": \"1.43-inch Always-on AMOLED Touchscreen\", \"Water Resistance\": \"5ATM (swim-proof)\", \"Sensors\": \"Heart Rate, SpO2, Accelerometer, Gyroscope\", \"Battery Life\": \"Up to 12 days in standard usage\", \"App Support\": \"Chronos Health App (iOS & Android)\", \"Material\": \"Aero-grade aluminum alloy\"}'),
(8, NULL, 'Full System Diagnostic & Dusting', 'full-system-diagnostic-dusting', 25000.00, 'service_diagnostic_dusting.png', 'Is your laptop or PC running hot, throttling, or making excessive noise? Bring it to Kigali TechHub for a comprehensive hardware diagnostic, complete internal dust removal, and high-performance thermal paste replacement (using Arctic MX-6). Improve your thermal performance and double your device lifespan.', 999, 0, '{\"Service Time\": \"2 - 3 Hours in our Kigali workshop\", \"Included\": \"Dust extraction, fan cleaning, motherboard checking, professional thermal paste re-application\", \"Warranty\": \"30-day thermal performance guarantee\"}'),
(9, NULL, 'Custom PC Building & Tuning', 'custom-pc-building-tuning', 75000.00, 'service_custom_pc_building.png', 'Assemble your dream workstation or high-refresh gaming desktop with professional support. Our expert hardware architects will consult on component matching, perform neat custom cable management, install the OS & essential drivers, optimize BIOS XMP profiles, and benchmark the entire system to ensure flawless stability.', 999, 1, '{\"Service Time\": \"1 Business Day\", \"Included\": \"Component selection support, precision physical assembly, clean routing, BIOS flash & fine-tuning, Stress tests\", \"Tuning Features\": \"XMP Profile Activation, custom fan speed mapping\"}');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tx_ref` (`tx_ref`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
