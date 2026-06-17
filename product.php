<?php
// product.php
// Premium product details page featuring specifications tables and recommended cross-sells

require_once __DIR__ . '/includes/header.php';

// Fetch product by slug
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: index.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
                           FROM `products` p 
                           LEFT JOIN `categories` c ON p.category_id = c.id 
                           WHERE p.slug = :slug");
    $stmt->execute(['slug' => $slug]);
    $product = $stmt->fetch();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

if (!$product) {
    header("Location: index.php");
    exit();
}

// Preserve raw product image data so the helper can resolve a file from name or slug when needed
$productImage = !empty($product['image']) ? $product['image'] : '';

// Decode product specifications
$specs = json_decode($product['specs'], true);
if (!is_array($specs)) {
    $specs = [];
}

// Fetch 3 related products in same category (excluding current product)
try {
    $relatedStmt = $pdo->prepare("SELECT p.*, c.slug as category_slug 
                                  FROM `products` p 
                                  LEFT JOIN `categories` c ON p.category_id = c.id
                                  WHERE p.category_id = :cat_id AND p.id != :prod_id 
                                  LIMIT 3");
    $relatedStmt->execute([
        'cat_id' => $product['category_id'],
        'prod_id' => $product['id']
    ]);
    $relatedProducts = $relatedStmt->fetchAll();
} catch (PDOException $e) {
    $relatedProducts = [];
}
?>

<!-- Product Detail Hero -->
<section class="detail-section">
    <div class="container">
        <!-- Breadcrumb Navigation -->
        <div style="font-size: 13.5px; color: var(--text-muted); margin-bottom: 24px; display: flex; gap: 8px;">
            <a href="index.php" style="hover:color: var(--accent-primary);">Home</a> 
            <span>&gt;</span> 
            <a href="index.php?cat=<?php echo htmlspecialchars($product['category_slug']); ?>"><?php echo htmlspecialchars($product['category_name']); ?></a> 
            <span>&gt;</span> 
            <span style="color: var(--text-secondary);"><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <div class="detail-grid">
            <!-- Left Column: Product Graphic/Image Panel -->
            <div class="detail-img-card" style="max-width: 680px; width: 100%;">
                <?php echo renderProductImage($productImage, $product['category_slug'], $product['name'], 'detail-img', $product['slug']); ?>
            </div>

            <!-- Right Column: Product Detail Metadata & Form -->
            <div class="detail-info">
                <span class="product-cat"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <h1 class="detail-name"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <!-- Stock Availability Badge -->
                <div style="margin-bottom: 20px;">
                    <?php if ($product['stock'] > 0): ?>
                        <span style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); font-weight: 700; font-size: 12px; padding: 4px 10px; border-radius: 50px; text-transform: uppercase;">
                            <i class="fa-solid fa-circle-check" style="margin-right: 4px;"></i> In Stock (<?php echo $product['stock']; ?> available)
                        </span>
                    <?php else: ?>
                        <span style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); font-weight: 700; font-size: 12px; padding: 4px 10px; border-radius: 50px; text-transform: uppercase;">
                            <i class="fa-solid fa-circle-xmark" style="margin-right: 4px;"></i> Temporarily Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Price Row -->
                <div class="detail-price-row">
                    <div class="product-price-box">
                        <span class="currency-label">Kigali Retail Price</span>
                        <span class="price-value"><?php echo formatRWF($product['price']); ?></span>
                    </div>
                </div>

                <!-- Product Description -->
                <p class="detail-desc"><?php echo htmlspecialchars($product['description']); ?></p>

                <!-- Actions: Qty Select & Add to Cart -->
                <?php if ($product['stock'] > 0): ?>
                    <div class="detail-action-row">
                        <!-- Custom styled quantity switcher -->
                        <div class="qty-selector">
                            <button class="qty-btn minus" aria-label="Decrease quantity">-</button>
                            <input type="text" class="qty-input" value="1" readonly>
                            <button class="qty-btn plus" aria-label="Increase quantity">+</button>
                        </div>

                        <!-- Direct interactive add to cart button -->
                        <button class="buy-btn add-cart-btn-trigger" data-product-id="<?php echo $product['id']; ?>">
                            <i class="fa-solid fa-shopping-bag"></i> Add to Shopping Cart
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Fast logistics guarantees -->
                <div style="margin-top: 36px; padding-top: 24px; border-top: 1px solid var(--border-color); display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-size: 13.5px; color: var(--text-secondary);">
                    <div>
                        <i class="fa-solid fa-truck-fast" style="color: var(--accent-primary); margin-right: 8px;"></i>
                        <strong>Express Delivery</strong><br>
                        Under 2 hrs inside Kigali (3,000 RWF flat or free > 500k)
                    </div>
                    <div>
                        <i class="fa-solid fa-shield-halved" style="color: var(--accent-primary); margin-right: 8px;"></i>
                        <strong>Warranty Assured</strong><br>
                        12-month manufacturer parts warranty included
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Specifications & Reviews Section -->
<section style="background: var(--bg-secondary); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 60px 0;">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 50px;">
            <!-- Specs Table Card -->
            <div>
                <h3 style="font-size: 22px; font-weight:700; margin-bottom: 24px;">Technical <span>Specifications</span></h3>
                <div class="detail-specs-list" style="margin-bottom: 0; background: var(--bg-primary);">
                    <?php if (!empty($specs)): ?>
                        <?php foreach ($specs as $key => $val): ?>
                            <div class="spec-item">
                                <span class="spec-key"><?php echo htmlspecialchars($key); ?></span>
                                <span class="spec-val"><?php echo htmlspecialchars($val); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-style: italic;">No specific dimensions or attributes are published for this service item.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Customer Reviews Simulation -->
            <div>
                <h3 style="font-size: 22px; font-weight:700; margin-bottom: 24px;">Verified <span>Reviews</span></h3>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- Review 1 -->
                    <div style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); padding: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <strong>Patrick N.</strong>
                            <span style="color: #facc15; font-size:12px;">★★★★★</span>
                        </div>
                        <p style="color: var(--text-secondary); font-size:13.5px;">Outstanding device! Purchased it yesterday and it was delivered to my office in Gasabo in less than an hour. The customer care is fantastic.</p>
                    </div>
                    
                    <!-- Review 2 -->
                    <div style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); padding: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <strong>Amina U.</strong>
                            <span style="color: #facc15; font-size:12px;">★★★★☆</span>
                        </div>
                        <p style="color: var(--text-secondary); font-size:13.5px;">Super fast service! Highly recommend this shop to anyone looking for genuine accessories in Kigali. The packaging was immaculate.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Upsell Related Products Section -->
