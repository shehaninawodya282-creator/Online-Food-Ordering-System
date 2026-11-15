<?php
// menu.php
require_once 'config/database.php';
require_once 'includes/auth.php';

$pageTitle = "Menu - Explore Dishes";
require_once 'includes/header.php';

$conn = getDBConnection();

$selectedCatId = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch all categories for filter tabs
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");

// Build query for food items
$query = "SELECT f.*, c.category_name 
          FROM food_items f 
          JOIN categories c ON f.category_id = c.category_id 
          WHERE 1=1";

$params = [];
$types = "";

if ($selectedCatId > 0) {
    $query .= " AND f.category_id = ?";
    $params[] = $selectedCatId;
    $types .= "i";
}

if (!empty($searchTerm)) {
    $query .= " AND (f.food_name LIKE ? OR f.description LIKE ?)";
    $like = "%" . $searchTerm . "%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$query .= " ORDER BY f.availability ASC, f.food_name ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$foodResult = $stmt->get_result();
?>

<div class="container">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 30px;">
        <div>
            <h1 style="font-size: 2.2rem; margin-bottom: 5px;">Our Delicious Menu</h1>
            <p style="color: var(--text-muted);">Choose from a range of freshly cooked meals prepared with love.</p>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="menu.php" style="display: flex; gap: 10px; max-width: 360px; width: 100%;">
            <?php if ($selectedCatId > 0): ?>
                <input type="hidden" name="cat_id" value="<?php echo $selectedCatId; ?>">
            <?php endif; ?>
            <input type="text" name="search" class="form-control" placeholder="Search dish..." value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>

    <!-- Category Tabs Filter -->
    <div class="category-tabs">
        <a href="menu.php<?php echo !empty($searchTerm) ? '?search=' . urlencode($searchTerm) : ''; ?>" class="category-tab <?php echo $selectedCatId === 0 ? 'active' : ''; ?>">
            All Categories
        </a>
        <?php while ($cat = $categories->fetch_assoc()): ?>
            <a href="menu.php?cat_id=<?php echo $cat['category_id']; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" 
               class="category-tab <?php echo $selectedCatId == $cat['category_id'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat['category_name']); ?>
            </a>
        <?php endwhile; ?>
    </div>

    <!-- Menu Items List -->
    <div class="food-grid">
        <?php 
        if ($foodResult && $foodResult->num_rows > 0):
            while ($food = $foodResult->fetch_assoc()):
                $imgUrl = !empty($food['image']) ? "assets/images/" . $food['image'] : "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=500&q=80";
                $isAvailable = ($food['availability'] === 'available');
        ?>
            <div class="food-card">
                <div class="food-img-container">
                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($food['food_name']); ?>" class="food-img" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=500&q=80'">
                    <span class="food-badge <?php echo !$isAvailable ? 'unavailable' : ''; ?>">
                        <?php echo $isAvailable ? htmlspecialchars($food['category_name']) : 'Unavailable'; ?>
                    </span>
                </div>
                <div class="food-details">
                    <h3 class="food-name"><?php echo htmlspecialchars($food['food_name']); ?></h3>
                    <p class="food-desc"><?php echo htmlspecialchars($food['description']); ?></p>
                    <div class="food-footer">
                        <span class="food-price">Rs. <?php echo number_format($food['price'], 2); ?></span>
                        <?php if ($isAvailable): ?>
                            <button onclick="addToCart(<?php echo $food['food_id']; ?>)" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-cart-plus"></i> Add
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled style="cursor: not-allowed; opacity: 0.6;">
                                Sold Out
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php 
            endwhile;
        else:
        ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px 0;">
                <i class="fa-solid fa-bowl-food" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                <h3>No dishes found</h3>
                <p style="color: var(--text-muted); margin-bottom: 20px;">Try adjusting your search criteria or category filter.</p>
                <a href="menu.php" class="btn btn-outline">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

