<?php
require_once 'includes/auth.php';
require_once 'db/init.php';

if (isLoggedIn()) {
    header('Location: my-orders.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $pdo = getDB();

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO users (name, email, password_hash, role, created_at)
                VALUES (:name, :email, :password_hash, :customer, :created_at)
            ');
            $stmt->execute([
                ':name'          => $name,
                ':email'         => $email,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':customer'      => 'customer',
                ':created_at'    => date('c'),
            ]);

            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            loginUser($user);

            header('Location: my-orders.php');
            exit;
        }
    }
}

$pageTitle = 'Register – Arun Ice Creams';
require_once 'includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <h1>Create account</h1>
        <p class="auth-sub">Register to track your orders.</p>

        <?php if ($error !== ''): ?>
            <div class="auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="register.php">
            <div class="form-group">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" required
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-primary">Register</button>
        </form>

        <p class="auth-footer">
            Already have an account? <a href="login.php">Login</a>
        </p>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
