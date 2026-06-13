<?php
// cart.php
// Elegant shopping cart page that displays items, handles quantity alterations, and summarizes totals

require_once __DIR__ . '/includes/header.php';

// Prepare variables
$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;

// Fetch full category information for each cart product to guarantee the vector illustration rendering is correct
if (!empty($cartItems)) {
    $productIds = array_keys($cartItems);
    $inClause = implode(',', array_fill(0, count($productIds), '?'));
    
    try {
        $stmt = $pdo->prepare("SELECT p.id, c.slug as category_slug 
                               FROM `products` p 
                               LEFT JOIN `categories` c ON p.category_id = c.id 
                               WHERE p.id IN ($inClause)");
        $stmt->execute($productIds);
        $prodCategories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        $prodCategories = [];
    }
}
?>

<!-- Cart Section -->
<section class="cart-section">
    <div class="container">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 30px;">
            Shopping <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Cart</span>
        </h1>

        <?php if (!empty($cartItems)): ?>
            <div class="cart-grid">
                <!-- Left: List of Items -->
                <div class="cart-table-card">
                    <?php foreach ($cartItems as $id => $item): ?>
                        <?php 
                        $itemSubtotal = $item['quantity'] * $item['price'];
                        $subtotal += $itemSubtotal;
                        
                        // Detect category slug for vector rendering fallback
                        $categorySlug = isset($prodCategories[$id]) ? $prodCategories[$id] : 'laptops';
                        ?>
                        <!-- Cart Item Row -->
                        <div class="cart-item-row" data-product-id="<?php echo $id; ?>">
                            <!-- Product Image & Meta -->
                            <div class="cart-item-info">
                                <div class="cart-item-img">
                                    <?php 
                                    // Use our utility image renderer
                                    echo renderProductImage($item['image'], $categorySlug, $item['name']); 
                                    ?>
                                </div>
                                <div class="cart-item-meta">
                                    <h3 class="cart-item-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <span class="cart-item-price"><?php echo formatRWF($item['price']); ?> each</span>
                                </div>
                            </div>

                            <!-- Quantity Controls -->
                            <div class="cart-item-qty">
                                <div class="qty-selector" style="padding: 4px;">
                                    <button class="qty-btn minus" style="width:30px; height:30px;">-</button>
                                    <input type="text" 
                                           class="qty-input cart-qty-trigger" 
                                           data-product-id="<?php echo $id; ?>" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           style="width: 38px; font-size:14px;" 
                                           readonly>
                                    <button class="qty-btn plus" style="width:30px; height:30px;">+</button>
                                </div>
                            </div>

                            <!-- Row Subtotal -->
                            <div class="cart-item-total" id="item-total-<?php echo $id; ?>">
                                <?php echo formatRWF($itemSubtotal); ?>
                            </div>

                            <!-- Remove Trigger -->
                            <button class="cart-item-remove remove-cart-btn-trigger" data-product-id="<?php echo $id; ?>" aria-label="Remove item">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Right: Summary Box -->
                <div class="summary-card">
                    <h2 class="summary-title">Order Summary</h2>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="cart-subtotal"><?php echo formatRWF($subtotal); ?></span>
                    </div>

                    <?php 
                    // Delivery Rules: 3,000 RWF flat, free above 500,000 RWF
                    $deliveryFee = ($subtotal > 500000) ? 0 : 3000;
                    $grandTotal = $subtotal + $deliveryFee;
                    ?>
                    
                    <div class="summary-row">
                        <span>Kigali Delivery Fee</span>
                        <span id="cart-delivery">
                            <?php if ($deliveryFee == 0): ?>
                                <strong style="color: var(--success); text-transform: uppercase;">Free</strong>
                            <?php else: ?>
                                <?php echo formatRWF($deliveryFee); ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Highlight the free shipping target if subtotal is below limit -->
                    <?php if ($subtotal < 500000): ?>
                        <div style="background: rgba(0, 242, 254, 0.04); border: 1px solid rgba(0, 242, 254, 0.1); border-radius: var(--border-radius-sm); padding: 12px; font-size: 12.5px; color: var(--accent-secondary); margin-bottom: 20px; line-height: 1.4;">
                            <i class="fa-solid fa-gift"></i> Add <strong><?php echo formatRWF(500000 - $subtotal); ?></strong> more to your cart to unlock <strong>FREE Express Delivery</strong>!
                        </div>
                    <?php endif; ?>

                    <div class="summary-row total">
                        <span>Total Price</span>
                        <span id="cart-total"><?php echo formatRWF($grandTotal); ?></span>
                    </div>

                    <!-- Secure Checkout Button Link -->
                    <a href="checkout.php" class="checkout-btn">
                        <i class="fa-solid fa-lock"></i> Proceed to Secure Checkout
                    </a>

                    <!-- Return Link -->
                    <div style="text-align: center; margin-top: 16px;">
                        <a href="index.php" style="font-size: 13.5px; color: var(--text-secondary); text-decoration: underline;">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty Cart Placeholder View -->
            <div class="cart-table-card empty-cart-view">
                <div class="empty-cart-icon">
                    <i class="fa-solid fa-basket-shopping"></i>
                </div>
                <h2 style="font-size: 24px; font-weight:700;">Your Cart is Currently Empty</h2>
                <p style="color: var(--text-secondary); margin-top: 8px; max-width: 400px; margin-left:auto; margin-right:auto;">
                    It looks like you haven't selected any hardware systems or diagnostics yet. Explore our categories to start shopping!
                </p>
                <a href="index.php" class="continue-btn">
                    <i class="fa-solid fa-circle-left"></i> Return to Storefront
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
