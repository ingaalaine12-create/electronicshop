<?php
// admin-product-add.php
// Secure page to add a new product to the store database

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth check gate
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/header.php';

// Handle adding a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $prodName = isset($_POST['name']) ? trim($_POST['name']) : '';
    $prodSlug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $prodPrice = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
    $prodCatId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $prodImage = isset($_POST['image']) ? trim($_POST['image']) : '';
    $prodDesc = isset($_POST['description']) ? trim($_POST['description']) : '';
    $prodStock = isset($_POST['stock']) ? (int)$_POST['stock'] : 10;
    $prodFeatured = isset($_POST['featured']) ? 1 : 0;
    $prodSpecs = isset($_POST['specs']) ? trim($_POST['specs']) : '{}';

    if (empty($prodName) || empty($prodSlug) || $prodPrice <= 0 || $prodCatId <= 0) {
        $_SESSION['admin_err'] = "Product name, slug, price, and category are required.";
        header("Location: admin-product-add.php");
        exit();
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `products` (`category_id`, `name`, `slug`, `price`, `image`, `description`, `stock`, `featured`, `specs`) 
                                   VALUES (:category_id, :name, :slug, :price, :image, :description, :stock, :featured, :specs)");
            $stmt->execute([
                'category_id' => $prodCatId,
                'name' => $prodName,
                'slug' => $prodSlug,
                'price' => $prodPrice,
                'image' => $prodImage ?: 'product_placeholder.png',
                'description' => $prodDesc,
                'stock' => $prodStock,
                'featured' => $prodFeatured,
                'specs' => $prodSpecs
            ]);
            $_SESSION['admin_msg'] = "Product '$prodName' added successfully!";
            header("Location: admin-products.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['admin_err'] = "Failed to add product: " . $e->getMessage();
            header("Location: admin-product-add.php");
            exit();
        }
    }
}

// Fetch categories for select dropdown
try {
    $catQuery = $pdo->query("SELECT * FROM `categories` ORDER BY `name` ASC");
    $allCategories = $catQuery->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!-- Administrative Add Product Section -->
<section class="admin-section">
    <div class="container" style="max-width: 700px;">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 8px;">
            Add New <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Product</span>
        </h1>
        <p style="color: var(--text-secondary); margin-bottom: 36px; font-size:15px;">Publish a new product specification card to the customer storefront.</p>

        <!-- Message Alerts -->
        <?php if (isset($_SESSION['admin_err'])): ?>
            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--border-radius-md); padding: 14px 20px; color: var(--danger); margin-bottom: 30px; font-size:14px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $_SESSION['admin_err']; unset($_SESSION['admin_err']); ?>
            </div>
        <?php endif; ?>

        <!-- Product Form Card -->
        <div class="stat-card" style="padding: 36px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg);">
            <form action="admin-product-add.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="action" value="add_product">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-input" placeholder="e.g. Echo Dot" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Product Slug</label>
                        <input type="text" name="slug" class="form-input" placeholder="e.g. echo-dot" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Price (RWF)</label>
                        <input type="number" step="0.01" name="price" class="form-input" placeholder="e.g. 45000" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: var(--border-radius-md); width: 100%; height: 50px; padding: 0 16px;" required>
                            <option value="">Select Category</option>
                            <?php foreach ($allCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Stock Qty</label>
                        <input type="number" name="stock" class="form-input" value="10" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; align-items: flex-start;">
                        <label class="form-label">Featured Product</label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-top: 14px; cursor: pointer;">
                            <input type="checkbox" name="featured" style="accent-color: var(--accent-primary); width: 18px; height: 18px;"> Promote on Home
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" placeholder="Detailed product specifications overview..." style="min-height: 80px;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Image Filename</label>
                    <input type="text" name="image" class="form-input" placeholder="e.g. product_echodot.png">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Technical Specs (JSON format)</label>
                    <textarea name="specs" class="form-textarea" placeholder='{"Color": "Charcoal", "WiFi": "802.11ac"}' style="min-height: 80px; font-family: monospace; font-size: 13px;"></textarea>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 10px;">
                    <a href="admin-products.php" class="admin-action-btn" style="flex: 1; text-align: center; padding: 14px; border-radius: var(--border-radius-md); display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                    <button type="submit" class="checkout-btn" style="flex: 1; margin: 0; padding: 14px; background: var(--accent-gradient-hover);">
                        <i class="fa-solid fa-plus-circle"></i> Publish Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
