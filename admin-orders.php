<?php
// admin-orders.php
// Dedicated admin page for checking client orders and updating delivery states

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth check gate
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/header.php';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';

    if ($orderId > 0 && in_array($newStatus, ['Pending', 'Shipped', 'Completed'])) {
        try {
            $stmt = $pdo->prepare("UPDATE `orders` SET `status` = :status WHERE `id` = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $orderId]);
            $_SESSION['admin_msg'] = "Order #KTH-" . (10000 + $orderId) . " updated to '$newStatus' status.";
        } catch (PDOException $e) {
            $_SESSION['admin_err'] = "Failed to update order: " . $e->getMessage();
        }
    }
    
    header("Location: admin-orders.php");
    exit();
}

// Fetch All Placed Orders
try {
    $ordersStmt = $pdo->query("SELECT * FROM `orders` ORDER BY `id` DESC");
    $orders = $ordersStmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!-- Administrative Orders Section -->
<section class="admin-section">
    <div class="container">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 8px;">
            Orders <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Queue</span>
        </h1>
        <p style="color: var(--text-secondary); margin-bottom: 36px; font-size:15px;">Monitor placed orders, process shipments, and mark completed transactions.</p>

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

        <!-- Orders Detailed Datatable -->
        <div class="admin-table-card">
            <?php if (count($orders) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order Ref</th>
                            <th>Order Date</th>
                            <th>Customer Contact</th>
                            <th>Delivery Address</th>
                            <th>Method</th>
                            <th>Total Amount</th>
                            <th>Delivery Status</th>
                            <th>Action Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <!-- Order Ref -->
                                <td><strong>#KTH-<?php echo 10000 + $ord['id']; ?></strong></td>
                                
                                <!-- Placed Date -->
                                <td><?php echo date('M d, Y, H:i', strtotime($ord['created_at'])); ?></td>
                                
                                <!-- Customer details -->
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($ord['customer_name']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($ord['customer_phone']); ?></div>
                                </td>
                                
                                <!-- Destination details -->
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($ord['delivery_district']); ?> District</div>
                                    <div style="font-size: 12px; color: var(--text-muted); text-overflow: ellipsis; white-space: nowrap; max-width: 180px; overflow: hidden;" title="<?php echo htmlspecialchars($ord['delivery_address']); ?>">
                                        <?php echo htmlspecialchars($ord['delivery_address']); ?>
                                    </div>
                                </td>
                                
                                <!-- Payment Method -->
                                <td>
                                    <span style="font-size: 13px; font-weight: 500; color: var(--accent-secondary);">
                                        <?php echo htmlspecialchars($ord['payment_method']); ?>
                                    </span>
                                </td>
                                
                                <!-- Total Amount -->
                                <td><strong><?php echo formatRWF($ord['total_amount']); ?></strong></td>
                                
                                <!-- Status Badge -->
                                <td>
                                    <?php 
                                    $statusClass = 'pending';
                                    if ($ord['status'] === 'Shipped') $statusClass = 'shipped';
                                    elseif ($ord['status'] === 'Completed') $statusClass = 'completed';
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($ord['status']); ?>
                                    </span>
                                </td>

                                <!-- Status Control Actions -->
                                <td>
                                    <form action="admin-orders.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                        
                                        <?php if ($ord['status'] === 'Pending'): ?>
                                            <input type="hidden" name="status" value="Shipped">
                                            <button type="submit" class="admin-action-btn" style="border-color: var(--info); color: var(--info);">
                                                <i class="fa-solid fa-truck"></i> Ship Order
                                            </button>
                                        <?php elseif ($ord['status'] === 'Shipped'): ?>
                                            <input type="hidden" name="status" value="Completed">
                                            <button type="submit" class="admin-action-btn" style="border-color: var(--success); color: var(--success);">
                                                <i class="fa-solid fa-house-chimney-user"></i> Complete
                                            </button>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size:12.5px; font-weight:600;">
                                                <i class="fa-solid fa-circle-check" style="color: var(--success);"></i> Fulfilled
                                            </span>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 48px 0;">
                    <i class="fa-solid fa-clipboard-list" style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                    <h3>No placed orders yet.</h3>
                    <p style="color: var(--text-secondary); margin-top: 6px;">Your client transaction queue will populate here as checkout purchases occur.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
