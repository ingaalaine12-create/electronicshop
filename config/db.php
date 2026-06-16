<?php
// config/db.php
// Elegant database connection manager with automatic schema creation and seeding

/**
 * Simple environment loader to read variables from .env file
 */
function get_env_variable($key, $default = null) {
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    if (getenv($key) !== false) {
        return getenv($key);
    }
    static $env = null;
    if ($env === null) {
        $env = [];
        $env_file = __DIR__ . '/../.env';
        if (file_exists($env_file)) {
            $raw = str_replace("\r\n", "\n", file_get_contents($env_file));
            $raw = str_replace("\r", "\n", $raw);
            $lines = explode("\n", $raw);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') continue;
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $env_name = trim($parts[0]);
                    $env_val  = trim($parts[1]);
                    if (strlen($env_val) >= 2) {
                        $first = $env_val[0];
                        $last  = $env_val[strlen($env_val) - 1];
                        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                            $env_val = substr($env_val, 1, -1);
                        }
                    }
                    $env[$env_name] = $env_val;
                }
            }
        }
    }
    return isset($env[$key]) ? $env[$key] : $default;
}

$host = get_env_variable('DB_HOST', 'localhost');
$user = get_env_variable('DB_USER', 'root');
$pass = get_env_variable('DB_PASS', '');
$dbname = get_env_variable('DB_NAME', 'electronicshop');

