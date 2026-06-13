<?php
// payment-callback.php
// Verifies transaction response from Flutterwave and redirects/updates order state

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$tx_ref = isset($_GET['tx_ref']) ? trim($_GET['tx_ref']) : '';
$transaction_id = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : '';

$verification_success = false;
$error_msg = '';
$order = null;

if (empty($tx_ref) || empty($status)) {
    $error_msg = "Invalid payment callback request: missing reference parameters.";
} else {
    try {
        // Retrieve matching order
        $orderStmt = $pdo->prepare("SELECT * FROM `orders` WHERE `tx_ref` = :tx_ref LIMIT 1");
        $orderStmt->execute(['tx_ref' => $tx_ref]);
        $order = $orderStmt->fetch();

        if (!$order) {
            $error_msg = "Order record not found for transaction reference: " . htmlspecialchars($tx_ref);
        } else {
            if ($status === 'cancelled') {
                // Mark order as cancelled
                $updateStmt = $pdo->prepare("UPDATE `orders` SET `status` = 'Cancelled' WHERE `id` = :id");
                $updateStmt->execute(['id' => $order['id']]);
                $error_msg = "Payment transaction was cancelled by the customer.";
            } elseif ($order['status'] === 'Completed' || $order['status'] === 'Shipped') {
                // Already processed, direct to success
                $verification_success = true;
            } else {
                // Call Flutterwave to verify
                $secret_key = get_env_variable('FLUTTERWAVE_SECRET_KEY');
                if (empty($secret_key)) {
                    throw new Exception("Gateway configuration missing on server.");
                }

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/" . urlencode($transaction_id) . "/verify",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_HTTPHEADER => [
                        "Authorization: Bearer " . $secret_key,
                        "Content-Type: application/json",
                        "Accept: application/json"
                    ]
                ]);

                $response = curl_exec($ch);
                $curl_err = curl_error($ch);
                curl_close($ch);

                if ($curl_err) {
                    throw new Exception("Connection to gateway failed during verification.");
                }

                $res_data = json_decode($response, true);
                if (isset($res_data['status']) && $res_data['status'] === 'success' && isset($res_data['data'])) {
                    $tx_data = $res_data['data'];
                    $verified_amount = floatval($tx_data['amount']);
                    $verified_currency = $tx_data['currency'];
                    $verified_status = $tx_data['status'];
                    
                    $expected_amount = floatval($order['total_amount']);

                    if ($verified_status === 'successful' && $verified_amount >= $expected_amount && $verified_currency === 'RWF') {
                        // Mark order as pending (ready to ship) and save transaction ID
                        $updateStmt = $pdo->prepare("UPDATE `orders` SET `status` = 'Pending', `transaction_id` = :tx_id WHERE `id` = :id");
                        $updateStmt->execute([
                            'tx_id' => $transaction_id,
                            'id' => $order['id']
                        ]);

                        // Retrieve order items to populate session for success page
                        $itemsStmt = $pdo->prepare("
                            SELECT oi.*, p.name 
                            FROM `order_items` oi
                            LEFT JOIN `products` p ON oi.product_id = p.id
                            WHERE oi.order_id = :order_id
                        ");
                        $itemsStmt->execute(['order_id' => $order['id']]);
                        $items = $itemsStmt->fetchAll();

                        $subtotal = 0;
                        foreach ($items as $item) {
                            $subtotal += $item['quantity'] * $item['price'];
                        }
                        $deliveryFee = ($subtotal > 500000) ? 0 : 3000;

                        $_SESSION['last_placed_order'] = [
                            'order_id' => $order['id'],
                            'name' => $order['customer_name'],
                            'phone' => $order['customer_phone'],
                            'address' => $order['delivery_address'] . ", " . $order['delivery_district'] . " District, " . $order['delivery_province'] . " Province",
                            'payment' => $order['payment_method'],
                            'subtotal' => $subtotal,
                            'delivery' => $deliveryFee,
                            'total' => $order['total_amount']
                        ];

                        $verification_success = true;
                        
                        // Redirect to Kigali TechHub order-success screen!
                        header("Location: order-success.php");
                        exit();
                    } else {
                        // Mark order as failed
                        $updateStmt = $pdo->prepare("UPDATE `orders` SET `status` = 'Failed', `transaction_id` = :tx_id WHERE `id` = :id");
                        $updateStmt->execute([
                            'tx_id' => $transaction_id,
                            'id' => $order['id']
                        ]);
                        $error_msg = "Transaction verification failed: status is " . htmlspecialchars($verified_status);
                    }
                } else {
                    $updateStmt = $pdo->prepare("UPDATE `orders` SET `status` = 'Failed' WHERE `id` = :id");
                    $updateStmt->execute(['id' => $order['id']]);
                    $error_msg = "Gateway verification check returned negative status: " . htmlspecialchars($res_data['message'] ?? 'Invalid payload');
                }
            }
        }
    } catch (Exception $e) {
        $error_msg = "Error verifying payment: " . $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Payment Failure Screen -->
<div class="container">
    <div class="success-card" style="border-color: rgba(239, 68, 68, 0.2) !important; box-shadow: 0 20px 40px rgba(239, 68, 68, 0.08) !important;">
        <!-- Red Cross Circle Icon -->
        <div class="success-icon-wrap" style="background: rgba(239, 68, 68, 0.1); border-color: var(--danger); color: var(--danger);">
            <i class="fa-solid fa-xmark"></i>
        </div>

        <h1 class="success-title" style="background: linear-gradient(135deg, #fca5a5 0%, #ef4444 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Payment Failed</h1>
        <p class="success-subtitle" style="color: var(--text-secondary);">
            We were unable to authorize and verify your transaction request with Flutterwave.
        </p>

        <!-- Receipt Box displaying Failure state -->
        <div class="receipt-box" style="background: rgba(239, 68, 68, 0.02); border-color: rgba(239, 68, 68, 0.1);">
            <div class="receipt-header" style="border-bottom-color: rgba(239, 68, 68, 0.08);">
                <span>Transaction Ref: <strong>#<?php echo htmlspecialchars($tx_ref ?: 'N/A'); ?></strong></span>
                <span style="color: var(--danger); font-weight: 700; text-transform: uppercase;">Unsuccessful</span>
            </div>

            <div style="font-size: 13.5px; line-height: 1.6; color: #fca5a5; padding: 10px 0;">
                <i class="fa-solid fa-circle-exclamation" style="margin-right: 6px;"></i>
                <?php echo htmlspecialchars($error_msg ?: 'Transaction could not be successfully validated.'); ?>
            </div>
        </div>

        <!-- Continue Button -->
        <div style="display: flex; gap: 16px; justify-content: center; margin-top: 30px;">
            <a href="cart.php" class="checkout-btn" style="margin-top: 0; padding: 14px 28px; width: auto; display: inline-flex; background: var(--accent-gradient);">
                <i class="fa-solid fa-rotate-left"></i> Return to Cart
            </a>
            <a href="index.php" class="checkout-btn" style="margin-top: 0; padding: 14px 28px; width: auto; display: inline-flex; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary);">
                Store Home
            </a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
