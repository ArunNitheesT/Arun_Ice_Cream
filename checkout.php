<?php
require_once 'includes/auth.php';

// Guard: redirect to cart if cart is empty
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    header('Location: cart.php?msg=empty');
    exit;
}

requireLogin('customer');

$pageTitle = 'Checkout – Arun Ice Creams';
require_once 'includes/header.php';

// Fetch cart details for server-side summary
require_once 'db/init.php';
$pdo = getDB();

$cartItems = [];
$grandTotal = 0.0;

foreach ($_SESSION['cart'] as $productId => $item) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
    if ($product) {
        $qty = (int)$item['quantity'];
        $lineTotal = round((float)$product['price'] * $qty, 2);
        $grandTotal += $lineTotal;
        $cartItems[] = [
            'product_id'     => (int)$product['id'],
            'name'           => $product['name'],
            'price'          => (float)$product['price'],
            'quantity'       => $qty,
            'line_total'     => $lineTotal,
            'image_filename' => $product['image_filename'],
        ];
    }
}
$grandTotal = round($grandTotal, 2);

$prefillName  = '';
$prefillEmail = '';
if (isCustomer()) {
    $cu = currentUser();
    $prefillName  = $cu['name'] ?? '';
    $prefillEmail = $cu['email'] ?? '';
}
?>

<section class="page-hero">
    <div class="container">
        <h1>Checkout</h1>
        <p>Enter delivery details to complete your Arun Ice Creams order</p>
    </div>
</section>

<section class="section" style="padding-top: 2rem;">
    <div class="container">
        <div class="checkout-layout">

            <!-- Order Summary -->
            <div class="checkout-summary">
                <h2 class="checkout-section-title">Order Summary</h2>

                <div class="order-summary-items" id="order-summary-body">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="order-summary-item">
                            <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                            <span>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-summary-total" id="order-summary-total">
                    <span>Total</span>
                    <span style="color:var(--color-primary)">₹<?= number_format($grandTotal, 2) ?></span>
                </div>
            </div>

            <!-- Order Form -->
            <div class="checkout-form-wrap">
                <h2 class="checkout-section-title">Your Details</h2>

                <form id="checkout-form" novalidate>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" placeholder="e.g. Arun Kumar" required
                               value="<?= htmlspecialchars($prefillName) ?>">
                        <span class="error-msg" id="name-error"></span>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" placeholder="e.g. arun@example.com" required
                               value="<?= htmlspecialchars($prefillEmail) ?>">
                        <span class="error-msg" id="email-error"></span>
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" placeholder="e.g. +91 98765 43210" required>
                        <span class="error-msg" id="phone-error"></span>
                    </div>

                    <!-- Delivery Address -->
                    <div class="form-group">
                        <label for="address">Delivery Address <span class="required">*</span></label>
                        <textarea id="address" name="address" rows="3" placeholder="e.g. 12, Ice Cream Lane, Chennai" required></textarea>
                        <span class="error-msg" id="address-error"></span>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-group">
                        <label>Payment Method <span class="required">*</span></label>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="Cash on Delivery">
                                <span class="payment-label">Cash on Delivery</span>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="UPI">
                                <span class="payment-label">UPI</span>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="Card">
                                <span class="payment-label">Card</span>
                            </label>
                        </div>
                        <span class="error-msg" id="payment-error"></span>
                    </div>

                    <!-- Hidden cart items JSON (populated by checkout.js) -->
                    <input type="hidden" id="cart-items-json" name="items" value="">

                    <!-- Submit -->
                    <button type="submit" class="btn-primary btn-submit">
                        Place Order
                    </button>

                </form>
            </div>

        </div>
    </div>
</section>

<!-- Page-specific JS -->
<script src="assets/js/checkout.js"></script>

<?php require_once 'includes/footer.php'; ?>