try {
    // 1. Connect to MySQL Server (Without choosing a DB first)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // 2. Create database if it does not exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 3. Switch to the created database
    $pdo->exec("USE `$dbname`");

    // 4. Create Tables
    // Categories Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `description` TEXT,
        `image` VARCHAR(255)
    ) ENGINE=InnoDB;");

    // Products Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category_id` INT,
        `name` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL UNIQUE,
        `price` DECIMAL(12,2) NOT NULL,
        `image` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `stock` INT NOT NULL DEFAULT 10,
        `featured` TINYINT(1) DEFAULT 0,
        `specs` TEXT,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;");

    // Orders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `customer_name` VARCHAR(255) NOT NULL,
        `customer_email` VARCHAR(255) NOT NULL,
        `customer_phone` VARCHAR(50) NOT NULL,
        `delivery_province` VARCHAR(100) NOT NULL,
        `delivery_district` VARCHAR(100) NOT NULL,
        `delivery_address` TEXT NOT NULL,
        `payment_method` VARCHAR(100) NOT NULL,
        `total_amount` DECIMAL(12,2) NOT NULL,
        `status` VARCHAR(50) DEFAULT 'Pending',
        `tx_ref` VARCHAR(100) DEFAULT NULL UNIQUE,
        `transaction_id` VARCHAR(100) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    try {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `tx_ref` VARCHAR(100) DEFAULT NULL UNIQUE");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `transaction_id` VARCHAR(100) DEFAULT NULL");
    } catch (PDOException $e) {}

    // Order Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT,
        `product_id` INT,
        `quantity` INT NOT NULL,
        `price` DECIMAL(12,2) NOT NULL,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;");

    // Admins Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 5. Seed Data if tables are empty
    // Seed Admins
    $stmt = $pdo->query("SELECT COUNT(*) FROM `admins`");
    if ($stmt->fetchColumn() == 0) {
        $username = 'admin';
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO `admins` (`username`, `password_hash`) VALUES (:username, :password_hash)");
        $ins->execute([
            'username' => $username,
            'password_hash' => $passwordHash
        ]);
    }

    // Seed Categories
    $stmt = $pdo->query("SELECT COUNT(*) FROM `categories`");
    if ($stmt->fetchColumn() == 0) {
        $categories = [
            ['name' => 'Laptops & Workstations', 'slug' => 'laptops', 'description' => 'Premium high-performance laptops and corporate workstations.', 'image' => 'category_laptops.png'],
            ['name' => 'Smartphones & Tablets', 'slug' => 'smartphones', 'description' => 'Next-gen devices, smartphones, and tablets from world leaders.', 'image' => 'category_phones.png'],
            ['name' => 'Premium Audio', 'slug' => 'audio', 'description' => 'Active noise cancelling headphones, earbuds, and premium home systems.', 'image' => 'category_audio.png'],
            ['name' => 'Wearables & Health', 'slug' => 'wearables', 'description' => 'Smartwatches, fitness bands, and smart lifestyle accessories.', 'image' => 'category_wearables.png'],
            ['name' => 'Tech Services', 'slug' => 'services', 'description' => 'Expert IT assistance, hardware repairs, and custom system assemblies in Kigali.', 'image' => 'category_services.png'],
        ];

        $ins = $pdo->prepare("INSERT INTO `categories` (`name`, `slug`, `description`, `image`) VALUES (:name, :slug, :description, :image)");
        foreach ($categories as $cat) {
            $ins->execute($cat);
        }
    }

    // Seed Products
    $stmt = $pdo->query("SELECT COUNT(*) FROM `products`");
    if ($stmt->fetchColumn() == 0) {
        // Fetch Category IDs
        $cats = $pdo->query("SELECT `id`, `slug` FROM `categories`")->fetchAll(PDO::FETCH_KEY_PAIR);

        $products = [
            [
                'category_id' => $cats['laptops'],
                'name' => 'ApexBook Pro 16',
                'slug' => 'apexbook-pro-16',
                'price' => 1450000.00, // RWF (approx $1100)
                'image' => 'product_apexbook_pro.png',
                'description' => 'Unleash your ultimate creative potential. The ApexBook Pro features a breathtaking 16" Liquid Retina XDR display, powered by the ultra-efficient Zenith M3 Octa-Core Processor, 16GB of unified RAM, and a blistering-fast 512GB SSD. Crafted with a premium single-body aluminum chassis, this is the ultimate tool for developers, designers, and professionals in Rwanda.',
                'stock' => 8,
                'featured' => 1,
                'specs' => '{"Display": "16.2-inch Liquid Retina XDR (3024 x 1964)", "Processor": "Zenith M3 Octa-Core 3.8GHz", "Memory": "16GB LPDDR5", "Storage": "512GB NVMe SSD", "OS": "ZenithOS Pro", "Battery Life": "Up to 22 Hours"}'
            ],
            [
                'category_id' => $cats['laptops'],
                'name' => 'Horizon Ultrabook 14',
                'slug' => 'horizon-ultrabook-14',
                'price' => 890000.00, // RWF (approx $680)
                'image' => 'product_horizon_ultrabook.png',
                'description' => 'Ultra-thin, feather-light, and engineered for those on the move. The Horizon Ultrabook 14 fits effortlessly into any briefcase. Boasting an Intel Core i5 12th Gen processor, 8GB RAM, and 256GB SSD, it balances robust multitasking capability with an extraordinary 14-hour battery life. Perfect for students and digital entrepreneurs.',
                'stock' => 12,
                'featured' => 0,
                'specs' => '{"Display": "14-inch Full HD IPS Anti-glare", "Processor": "Intel Core i5-1240P 4.4GHz", "Memory": "8GB LPDDR4X", "Storage": "256GB PCIe SSD", "OS": "Windows 11 Home", "Weight": "1.2 kg"}'
            ],
            [
                'category_id' => $cats['smartphones'],
                'name' => 'Quantum Phone X',
                'slug' => 'quantum-phone-x',
                'price' => 1100000.00, // RWF (approx $840)
                'image' => 'product_quantum_phone.png',
                'description' => 'Welcome to the future of mobile intelligence. The Quantum Phone X redefines visual performance with a brilliant 120Hz Dynamic AMOLED display and a state-of-the-art 108MP AI-enhanced triple camera. Powered by the high-octane Bionic-9 processor, it supports ultra-fast 5G networks, wireless charging, and has IP68 dust/water resistance.',
                'stock' => 15,
                'featured' => 1,
                'specs' => '{"Screen Size": "6.7-inch Dynamic AMOLED 2X 120Hz", "Processor": "Bionic-9 Octa-core 5nm", "Rear Camera": "108MP Main + 12MP Ultra-wide + 5MP Macro", "Front Camera": "32MP Dual Pixel", "Battery": "5000 mAh with 45W Fast Charging", "Security": "Under-display Ultrasonic Fingerprint"}'
            ],
            [
                'category_id' => $cats['smartphones'],
                'name' => 'Nexus Alpha 5G',
                'slug' => 'nexus-alpha-5g',
                'price' => 620000.00, // RWF (approx $475)
                'image' => 'product_nexus_alpha.png',
                'description' => 'Unbeatable speed and high-tier specifications made accessible. The Nexus Alpha 5G is built to impress, sporting a stunning 6.5" cinematic display, smooth 90Hz refresh rate, a massive 5000mAh battery that easily stretches into a second day, and a sharp 50MP triple-camera setup to record your favorite memories.',
                'stock' => 20,
                'featured' => 0,
                'specs' => '{"Display": "6.5-inch IPS LCD 90Hz", "Processor": "Dimensity 700 5G", "RAM/Storage": "6GB RAM / 128GB (Expandable)", "Main Camera": "50MP Dual Camera", "Battery": "5000mAh with 18W Charger", "Color Options": "Midnight Black, Aura Blue"}'
            ],
            [
                'category_id' => $cats['audio'],
                'name' => 'AeroFlow ANC Wireless Headphones',
                'slug' => 'aeroflow-anc-headphones',
                'price' => 280000.00, // RWF (approx $215)
                'image' => 'product_aeroflow_headphones.png',
                'description' => 'Escape the noise and immerse yourself in pristine sound. The AeroFlow headphones feature advanced hybrid Active Noise Cancellation that filters out up to 95% of background noise. Enjoy hi-res wireless sound with deep bass, lush mids, plush memory foam earcups, and an incredible 40 hours of continuous playback with ANC enabled.',
                'stock' => 14,
                'featured' => 1,
                'specs' => '{"ANC Technology": "Hybrid Active Noise Cancelling", "Driver Size": "40mm Dynamic Drivers", "Bluetooth Version": "Bluetooth 5.2", "Battery Life": "40 Hrs (ANC On) / 60 Hrs (ANC Off)", "Charging Port": "USB Type-C Fast Charge (10m charge = 4h play)", "Audio Formats": "LDAC, AAC, SBC"}'
            ],
            [
                'category_id' => $cats['audio'],
                'name' => 'SonicWave Mini Bluetooth Speaker',
                'slug' => 'sonicwave-mini-speaker',
                'price' => 950000.00 / 10, // Let's make it 95,000 RWF (approx $72)
                'image' => 'product_sonicwave_speaker.png',
                'description' => 'Compact size, colossal sound. The SonicWave Mini is IPX7 waterproof and ruggedly engineered, making it the ultimate speaker for your outdoor hikes, beach visits, and pool parties. Delivering punchy 360-degree audio and dual passive bass radiators, it offers up to 15 hours of heavy, immersive music playback.',
                'stock' => 25,
                'featured' => 0,
                'specs' => '{"Output Power": "15 Watts RMS", "Waterproof Rating": "IPX7 certified", "Connectivity": "Bluetooth 5.0 + AUX input", "Battery": "Up to 15 hours play time", "Special Features": "TWS Stereo Pairing (connect two speakers)", "Dimensions": "90mm x 90mm x 110mm"}'
            ],
            [
                'category_id' => $cats['wearables'],
                'name' => 'Chronos Fit Smartwatch',
                'slug' => 'chronos-fit-smartwatch',
                'price' => 180000.00, // RWF (approx $138)
                'image' => 'product_chronos_smartwatch.png',
                'description' => 'Your dedicated 24/7 personal health and lifestyle assistant. Chronos Fit incorporates a premium Always-On AMOLED touchscreen. Tracks real-time heart rate, blood oxygen levels (SpO2), stress levels, sleep stages, and features over 90 sport profiles. Syncs beautifully with iOS & Android to deliver calls, texts, and alerts.',
                'stock' => 18,
                'featured' => 1,
                'specs' => '{"Display": "1.43-inch Always-on AMOLED Touchscreen", "Water Resistance": "5ATM (swim-proof)", "Sensors": "Heart Rate, SpO2, Accelerometer, Gyroscope", "Battery Life": "Up to 12 days in standard usage", "App Support": "Chronos Health App (iOS & Android)", "Material": "Aero-grade aluminum alloy"}'
            ],
            [
                'category_id' => $cats['services'],
                'name' => 'Full System Diagnostic & Dusting',
                'slug' => 'full-system-diagnostic-dusting',
                'price' => 25000.00, // RWF (approx $19)
                'image' => 'service_diagnostic_dusting.png',
                'description' => 'Is your laptop or PC running hot, throttling, or making excessive noise? Bring it to Kigali TechHub for a comprehensive hardware diagnostic, complete internal dust removal, and high-performance thermal paste replacement (using Arctic MX-6). Improve your thermal performance and double your device lifespan.',
                'stock' => 999, // Infinite service slot
                'featured' => 0,
                'specs' => '{"Service Time": "2 - 3 Hours in our Kigali workshop", "Included": "Dust extraction, fan cleaning, motherboard checking, professional thermal paste re-application", "Warranty": "30-day thermal performance guarantee"}'
            ],
            [
                'category_id' => $cats['services'],
                'name' => 'Custom PC Building & Tuning',
                'slug' => 'custom-pc-building-tuning',
                'price' => 75000.00, // RWF (approx $57)
                'image' => 'service_custom_pc_building.png',
                'description' => 'Assemble your dream workstation or high-refresh gaming desktop with professional support. Our expert hardware architects will consult on component matching, perform neat custom cable management, install the OS & essential drivers, optimize BIOS XMP profiles, and benchmark the entire system to ensure flawless stability.',
                'stock' => 999,
                'featured' => 1,
                'specs' => '{"Service Time": "1 Business Day", "Included": "Component selection support, precision physical assembly, clean routing, BIOS flash & fine-tuning, Stress tests", "Tuning Features": "XMP Profile Activation, custom fan speed mapping"}'
            ]
        ];

        $ins = $pdo->prepare("INSERT INTO `products` (`category_id`, `name`, `slug`, `price`, `image`, `description`, `stock`, `featured`, `specs`) VALUES (:category_id, :name, :slug, :price, :image, :description, :stock, :featured, :specs)");
        foreach ($products as $prod) {
            $ins->execute($prod);
        }
    }

} catch (PDOException $e) {
    // If it fails, we will output a helpful message for debugging
    die("Database Error: " . $e->getMessage());
}
?>
