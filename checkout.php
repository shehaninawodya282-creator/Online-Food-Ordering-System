<?php
// checkout.php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Fetch user info for pre-filling
$userStmt = $conn->prepare("SELECT name, email, phone FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

// Fetch cart items
$stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartRow = $stmt->get_result()->fetch_assoc();
$cartId = $cartRow ? $cartRow['cart_id'] : 0;

$cartItems = [];
$subtotal = 0;

if ($cartId > 0) {
    $q = "SELECT ci.quantity, f.food_id, f.food_name, f.price, f.availability 
          FROM cart_items ci
          JOIN food_items f ON ci.food_id = f.food_id
          WHERE ci.cart_id = ?";
    $itemStmt = $conn->prepare($q);
    $itemStmt->bind_param("i", $cartId);
    $itemStmt->execute();
    $res = $itemStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if ($row['availability'] === 'available') {
            $cartItems[] = $row;
            $subtotal += ($row['price'] * $row['quantity']);
        }
    }
}

// Redirect if cart is empty
if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

$deliveryFee = 150.00;
$grandTotal = $subtotal + $deliveryFee;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cash_on_delivery';

    $allowedPayments = ['cash_on_delivery', 'card', 'bank_transfer'];
    if (!in_array($paymentMethod, $allowedPayments)) {
        $paymentMethod = 'cash_on_delivery';
    }

    if (empty($deliveryAddress) || empty($contactNumber)) {
        $error = 'Please enter delivery address and contact number.';
    } else {
        // Begin transaction
        $conn->begin_transaction();
        try {
            // 1. Insert into orders table
            $orderStmt = $conn->prepare("INSERT INTO orders (user_id, delivery_address, contact_number, total_amount, payment_method, order_status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $orderStmt->bind_param("issds", $userId, $deliveryAddress, $contactNumber, $grandTotal, $paymentMethod);
            $orderStmt->execute();
            $orderId = $orderStmt->insert_id;

            // 2. Insert into order_items
            $itemInsert = $conn->prepare("INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $itemInsert->bind_param("iiid", $orderId, $item['food_id'], $item['quantity'], $item['price']);
                $itemInsert->execute();
            }

            // 3. Insert into payments table
            $payStatus = ($paymentMethod === 'cash_on_delivery') ? 'Pending' : 'Paid';
            $payStmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, payment_amount, payment_status) VALUES (?, ?, ?, ?)");
            $payStmt->bind_param("isds", $orderId, $paymentMethod, $grandTotal, $payStatus);
            $payStmt->execute();

            // 4. Empty user cart
            $emptyCart = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $emptyCart->bind_param("i", $cartId);
            $emptyCart->execute();

            $conn->commit();

            // Redirect to order confirmation/tracking
            header("Location: orders.php?success_order=" . $orderId);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Failed to place order: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Checkout - QuickBite";
require_once 'includes/header.php';
?>

<div class="container">
    <h1 style="font-size: 2rem; margin-bottom: 25px;"><i class="fa-solid fa-credit-card"></i> Complete Checkout</h1>

    <?php if (!empty($error)): ?>
        <div style="background: #ffeaa7; color: #d63031; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="cart-layout">
        <!-- Delivery and Payment Information -->
        <div style="background: #fff; padding: 30px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <form action="checkout.php" method="POST" id="checkout-form">
                <h3 style="margin-bottom: 20px; font-size: 1.25rem;"><i class="fa-solid fa-map-location-dot"></i> Delivery Details</h3>
                
                <div class="form-group">
                    <label for="contact_number">Recipient Contact Number *</label>
                    <input type="tel" id="contact_number" name="contact_number" class="form-control" placeholder="e.g. 0771234567" required value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="delivery_address">Complete Delivery Address *</label>
                    <textarea id="delivery_address" name="delivery_address" class="form-control" rows="3" placeholder="Apartment / House No, Street Name, City, Landmark" required></textarea>
                </div>

                <h3 style="margin: 30px 0 15px; font-size: 1.25rem;"><i class="fa-solid fa-money-check-dollar"></i> Payment Method</h3>
                
                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px;">
                    <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1.5px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer; transition: var(--transition);">
                        <input type="radio" name="payment_method" value="cash_on_delivery" checked>
                        <div>
                            <strong><i class="fa-solid fa-hand-holding-dollar"></i> Cash On Delivery (COD)</strong>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">Pay in cash directly when your food arrives.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1.5px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer; transition: var(--transition);">
                        <input type="radio" name="payment_method" value="card">
                        <div>
                            <strong><i class="fa-regular fa-credit-card"></i> Credit / Debit Card</strong>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">Pay securely using Visa, MasterCard, or American Express.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1.5px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer; transition: var(--transition);">
                        <input type="radio" name="payment_method" value="bank_transfer">
                        <div>
                            <strong><i class="fa-solid fa-building-columns"></i> Bank Transfer</strong>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">Direct bank transfer / wire payment.</div>
                        </div>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 14px; font-size: 1.05rem;">
                    <i class="fa-solid fa-check-circle"></i> Place Order ($<?php echo number_format($grandTotal, 2); ?>)
                </button>
            </form>
        </div>

        <!-- Order Summary Side Card -->
        <div>
            <div class="order-summary-card">
                <h3>Order Review (<?php echo count($cartItems); ?> items)</h3>
                
                <div style="max-height: 250px; overflow-y: auto; margin-bottom: 15px;">
                    <?php foreach ($cartItems as $item): ?>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 10px;">
                            <span><?php echo htmlspecialchars($item['food_name']); ?> &times; <?php echo $item['quantity']; ?></span>
                            <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr style="border: none; border-top: 1px solid var(--border-color); margin: 15px 0;">

                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee:</span>
                    <span>$<?php echo number_format($deliveryFee, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Grand Total:</span>
                    <span>$<?php echo number_format($grandTotal, 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

