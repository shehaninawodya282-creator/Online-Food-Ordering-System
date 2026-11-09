<?php
// admin/foods.php
$adminTitle = "Menu & Food Items";
require_once 'header.php';

$conn = getDBConnection();

$msg = '';
$error = '';

// Handle Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $foodName = trim($_POST['food_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $availability = $_POST['availability'] === 'unavailable' ? 'unavailable' : 'available';

    if (empty($foodName) || $categoryId <= 0 || $price <= 0) {
        $error = "Please fill in all required fields properly.";
    } else {
        // Image handling: allow URL / filename or uploaded image
        $imageName = $_POST['existing_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['image']['tmp_name'];
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetDir = "../assets/images/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            if (move_uploaded_file($fileTmp, $targetDir . $fileName)) {
                $imageName = $fileName;
            }
        } elseif (!empty($_POST['image_url'])) {
            $imageName = trim($_POST['image_url']);
        }

        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO food_items (food_name, category_id, description, price, image, availability) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisdss", $foodName, $categoryId, $description, $price, $imageName, $availability);
            if ($stmt->execute()) {
                $msg = "Food item created successfully!";
            } else {
                $error = "Failed to add food item: " . $conn->error;
            }
        } elseif ($action === 'update') {
            $foodId = intval($_POST['food_id'] ?? 0);
            $stmt = $conn->prepare("UPDATE food_items SET food_name = ?, category_id = ?, description = ?, price = ?, image = ?, availability = ? WHERE food_id = ?");
            $stmt->bind_param("sisdssi", $foodName, $categoryId, $description, $price, $imageName, $availability, $foodId);
            if ($stmt->execute()) {
                $msg = "Food item updated successfully!";
            } else {
                $error = "Failed to update food item: " . $conn->error;
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    // Check if food item is in order items (restricted by foreign key)
    $chk = $conn->prepare("SELECT COUNT(*) as c FROM order_items WHERE food_id = ?");
    $chk->bind_param("i", $delId);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()['c'] > 0) {
        $error = "Cannot delete this food item because it has associated orders in history. You can mark its availability as 'unavailable' instead.";
    } else {
        $del = $conn->prepare("DELETE FROM food_items WHERE food_id = ?");
        $del->bind_param("i", $delId);
        if ($del->execute()) {
            $msg = "Food item deleted.";
        }
    }
}

// Fetch categories for select dropdown
$catResult = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$allCats = $catResult->fetch_all(MYSQLI_ASSOC);

// Check if editing
$editItem = null;
if (isset($_GET['edit_id'])) {
    $eId = intval($_GET['edit_id']);
    $eStmt = $conn->prepare("SELECT * FROM food_items WHERE food_id = ?");
    $eStmt->bind_param("i", $eId);
    $eStmt->execute();
    $editItem = $eStmt->get_result()->fetch_assoc();
}

// Fetch all food items
$foods = $conn->query("SELECT f.*, c.category_name 
                       FROM food_items f 
                       JOIN categories c ON f.category_id = c.category_id 
                       ORDER BY f.food_id DESC");
?>

<?php if (!empty($msg)): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px;">
        <i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Create / Edit Form Card -->
<div class="admin-table-card">
    <h3>
        <span><i class="fa-solid <?php echo $editItem ? 'fa-pen' : 'fa-plus'; ?>"></i> <?php echo $editItem ? 'Edit Food Item' : 'Add New Food Item'; ?></span>
        <?php if ($editItem): ?>
            <a href="foods.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
        <?php endif; ?>
    </h3>

    <form method="POST" action="foods.php" enctype="multipart/form-data" style="margin-top: 15px;">
        <input type="hidden" name="action" value="<?php echo $editItem ? 'update' : 'create'; ?>">
        <?php if ($editItem): ?>
            <input type="hidden" name="food_id" value="<?php echo $editItem['food_id']; ?>">
            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editItem['image'] ?? ''); ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label>Food Item Name *</label>
                <input type="text" name="food_name" class="form-control" required value="<?php echo htmlspecialchars($editItem['food_name'] ?? ''); ?>" placeholder="e.g. Chicken Biryani">
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php foreach ($allCats as $c): ?>
                        <option value="<?php echo $c['category_id']; ?>" <?php echo ($editItem && $editItem['category_id'] == $c['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Price ($) *</label>
                <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo htmlspecialchars($editItem['price'] ?? ''); ?>" placeholder="0.00">
            </div>

            <div class="form-group">
                <label>Availability</label>
                <select name="availability" class="form-control">
                    <option value="available" <?php echo ($editItem && $editItem['availability'] === 'available') ? 'selected' : ''; ?>>Available</option>
                    <option value="unavailable" <?php echo ($editItem && $editItem['availability'] === 'unavailable') ? 'selected' : ''; ?>>Unavailable (Sold Out)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Item description, ingredients, taste notes..."><?php echo htmlspecialchars($editItem['description'] ?? ''); ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label>Upload Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div class="form-group">
                <label>Or Image Filename / URL</label>
                <input type="text" name="image_url" class="form-control" placeholder="chicken_rice.jpg or https://..." value="<?php echo htmlspecialchars($editItem['image'] ?? ''); ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> <?php echo $editItem ? 'Save Changes' : 'Create Food Item'; ?>
        </button>
    </form>
</div>

<!-- Foods List Table -->
<div class="admin-table-card">
    <h3><span><i class="fa-solid fa-utensils"></i> All Food Menu Items</span></h3>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Dish Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($f = $foods->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $f['food_id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($f['food_name']); ?></strong>
                            <div style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars(substr($f['description'] ?? '', 0, 50)); ?>...</div>
                        </td>
                        <td><?php echo htmlspecialchars($f['category_name']); ?></td>
                        <td><strong>$<?php echo number_format($f['price'], 2); ?></strong></td>
                        <td>
                            <span class="status-badge <?php echo $f['availability'] === 'available' ? 'status-Delivered' : 'status-Cancelled'; ?>">
                                <?php echo ucfirst($f['availability']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="foods.php?edit_id=<?php echo $f['food_id']; ?>" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="foods.php?delete_id=<?php echo $f['food_id']; ?>" onclick="return confirm('Are you sure you want to delete this food item?');" class="btn btn-outline btn-sm text-danger" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>