<?php if (count($relatedProducts) > 0): ?>
    <section style="padding: 60px 0 80px 0;">
        <div class="container">
            <h3 style="font-size: 24px; font-weight: 700; margin-bottom: 30px;">Related <span>Recommendations</span></h3>
            
            <div class="products-grid">
                <?php foreach ($relatedProducts as $rel): ?>
                    <!-- Product Card -->
                    <div class="product-card" data-category-slug="<?php echo htmlspecialchars($rel['category_slug']); ?>">
                        <a href="product.php?slug=<?php echo htmlspecialchars($rel['slug']); ?>" style="display: block;">
                            <?php echo renderProductImage($rel['image'] ?? '', $rel['category_slug'], $rel['name'], 'product-img', $rel['slug']); ?>
                        </a>

                        <div class="product-info">
                            <a href="product.php?slug=<?php echo htmlspecialchars($rel['slug']); ?>">
                                <h4 class="product-name" style="font-size:16px;"><?php echo htmlspecialchars($rel['name']); ?></h4>
                            </a>
                            <p class="product-desc"><?php echo htmlspecialchars($rel['description']); ?></p>
                            
                            <div class="product-footer">
                                <div class="product-price-box">
                                    <span class="currency-label">Local Price</span>
                                    <span class="price-value" style="font-size: 16px;"><?php echo formatRWF($rel['price']); ?></span>
                                </div>
                                <button class="add-cart-btn add-cart-btn-trigger" data-product-id="<?php echo $rel['id']; ?>" aria-label="Add to Cart">
                                    <i class="fa-solid fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
