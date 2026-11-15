<?php
// orders.php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle = "My Orders - QuickBite";
require_once 'includes/header.php';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Check if review submitted
$reviewMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $foodId = intval($_POST['food_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if ($foodId > 0 && $rating >= 1 && $rating <= 5) {
        $revStmt = $conn->prepare("INSERT INTO reviews (user_id, food_id, rating, comment) VALUES (?, ?, ?, ?)");
        $revStmt->bind_param("iiis", $userId, $foodId, $rating, $comment);
        if ($revStmt->execute()) {
            $reviewMessage = "Thank you for your feedback!";
        }
    }
}

// Fetch all orders for this user
$ordersQuery = "SELECT o.*, p.payment_status 
                FROM orders o 
                LEFT JOIN payments p ON o.order_id = p.order_id 
                WHERE o.user_id = ? 
                ORDER BY o.order_id DESC";
$stmt = $conn->prepare($ordersQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$ordersResult = $stmt->get_result();

$successOrder = isset($_GET['success_order']) ? intval($_GET['success_order']) : 0;
?>

<div class="container">
    <?php if ($successOrder > 0): ?>
        <div style="background: #e6fffa; border: 1.5px solid #38b2ac; color: #234e52; padding: 20px; border-radius: var(--radius-md); margin-bottom: 30px; text-align: center;">
            <i class="fa-solid fa-circle-check" style="font-size: 2.5rem; color: #38b2ac; margin-bottom: 10px;"></i>
            <h2>Order Placed Successfully!</h2>
            <p>Your order <strong>#<?php echo $successOrder; ?></strong> has been received and is being processed by our kitchen.</p>
        </div>
    <?php endif; ?>

    <?php if (!empty($reviewMessage)): ?>
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px;">
            <i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($reviewMessage); ?>
        </div>
    <?php endif; ?>

    <h1 style="font-size: 2rem; margin-bottom: 25px;"><i class="fa-solid fa-receipt"></i> Order History & Tracking</h1>

    <?php if ($ordersResult->num_rows === 0): ?>
        <div style="background: #fff; padding: 60px 20px; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <i class="fa-solid fa-clock-rotate-left" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
            <h3>No orders found</h3>
            <p style="color: var(--text-muted); margin-bottom: 20px;">You haven't placed any orders yet.</p>
            <a href="menu.php" class="btn btn-primary"><i class="fa-solid fa-utensils"></i> Order Now</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php while ($order = $ordersResult->fetch_assoc()): 
                $orderId = $order['order_id'];
                
                // Fetch items for this order
                $itemQ = "SELECT oi.*, f.food_name, f.image 
                          FROM order_items oi 
                          JOIN food_items f ON oi.food_id = f.food_id 
                          WHERE oi.order_id = ?";
                $iStmt = $conn->prepare($itemQ);
                $iStmt->bind_param("i", $orderId);
                $iStmt->execute();
                $items = $iStmt->get_result();

                $badgeClass = 'status-' . str_replace(' ', '_', $order['order_status']);
            ?>
                <div style="background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 22px; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px;">
                        <div>
                            <span style="font-size: 1.15rem; font-weight: 700;">Order #<?php echo $order['order_id']; ?></span>
                            <span style="color: var(--text-muted); font-size: 0.85rem; margin-left: 10px;">
                                <i class="fa-regular fa-calendar"></i> <?php echo date("M d, Y - h:i A", strtotime($order['order_date'])); ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span class="status-badge <?php echo $badgeClass; ?>">
                                <i class="fa-solid fa-circle-dot"></i> <?php echo htmlspecialchars($order['order_status']); ?>
                            </span>
                            <span class="status-badge status-<?php echo htmlspecialchars($order['payment_status'] ?? 'Pending'); ?>">
                                Payment: <?php echo htmlspecialchars($order['payment_status'] ?? 'Pending'); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Items list -->
                    <div style="margin-bottom: 15px;">
                        <?php while ($item = $items->fetch_assoc()): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed var(--border-color); font-size: 0.95rem;">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['food_name']); ?></strong> &times; <?php echo $item['quantity']; ?>
                                    
                                    <!-- Quick Review modal trigger button if order delivered -->
                                    <?php if ($order['order_status'] === 'Delivered'): ?>
                                        <button onclick="openReviewModal(<?php echo $item['food_id']; ?>, '<?php echo addslashes($item['food_name']); ?>')" class="btn btn-outline btn-sm" style="margin-left: 10px; padding: 2px 8px; font-size: 0.75rem;">
                                            <i class="fa-regular fa-star"></i> Review
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <span>Rs.<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Order Footer -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px; font-size: 0.9rem;">
                        <div>
                            <div><strong>Delivery to:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></div>
                            <div><strong>Contact:</strong> <?php echo htmlspecialchars($order['contact_number']); ?> | <strong>Payment:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></div>
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary);">
                            Total: Rs.<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Simple Review Modal -->
<div id="reviewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 90%; max-width: 450px; padding: 25px; border-radius: var(--radius-md); position: relative;">
        <h3 style="margin-bottom: 10px;">Review <span id="reviewFoodName"></span></h3>
        <form method="POST" action="orders.php">
            <input type="hidden" name="action" value="submit_review">
            <input type="hidden" name="food_id" id="reviewFoodId">

            <div class="form-group">
                <label>Rating (1 to 5 Stars):</label>
                <select name="rating" class="form-control" required>
                    <option value="5">⭐⭐⭐⭐⭐ 5 - Excellent</option>
                    <option value="4">⭐⭐⭐⭐ 4 - Very Good</option>
                    <option value="3">⭐⭐⭐ 3 - Good</option>
                    <option value="2">⭐⭐ 2 - Fair</option>
                    <option value="1">⭐ 1 - Poor</option>
                </select>
            </div>

            <div class="form-group">
                <label>Comment:</label>
                <textarea name="comment" class="form-control" rows="3" placeholder="Share your experience with this dish..." required></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                <button type="button" class="btn btn-secondary" onclick="closeReviewModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(foodId, foodName) {
    document.getElementById('reviewFoodId').value = foodId;
    document.getElementById('reviewFoodName').innerText = foodName;
    document.getElementById('reviewModal').style.display = 'flex';
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>

