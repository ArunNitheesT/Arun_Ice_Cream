<?php
require_once 'includes/auth.php';
require_once 'db/init.php';

if (isLoggedIn()) {
    $user = currentUser();
    header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'my-orders.php'));
    exit;
}

$error = '';
$activeTab = ($_GET['type'] ?? 'customer') === 'admin' ? 'admin' : 'customer';
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activeTab = ($_POST['login_type'] ?? 'customer') === 'admin' ? 'admin' : 'customer';
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email AND role = :role');
        $stmt->execute([':email' => $email, ':role' => $activeTab]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);

            if ($redirect !== '' && strpos($redirect, 'login.php') === false) {
                header('Location: ' . $redirect);
            } elseif ($activeTab === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: my-orders.php');
            }
            exit;
        }

        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Login – Arun Ice Creams';
require_once 'includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <h1>Login</h1>
        <p class="auth-sub">Sign in as a customer or admin.</p>

        <div class="auth-tabs">
            <a href="login.php?type=customer" class="auth-tab <?= $activeTab === 'customer' ? 'active' : '' ?>">Customer</a>
            <a href="login.php?type=admin" class="auth-tab <?= $activeTab === 'admin' ? 'active' : '' ?>">Admin</a>
        </div>

        <?php if ($error !== ''): ?>
            <div class="auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <input type="hidden" name="login_type" value="<?= htmlspecialchars($activeTab) ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <?php if ($activeTab === 'customer'): ?>
            <p class="auth-footer">
                New here? <a href="register.php">Create an account</a>
            </p>
        <?php endif; ?>

        <div class="auth-hint">
            <strong>Demo accounts</strong><br>
            Admin: <code>admin@arunicecreams.in</code> / <code>admin123</code><br>
            Customer: <code>customer@example.com</code> / <code>customer123</code>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
