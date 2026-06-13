<?php
// admin-category-add.php
// Secure page to add a new category to the store database

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth check gate
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/header.php';

// Handle adding a category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $catName = isset($_POST['name']) ? trim($_POST['name']) : '';
    $catSlug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $catDesc = isset($_POST['description']) ? trim($_POST['description']) : '';
    $catImage = isset($_POST['image']) ? trim($_POST['image']) : '';

    if (empty($catName) || empty($catSlug)) {
        $_SESSION['admin_err'] = "Category name and slug are required.";
        header("Location: admin-category-add.php");
        exit();
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `categories` (`name`, `slug`, `description`, `image`) VALUES (:name, :slug, :description, :image)");
            $stmt->execute([
                'name' => $catName,
                'slug' => $catSlug,
                'description' => $catDesc,
                'image' => $catImage ?: 'category_placeholder.png'
            ]);
            $_SESSION['admin_msg'] = "Category '$catName' added successfully!";
            header("Location: admin-categories.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['admin_err'] = "Failed to add category: " . $e->getMessage();
            header("Location: admin-category-add.php");
            exit();
        }
    }
}
?>

<!-- Administrative Add Category Section -->
<section class="admin-section">
    <div class="container" style="max-width: 650px;">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 8px;">
            Add New <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Category</span>
        </h1>
        <p style="color: var(--text-secondary); margin-bottom: 36px; font-size:15px;">Create a new product category segment for the store catalog.</p>

        <!-- Message Alerts -->
        <?php if (isset($_SESSION['admin_err'])): ?>
            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--border-radius-md); padding: 14px 20px; color: var(--danger); margin-bottom: 30px; font-size:14px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $_SESSION['admin_err']; unset($_SESSION['admin_err']); ?>
            </div>
        <?php endif; ?>

        <!-- Category Creation Form Card -->
        <div class="stat-card" style="padding: 36px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg);">
            <form action="admin-category-add.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="action" value="add_category">
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Smart Home" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Category Slug</label>
                    <input type="text" name="slug" class="form-input" placeholder="e.g. smart-home" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" placeholder="Brief category summary..." style="min-height: 100px;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Image Filename</label>
                    <input type="text" name="image" class="form-input" placeholder="e.g. category_smarthome.png">
                </div>

                <div style="display: flex; gap: 16px; margin-top: 10px;">
                    <a href="admin-categories.php" class="admin-action-btn" style="flex: 1; text-align: center; padding: 14px; border-radius: var(--border-radius-md); display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                    <button type="submit" class="checkout-btn" style="flex: 1; margin: 0; padding: 14px;">
                        <i class="fa-solid fa-folder-plus"></i> Create Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
