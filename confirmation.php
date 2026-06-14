<?php
// Order confirmation page
require_once 'db/init.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate order_id parameter
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch order from database
$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Decode order items
$orderItems = json_decode($order['items_json'], true);
if (!is_array($orderItems)) {
    $orderItems = [];
}

$pageTitle = 'Order Confirmed – Arun Ice Creams';
require_once 'includes/header.php';
?>

<!-- Confirmation Page -->
<section class="section confirmation-section">
    <div class="container">

        <!-- Success Banner -->
        <div class="confirmation-banner">
            <span class="confirmation-icon">&#10004;</span>
            <h1>Order Confirmed!</h1>
            <p class="confirmation-subtitle">Thank you for ordering from Arun Ice Creams. Your treats are on the way!</p>
        </div>

        <!-- Order Details -->
        <div class="confirmation-card">

            <!-- Order info -->
            <div class="confirmation-header">
                <div class="confirmation-detail">
                    <span class="detail-label">Order ID</span>
                    <span class="detail-value">#<?= htmlspecialchars($order['id']) ?></span>
                </div>
                <div class="confirmation-detail">
                    <span class="detail-label">Customer</span>
                    <span class="detail-value"><?= htmlspecialchars($order['customer_name']) ?></span>
                </div>
                <div class="confirmation-detail">
                    <span class="detail-label">Email</span>
                    <span class="detail-value"><?= htmlspecialchars($order['customer_email']) ?></span>
                </div>
                <div class="confirmation-detail">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value"><?= htmlspecialchars($order['customer_phone']) ?></span>
                </div>
                <div class="confirmation-detail">
                    <span class="detail-label">Payment</span>
                    <span class="detail-value"><?= htmlspecialchars($order['payment_method']) ?></span>
                </div>
                <div class="confirmation-detail">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <span class="status-badge status-<?= htmlspecialchars($order['status'] ?? 'pending') ?>">
                            <?= htmlspecialchars(ucfirst($order['status'] ?? 'pending')) ?>
                        </span>
                    </span>
                </div>
                <div class="confirmation-detail" style="grid-column: 1 / -1;">
                    <span class="detail-label">Delivery Address</span>
                    <span class="detail-value"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></span>
                </div>
            </div>

            <!-- Order items -->
            <h2 class="confirmation-items-title">Order Items</h2>
            <div class="confirmation-items">
                <?php foreach ($orderItems as $item): ?>
                    <div class="confirmation-item">
                        <span class="item-name">
                            <?= htmlspecialchars($item['name']) ?>
                            <small>× <?= (int)$item['quantity'] ?></small>
                        </span>
                        <span class="item-price">₹<?= number_format((float)($item['line_total'] ?? ($item['price'] * $item['quantity'])), 2) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order total -->
            <div class="confirmation-total">
                <span>Grand Total</span>
                <span>₹<?= number_format((float)$order['total_amount'], 2) ?></span>
            </div>

        </div>

        <!-- Continue Shopping -->
        <div class="text-center" style="margin-top: 2rem;">
            <a href="index.php" class="btn-primary" style="display:inline-block; width:auto; padding: 0.75rem 2rem;">
                Continue Shopping
            </a>
        </div>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
