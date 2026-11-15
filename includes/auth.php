<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'user_id' => $_SESSION['user_id'],
        'name' => $_SESSION['name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'customer'
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /Online-Food-Ordering-System/login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
        header("Location: /Online-Food-Ordering-System/index.php");
        exit();
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

