<?php
// admin-product-edit.php
// Admin page for editing an existing product

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth check gate
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/header.php';

// Get product ID from query parameter
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header("Location: admin-products.php");
    exit();
}

// Fetch the product
try {
    $stmt = $pdo->prepare("SELECT * FROM `products` WHERE `id` = :id");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

if (!$product) {
    header("Location: admin-products.php");
    exit();
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    $prodName = isset($_POST['name']) ? trim($_POST['name']) : '';
    $prodSlug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $prodPrice = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
    $prodCatId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $prodImage = isset($_POST['image']) ? trim($_POST['image']) : '';
    $prodDesc = isset($_POST['description']) ? trim($_POST['description']) : '';
    $prodStock = isset($_POST['stock']) ? (int)$_POST['stock'] : 10;
    $prodFeatured = isset($_POST['featured']) ? 1 : 0;
    $prodSpecs = isset($_POST['specs']) ? trim($_POST['specs']) : '{}';

    $uploadDir = __DIR__ . '/assets/images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // If admin provided a manual image filename, keep it only if the file exists.
    if ($prodImage !== '' && !file_exists($uploadDir . $prodImage)) {
        $prodImage = '';
    }

    $uploadedImageName = null;
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['admin_err'] = 'Image upload failed. Please try again.';
            header('Location: admin-product-edit.php?id=' . $productId);
            exit();
        }

        if ($_FILES['image_file']['size'] > 5 * 1024 * 1024) {
            $_SESSION['admin_err'] = 'Image file size must be 5MB or less.';
            header('Location: admin-product-edit.php?id=' . $productId);
            exit();
        }

        $imageInfo = @getimagesize($_FILES['image_file']['tmp_name']);
        if (!$imageInfo || !in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
            $_SESSION['admin_err'] = 'Only JPG, PNG, WEBP, and GIF images are allowed.';
            header('Location: admin-product-edit.php?id=' . $productId);
            exit();
        }

        $originalName = pathinfo($_FILES['image_file']['name'], PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalName);
        if ($safeName === '') {
            $safeName = 'product';
        }
        $uploadedImageName = $safeName . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

        if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $uploadedImageName)) {
            $_SESSION['admin_err'] = 'Unable to save the uploaded image.';
            header('Location: admin-product-edit.php?id=' . $productId);
            exit();
        }
    }

    if (empty($prodName) || empty($prodSlug) || $prodPrice <= 0 || $prodCatId <= 0) {
        $_SESSION['admin_err'] = "Product name, slug, price, and category are required.";
        header("Location: admin-product-edit.php?id=" . $productId);
        exit();
    } else {
        try {
            $finalImage = $uploadedImageName ?? ($prodImage ?: $product['image']);
            
            $stmt = $pdo->prepare("UPDATE `products` SET `category_id` = :category_id, `name` = :name, `slug` = :slug, `price` = :price, `image` = :image, `description` = :description, `stock` = :stock, `featured` = :featured, `specs` = :specs WHERE `id` = :id");
            $stmt->execute([
                'id' => $productId,
                'category_id' => $prodCatId,
                'name' => $prodName,
                'slug' => $prodSlug,
                'price' => $prodPrice,
                'image' => $finalImage,
                'description' => $prodDesc,
                'stock' => $prodStock,
                'featured' => $prodFeatured,
                'specs' => $prodSpecs
            ]);
            $_SESSION['admin_msg'] = "Product '$prodName' updated successfully!";
            header("Location: admin-products.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['admin_err'] = "Failed to update product: " . $e->getMessage();
            header("Location: admin-product-edit.php?id=" . $productId);
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

<!-- Administrative Edit Product Section -->
<section class="admin-section">
    <div class="container" style="max-width: 700px;">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 8px;">
            Edit <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Product</span>
        </h1>
        <p style="color: var(--text-secondary); margin-bottom: 36px; font-size:15px;">Update product details and specifications.</p>

        <!-- Message Alerts -->
        <?php if (isset($_SESSION['admin_err'])): ?>
            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--border-radius-md); padding: 14px 20px; color: var(--danger); margin-bottom: 30px; font-size:14px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $_SESSION['admin_err']; unset($_SESSION['admin_err']); ?>
            </div>
        <?php endif; ?>

        <!-- Product Form Card -->
        <div class="stat-card" style="padding: 36px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg);">
            <form action="admin-product-edit.php?id=<?php echo $productId; ?>" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="action" value="edit_product">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-input" placeholder="e.g. Echo Dot" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Product Slug</label>
                        <input type="text" name="slug" class="form-input" placeholder="e.g. echo-dot" value="<?php echo htmlspecialchars($product['slug']); ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Price (RWF)</label>
                        <input type="number" step="0.01" name="price" class="form-input" placeholder="e.g. 45000" value="<?php echo $product['price']; ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: var(--border-radius-md); width: 100%; height: 50px; padding: 0 16px;" required>
                            <option value="">Select Category</option>
                            <?php foreach ($allCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $product['category_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Stock Qty</label>
                        <input type="number" name="stock" class="form-input" value="<?php echo $product['stock']; ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; align-items: flex-start;">
                        <label class="form-label">Featured Product</label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-top: 14px; cursor: pointer;">
                            <input type="checkbox" name="featured" style="accent-color: var(--accent-primary); width: 18px; height: 18px;" <?php echo ($product['featured'] == 1) ? 'checked' : ''; ?>> Promote on Home
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" placeholder="Detailed product specifications overview..." style="min-height: 80px;"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Current Image</label>
                    <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px;">
                        <i class="fa-solid fa-image"></i> <?php echo htmlspecialchars($product['image']); ?>
                    </p>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Upload New Image</label>
                    <input type="file" name="image_file" class="form-input" accept="image/*">
                    <small style="color: var(--text-muted); font-size: 13px;">Optional. JPG, PNG, WEBP, or GIF only, max 5MB. Leave empty to keep current image.</small>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Image Filename</label>
                    <input type="text" name="image" class="form-input" placeholder="e.g. product_echodot.png" value="<?php echo htmlspecialchars($product['image']); ?>">
                    <small style="color: var(--text-muted); font-size: 13px;">Optional. Use only when image already exists in assets/images/.</small>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Technical Specs (JSON format)</label>
                    <textarea name="specs" class="form-textarea" placeholder='{"Color": "Charcoal", "WiFi": "802.11ac"}' style="min-height: 80px; font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars($product['specs']); ?></textarea>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 10px;">
                    <a href="admin-products.php" class="admin-action-btn" style="flex: 1; text-align: center; padding: 14px; border-radius: var(--border-radius-md); display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                    <button type="submit" class="checkout-btn" style="flex: 1; margin: 0; padding: 14px; background: var(--accent-gradient-hover);">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
