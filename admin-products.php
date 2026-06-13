<?php
// admin-products.php
// Dedicated admin page for managing store product items

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth check gate
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/header.php';

// Fetch products for list
try {
    // Query list of products with category name joins
    $prodStmt = $pdo->query("SELECT p.*, c.name as category_name 
                             FROM `products` p 
                             LEFT JOIN `categories` c ON p.category_id = c.id 
                             ORDER BY p.id DESC");
    $products = $prodStmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!-- Administrative Products Section -->
<section class="admin-section">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 36px;">
            <div>
                <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 8px;">
                    Manage <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Products</span>
                </h1>
                <p style="color: var(--text-secondary); font-size:15px;">Review the product inventory catalog currently registered in the database.</p>
            </div>
            <a href="admin-product-add.php" class="buy-btn" style="flex: none; margin: 0; padding: 12px 24px; font-size: 14px; gap: 8px;">
                <i class="fa-solid fa-plus-circle"></i> Add New Product
            </a>
        </div>

        <!-- Message Alerts -->
        <?php if (isset($_SESSION['admin_msg'])): ?>
            <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: var(--border-radius-md); padding: 14px 20px; color: var(--success); margin-bottom: 30px; font-size:14px;">
                <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['admin_msg']; unset($_SESSION['admin_msg']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['admin_err'])): ?>
            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--border-radius-md); padding: 14px 20px; color: var(--danger); margin-bottom: 30px; font-size:14px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $_SESSION['admin_err']; unset($_SESSION['admin_err']); ?>
            </div>
        <?php endif; ?>

        <!-- Existing Products List Table -->
        <div class="admin-table-card" style="margin: 0 0 60px 0; padding: 30px;">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;">Database Product Catalog</h3>
            
            <?php if (count($products) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product Ref</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <tr>
                                <td><strong>#PROD-<?php echo $prod['id']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                <td><span style="font-size:12.5px; font-weight:600; color: var(--accent-primary); text-transform: uppercase;"><?php echo htmlspecialchars($prod['category_name']); ?></span></td>
                                <td><strong><?php echo formatRWF($prod['price']); ?></strong></td>
                                <td>
                                    <span style="font-weight:700; color: <?php echo ($prod['stock'] > 0) ? 'var(--success)' : 'var(--danger)'; ?>;">
                                        <?php echo $prod['stock']; ?> units
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">No products registered in the database.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
