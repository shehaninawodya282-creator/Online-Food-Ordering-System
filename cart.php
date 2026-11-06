<?php
// cart.php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle = "Shopping Cart - QuickBite";
require_once 'includes/header.php';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get cart ID
$stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartRow = $stmt->get_result()->fetch_assoc();
$cartId = $cartRow ? $cartRow['cart_id'] : 0;

$cartItems = [];
$subtotal = 0;

if ($cartId > 0) {
    $q = "SELECT ci.cart_item_id, ci.quantity, f.food_id, f.food_name, f.price, f.image, f.availability 
          FROM cart_items ci
          JOIN food_items f ON ci.food_id = f.food_id
          WHERE ci.cart_id = ?";
    $itemStmt = $conn->prepare($q);
    $itemStmt->bind_param("i", $cartId);
    $itemStmt->execute();
    $res = $itemStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $cartItems[] = $row;
        if ($row['availability'] === 'available') {
            $subtotal += ($row['price'] * $row['quantity']);
        }
    }
}

$deliveryFee = $subtotal > 0 ? 150.00 : 0.00;
$grandTotal = $subtotal + $deliveryFee;
?>

<div class="container">
    <h1 style="font-size: 2rem; margin-bottom: 25px;"><i class="fa-solid fa-cart-shopping"></i> Your Shopping Cart</h1>

    <?php if (empty($cartItems)): ?>
        <div style="background: #fff; padding: 60px 20px; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <i class="fa-solid fa-basket-shopping" style="font-size: 3.5rem; color: #ced6e0; margin-bottom: 15px;"></i>
            <h3>Your cart is empty!</h3>
            <p style="color: var(--text-muted); margin-bottom: 25px;">Looks like you haven't added anything to your cart yet.</p>
            <a href="menu.php" class="btn btn-primary"><i class="fa-solid fa-utensils"></i> Browse Menu</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div style="overflow-x: auto;">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Dish</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): 
                            $lineTotal = $item['price'] * $item['quantity'];
                            $imgSrc = !empty($item['image']) ? "assets/images/" . $item['image'] : "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=150&q=80";
                        ?>
                            <tr id="cart-row-<?php echo $item['cart_item_id']; ?>">
                                <td>
                                    <div class="cart-item-info">
                                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="" class="cart-item-img" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=150&q=80'">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['food_name']); ?></strong>
                                            <?php if ($item['availability'] !== 'available'): ?>
                                                <div style="color: var(--danger); font-size: 0.8rem;">Currently Out of Stock</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>Rs.<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <div class="qty-control">
                                        <button class="qty-btn" onclick="changeQuantity(<?php echo $item['cart_item_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                        <span style="font-weight: 600; min-width: 20px; text-align: center;"><?php echo $item['quantity']; ?></span>
                                        <button class="qty-btn" onclick="changeQuantity(<?php echo $item['cart_item_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                    </div>
                                </td>
                                <td><strong>Rs.<?php echo number_format($lineTotal, 2); ?></strong></td>
                                <td>
                                    <button onclick="removeCartItem(<?php echo $item['cart_item_id']; ?>)" class="btn btn-outline btn-sm text-danger" title="Remove Item">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px;">
                    <a href="menu.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Continue Ordering</a>
                </div>
            </div>

            <div>
                <div class="order-summary-card">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Items Subtotal:</span>
                        <span>Rs.<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee:</span>
                        <span>Rs.<?php echo number_format($deliveryFee, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Grand Total:</span>
                        <span>Rs.<?php echo number_format($grandTotal, 2); ?></span>
                    </div>

                    <a href="checkout.php" class="btn btn-primary btn-block" style="margin-top: 25px; padding: 14px;">
                        Proceed to Checkout <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function changeQuantity(cartItemId, newQuantity) {
    const formData = new FormData();
    formData.append('cart_item_id', cartItemId);
    formData.append('quantity', newQuantity);

    fetch('api/cart_actions.php?action=update', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message, 'error');
        }
    });
}

function removeCartItem(cartItemId) {
    if (!confirm('Are you sure you want to remove this item?')) return;

    const formData = new FormData();
    formData.append('cart_item_id', cartItemId);

    fetch('api/cart_actions.php?action=remove', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message, 'error');
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>

