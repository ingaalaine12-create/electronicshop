<?php
// checkout.php
// Localized checkout form collecting customer details, validating parameters, and executing database orders

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

$cartItems = $_SESSION['cart'];
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['quantity'] * $item['price'];
}

$deliveryFee = ($subtotal > 500000) ? 0 : 3000;
$grandTotal = $subtotal + $deliveryFee;

$errors = [];
$debug_lines = [];

// Form Submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
    $email = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
    $phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
    $province = isset($_POST['delivery_province']) ? trim($_POST['delivery_province']) : '';
    $district = isset($_POST['delivery_district']) ? trim($_POST['delivery_district']) : '';
    $address = isset($_POST['delivery_address']) ? trim($_POST['delivery_address']) : '';
    $paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
    $momoPhone = isset($_POST['payment_phone']) ? trim($_POST['payment_phone']) : '';

    // Validation checks
    if (empty($name)) $errors[] = "Please enter your full name.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if (empty($phone)) $errors[] = "Please enter your contact phone number.";
    if (empty($province)) $errors[] = "Please choose a delivery province.";
    if (empty($district)) $errors[] = "Please choose a delivery district.";
    if (empty($address)) $errors[] = "Please enter your delivery street address (Sector, Cell, Street).";
    if (empty($paymentMethod)) $errors[] = "Please select a payment method.";

    // Localized Mobile Money payment phone validation
    if (($paymentMethod === 'MTN Mobile Money' || $paymentMethod === 'Airtel Money') && empty($momoPhone)) {
        $errors[] = "Please provide the phone number connected to your Mobile Money account.";
    }

    if (empty($errors)) {
        try {
            // Start a safe SQL transaction
            $pdo->beginTransaction();

            $tx_ref = 'kth-order-' . time() . '-' . rand(1000, 9999);

            // 1. Insert into Orders
            $orderSql = "INSERT INTO `orders` 
                         (`customer_name`, `customer_email`, `customer_phone`, `delivery_province`, `delivery_district`, `delivery_address`, `payment_method`, `total_amount`, `status`, `tx_ref`) 
                         VALUES (:name, :email, :phone, :province, :district, :address, :payment, :total, 'Pending', :tx_ref)";
            
            $orderStmt = $pdo->prepare($orderSql);
            $orderStmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'province' => $province,
                'district' => $district,
                'address' => $address,
                'payment' => $paymentMethod,
                'total' => $grandTotal,
                'tx_ref' => $tx_ref
            ]);

            $orderId = $pdo->lastInsertId();

            // 2. Insert order items & Deduct stock
            $itemSql = "INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price`) VALUES (:order_id, :prod_id, :qty, :price)";
            $itemStmt = $pdo->prepare($itemSql);

            $stockSql = "UPDATE `products` SET `stock` = `stock` - :qty WHERE `id` = :prod_id";
            $stockStmt = $pdo->prepare($stockSql);

            foreach ($cartItems as $id => $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'prod_id' => $id,
                    'qty' => $item['quantity'],
                    'price' => $item['price']
                ]);

                if ($id != 8 && $id != 9) {
                    $stockStmt->execute([
                        'qty' => $item['quantity'],
                        'prod_id' => $id
                    ]);
                }
            }

            // Commit Transaction successfully
            $pdo->commit();

            // Store placed order summary in session
            $_SESSION['last_placed_order'] = [
                'order_id' => $orderId,
                'name' => $name,
                'phone' => ($paymentMethod === 'MTN Mobile Money' || $paymentMethod === 'Airtel Money') ? $momoPhone : $phone,
                'address' => "$address, $district District, $province Province",
                'payment' => $paymentMethod,
                'subtotal' => $subtotal,
                'delivery' => $deliveryFee,
                'total' => $grandTotal
            ];

            // If cash on delivery, clear cart and redirect straight to success screen
            if ($paymentMethod === 'Cash on Delivery') {
                unset($_SESSION['cart']);
                header("Location: order-success.php");
                exit();
            }

            // Otherwise, load Flutterwave Keys and Redirect
            $secret_key = get_env_variable('FLUTTERWAVE_SECRET_KEY');
            $public_key = get_env_variable('FLUTTERWAVE_PUBLIC_KEY');

            if (empty($secret_key)) {
                $debug_lines[] = "❌ <strong>FLUTTERWAVE_SECRET_KEY</strong> not found in <code>.env</code> file. Check your key syntax.";
                throw new Exception("Payment gateway config error: Secret key missing.");
            }

            if (!function_exists('curl_init')) {
                $debug_lines[] = "❌ <strong>PHP cURL extension</strong> is not enabled in your XAMPP installation.";
                throw new Exception("Server missing cURL adapter extension.");
            }

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $redirect_url = $protocol . '://' . $host . '/electronicshop/payment-callback.php';

            $payload = [
                'tx_ref' => $tx_ref,
                'amount' => $grandTotal,
                'currency' => 'RWF',
                'redirect_url' => $redirect_url,
                'customer' => [
                    'email' => $email,
                    'phonenumber' => ($paymentMethod === 'MTN Mobile Money' || $paymentMethod === 'Airtel Money') ? $momoPhone : $phone,
                    'name' => $name
                ],
                'customizations' => [
                    'title' => "Kigali TechHub Store",
                    'description' => "Payment for Order #KTH-" . (10000 + $orderId),
                    'logo' => $protocol . '://' . $host . '/electronicshop/assets/images/logo.png'
                ]
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.flutterwave.com/v3/payments',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $secret_key,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);

            $response = curl_exec($ch);
            $curl_err = curl_error($ch);
            $curl_info = curl_getinfo($ch);
            $http_code = $curl_info['http_code'];
            curl_close($ch);

            $debug_lines[] = "ℹ️ Flutterwave API HTTP response status code: <code>$http_code</code>";

            if ($curl_err) {
                $debug_lines[] = "❌ cURL connection error: <code>" . htmlspecialchars($curl_err) . "</code>";
                throw new Exception("Connection to payment gateway failed.");
            }

            if (empty($response)) {
                $debug_lines[] = "❌ Empty response from Flutterwave gateway.";
                throw new Exception("Payment gateway returned empty payload.");
            }

            $res_data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $debug_lines[] = "❌ Invalid JSON data returned from API.";
                throw new Exception("Gateway returned invalid response format.");
            }

            if (isset($res_data['status']) && $res_data['status'] === 'success' && !empty($res_data['data']['link'])) {
                // Clear active cart & Redirect
                unset($_SESSION['cart']);
                header("Location: " . $res_data['data']['link']);
                exit();
            } else {
                $fw_msg = isset($res_data['message']) ? $res_data['message'] : 'Unknown response error.';
                $debug_lines[] = "❌ Gateway error details: <code>" . htmlspecialchars($fw_msg) . "</code>";
                if (!empty($res_data)) {
                    $debug_lines[] = "ℹ️ Full Gateway Payload: <pre style='margin: 8px 0 0; color: #94a3b8; font-size:11.5px; font-family: monospace; white-space: pre-wrap;'>" . htmlspecialchars(json_encode($res_data, JSON_PRETTY_PRINT)) . "</pre>";
                }
                throw new Exception("Gateway Error: " . $fw_msg);
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Styled loading indicator overlay -->
<div id="checkout-payment-loading" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(10,17,30,0.95); flex-direction:column; align-items:center; justify-content:center; gap:20px;">
    <div style="width: 56px; height: 56px; border-radius: 50%; border: 4px solid rgba(255,255,255,0.08); border-top-color: var(--accent-primary); animation: spinLoading 0.9s linear infinite;"></div>
    <p style="color:var(--text-secondary); font-size:15px; font-family:'Plus Jakarta Sans', sans-serif;">Redirecting to secure Flutterwave checkout portal…</p>
</div>

<style>
@keyframes spinLoading {
    to { transform: rotate(360deg); }
}
.debug-block {
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: var(--border-radius-sm);
    padding: 14px;
    margin-top: 14px;
    font-size: 12.5px;
    font-family: monospace;
    text-align: left;
    color: var(--text-secondary);
}
.debug-block summary {
    cursor: pointer;
    font-weight: 700;
    color: var(--accent-secondary);
    user-select: none;
    outline: none;
}
</style>

<!-- Checkout Container -->
<section class="cart-section">
    <div class="container">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 30px;">
            Secure <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Checkout</span>
        </h1>

        <!-- Error Dialog Banner -->
        <?php if (!empty($errors)): ?>
            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--border-radius-md); padding: 18px 24px; color: var(--danger); margin-bottom: 30px; font-size:14.5px;">
                <strong style="display: block; margin-bottom: 8px;"><i class="fa-solid fa-triangle-exclamation"></i> Transaction Error:</strong>
                <ul style="list-style-position: inside; margin-bottom:0;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (!empty($debug_lines)): ?>
                    <details class="debug-block">
                        <summary>🔧 Gateway Connection Logs (click to expand)</summary>
                        <div style="margin-top: 10px; line-height: 1.7;">
                            <?php foreach ($debug_lines as $log): ?>
                                <div style="border-bottom: 1px solid rgba(255,255,255,0.03); padding: 4px 0;">
                                    <?php echo $log; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form id="checkout-payment-form" action="checkout.php" method="POST">
            <div class="cart-grid">
                <!-- Left Column: Customer Form Details -->
                <div class="cart-table-card">
                    <h2 style="font-size: 20px; font-weight:700; margin-bottom: 24px; border-bottom:1px solid var(--border-color); padding-bottom:12px;">
                        1. Customer Information
                    </h2>
                    
                    <div class="form-grid">
                        <!-- Full Name -->
                        <div class="form-group">
                            <label class="form-label" for="customer_name">Full Name</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-input" placeholder="e.g., Patrick Nkurunziza" value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>" required>
                        </div>
                        
                        <!-- Email Address -->
                        <div class="form-group">
                            <label class="form-label" for="customer_email">Email Address</label>
                            <input type="email" id="customer_email" name="customer_email" class="form-input" placeholder="e.g., patrick@domain.rw" value="<?php echo isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : ''; ?>" required>
                        </div>

                        <!-- Phone Number -->
                        <div class="form-group full-width">
                            <label class="form-label" for="customer_phone">Phone Number</label>
                            <input type="text" id="customer_phone" name="customer_phone" class="form-input" placeholder="e.g., 0788123456" value="<?php echo isset($_POST['customer_phone']) ? htmlspecialchars($_POST['customer_phone']) : ''; ?>" required>
                            <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Provide a valid contact number (MTN, Airtel, or liquid lines) for delivery communication.</span>
                        </div>
                    </div>

                    <h2 style="font-size: 20px; font-weight:700; margin-top: 40px; margin-bottom: 24px; border-bottom:1px solid var(--border-color); padding-bottom:12px;">
                        2. Local Delivery Details (Rwanda)
                    </h2>

                    <div class="form-grid">
                        <!-- Province -->
                        <div class="form-group">
                            <label class="form-label" for="delivery_province">Province</label>
                            <select id="delivery_province" name="delivery_province" class="form-select" required>
                                <option value="">-- Choose Province --</option>
                                <option value="Kigali City" <?php echo (isset($_POST['delivery_province']) && $_POST['delivery_province'] === 'Kigali City') ? 'selected' : ''; ?>>Kigali City (Gasabo, Nyarugenge, Kicukiro)</option>
                                <option value="Eastern Province" <?php echo (isset($_POST['delivery_province']) && $_POST['delivery_province'] === 'Eastern Province') ? 'selected' : ''; ?>>Eastern Province</option>
                                <option value="Western Province" <?php echo (isset($_POST['delivery_province']) && $_POST['delivery_province'] === 'Western Province') ? 'selected' : ''; ?>>Western Province</option>
                                <option value="Northern Province" <?php echo (isset($_POST['delivery_province']) && $_POST['delivery_province'] === 'Northern Province') ? 'selected' : ''; ?>>Northern Province</option>
                                <option value="Southern Province" <?php echo (isset($_POST['delivery_province']) && $_POST['delivery_province'] === 'Southern Province') ? 'selected' : ''; ?>>Southern Province</option>
                            </select>
                        </div>

                        <!-- District -->
                        <div class="form-group">
                            <label class="form-label" for="delivery_district">District</label>
                            <select id="delivery_district" name="delivery_district" class="form-select" required>
                                <option value="">-- Choose District --</option>
                                <option value="Gasabo" <?php echo (isset($_POST['delivery_district']) && $_POST['delivery_district'] === 'Gasabo') ? 'selected' : ''; ?>>Gasabo</option>
                                <option value="Nyarugenge" <?php echo (isset($_POST['delivery_district']) && $_POST['delivery_district'] === 'Nyarugenge') ? 'selected' : ''; ?>>Nyarugenge</option>
                                <option value="Kicukiro" <?php echo (isset($_POST['delivery_district']) && $_POST['delivery_district'] === 'Kicukiro') ? 'selected' : ''; ?>>Kicukiro</option>
                                <option value="Musanze" <?php echo (isset($_POST['delivery_district']) && $_POST['delivery_district'] === 'Musanze') ? 'selected' : ''; ?>>Musanze</option>
                                <option value="Rubavu" <?php echo (isset($_POST['delivery_district']) && $_POST['delivery_district'] === 'Rubavu') ? 'selected' : ''; ?>>Rubavu</option>
                                <option value="Huye" <?php echo (isset($_POST['delivery_district']) && $_POST['delivery_district'] === 'Huye') ? 'selected' : ''; ?>>Huye</option>
                                <option value="Rwamagana" <?php echo (isset($_POST['delivery_district']) && $_POST['delivery_district'] === 'Rwamagana') ? 'selected' : ''; ?>>Rwamagana</option>
                            </select>
                        </div>

                        <!-- Street Address Details -->
                        <div class="form-group full-width">
                            <label class="form-label" for="delivery_address">Full Street Address</label>
                            <textarea id="delivery_address" name="delivery_address" class="form-textarea" placeholder="e.g., Kimihurura Sector, Rugando Cell, KG 28 Ave, House 14" required><?php echo isset($_POST['delivery_address']) ? htmlspecialchars($_POST['delivery_address']) : ''; ?></textarea>
                        </div>
                    </div>

                    <h2 style="font-size: 20px; font-weight:700; margin-top: 40px; margin-bottom: 24px; border-bottom:1px solid var(--border-color); padding-bottom:12px;">
                        3. Secure Payment System
                    </h2>

                    <!-- Styled Card Options -->
                    <div class="payment-selector-grid">
                        <!-- MTN MoMo -->
                        <div class="payment-option-card <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'MTN Mobile Money') ? 'selected' : ''; ?>" id="card-momo">
                            <input type="radio" name="payment_method" value="MTN Mobile Money" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'MTN Mobile Money') ? 'checked' : ''; ?> required>
                            <div class="payment-logo payment-momo"><i class="fa-solid fa-mobile-screen"></i></div>
                            <span class="payment-name">MTN MoMo</span>
                        </div>
                        
                        <!-- Airtel Money -->
                        <div class="payment-option-card <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Airtel Money') ? 'selected' : ''; ?>" id="card-airtel">
                            <input type="radio" name="payment_method" value="Airtel Money" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Airtel Money') ? 'checked' : ''; ?>>
                            <div class="payment-logo payment-airtel"><i class="fa-solid fa-mobile-screen-button"></i></div>
                            <span class="payment-name">Airtel Money</span>
                        </div>

                        <!-- Credit Card -->
                        <div class="payment-option-card <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Card Payment') ? 'selected' : ''; ?>" id="card-credit">
                            <input type="radio" name="payment_method" value="Card Payment" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Card Payment') ? 'checked' : ''; ?>>
                            <div class="payment-logo payment-card"><i class="fa-regular fa-credit-card"></i></div>
                            <span class="payment-name">Credit Card</span>
                        </div>

                        <!-- Cash On Delivery -->
                        <div class="payment-option-card <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Cash on Delivery') ? 'selected' : ''; ?>" id="card-cod">
                            <input type="radio" name="payment_method" value="Cash on Delivery" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Cash on Delivery') ? 'checked' : ''; ?>>
                            <div class="payment-logo payment-cod"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                            <span class="payment-name">Cash On Delivery</span>
                        </div>
                    </div>

                    <!-- Slide out details block for Mobile Money push notification -->
                    <div id="momo-details-field" style="display: <?php echo (isset($_POST['payment_method']) && ($_POST['payment_method'] === 'MTN Mobile Money' || $_POST['payment_method'] === 'Airtel Money')) ? 'block' : 'none'; ?>; background: rgba(0, 242, 254, 0.03); border: 1px solid var(--border-color-active); border-radius: var(--border-radius-md); padding: 20px; margin-top: 24px;">
                        <h4 style="margin-bottom: 8px; color: var(--accent-primary); font-size: 14.5px;"><i class="fa-solid fa-bell"></i> Instant Push Notification</h4>
                        <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.5;">We will trigger a secure payment push request directly to your phone. Enter your mobile money wallet phone below:</p>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="payment_phone"><span id="momo-provider-name">Mobile Money</span> Wallet Phone</label>
                            <input type="text" id="payment_phone" name="payment_phone" class="form-input" placeholder="e.g., 0788123456" value="<?php echo isset($_POST['payment_phone']) ? htmlspecialchars($_POST['payment_phone']) : ''; ?>" style="max-width: 300px;">
                        </div>
                    </div>
                </div>

                <!-- Right Column: Final Order summary metrics -->
                <div class="summary-card">
                    <h2 class="summary-title">Your Order Summary</h2>
                    
                    <!-- Short checklist list of items -->
                    <div style="max-height: 200px; overflow-y: auto; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                        <?php foreach ($cartItems as $id => $item): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size:13.5px; margin-bottom: 10px; color: var(--text-secondary);">
                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;">
                                    <?php echo htmlspecialchars($item['name']); ?> <strong style="color: var(--text-primary);">x<?php echo $item['quantity']; ?></strong>
                                </span>
                                <span style="font-weight: 600; color: var(--text-primary);"><?php echo formatRWF($item['quantity'] * $item['price']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-row">
                        <span>Cart Subtotal</span>
                        <span><?php echo formatRWF($subtotal); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Kigali Delivery Fee</span>
                        <span>
                            <?php if ($deliveryFee == 0): ?>
                                <strong style="color: var(--success); text-transform: uppercase;">Free</strong>
                            <?php else: ?>
                                <?php echo formatRWF($deliveryFee); ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="summary-row total" style="margin-top: 16px; padding-top: 16px;">
                        <span>Grand Total</span>
                        <span class="price-value"><?php echo formatRWF($grandTotal); ?></span>
                    </div>

                    <!-- Place Order Submission Submit -->
                    <button type="submit" id="checkout-payment-btn" class="checkout-btn">
                        <i class="fa-solid fa-credit-card"></i> Authorize & Place Order
                    </button>

                    <div style="text-align: center; margin-top: 16px; font-size: 12px; color: var(--text-muted);">
                        <i class="fa-solid fa-lock" style="color: var(--success);"></i> Fully encrypted, secure transaction.
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
// Show payment loading indicator if payment method is digital
document.getElementById('checkout-payment-form').addEventListener('submit', function(e) {
    if (this.checkValidity()) {
        const method = document.querySelector('input[name="payment_method"]:checked').value;
        if (method !== 'Cash on Delivery') {
            const btn = document.getElementById('checkout-payment-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing Payment…';
            document.getElementById('checkout-payment-loading').style.display = 'flex';
        }
    }
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
