<?php
// admin-categories.php
// Dedicated admin page for managing store product categories

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth check gate
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/header.php';

// Fetch existing categories
try {
    $catStmt = $pdo->query("SELECT * FROM `categories` ORDER BY `id` ASC");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!-- Administrative Categories Section -->
<section class="admin-section">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 36px;">
            <div>
                <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 8px;">
                    Manage <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Categories</span>
                </h1>
                <p style="color: var(--text-secondary); font-size:15px;">Review the product categories currently registered for the storefront.</p>
            </div>
            <a href="admin-category-add.php" class="buy-btn" style="flex: none; margin: 0; padding: 12px 24px; font-size: 14px; gap: 8px;">
                <i class="fa-solid fa-folder-plus"></i> Add New Category
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

        <!-- Existing Categories List Table -->
        <div class="admin-table-card" style="margin: 0 0 60px 0; padding: 30px;">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;">Database Category Records</h3>
            
            <?php if (count($categories) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Category Name</th>
                            <th>Category Slug</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong>#CAT-<?php echo $cat['id']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><span style="font-family: monospace; color: var(--accent-secondary);"><?php echo htmlspecialchars($cat['slug']); ?></span></td>
                                <td style="color: var(--text-secondary); max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($cat['description']); ?>">
                                    <?php echo htmlspecialchars($cat['description']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">No categories registered in the database.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
