<?php
// index.php
// Beautiful interactive homepage featuring responsive product catalog, category tabs, and services

require_once __DIR__ . '/includes/header.php';

// Fetch all categories for filter tabs
try {
    $catQuery = $pdo->query("SELECT * FROM `categories` ORDER BY `id` ASC");
    $categories = $catQuery->fetchAll();
} catch (PDOException $e) {
    die("Error loading categories: " . $e->getMessage());
}

// Check if category filter or search query is set in URL parameters
$selectedCatSlug = isset($_GET['cat']) ? trim($_GET['cat']) : 'all';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build Query (load all products to support dynamic interactive category and search filtering client-side)
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
        FROM `products` p 
        LEFT JOIN `categories` c ON p.category_id = c.id
        ORDER BY p.featured DESC, p.id ASC";

try {
    $prodStmt = $pdo->prepare($sql);
    $prodStmt->execute();
    $products = $prodStmt->fetchAll();
} catch (PDOException $e) {
    die("Error loading products: " . $e->getMessage());
}
?>

<!-- Hero Banner Section -->
<section class="hero" id="home">
    <div class="container">
        <div class="hero-content">
            <span class="hero-tag">Now in Kigali, Rwanda</span>
            <h1 class="hero-title">Experience the Next Gen of <span>Electronics & Tech</span></h1>
            <p class="hero-desc">Welcome to Kigali's premier digital hub. Explore high-performance laptops, flagship mobile devices, high-fidelity audio, and elite IT services tailored for you.</p>
            
            <!-- Quick Search Bar -->
            <div class="hero-search-bar">
                <input type="text" class="hero-search-input" placeholder="Search smartphones, laptops, repairs..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button class="hero-search-btn"><i class="fa-solid fa-search"></i> Search</button>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section" id="categories">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Browse by <span>Category</span></h2>
                <p class="section-subtitle">Select a category to filter our next-gen catalog</p>
            </div>
        </div>
        
        <!-- Category Filter Tabs -->
        <div class="category-tabs">
            <button class="category-tab <?php echo ($selectedCatSlug === 'all') ? 'active' : ''; ?>" data-category="all">
                <i class="fa-solid fa-layer-group"></i> All Products
            </button>
            <?php foreach ($categories as $cat): ?>
                <?php
                // Choose icons depending on category slug
                $iconClass = 'fa-tag';
                if ($cat['slug'] === 'laptops') $iconClass = 'fa-laptop';
                elseif ($cat['slug'] === 'smartphones') $iconClass = 'fa-mobile-screen';
                elseif ($cat['slug'] === 'audio') $iconClass = 'fa-headphones';
                elseif ($cat['slug'] === 'wearables') $iconClass = 'fa-clock';
                elseif ($cat['slug'] === 'services') $iconClass = 'fa-screwdriver-wrench';
                ?>
                <button class="category-tab <?php echo ($selectedCatSlug === $cat['slug']) ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($cat['slug']); ?>">
                    <i class="fa-solid <?php echo $iconClass; ?>"></i> <?php echo htmlspecialchars($cat['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Product Catalog Section -->
<section class="products-section">
    <div class="container">
        <!-- Products Grid -->
        <div class="products-grid" id="products-catalog-grid">
            <?php
            // Calculate matches on the server side for initial display state
            $initialMatchedCount = 0;
            foreach ($products as $prod) {
                $matchesCat = ($selectedCatSlug === 'all' || $prod['category_slug'] === $selectedCatSlug);
                $matchesSearch = (empty($searchQuery) || stripos($prod['name'], $searchQuery) !== false || stripos($prod['description'], $searchQuery) !== false);
                if ($matchesCat && $matchesSearch) {
                    $initialMatchedCount++;
                }
            }
            ?>
            <?php foreach ($products as $prod): ?>
                <?php
                $matchesCat = ($selectedCatSlug === 'all' || $prod['category_slug'] === $selectedCatSlug);
                $matchesSearch = (empty($searchQuery) || stripos($prod['name'], $searchQuery) !== false || stripos($prod['description'], $searchQuery) !== false);
                $displayStyle = ($matchesCat && $matchesSearch) ? 'flex' : 'none';
                ?>
                <!-- Product Card -->
                <div class="product-card" data-category-slug="<?php echo htmlspecialchars($prod['category_slug']); ?>" style="display: <?php echo $displayStyle; ?>;">
                    <!-- Featured Badge -->
                    <?php if ($prod['featured'] == 1): ?>
                        <span class="product-badge featured">Featured</span>
                    <?php endif; ?>

                    <!-- Product Image Wrapper -->
                    <a href="product.php?slug=<?php echo htmlspecialchars($prod['slug']); ?>" style="display: block;">
                        <?php 
                        // Render physical image or customized vector fallback
                        echo renderProductImage($prod['image'], $prod['category_slug'], $prod['name']); 
                        ?>
                    </a>

                    <!-- Product Information Details -->
                    <div class="product-info">
                        <span class="product-cat"><?php echo htmlspecialchars($prod['category_name']); ?></span>
                        <a href="product.php?slug=<?php echo htmlspecialchars($prod['slug']); ?>">
                            <h3 class="product-name"><?php echo htmlspecialchars($prod['name']); ?></h3>
                        </a>
                        <p class="product-desc"><?php echo htmlspecialchars($prod['description']); ?></p>
                        
                        <!-- Card Footer containing pricing and Add to Cart -->
                        <div class="product-footer">
                            <div class="product-price-box">
                                <span class="currency-label">Local Price</span>
                                <span class="price-value"><?php echo formatRWF($prod['price']); ?></span>
                            </div>
                            
                            <!-- Add to Cart Button Trigger -->
                            <button class="add-cart-btn add-cart-btn-trigger" data-product-id="<?php echo $prod['id']; ?>" aria-label="Add to Cart">
                                <i class="fa-solid fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Dynamic "No products match" message container -->
            <div id="no-products-message" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; display: <?php echo ($initialMatchedCount === 0) ? 'block' : 'none'; ?>;">
                <i class="fa-regular fa-folder-open" style="font-size: 48px; color: var(--text-muted); margin-bottom: 20px;"></i>
                <h3 style="font-size: 20px;">No products match your filters.</h3>
                <p style="color: var(--text-secondary); margin-top: 8px;">Try refining your keywords or choosing a different filter tab above.</p>
            </div>
        </div>
    </div>
</section>

<!-- Dedicated Service Promotional Banner -->
<section class="container" id="services" style="margin-bottom: 80px;">
    <div style="background: linear-gradient(135deg, rgba(0, 242, 254, 0.03) 0%, rgba(79, 172, 254, 0.05) 100%); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg); padding: 48px; display: flex; align-items: center; justify-content: space-between; gap: 30px; flex-wrap: wrap; box-shadow: var(--shadow-premium);">
        <div style="max-width: 600px;">
            <span class="hero-tag" style="background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.2); color: var(--warning); margin-bottom: 16px;">Kigali Workshop Center</span>
            <h2 style="font-size: 32px; font-weight:800; line-height: 1.2; margin-bottom: 12px;">Need Diagnostic Support or a Custom Rig Assembly?</h2>
            <p style="color: var(--text-secondary); font-size:15px; line-height: 1.6;">Our industry-certified local IT specialists are right here at Kigali Heights. We provide lightning-fast thermal pastes dusting, professional components diagnostic troubleshooting, and tidy cable custom PC builds with guaranteed warranties.</p>
        </div>
        <div>
            <a href="index.php?cat=services" class="checkout-btn" style="margin-top: 0; padding: 14px 28px; white-space: nowrap;">
                <i class="fa-solid fa-screwdriver-wrench"></i> Book Service Now
            </a>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
