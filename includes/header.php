<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';

$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['quantity'] ?? 0);
    }
}

$pageTitle = $pageTitle ?? 'Arun Ice Creams';
$currentPage = basename($_SERVER['PHP_SELF']);
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Arun Ice Creams – Handcrafted ice creams from Chennai. Browse cones, cups, bars, sundaes and order online.">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
    <nav class="navbar">
        <a href="index.php" class="nav-logo">
            <span class="logo-mark">A</span>
            <span>
                <span class="logo-text">Arun Ice Creams</span>
                <span class="logo-tagline">Chennai since 1995</span>
            </span>
        </a>

        <input type="checkbox" id="nav-toggle" class="nav-toggle">
        <label for="nav-toggle" class="nav-hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </label>

        <ul class="nav-links">
            <li>
                <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
            </li>
            <li>
                <a href="products.php" class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>">Products</a>
            </li>
            <li>
                <a href="cart.php" class="nav-link nav-cart <?= $currentPage === 'cart.php' ? 'active' : '' ?>">
                    Cart
                    <span id="cart-badge" class="cart-badge<?= $cartCount === 0 ? ' hidden' : '' ?>"><?= $cartCount ?></span>
                </a>
            </li>
            <?php if ($user && $user['role'] === 'customer'): ?>
                <li>
                    <a href="my-orders.php" class="nav-link <?= $currentPage === 'my-orders.php' ? 'active' : '' ?>">My Orders</a>
                </li>
            <?php endif; ?>
            <?php if ($user && $user['role'] === 'admin'): ?>
                <li>
                    <a href="admin.php" class="nav-link <?= $currentPage === 'admin.php' ? 'active' : '' ?>">Admin</a>
                </li>
            <?php endif; ?>
            <?php if ($user): ?>
                <li>
                    <a href="logout.php" class="nav-link">Logout</a>
                </li>
            <?php else: ?>
                <li>
                    <a href="login.php" class="nav-link <?= in_array($currentPage, ['login.php', 'register.php'], true) ? 'active' : '' ?>">Login</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main>
