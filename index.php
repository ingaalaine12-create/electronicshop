<?php
// index.php
// Beautiful interactive homepage featuring responsive product catalog, category tabs, and services

require_once __DIR__ . '/includes/header.php';

// Handle suggestion form submission
$suggestionMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_suggestion') {
    $suggestionName = isset($_POST['suggestion_name']) ? trim($_POST['suggestion_name']) : '';
    $suggestionEmail = isset($_POST['suggestion_email']) ? trim($_POST['suggestion_email']) : '';
    $suggestionSubject = isset($_POST['suggestion_subject']) ? trim($_POST['suggestion_subject']) : '';
    $suggestionMessage = isset($_POST['suggestion_message']) ? trim($_POST['suggestion_message']) : '';
    $suggestionRating = isset($_POST['suggestion_rating']) ? (int)$_POST['suggestion_rating'] : null;

    if (empty($suggestionName) || empty($suggestionEmail) || empty($suggestionSubject) || empty($suggestionMessage)) {
        $suggestionError = "All fields are required.";
    } elseif (!filter_var($suggestionEmail, FILTER_VALIDATE_EMAIL)) {
        $suggestionError = "Please enter a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `suggestions` (`name`, `email`, `subject`, `message`, `rating`) VALUES (:name, :email, :subject, :message, :rating)");
            $stmt->execute([
                'name' => $suggestionName,
                'email' => $suggestionEmail,
                'subject' => $suggestionSubject,
                'message' => $suggestionMessage,
                'rating' => $suggestionRating
            ]);
            $suggestionSuccess = "Thank you for your suggestion! We appreciate your feedback.";
            $suggestionName = '';
            $suggestionEmail = '';
            $suggestionSubject = '';
            $suggestionMessage = '';
            $suggestionRating = null;
        } catch (PDOException $e) {
            $suggestionError = "Failed to submit suggestion. Please try again.";
        }
    }
}

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

<!-- User Suggestion Box Section -->
<section style="padding: 80px 0; background: var(--bg-secondary); border-top: 1px solid var(--border-color);">
    <div class="container" style="max-width: 700px;">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-size: 36px; font-weight: 800; margin-bottom: 12px;">
                Share Your <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Feedback</span>
            </h2>
            <p style="color: var(--text-secondary); font-size: 15px; max-width: 500px; margin: 0 auto;">
                We'd love to hear your thoughts, suggestions, and feedback. Help us improve the Kigali TechHub experience.
            </p>
        </div>

        <?php if (isset($suggestionSuccess)): ?>
            <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: var(--border-radius-md); padding: 16px 20px; color: var(--success); margin-bottom: 30px; font-size: 14px;">
                <i class="fa-solid fa-circle-check"></i> <?php echo $suggestionSuccess; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($suggestionError)): ?>
            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--border-radius-md); padding: 16px 20px; color: var(--danger); margin-bottom: 30px; font-size: 14px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $suggestionError; ?>
            </div>
        <?php endif; ?>

        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg); padding: 40px;">
            <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="action" value="submit_suggestion">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Your Name</label>
                        <input type="text" name="suggestion_name" class="form-input" placeholder="John Doe" value="<?php echo isset($suggestionName) ? htmlspecialchars($suggestionName) : ''; ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="suggestion_email" class="form-input" placeholder="john@example.com" value="<?php echo isset($suggestionEmail) ? htmlspecialchars($suggestionEmail) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Subject</label>
                    <input type="text" name="suggestion_subject" class="form-input" placeholder="e.g. Product Suggestion, Service Feedback, General Comment" value="<?php echo isset($suggestionSubject) ? htmlspecialchars($suggestionSubject) : ''; ?>" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Your Message</label>
                    <textarea name="suggestion_message" class="form-textarea" placeholder="Tell us what you think..." style="min-height: 120px;" required><?php echo isset($suggestionMessage) ? htmlspecialchars($suggestionMessage) : ''; ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Overall Experience Rating (Optional)</label>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="suggestion_rating" value="<?php echo $i; ?>" <?php echo (isset($suggestionRating) && $suggestionRating == $i) ? 'checked' : ''; ?> style="cursor: pointer;">
                                <span style="font-size: 20px;">
                                    <?php for ($j = 0; $j < $i; $j++): ?>
                                        <i class="fa-solid fa-star" style="color: #facc15; margin-right: 2px;"></i>
                                    <?php endfor; ?>
                                </span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <button type="submit" class="buy-btn" style="margin-top: 20px; padding: 14px; width: 100%;">
                    <i class="fa-solid fa-paper-plane"></i> Submit Feedback
                </button>
            </form>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
