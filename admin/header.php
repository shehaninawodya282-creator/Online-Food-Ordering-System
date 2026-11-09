<?php
// admin/header.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($adminTitle) ? htmlspecialchars($adminTitle) . " - Admin" : "QuickBite Admin"; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">

    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <i class="fa-solid fa-utensils" style="color: var(--primary);"></i>
            <span>QuickBite <small style="font-size: 0.65em; background: var(--primary); padding: 2px 6px; border-radius: 4px;">ADMIN</small></span>
        </div>

        <ul class="admin-menu">
            <li class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                <a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
            </li>
            <li class="<?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                <a href="orders.php"><i class="fa-solid fa-receipt"></i> Orders</a>
            </li>
            <li class="<?php echo $currentPage === 'foods.php' ? 'active' : ''; ?>">
                <a href="foods.php"><i class="fa-solid fa-burger"></i> Food Items</a>
            </li>
            <li class="<?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">
                <a href="categories.php"><i class="fa-solid fa-layer-group"></i> Categories</a>
            </li>
            <li class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                <a href="users.php"><i class="fa-solid fa-users"></i> Users</a>
            </li>
            <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 10px;">
                <a href="../index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Visit Storefront</a>
            </li>
            <li>
                <a href="../logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <div class="admin-content">
        <!-- Top Navigation -->
        <header class="admin-topbar">
            <div>
                <h4 style="margin: 0; font-size: 1.1rem;"><?php echo isset($adminTitle) ? htmlspecialchars($adminTitle) : 'Overview'; ?></h4>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 0.9rem;">
                    Hello, <strong><?php echo htmlspecialchars($user['name']); ?></strong> 
                    <span class="status-badge" style="background:#e2e8f0; color:#334155; margin-left: 6px;"><?php echo strtoupper($user['role']); ?></span>
                </span>
            </div>
        </header>

        <div id="toast-container" class="toast-container"></div>
        <div class="admin-container">

