<?php
// admin/index.php
$adminTitle = "Dashboard Overview";
require_once 'header.php';

$conn = getDBConnection();

// Metrics
$totalOrders = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$totalRevenue = $conn->query("SELECT SUM(total_amount) as s FROM orders WHERE order_status != 'Cancelled'")->fetch_assoc()['s'] ?? 0;
$totalFoods = $conn->query("SELECT COUNT(*) as c FROM food_items")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'customer'")->fetch_assoc()['c'];

// Recent Orders
$recentOrders = $conn->query("SELECT o.*, u.name as customer_name 
                              FROM orders o 
                              JOIN users u ON o.user_id = u.user_id 
                              ORDER BY o.order_id DESC LIMIT 6");
?>

<!-- Statistics Overview -->
<div class="stat-cards-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-receipt"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($totalOrders); ?></h3>
            <p>Total Orders</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3>$<?php echo number_format($totalRevenue, 2); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fa-solid fa-burger"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($totalFoods); ?></h3>
            <p>Menu Dishes</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($totalUsers); ?></h3>
            <p>Customers</p>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="admin-table-card">
    <h3>
        <span><i class="fa-solid fa-clock-rotate-left"></i> Recent Orders</span>
        <a href="orders.php" class="btn btn-outline btn-sm">View All Orders</a>
    </h3>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
                    <?php while ($ord = $recentOrders->fetch_assoc()): 
                        $badgeClass = 'status-' . str_replace(' ', '_', $ord['order_status']);
                    ?>
                        <tr>
                            <td><strong>#<?php echo $ord['order_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($ord['contact_number']); ?></td>
                            <td><strong>$<?php echo number_format($ord['total_amount'], 2); ?></strong></td>
                            <td><?php echo ucwords(str_replace('_', ' ', $ord['payment_method'])); ?></td>
                            <td><span class="status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($ord['order_status']); ?></span></td>
                            <td><?php echo date("M d, H:i", strtotime($ord['order_date'])); ?></td>
                            <td>
                                <a href="orders.php?view_id=<?php echo $ord['order_id']; ?>" class="btn btn-outline btn-sm">
                                    <i class="fa-solid fa-eye"></i> Details
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 25px;">No orders found yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>

