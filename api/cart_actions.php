<?php
// api/cart_actions.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

$conn = getDBConnection();
$action = $_GET['action'] ?? '';

// If not logged in
if (!isLoggedIn()) {
    if ($action === 'count') {
        echo json_encode(['count' => 0]);
        exit();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Please sign in to manage your cart.',
        'redirect' => 'login.php'
    ]);
    exit();
}

$user = currentUser();
$userId = $user['user_id'];

// Helper to get or create cart
function getUserCartId($conn, $userId) {
    $stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return $row['cart_id'];
    }
    // Create cart
    $ins = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
    $ins->bind_param("i", $userId);
    $ins->execute();
    return $ins->insert_id;
}

$cartId = getUserCartId($conn, $userId);

if ($action === 'count') {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    echo json_encode(['count' => (int)($res['total_items'] ?? 0)]);
    exit();
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $foodId = intval($_POST['food_id'] ?? 0);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));

    if ($foodId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid food item']);
        exit();
    }

    // Check availability
    $chk = $conn->prepare("SELECT availability FROM food_items WHERE food_id = ?");
    $chk->bind_param("i", $foodId);
    $chk->execute();
    $food = $chk->get_result()->fetch_assoc();
    if (!$food || $food['availability'] !== 'available') {
        echo json_encode(['success' => false, 'message' => 'Item is currently unavailable']);
        exit();
    }

    // Check if exists in cart
    $itemStmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND food_id = ?");
    $itemStmt->bind_param("ii", $cartId, $foodId);
    $itemStmt->execute();
    $existing = $itemStmt->get_result()->fetch_assoc();

    if ($existing) {
        $newQty = $existing['quantity'] + $quantity;
        $upd = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
        $upd->bind_param("ii", $newQty, $existing['cart_item_id']);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO cart_items (cart_id, food_id, quantity) VALUES (?, ?, ?)");
        $ins->bind_param("iii", $cartId, $foodId, $quantity);
        $ins->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Item added to cart!']);
    exit();
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartItemId = intval($_POST['cart_item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($quantity <= 0) {
        $del = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ? AND cart_id = ?");
        $del->bind_param("ii", $cartItemId, $cartId);
        $del->execute();
        echo json_encode(['success' => true, 'message' => 'Item removed']);
    } else {
        $upd = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND cart_id = ?");
        $upd->bind_param("iii", $quantity, $cartItemId, $cartId);
        $upd->execute();
        echo json_encode(['success' => true, 'message' => 'Quantity updated']);
    }
    exit();
}

if ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartItemId = intval($_POST['cart_item_id'] ?? 0);
    $del = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ? AND cart_id = ?");
    $del->bind_param("ii", $cartItemId, $cartId);
    $del->execute();
    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);

