<?php
/**
 * Session-based authentication helpers.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool {
    return currentUser() !== null;
}

function isAdmin(): bool {
    $user = currentUser();
    return $user && ($user['role'] ?? '') === 'admin';
}

function isCustomer(): bool {
    $user = currentUser();
    return $user && ($user['role'] ?? '') === 'customer';
}

function requireLogin(?string $role = null): void {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'index.php'));
        exit;
    }
    if ($role !== null && (currentUser()['role'] ?? '') !== $role) {
        header('Location: index.php');
        exit;
    }
}

function loginUser(array $user): void {
    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
}

function logoutUser(): void {
    unset($_SESSION['user']);
}
