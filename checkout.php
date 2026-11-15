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

    $allowedPayments = [
        'cash_on_delivery',
        'card',
        'bank_transfer'
    ];

    if (!in_array($paymentMethod, $allowedPayments)) {
        $paymentMethod = 'cash_on_delivery';
    }

    if (empty($deliveryAddress) || empty($contactNumber)) {

        $error = 'Please enter delivery address and contact number.';

    } elseif ($paymentMethod === 'card') {

        $cardName = trim($_POST['card_name'] ?? '');
        $cardNumber = preg_replace(
            '/\s+/',
            '',
            trim($_POST['card_number'] ?? '')
        );
        $cardExpiry = trim($_POST['card_expiry'] ?? '');
        $cardCvv = trim($_POST['card_cvv'] ?? '');

        if (
            empty($cardName) ||
            empty($cardNumber) ||
            empty($cardExpiry) ||
            empty($cardCvv)
        ) {

            $error = 'Please fill out all card details.';

        } elseif (!preg_match('/^[0-9]{13,19}$/', $cardNumber)) {

            $error = 'Please enter a valid card number (13-19 digits).';

        } elseif (!preg_match(
            '/^(0[1-9]|1[0-2])\/([0-9]{2})$/',
            $cardExpiry
        )) {

            $error = 'Please enter a valid expiry date in MM/YY format.';

        } elseif (!preg_match('/^[0-9]{3,4}$/', $cardCvv)) {

            $error = 'Please enter a valid 3 or 4 digit CVV.';
        }

    } elseif ($paymentMethod === 'bank_transfer') {

        $bankName = trim($_POST['bank_name'] ?? '');
        $bankRef = trim($_POST['bank_reference'] ?? '');

        if (empty($bankName) || empty($bankRef)) {
            $error =
                'Please enter the bank name and transfer reference / transaction ID.';
        }
    }

    if (empty($error)) {

        // Begin transaction
        $conn->begin_transaction();

        try {

            // 1. Insert into orders table
            $orderStmt = $conn->prepare(
                "INSERT INTO orders
                (user_id, delivery_address, contact_number,
                total_amount, payment_method, order_status)
                VALUES (?, ?, ?, ?, ?, 'Pending')"
            );

            $orderStmt->bind_param(
                "issds",
                $userId,
                $deliveryAddress,
                $contactNumber,
                $grandTotal,
                $paymentMethod
            );

            $orderStmt->execute();

            $orderId = $orderStmt->insert_id;

            // 2. Insert into order_items
            $itemInsert = $conn->prepare(
                "INSERT INTO order_items
                (order_id, food_id, quantity, price)
                VALUES (?, ?, ?, ?)"
            );

            foreach ($cartItems as $item) {

                $itemInsert->bind_param(
                    "iiid",
                    $orderId,
                    $item['food_id'],
                    $item['quantity'],
                    $item['price']
                );

                $itemInsert->execute();
            }

            // 3. Insert into payments table
            $payStatus =
                ($paymentMethod === 'cash_on_delivery')
                ? 'Pending'
                : 'Paid';

            $payStmt = $conn->prepare(
                "INSERT INTO payments
                (order_id, payment_method,
                payment_amount, payment_status)
                VALUES (?, ?, ?, ?)"
            );

            $payStmt->bind_param(
                "isds",
                $orderId,
                $paymentMethod,
                $grandTotal,
                $payStatus
            );

            $payStmt->execute();

            // 4. Empty user cart
            $emptyCart = $conn->prepare(
                "DELETE FROM cart_items WHERE cart_id = ?"
            );

            $emptyCart->bind_param("i", $cartId);
            $emptyCart->execute();

            $conn->commit();

            // Redirect to order confirmation/tracking
            header("Location: orders.php?success_order=" . $orderId);
            exit();

        } catch (Exception $e) {

            $conn->rollback();

            $error =
                'Failed to place order: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Checkout - QuickBite";

require_once 'includes/header.php';
?>

<div class="container">

    <h1 style="font-size: 2rem; margin-bottom: 25px;">
        <i class="fa-solid fa-credit-card"></i>
        Complete Checkout
    </h1>

    <?php if (!empty($error)): ?>

        <div style="
            background: #ffeaa7;
            color: #d63031;
            padding: 12px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
        ">

            <i class="fa-solid fa-triangle-exclamation"></i>

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>

    <div class="cart-layout">

        <!-- Delivery and Payment Information -->
        <div style="
            background: #fff;
            padding: 30px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        ">

            <form action="checkout.php"
                  method="POST"
                  id="checkout-form">

                <h3 style="
                    margin-bottom: 20px;
                    font-size: 1.25rem;
                ">

                    <i class="fa-solid fa-map-location-dot"></i>
                    Delivery Details

                </h3>

                <div class="form-group">

                    <label for="contact_number">
                        Recipient Contact Number *
                    </label>

                    <input
                        type="tel"
                        id="contact_number"
                        name="contact_number"
                        class="form-control"
                        placeholder="e.g. 0771234567"
                        required
                        value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>"
                    >

                </div>

                <div class="form-group">

                    <label for="delivery_address">
                        Complete Delivery Address *
                    </label>

                    <textarea
                        id="delivery_address"
                        name="delivery_address"
                        class="form-control"
                        rows="3"
                        placeholder="Apartment / House No, Street Name, City, Landmark"
                        required
                    ></textarea>

                </div>

                <h3 style="
                    margin: 30px 0 15px;
                    font-size: 1.25rem;
                ">

                    <i class="fa-solid fa-money-check-dollar"></i>
                    Payment Method

                </h3>

                <div style="
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    margin-bottom: 25px;
                ">

                    <!-- Cash on Delivery -->
                    <label style="
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 14px;
                        border: 1.5px solid var(--border-color);
                        border-radius: var(--radius-sm);
                        cursor: pointer;
                        transition: var(--transition);
                    ">

                        <input
                            type="radio"
                            name="payment_method"
                            value="cash_on_delivery"
                            checked
                        >

                        <div>

                            <strong>
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                Cash On Delivery (COD)
                            </strong>

                            <div style="
                                font-size: 0.85rem;
                                color: var(--text-muted);
                            ">

                                Pay in cash directly when your food arrives.

                            </div>

                        </div>

                    </label>

                    <!-- Card -->
                    <label style="
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 14px;
                        border: 1.5px solid var(--border-color);
                        border-radius: var(--radius-sm);
                        cursor: pointer;
                        transition: var(--transition);
                    ">

                        <input
                            type="radio"
                            name="payment_method"
                            value="card"
                        >

                        <div>

                            <strong>
                                <i class="fa-regular fa-credit-card"></i>
                                Credit / Debit Card
                            </strong>

                            <div style="
                                font-size: 0.85rem;
                                color: var(--text-muted);
                            ">

                                Pay securely using Visa, MasterCard, or American Express.

                            </div>

                        </div>

                    </label>

                    <!-- Bank Transfer -->
                    <label style="
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 14px;
                        border: 1.5px solid var(--border-color);
                        border-radius: var(--radius-sm);
                        cursor: pointer;
                        transition: var(--transition);
                    ">

                        <input
                            type="radio"
                            name="payment_method"
                            value="bank_transfer"
                        >

                        <div>

                            <strong>
                                <i class="fa-solid fa-building-columns"></i>
                                Bank Transfer
                            </strong>

                            <div style="
                                font-size: 0.85rem;
                                color: var(--text-muted);
                            ">

                                Direct bank transfer / wire payment.

                            </div>

                        </div>

                    </label>

                </div>

                <!-- Card Details Section -->
                <div
                    id="card-details-section"
                    style="
                        display: none;
                        background: #fafafa;
                        border: 1.5px solid var(--border-color);
                        border-radius: var(--radius-sm);
                        padding: 18px;
                        margin-bottom: 25px;
                    "
                >

                    <div style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 15px;
                    ">

                        <h4 style="
                            margin: 0;
                            font-size: 1rem;
                            color: var(--text-dark);
                        ">

                            <i class="fa-regular fa-credit-card"
                               style="color: var(--primary);">
                            </i>

                            Enter Card Details

                        </h4>

                        <div style="
                            font-size: 1.25rem;
                            color: #666;
                            display: flex;
                            gap: 8px;
                        ">

                            <i class="fa-brands fa-cc-visa"></i>
                            <i class="fa-brands fa-cc-mastercard"></i>
                            <i class="fa-brands fa-cc-amex"></i>

                        </div>

                    </div>

                    <div class="form-group"
                         style="margin-bottom: 14px;">

                        <label for="card_name"
                               style="
                               font-size: 0.9rem;
                               font-weight: 500;
                               ">

                            Cardholder Name *

                        </label>

                        <input
                            type="text"
                            id="card_name"
                            name="card_name"
                            class="form-control"
                            placeholder="e.g. John Doe"
                        >

                    </div>

                    <div class="form-group"
                         style="margin-bottom: 14px;">

                        <label for="card_number"
                               style="
                               font-size: 0.9rem;
                               font-weight: 500;
                               ">

                            Card Number *

                        </label>

                        <div style="position: relative;">

                            <input
                                type="text"
                                id="card_number"
                                name="card_number"
                                class="form-control"
                                placeholder="1234 5678 9012 3456"
                                maxlength="19"
                            >

                            <i class="fa-solid fa-lock"
                               style="
                               position: absolute;
                               right: 12px;
                               top: 50%;
                               transform: translateY(-50%);
                               color: #999;
                               ">
                            </i>

                        </div>

                    </div>

                    <div style="
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 14px;
                    ">

                        <div class="form-group"
                             style="margin-bottom: 0;">

                            <label for="card_expiry"
                                   style="
                                   font-size: 0.9rem;
                                   font-weight: 500;
                                   ">

                                Expiry Date *

                            </label>

                            <input
                                type="text"
                                id="card_expiry"
                                name="card_expiry"
                                class="form-control"
                                placeholder="MM/YY"
                                maxlength="5"
                            >

                        </div>

                        <div class="form-group"
                             style="margin-bottom: 0;">

                            <label for="card_cvv"
                                   style="
                                   font-size: 0.9rem;
                                   font-weight: 500;
                                   ">

                                CVV / CVC *

                            </label>

                            <input
                                type="password"
                                id="card_cvv"
                                name="card_cvv"
                                class="form-control"
                                placeholder="123"
                                maxlength="4"
                            >

                        </div>

                    </div>

                </div>

                <!-- Bank Transfer Details Section -->
                <div
                    id="bank-details-section"
                    style="
                        display: none;
                        background: #fafafa;
                        border: 1.5px solid var(--border-color);
                        border-radius: var(--radius-sm);
                        padding: 18px;
                        margin-bottom: 25px;
                    "
                >

                    <div style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 12px;
                    ">

                        <h4 style="
                            margin: 0;
                            font-size: 1rem;
                            color: var(--text-dark);
                        ">

                            <i class="fa-solid fa-building-columns"
                               style="color: var(--primary);">
                            </i>

                            Bank Transfer Details

                        </h4>

                    </div>

                    <!-- Store Beneficiary Account Info -->
                    <div style="
                        background: #f0f4f8;
                        border-left: 4px solid var(--primary);
                        padding: 12px 14px;
                        border-radius: 4px;
                        font-size: 0.88rem;
                        margin-bottom: 15px;
                        color: #2d3748;
                    ">

                        <div style="
                            font-weight: 600;
                            margin-bottom: 4px;
                        ">

                            Transfer funds to our account:

                        </div>

                        <div>
                            <strong>Bank:</strong>
                            Commercial Bank
                        </div>

                        <div>
                            <strong>Account Name:</strong>
                            QuickBite Foods Ltd
                        </div>

                        <div>
                            <strong>Account Number:</strong>
                            800123456789
                        </div>

                        <div>
                            <strong>Branch:</strong>
                            Main Branch
                        </div>

                    </div>

                    <div class="form-group"
                         style="margin-bottom: 14px;">

                        <label for="bank_name"
                               style="
                               font-size: 0.9rem;
                               font-weight: 500;
                               ">

                            Your Sender Bank Name *

                        </label>

                        <input
                            type="text"
                            id="bank_name"
                            name="bank_name"
                            class="form-control"
                            placeholder="e.g. Bank of Ceylon, HNB, Chase"
                        >

                    </div>

                    <div class="form-group"
                         style="margin-bottom: 0;">

                        <label for="bank_reference"
                               style="
                               font-size: 0.9rem;
                               font-weight: 500;
                               ">

                            Transfer / Transaction Reference Number *

                        </label>

                        <input
                            type="text"
                            id="bank_reference"
                            name="bank_reference"
                            class="form-control"
                            placeholder="e.g. TXN-98421035"
                        >

                        <small style="
                            color: var(--text-muted);
                            font-size: 0.8rem;
                            display: block;
                            margin-top: 4px;
                        ">

                            Enter the transaction or deposit slip reference
                            number from your banking receipt.

                        </small>

                    </div>

                </div>

                <!-- Place Order Button -->
                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                    style="
                        padding: 14px;
                        font-size: 1.05rem;
                    "
                >

                    <i class="fa-solid fa-check-circle"></i>

                    Place Order
                    (Rs <?php echo number_format($grandTotal, 2); ?>)

                </button>

            </form>

        </div>

        <!-- Order Summary -->
        <div>

            <div class="order-summary-card">

                <h3>
                    Order Review
                    (<?php echo count($cartItems); ?> items)
                </h3>

                <div style="
                    max-height: 250px;
                    overflow-y: auto;
                    margin-bottom: 15px;
                ">

                    <?php foreach ($cartItems as $item): ?>

                        <div style="
                            display: flex;
                            justify-content: space-between;
                            font-size: 0.9rem;
                            margin-bottom: 10px;
                        ">

                            <span>

                                <?php echo htmlspecialchars(
                                    $item['food_name']
                                ); ?>

                                &times;

                                <?php echo $item['quantity']; ?>

                            </span>

                            <span>

                                Rs <?php echo number_format(
                                    $item['price'] * $item['quantity'],
                                    2
                                ); ?>

                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

                <hr style="
                    border: none;
                    border-top: 1px solid var(--border-color);
                    margin: 15px 0;
                ">

                <!-- Subtotal -->
                <div class="summary-row">

                    <span>Subtotal:</span>

                    <span>
                        Rs <?php echo number_format(
                            $subtotal,
                            2
                        ); ?>
                    </span>

                </div>

                <!-- Delivery Fee -->
                <div class="summary-row">

                    <span>Delivery Fee:</span>

                    <span>
                        Rs <?php echo number_format(
                            $deliveryFee,
                            2
                        ); ?>
                    </span>

                </div>

                <!-- Grand Total -->
                <div class="summary-row total">

                    <span>Grand Total:</span>

                    <span>
                        Rs <?php echo number_format(
                            $grandTotal,
                            2
                        ); ?>
                    </span>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const paymentRadios =
        document.querySelectorAll(
            'input[name="payment_method"]'
        );

    const cardSection =
        document.getElementById(
            'card-details-section'
        );

    const cardInputs =
        cardSection.querySelectorAll('input');

    const bankSection =
        document.getElementById(
            'bank-details-section'
        );

    const bankInputs =
        bankSection.querySelectorAll('input');

    const cardNumberInput =
        document.getElementById(
            'card_number'
        );

    const cardExpiryInput =
        document.getElementById(
            'card_expiry'
        );


    function togglePaymentSections() {

        const selected =
            document.querySelector(
                'input[name="payment_method"]:checked'
            );

        const method =
            selected ? selected.value : '';


        // Handle Card section
        if (method === 'card') {

            cardSection.style.display =
                'block';

            cardInputs.forEach(function (input) {
                input.setAttribute(
                    'required',
                    'required'
                );
            });

        } else {

            cardSection.style.display =
                'none';

            cardInputs.forEach(function (input) {
                input.removeAttribute(
                    'required'
                );
            });

        }


        // Handle Bank Transfer section
        if (method === 'bank_transfer') {

            bankSection.style.display =
                'block';

            bankInputs.forEach(function (input) {
                input.setAttribute(
                    'required',
                    'required'
                );
            });

        } else {

            bankSection.style.display =
                'none';

            bankInputs.forEach(function (input) {
                input.removeAttribute(
                    'required'
                );
            });

        }

    }


    // Payment method change
    paymentRadios.forEach(function (radio) {

        radio.addEventListener(
            'change',
            togglePaymentSections
        );

    });


    // Format card number
    // Example: 1234 5678 9012 3456
    if (cardNumberInput) {

        cardNumberInput.addEventListener(
            'input',
            function (e) {

                let value =
                    e.target.value.replace(
                        /\D/g,
                        ''
                    );

                if (value.length > 16) {
                    value =
                        value.slice(0, 16);
                }

                let formatted =
                    value.match(/.{1,4}/g)
                    ?.join(' ') || value;

                e.target.value =
                    formatted;

            }
        );

    }


    // Format expiry date
    // Example: 12/28
    if (cardExpiryInput) {

        cardExpiryInput.addEventListener(
            'input',
            function (e) {

                let value =
                    e.target.value.replace(
                        /\D/g,
                        ''
                    );

                if (value.length > 4) {
                    value =
                        value.slice(0, 4);
                }

                if (value.length >= 2) {

                    e.target.value =
                        value.slice(0, 2)
                        + '/'
                        + value.slice(2);

                } else {

                    e.target.value =
                        value;

                }

            }
        );

    }


    // Initialize payment sections
    togglePaymentSections();

});

</script>

<?php
require_once 'includes/footer.php';
?>
