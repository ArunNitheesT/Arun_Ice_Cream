<?php
// Cart API - manages shopping cart via sessions
// GET = get cart, POST with action = add/remove/update/clear

require_once __DIR__ . '/../db/init.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ── Helper: compute cart count (total units) ──────────────────────────────
function cartCount(): int {
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    return (int) array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// ── Helper: compute cart total (sum of price × qty) ───────────────────────
function cartTotal(PDO $pdo): float {
    if (empty($_SESSION['cart'])) {
        return 0.0;
    }
    $total = 0.0;
    foreach ($_SESSION['cart'] as $item) {
        $stmt = $pdo->prepare('SELECT price FROM products WHERE id = :id');
        $stmt->execute([':id' => $item['product_id']]);
        $product = $stmt->fetch();
        if ($product) {
            $total += (float)$product['price'] * (int)$item['quantity'];
        }
    }
    return round($total, 2);
}

// ── Helper: build full cart array with product details ────────────────────
function buildCartItems(PDO $pdo): array {
    if (empty($_SESSION['cart'])) {
        return [];
    }
    $items = [];
    foreach ($_SESSION['cart'] as $productId => $item) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();
        if ($product) {
            $items[] = [
                'product_id'     => (int)$product['id'],
                'name'           => $product['name'],
                'category'       => $product['category'],
                'price'          => (float)$product['price'],
                'image_filename' => $product['image_filename'],
                'quantity'       => (int)$item['quantity'],
                'line_total'     => round((float)$product['price'] * (int)$item['quantity'], 2),
            ];
        }
    }
    return $items;
}

try {
    $pdo = getDB();
    $method = $_SERVER['REQUEST_METHOD'];

    // ── GET: return cart contents ─────────────────────────────────────────
    if ($method === 'GET') {
        $items = buildCartItems($pdo);
        echo json_encode([
            'success'    => true,
            'items'      => $items,
            'cart_count' => cartCount(),
            'cart_total' => cartTotal($pdo),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── POST: cart mutations ──────────────────────────────────────────────
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $action = trim($_POST['action'] ?? '');

    if ($action === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameter: action']);
        exit;
    }

    // ── CLEAR ─────────────────────────────────────────────────────────────
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        echo json_encode([
            'success'    => true,
            'cart_count' => 0,
            'cart_total' => 0.0,
            'message'    => 'Cart cleared',
        ]);
        exit;
    }

    // All other actions require product_id
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameter: product_id']);
        exit;
    }

    // Validate product exists in DB (never trust client)
    $stmt = $pdo->prepare('SELECT id, name, price FROM products WHERE id = :id');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit;
    }

    // Initialise cart if needed
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // ── ADD ───────────────────────────────────────────────────────────────
    if ($action === 'add') {
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity']++;
        } else {
            $_SESSION['cart'][$productId] = [
                'product_id' => $productId,
                'quantity'   => 1,
            ];
        }
        echo json_encode([
            'success'    => true,
            'cart_count' => cartCount(),
            'cart_total' => cartTotal($pdo),
            'message'    => htmlspecialchars($product['name']) . ' added to cart!',
        ]);
        exit;
    }

    // ── REMOVE ────────────────────────────────────────────────────────────
    if ($action === 'remove') {
        unset($_SESSION['cart'][$productId]);
        echo json_encode([
            'success'    => true,
            'cart_count' => cartCount(),
            'cart_total' => cartTotal($pdo),
            'message'    => 'Item removed from cart',
        ]);
        exit;
    }

    // ── UPDATE ────────────────────────────────────────────────────────────
    if ($action === 'update') {
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : -1;

        if ($quantity < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid quantity']);
            exit;
        }

        if ($quantity === 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = [
                'product_id' => $productId,
                'quantity'   => $quantity,
            ];
        }

        echo json_encode([
            'success'    => true,
            'cart_count' => cartCount(),
            'cart_total' => cartTotal($pdo),
            'quantity'   => $quantity,
            'message'    => 'Cart updated',
        ]);
        exit;
    }

    // Unknown action
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown action: ' . htmlspecialchars($action)]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
}
