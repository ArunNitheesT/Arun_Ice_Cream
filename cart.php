<?php
// Shopping cart page
$pageTitle = 'Your Cart – Arun Ice Creams';
require_once 'includes/header.php';

$checkoutHref = isCustomer()
    ? 'checkout.php'
    : 'login.php?redirect=' . urlencode('checkout.php');
$checkoutLabel = isCustomer() ? 'Proceed to Checkout' : 'Login to Checkout';
?>

<section class="page-hero">
    <div class="container">
        <h1>Your Cart</h1>
        <p>Review your scoops and proceed to checkout</p>
    </div>
</section>

<section class="section cart-section" style="padding-top: 2rem;">
    <div class="container">

        <!-- Cart content (hidden when cart is empty; JS removes hidden once loaded) -->
        <div id="cart-content" hidden>

        <div class="cart-table-wrap">
            <table class="cart-table" id="cart-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th class="hide-mobile">Unit Price</th>
                        <th>Qty</th>
                        <th>Line Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="cart-body">
                    <!-- Populated by cart.js -->
                    <tr>
                        <td colspan="6" style="text-align:center; padding:2rem; color:var(--color-muted);">
                            Loading cart…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cart summary bar -->
        <div class="cart-summary">
            <div class="cart-summary-left">
                <button id="btn-clear-cart" class="btn-outline btn-danger-outline">Clear Cart</button>
            </div>
            <div class="cart-summary-right">
                <span class="cart-subtotal-label">Subtotal:</span>
                <span class="cart-subtotal-amount" id="cart-subtotal">₹0.00</span>
            </div>
        </div>

        <!-- Cart actions -->
        <div class="cart-actions">
            <a href="products.php" class="btn-outline">Continue Shopping</a>
            <a href="<?= htmlspecialchars($checkoutHref) ?>" class="btn-primary is-disabled" id="btn-checkout" aria-disabled="true" tabindex="-1"><?= htmlspecialchars($checkoutLabel) ?></a>
        </div>

        </div><!-- /#cart-content -->

        <!-- Empty state (hidden by default) -->
        <div id="cart-empty" class="empty-state" hidden>
            <h2 class="empty-title">Your cart is empty</h2>
            <p>Looks like you haven't added any ice creams yet!</p>
            <a href="products.php" class="btn-primary" style="display:inline-block; width:auto; padding: 0.75rem 2rem; margin-top:1rem;">
                Browse Products
            </a>
        </div>

    </div>
</section>

<!-- Page-specific JS -->
<script src="assets/js/cart.js"></script>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'empty'): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    showToast('Your cart is empty. Add some ice creams first!', 'error');
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
