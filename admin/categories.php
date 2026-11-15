<?php
// admin/categories.php
$adminTitle = "Categories Management";
require_once 'header.php';

$conn = getDBConnection();

$msg = '';
$error = '';

// Add / Update Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $categoryName = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($categoryName)) {
        $error = "Category name cannot be empty.";
    } else {
        if ($_POST['action'] === 'create') {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $categoryName, $description);
            if ($stmt->execute()) {
                $msg = "Category added successfully!";
            } else {
                $error = "Category already exists or error: " . $conn->error;
            }
        } elseif ($_POST['action'] === 'update') {
            $catId = intval($_POST['category_id'] ?? 0);
            $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
            $stmt->bind_param("ssi", $categoryName, $description, $catId);
            if ($stmt->execute()) {
                $msg = "Category updated successfully!";
            } else {
                $error = "Update error: " . $conn->error;
            }
        }
    }
}

// Delete Category
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    // Check if food items exist under this category
    $chk = $conn->prepare("SELECT COUNT(*) as c FROM food_items WHERE category_id = ?");
    $chk->bind_param("i", $delId);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()['c'] > 0) {
        $error = "Cannot delete category because there are food items assigned to it.";
    } else {
        $del = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $del->bind_param("i", $delId);
        if ($del->execute()) {
            $msg = "Category removed successfully!";
        }
    }
}

// Edit item fetch
$editCat = null;
if (isset($_GET['edit_id'])) {
    $eId = intval($_GET['edit_id']);
    $eStmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $eStmt->bind_param("i", $eId);
    $eStmt->execute();
    $editCat = $eStmt->get_result()->fetch_assoc();
}

// Fetch all categories with food count
$q = "SELECT c.*, COUNT(f.food_id) as total_items 
      FROM categories c 
      LEFT JOIN food_items f ON c.category_id = f.category_id 
      GROUP BY c.category_id 
      ORDER BY c.category_name ASC";
$categories = $conn->query($q);
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

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 25px; align-items: start;">
    <!-- Form Card -->
    <div class="admin-table-card">
        <h3>
            <span><i class="fa-solid <?php echo $editCat ? 'fa-pen' : 'fa-plus'; ?>"></i> <?php echo $editCat ? 'Edit Category' : 'New Category'; ?></span>
            <?php if ($editCat): ?>
                <a href="categories.php" class="btn btn-secondary btn-sm">Cancel</a>
            <?php endif; ?>
        </h3>

        <form method="POST" action="categories.php">
            <input type="hidden" name="action" value="<?php echo $editCat ? 'update' : 'create'; ?>">
            <?php if ($editCat): ?>
                <input type="hidden" name="category_id" value="<?php echo $editCat['category_id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="category_name" class="form-control" required value="<?php echo htmlspecialchars($editCat['category_name'] ?? ''); ?>" placeholder="e.g. Pasta">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Category description..."><?php echo htmlspecialchars($editCat['description'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-floppy-disk"></i> <?php echo $editCat ? 'Update Category' : 'Save Category'; ?>
            </button>
        </form>
    </div>

    <!-- Categories List Card -->
    <div class="admin-table-card">
        <h3><span><i class="fa-solid fa-layer-group"></i> Existing Categories</span></h3>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Total Dishes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cat['category_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></td>
                            <td><span class="status-badge" style="background:#f1f5f9; color:#475569;"><?php echo $cat['total_items']; ?> items</span></td>
                            <td>
                                <a href="categories.php?edit_id=<?php echo $cat['category_id']; ?>" class="btn btn-outline btn-sm" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="categories.php?delete_id=<?php echo $cat['category_id']; ?>" onclick="return confirm('Delete this category?');" class="btn btn-outline btn-sm text-danger" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

