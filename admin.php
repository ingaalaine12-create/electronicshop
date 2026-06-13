<?php
// admin.php
// Premium administrative operations dashboard showing overview statistics

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth check gate
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/header.php';

// Fetch Overview Metrics
try {
    // Total Revenue (all orders)
    $revStmt = $pdo->query("SELECT SUM(`total_amount`) FROM `orders`");
    $totalRevenue = (float)$revStmt->fetchColumn();

    // Total Orders Count
    $countStmt = $pdo->query("SELECT COUNT(*) FROM `orders`");
    $totalOrders = (int)$countStmt->fetchColumn();

    // Pending Orders Count
    $pendingStmt = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'Pending'");
    $pendingOrders = (int)$pendingStmt->fetchColumn();

    // Completed Orders Count
    $completedStmt = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'Completed'");
    $completedOrders = (int)$completedStmt->fetchColumn();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!-- Administrative Dashboard Section -->
<section class="admin-section">
    <div class="container">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 8px;">
            Administrative <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Dashboard</span>
        </h1>
        <p style="color: var(--text-secondary); margin-bottom: 36px; font-size:15px;">Monitor placed orders, track sales volumes, and manage product deliveries inside Rwanda.</p>

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

        <!-- Statistics Scorecards Grid -->
        <div class="admin-grid">
            <!-- Metric Card 1: Revenue -->
            <div class="stat-card">
                <span class="stat-label">Gross Revenue</span>
                <div class="stat-value revenue"><?php echo formatRWF($totalRevenue); ?></div>
            </div>

            <!-- Metric Card 2: Total Orders -->
            <div class="stat-card">
                <span class="stat-label">Total Transactions</span>
                <div class="stat-value"><?php echo $totalOrders; ?></div>
            </div>

            <!-- Metric Card 3: Pending Queue -->
            <div class="stat-card">
                <span class="stat-label">Pending Queue</span>
                <div class="stat-value" style="color: var(--warning);"><?php echo $pendingOrders; ?></div>
            </div>

            <!-- Metric Card 4: Fulfilled Deliveries -->
            <div class="stat-card">
                <span class="stat-label">Fulfilled Deliveries</span>
                <div class="stat-value" style="color: var(--success);"><?php echo $completedOrders; ?></div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
