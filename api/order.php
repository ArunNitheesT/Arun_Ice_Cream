<?php
/**
 * Order API endpoint.
 *
 * POST /api/order.php
 *   name, email, phone, address, payment_method, items (JSON string)
 *
 * Returns: {"success": true, "order_id": N}
 *       or {"success": false, "error": "..."}
 */

require_once __DIR__ . '/../db/init.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isCustomer()) {
    http_response_code(isLoggedIn() ? 403 : 401);
    echo json_encode([
        'success' => false,
        'error'   => isLoggedIn()
            ? 'Only customer accounts can place orders.'
            : 'Please login to place an order.',
    ]);
    exit;
}

// ── Read and sanitise inputs ──────────────────────────────────────────────
$name          = trim($_POST['name']           ?? '');
$email         = trim($_POST['email']          ?? '');
$phone         = trim($_POST['phone']          ?? '');
$address       = trim($_POST['address']        ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? '');
$itemsJson      = trim($_POST['items'] ?? '');

// ── Server-side validation ────────────────────────────────────────────────
$validPaymentMethods = ['Cash on Delivery', 'UPI', 'Card'];

if ($name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Full name is required']);
    exit;
}

if ($email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email address is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

if ($phone === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Phone number is required']);
    exit;
}

if ($address === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Delivery address is required']);
    exit;
}

if (!in_array($paymentMethod, $validPaymentMethods, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payment method']);
    exit;
}

// Decode items JSON
$items = json_decode($itemsJson, true);
if (!is_array($items) || count($items) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

try {
    $pdo = getDB();

    // ── Compute total server-side (never trust client prices) ─────────────
    $totalAmount = 0.0;
    $orderItems  = [];

    foreach ($items as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $quantity  = (int)($item['quantity']   ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid item in cart']);
            exit;
        }

        // Fetch authoritative price from DB
        $stmt = $pdo->prepare('SELECT id, name, price FROM products WHERE id = :id');
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Product not found: ' . $productId]);
            exit;
        }

        $lineTotal    = round((float)$product['price'] * $quantity, 2);
        $totalAmount += $lineTotal;

        $orderItems[] = [
            'product_id' => (int)$product['id'],
            'name'       => $product['name'],
            'price'      => (float)$product['price'],
            'quantity'   => $quantity,
            'line_total' => $lineTotal,
        ];
    }

    $totalAmount = round($totalAmount, 2);

    if ($totalAmount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Order total must be greater than zero']);
        exit;
    }

    // Link order to logged-in customer if applicable
    $userId = (int) currentUser()['id'];

    // ── Insert order into DB ──────────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO orders
            (customer_name, customer_email, customer_phone, delivery_address,
             payment_method, items_json, total_amount, created_at, status, user_id)
        VALUES
            (:name, :email, :phone, :address,
             :payment_method, :items_json, :total_amount, :created_at, :status, :user_id)
    ");

    $stmt->execute([
        ':name'           => $name,
        ':email'          => $email,
        ':phone'          => $phone,
        ':address'        => $address,
        ':payment_method' => $paymentMethod,
        ':items_json'     => json_encode($orderItems, JSON_UNESCAPED_UNICODE),
        ':total_amount'   => $totalAmount,
        ':created_at'     => date('c'),
        ':status'         => 'pending',
        ':user_id'        => $userId,
    ]);

    $orderId = (int)$pdo->lastInsertId();

    // ── Clear session cart on success ─────────────────────────────────────
    $_SESSION['cart'] = [];

    echo json_encode([
        'success'  => true,
        'order_id' => $orderId,
    ]);

} catch (PDOException $e) {
    // Do NOT clear cart on DB failure
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Failed to save order. Please try again.',
    ]);
}
