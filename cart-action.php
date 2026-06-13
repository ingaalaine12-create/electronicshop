<?php
// cart-action.php
// Secure server-side controller for handling session cart AJAX actions

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$response = [
    'success' => false,
    'message' => 'Invalid action or request.',
    'cart_count' => 0,
    'subtotal' => 0,
    'total' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($productId > 0) {
        // Fetch product information to verify it exists and get pricing
        $stmt = $pdo->prepare("SELECT * FROM `products` WHERE `id` = :id");
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();

        if ($product) {
            $price = (float)$product['price'];
            $stock = (int)$product['stock'];

            switch ($action) {
                case 'add':
                    if (isset($_SESSION['cart'][$productId])) {
                        $newQty = $_SESSION['cart'][$productId]['quantity'] + $quantity;
                    } else {
                        $newQty = $quantity;
                    }

                    // Clamp to stock
                    if ($newQty > $stock) {
                        $newQty = $stock;
                        $response['message'] = "Limited stock available! Only $stock items added.";
                    }

                    $_SESSION['cart'][$productId] = [
                        'id' => $productId,
                        'name' => $product['name'],
                        'price' => $price,
                        'image' => $product['image'],
                        'quantity' => $newQty
                    ];
                    
                    $response['success'] = true;
                    $response['message'] = 'Product successfully added to cart!';
                    break;

                case 'update':
                    if ($quantity <= 0) {
                        unset($_SESSION['cart'][$productId]);
                    } else {
                        // Clamp to stock
                        if ($quantity > $stock) {
                            $quantity = $stock;
                            $response['message'] = "Only $stock items available in stock.";
                        }
                        
                        if (isset($_SESSION['cart'][$productId])) {
                            $_SESSION['cart'][$productId]['quantity'] = $quantity;
                        }
                    }
                    $response['success'] = true;
                    break;

                case 'remove':
                    if (isset($_SESSION['cart'][$productId])) {
                        unset($_SESSION['cart'][$productId]);
                    }
                    $response['success'] = true;
                    break;
            }
        } else {
            $response['message'] = 'Product not found.';
        }
    }
}

// Compute live figures to return to client
$cartCount = 0;
$subtotal = 0;
$itemsResponse = [];

foreach ($_SESSION['cart'] as $id => $item) {
    $itemSubtotal = $item['quantity'] * $item['price'];
    $subtotal += $itemSubtotal;
    $cartCount += $item['quantity'];

    $itemsResponse[$id] = [
        'quantity' => $item['quantity'],
        'subtotal' => $itemSubtotal
    ];
}

// Simple localized logistics: Delivery is 3000 RWF flat, but free for orders above 500,000 RWF!
$deliveryFee = ($subtotal > 500000 || $subtotal == 0) ? 0 : 3000;
$grandTotal = $subtotal + $deliveryFee;

$response['cart_count'] = $cartCount;
$response['subtotal'] = $subtotal;
$response['total'] = $grandTotal;
$response['items'] = $itemsResponse;

if ($response['success'] && empty($response['message'])) {
    $response['message'] = 'Success';
}

echo json_encode($response);
exit();
?>
