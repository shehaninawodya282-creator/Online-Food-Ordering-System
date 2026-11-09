<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['name'] ?? '';
$userRole = $_SESSION['role'] ?? 'customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " - QuickBite" : "QuickBite - Online Food Ordering"; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google Fonts & Font Awesome for icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fa-solid fa-utensils"></i> Quick<span>Bite</span>
            </a>

            <div class="menu-toggle" id="mobile-menu-toggle">
                <i class="fa-solid fa-bars"></i>
            </div>

            <nav class="nav-links" id="nav-links">
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'active' : ''; ?>">Menu</a>
                
                <?php if ($isLoggedIn): ?>
                    <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">My Orders</a>
                    <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                        <a href="admin/index.php" class="admin-badge"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="nav-actions">
                <a href="cart.php" class="cart-btn" title="View Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-badge" id="cartCount">0</span>
                </a>

                <?php if ($isLoggedIn): ?>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <i class="fa-regular fa-user"></i>
                            <span class="user-name"><?php echo htmlspecialchars(explode(' ', $userName)[0]); ?></span>
                            <i class="fa-solid fa-chevron-down arrow-icon"></i>
                        </button>
                        <div class="dropdown-content">
                            <span class="dropdown-header">Signed in as <strong><?php echo htmlspecialchars($userName); ?></strong> (<?php echo htmlspecialchars($userRole); ?>)</span>
                            <hr>
                            <a href="orders.php"><i class="fa-solid fa-receipt"></i> My Orders</a>
                            <a href="logout.php" class="text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div id="toast-container" class="toast-container"></div>
    <main class="main-wrapper">

