<?php
// order-success.php
// Elegant order completion screen displaying a receipt overview and localized delivery details

require_once __DIR__ . '/includes/header.php';

// Check if a successfully placed order exists in session
if (!isset($_SESSION['last_placed_order'])) {
    header("Location: index.php");
    exit();
}

$order = $_SESSION['last_placed_order'];

// Clear the success token on reload so they don't see it indefinitely,
// but let's keep it long enough for them to print or read it! 
// We will let it persist until they click "Return to Store" or navigate away.
?>

<!-- Success Screen -->
<div class="container">
    <div class="success-card">
        <!-- Glowing Checkmark icon with entry animation -->
        <div class="success-icon-wrap">
            <i class="fa-solid fa-check"></i>
        </div>

        <h1 class="success-title">Order Placed!</h1>
        <p class="success-subtitle">
            Thank you, <strong><?php echo htmlspecialchars($order['name']); ?></strong>. Your payment authorization was successful and your order is now in our queue.
        </p>

        <!-- Dynamic Digital Receipt Box -->
        <div class="receipt-box">
            <div class="receipt-header">
                <span>Receipt Ref: <strong>#KTH-<?php echo 10000 + $order['order_id']; ?></strong></span>
                <span>Date: <?php echo date('M d, Y, h:i A'); ?></span>
            </div>

            <!-- Total rows details -->
            <div class="receipt-row">
                <span style="color: var(--text-secondary);">Subtotal Price:</span>
                <span><?php echo formatRWF($order['subtotal']); ?></span>
            </div>

            <div class="receipt-row">
                <span style="color: var(--text-secondary);">Kigali Delivery Charge:</span>
                <span>
                    <?php if ($order['delivery'] == 0): ?>
                        <strong style="color: var(--success); text-transform: uppercase;">Free</strong>
                    <?php else: ?>
                        <?php echo formatRWF($order['delivery']); ?>
                    <?php endif; ?>
                </span>
            </div>

            <div class="receipt-row total">
                <span>Amount Paid:</span>
                <span><?php echo formatRWF($order['total']); ?></span>
            </div>

            <!-- Logistics details -->
            <div style="margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--border-color); font-size: 13px; line-height: 1.5;">
                <div style="margin-bottom: 6px;">
                    <i class="fa-solid fa-map-location-dot" style="color: var(--accent-primary); margin-right: 6px;"></i>
                    <strong>Delivery Destination:</strong> <?php echo htmlspecialchars($order['address']); ?>
                </div>
                <div>
                    <i class="fa-solid fa-wallet" style="color: var(--accent-primary); margin-right: 6px;"></i>
                    <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment']); ?>
                </div>
            </div>
        </div>

        <!-- Custom Instructions depending on Payment Type -->
        <div style="background: rgba(0, 242, 254, 0.03); border: 1px solid rgba(0, 242, 254, 0.1); border-radius: var(--border-radius-md); padding: 24px; text-align: left; margin-bottom: 32px; font-size:14px; line-height: 1.6;">
            <?php if ($order['payment'] === 'MTN Mobile Money' || $order['payment'] === 'Airtel Money'): ?>
                <h3 style="font-size:15px; font-weight:700; color: var(--warning); margin-bottom: 8px;">
                    <i class="fa-solid fa-spinner fa-spin" style="margin-right: 6px;"></i> Action Required: Approve MoMo Request
                </h3>
                <p style="color: var(--text-secondary);">
                    We have initiated a secure payment request directly to your mobile wallet on <strong><?php echo htmlspecialchars($order['phone']); ?></strong>. Please check your phone, enter your PIN to approve the transaction, and your items will immediately enter our dispatch queue.
                </p>
            <?php else: ?>
                <h3 style="font-size:15px; font-weight:700; color: var(--accent-primary); margin-bottom: 8px;">
                    <i class="fa-solid fa-circle-check" style="margin-right: 6px;"></i> What Happens Next?
                </h3>
                <p style="color: var(--text-secondary);">
                    Our logistics dispatcher is revieweing your order details. A member of our Kigali Heights logistics team will contact you at <strong><?php echo htmlspecialchars($order['phone']); ?></strong> within the next 15-30 minutes to verify your exact sector and schedule the final dispatch.
                </p>
            <?php endif; ?>
        </div>

        <!-- Continue Button -->
        <a href="index.php" class="checkout-btn" style="margin-top: 0; padding: 14px 28px; width: auto; display: inline-flex;" onclick="<?php unset($_SESSION['last_placed_order']); ?>">
            <i class="fa-solid fa-store"></i> Return to Storefront
        </a>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
