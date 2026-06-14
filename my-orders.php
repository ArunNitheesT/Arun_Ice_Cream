<?php
require_once 'includes/auth.php';
require_once 'db/init.php';

requireLogin('customer');

$user = currentUser();
$pdo = getDB();

$stmt = $pdo->prepare('
    SELECT * FROM orders
    WHERE user_id = :user_id OR customer_email = :email
    ORDER BY created_at DESC
');
$stmt->execute([
    ':user_id' => $user['id'],
    ':email'   => $user['email'],
]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders – Arun Ice Creams';
require_once 'includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>My Orders</h1>
        <p>Welcome, <?= htmlspecialchars($user['name']) ?></p>
    </div>
</section>

<section class="section" style="padding-top: 1.5rem;">
    <div class="container">

        <?php if (count($orders) === 0): ?>
            <div class="empty-state">
                <h2 class="empty-title">No orders yet</h2>
                <p>Browse products and place your first order.</p>
                <a href="products.php" class="btn-primary" style="width:auto;display:inline-block;margin-top:1rem;">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order):
                            $items = json_decode($order['items_json'], true) ?: [];
                            $itemCount = array_sum(array_column($items, 'quantity'));
                        ?>
                            <tr>
                                <td>#<?= (int)$order['id'] ?></td>
                                <td><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?></td>
                                <td>₹<?= number_format((float)$order['total_amount'], 2) ?></td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($order['status'] ?? 'pending') ?>">
                                        <?= htmlspecialchars(ucfirst($order['status'] ?? 'pending')) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($order['created_at']))) ?></td>
                                <td>
                                    <a href="confirmation.php?order_id=<?= (int)$order['id'] ?>">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
