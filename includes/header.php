<?php
// includes/header.php
// Modular, premium header featuring dynamic navigation, brand identity, and cart badges

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/utils.php';

// Calculate active cart count
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Current page check helper
$currentPage = basename($_SERVER['PHP_SELF']);
$isAdminPage = (strpos($currentPage, 'admin') === 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kigali TechHub | Premium Electronics Shop</title>
    
    <!-- Meta SEO Tags -->
    <meta name="description" content="Kigali's premier high-end destination for next-gen electronics, laptops, smartphones, active headphones, smartwatches, and custom IT services in Rwanda.">
    <meta name="keywords" content="electronics, laptops, smartphones, momo pay, kigali, rwanda, tech shop, buy online">
    <meta name="author" content="Kigali TechHub">
 
    <!-- CSS & Typography -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    
    <!-- FontAwesome for standard UI icons if needed -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="<?php echo ($isAdminPage) ? 'admin-body' : ''; ?>">
 
    <!-- Header / Navigation Bar -->
    <header class="header">
        <div class="container nav-container">
            <!-- Brand Logo -->
            <?php if ($isAdminPage): ?>
                <a href="index.php" class="logo" id="main-logo">
                    <i class="fa-solid fa-gauge-high"></i> Kigali<span>TechHub Admin</span>
                </a>
            <?php else: ?>
                <a href="index.php" class="logo" id="main-logo">
                    <i class="fa-solid stroke-amber-500 fa-cubes-split"></i> Kigali<span>TechHub</span>
                </a>
            <?php endif; ?>
 
            <!-- Mobile Hamburger Menu Button -->
            <button class="menu-toggle" id="menu-hamburger" aria-label="Toggle navigation">☰</button>
 
            <?php if ($isAdminPage): ?>
                <!-- Dedicated Admin Navigation Links -->
                <nav class="nav-menu" id="nav-links">
                    <a href="admin.php" class="nav-link <?php echo ($currentPage === 'admin.php') ? 'active' : ''; ?>" id="nav-admin-dash">
                        <i class="fa-solid fa-chart-line"></i> Dashboard
                    </a>
                    <a href="admin-orders.php" class="nav-link <?php echo ($currentPage === 'admin-orders.php') ? 'active' : ''; ?>" id="nav-admin-orders">
                        <i class="fa-solid fa-list-check"></i> Orders Queue
                    </a>
                    <a href="admin-categories.php" class="nav-link <?php echo ($currentPage === 'admin-categories.php') ? 'active' : ''; ?>" id="nav-admin-cats">
                        <i class="fa-solid fa-folder"></i> View Categories
                    </a>
                    <a href="admin-category-add.php" class="nav-link <?php echo ($currentPage === 'admin-category-add.php') ? 'active' : ''; ?>" id="nav-admin-cats-add">
                        <i class="fa-solid fa-folder-plus"></i> Add Category
                    </a>
                    <a href="admin-products.php" class="nav-link <?php echo ($currentPage === 'admin-products.php') ? 'active' : ''; ?>" id="nav-admin-prods">
                        <i class="fa-solid fa-boxes-stacked"></i> View Products
                    </a>
                    <a href="admin-product-add.php" class="nav-link <?php echo ($currentPage === 'admin-product-add.php') ? 'active' : ''; ?>" id="nav-admin-prods-add">
                        <i class="fa-solid fa-plus"></i> Add Product
                    </a>
                </nav>
                
                <!-- Action Bar for Admin -->
                <div class="nav-actions">
                    <a href="logout.php" class="admin-link-btn" id="nav-logout" style="border-color: var(--danger); color: var(--danger);">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                    <a href="index.php" class="admin-link-btn" id="nav-back-store" style="border-color: var(--accent-primary); color: var(--text-primary);">
                        <i class="fa-solid fa-store"></i> Back to Store
                    </a>
                </div>
            <?php else: ?>
                <!-- Navigation Links -->
                <nav class="nav-menu" id="nav-links">
                    <a href="index.php" class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" id="nav-home">Home</a>
                    <a href="index.php#categories" class="nav-link" id="nav-cats">Categories</a>
                    <a href="index.php#services" class="nav-link" id="nav-servs">Services</a>
                    <a href="cart.php" class="nav-link <?php echo ($currentPage == 'cart.php') ? 'active' : ''; ?>" id="nav-cart-text">My Cart</a>
                </nav>
 
                <!-- Action Bar -->
                <div class="nav-actions">
                    <!-- Cart Floating Button -->
                    <a href="cart.php" class="cart-icon-btn" id="nav-cart-icon" aria-label="Shopping Cart">
                        <i class="fa-solid fa-shopping-bag"></i>
                        <span class="cart-badge" style="<?php echo ($cartCount > 0) ? 'display: flex;' : 'display: none;'; ?>"><?php echo $cartCount; ?></span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>
