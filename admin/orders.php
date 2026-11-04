<?php
// admin/orders.php
$adminTitle = "Orders Management";
require_once 'header.php';

$conn = getDBConnection();

$msg = '';

// Update Order Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $newStatus = $_POST['order_status'] ?? '';
    $paymentStatus = $_POST['payment_status'] ?? '';

    $allowedStatus = ['Pending', 'Confirmed', 'Preparing', 'Ready for Delivery', 'Delivered', 'Cancelled'];
    $allowedPayment = ['Pending', 'Paid', 'Failed', 'Refunded'];

    if ($orderId > 0 && in_array($newStatus, $allowedStatus)) {
        $updOrder = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $updOrder->bind_param("si", $newStatus, $orderId);
        $updOrder->execute();

        if (in_array($paymentStatus, $allowedPayment)) {
            $updPay = $conn->prepare("UPDATE payments SET payment_status = ? WHERE order_id = ?");
            $updPay->bind_param("si", $paymentStatus, $orderId);
            $updPay->execute();
        }

        $msg = "Order #{$orderId} updated successfully!";
    }
}

// Fetch single order details if requested
$viewOrderId = intval($_GET['view_id'] ?? 0);
$selectedOrder = null;
$selectedOrderItems = [];

if ($viewOrderId > 0) {
    $q = "SELECT o.*, u.name as customer_name, u.email as customer_email, p.payment_status 
          FROM orders o 
          JOIN users u ON o.user_id = u.user_id 
          LEFT JOIN payments p ON o.order_id = p.order_id 
          WHERE o.order_id = ?";
    $stmt = $conn->prepare($q);
    $stmt->bind_param("i", $viewOrderId);
    $stmt->execute();
    $selectedOrder = $stmt->get_result()->fetch_assoc();

    if ($selectedOrder) {
        $iQ = "SELECT oi.*, f.food_name, f.image 
               FROM order_items oi 
               JOIN food_items f ON oi.food_id = f.food_id 
               WHERE oi.order_id = ?";
        $iStmt = $conn->prepare($iQ);
        $iStmt->bind_param("i", $viewOrderId);
        $iStmt->execute();
        $selectedOrderItems = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Filter orders
$statusFilter = $_GET['filter_status'] ?? '';
$whereClause = "";
$bindParams = [];
if (!empty($statusFilter)) {
    $whereClause = "WHERE o.order_status = ?";
    $bindParams[] = $statusFilter;
}

$ordersQuery = "SELECT o.*, u.name as customer_name, p.payment_status 
                FROM orders o 
                JOIN users u ON o.user_id = u.user_id 
                LEFT JOIN payments p ON o.order_id = p.order_id 
                $whereClause
                ORDER BY o.order_id DESC";

$stmt = $conn->prepare($ordersQuery);
if (!empty($bindParams)) {
    $stmt->bind_param("s", $statusFilter);
}
$stmt->execute();
$allOrders = $stmt->get_result();
?>

<?php if (!empty($msg)): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px;">
        <i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if ($selectedOrder): ?>
    <!-- Modal/Card for Viewing Selected Order Details -->
    <div class="admin-table-card" style="border: 2px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3><i class="fa-solid fa-receipt"></i> Order Details #<?php echo $selectedOrder['order_id']; ?></h3>
            <a href="orders.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-xmark"></i> Close Details</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 25px; background: #f8fafc; padding: 20px; border-radius: 8px;">
            <div>
                <strong>Customer:</strong> <?php echo htmlspecialchars($selectedOrder['customer_name']); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($selectedOrder['customer_email']); ?><br>
                <strong>Phone:</strong> <?php echo htmlspecialchars($selectedOrder['contact_number']); ?>
            </div>
            <div>
                <strong>Delivery Address:</strong><br>
                <?php echo nl2br(htmlspecialchars($selectedOrder['delivery_address'])); ?>
            </div>
            <div>
                <strong>Order Date:</strong> <?php echo date("M d, Y - h:i A", strtotime($selectedOrder['order_date'])); ?><br>
                <strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $selectedOrder['payment_method'])); ?><br>
                <strong>Order Total:</strong> <span style="color: var(--primary); font-size: 1.15rem; font-weight: bold;">$<?php echo number_format($selectedOrder['total_amount'], 2); ?></span>
            </div>
        </div>

        <!-- Status update form -->
        <form method="POST" action="orders.php?view_id=<?php echo $selectedOrder['order_id']; ?>" style="display: flex; gap: 15px; align-items: flex-end; background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?php echo $selectedOrder['order_id']; ?>">

            <div style="flex: 1;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Order Status</label>
                <select name="order_status" class="form-control">
                    <?php 
                    $statuses = ['Pending', 'Confirmed', 'Preparing', 'Ready for Delivery', 'Delivered', 'Cancelled'];
                    foreach ($statuses as $st): ?>
                        <option value="<?php echo $st; ?>" <?php echo ($selectedOrder['order_status'] === $st) ? 'selected' : ''; ?>>
                            <?php echo $st; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Payment Status</label>
                <select name="payment_status" class="form-control">
                    <?php 
                    $payStatuses = ['Pending', 'Paid', 'Failed', 'Refunded'];
                    foreach ($payStatuses as $pst): ?>
                        <option value="<?php echo $pst; ?>" <?php echo (($selectedOrder['payment_status'] ?? '') === $pst) ? 'selected' : ''; ?>>
                            <?php echo $pst; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="height: 44px;">
                <i class="fa-solid fa-save"></i> Update Order
            </button>
        </form>

        <h4 style="margin-bottom: 12px;">Ordered Items</h4>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($selectedOrderItems as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['food_name']); ?></strong></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Orders Master List -->
<div class="admin-table-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
        <h3 style="margin: 0;"><i class="fa-solid fa-list-check"></i> Manage All Customer Orders</h3>
        
        <!-- Status Filter -->
        <form method="GET" action="orders.php" style="display: flex; gap: 10px;">
            <select name="filter_status" class="form-control" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="Confirmed" <?php echo $statusFilter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="Preparing" <?php echo $statusFilter === 'Preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="Ready for Delivery" <?php echo $statusFilter === 'Ready for Delivery' ? 'selected' : ''; ?>>Ready for Delivery</option>
                <option value="Delivered" <?php echo $statusFilter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($allOrders && $allOrders->num_rows > 0): ?>
                    <?php while ($ord = $allOrders->fetch_assoc()): 
                        $statusClass = 'status-' . str_replace(' ', '_', $ord['order_status']);
                    ?>
                        <tr>
                            <td><strong>#<?php echo $ord['order_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                            <td><strong>$<?php echo number_format($ord['total_amount'], 2); ?></strong></td>
                            <td><?php echo ucwords(str_replace('_', ' ', $ord['payment_method'])); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($ord['payment_status'] ?? 'Pending'); ?>"><?php echo htmlspecialchars($ord['payment_status'] ?? 'Pending'); ?></span></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($ord['order_status']); ?></span></td>
                            <td><?php echo date("M d, Y H:i", strtotime($ord['order_date'])); ?></td>
                            <td>
                                <a href="orders.php?view_id=<?php echo $ord['order_id']; ?>" class="btn btn-outline btn-sm">
                                    <i class="fa-solid fa-pen-to-square"></i> Manage
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 25px;">No orders found matching the criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>

