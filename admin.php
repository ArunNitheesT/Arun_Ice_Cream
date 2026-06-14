<?php
require_once 'includes/auth.php';
require_once 'db/init.php';

requireLogin('admin');

$pdo = getDB();
$view = $_GET['view'] ?? 'orders';
$message = '';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status  = $_POST['status'] ?? '';

    if ($orderId > 0 && in_array($status, ['pending', 'delivered'], true)) {
        $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $orderId]);
        $message = 'Order #' . $orderId . ' updated.';
        $view = 'orders';
    }
}

$totalOrders    = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingOrders  = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$totalProducts  = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

$orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();
$products = $pdo->query('SELECT * FROM products ORDER BY category, name')->fetchAll();

$pageTitle = 'Admin – Arun Ice Creams';
require_once 'includes/header.php';
?>

<div class="admin-bar">
    <div class="container">
        <strong>Admin Panel</strong>
        <nav class="admin-nav">
            <a href="admin.php?view=orders" class="<?= $view === 'orders' ? 'active' : '' ?>">Orders</a>
            <a href="admin.php?view=products" class="<?= $view === 'products' ? 'active' : '' ?>">Products</a>
            <a href="index.php">Store</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</div>

<section class="section" style="padding-top: 0;">
    <div class="container">

        <?php if ($message !== ''): ?>
            <div class="auth-error" style="background:#d1fae5;color:#065f46;margin-bottom:1rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-num"><?= $totalOrders ?></div>
                <div class="stat-label">Total orders</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?= $pendingOrders ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?= $totalProducts ?></div>
                <div class="stat-label">Products</div>
            </div>
        </div>

        <?php if ($view === 'products'): ?>
            <div class="page-panel">
                <h2>Products</h2>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Featured</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?= (int)$p['id'] ?></td>
                                    <td><?= htmlspecialchars($p['name']) ?></td>
                                    <td><?= htmlspecialchars($p['category']) ?></td>
                                    <td>₹<?= number_format((float)$p['price'], 2) ?></td>
                                    <td><?= (int)$p['is_featured'] ? 'Yes' : 'No' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div class="page-panel">
                <h2>Orders</h2>
                <?php if (count($orders) === 0): ?>
                    <p style="color:var(--muted)">No orders yet.</p>
                <?php else: ?>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= (int)$order['id'] ?></td>
                                        <td>
                                            <?= htmlspecialchars($order['customer_name']) ?><br>
                                            <small style="color:var(--muted)"><?= htmlspecialchars($order['customer_email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                                        <td>₹<?= number_format((float)$order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= htmlspecialchars($order['status'] ?? 'pending') ?>">
                                                <?= htmlspecialchars(ucfirst($order['status'] ?? 'pending')) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars(date('d M Y', strtotime($order['created_at']))) ?></td>
                                        <td>
                                            <form method="post" style="display:flex;gap:0.35rem;align-items:center;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                                <select name="status" class="status-select">
                                                    <option value="pending" <?= ($order['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="delivered" <?= ($order['status'] ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                </select>
                                                <button type="submit" class="btn-sm">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
