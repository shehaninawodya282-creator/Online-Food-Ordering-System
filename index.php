<?php
// index.php
require_once 'config/database.php';
require_once 'includes/auth.php';

$pageTitle = "Home - Order Delicious Food";
require_once 'includes/header.php';

$conn = getDBConnection();

// Fetch Categories
$catResult = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");

// Fetch Featured / Popular Items
$foodQuery = "SELECT f.*, c.category_name 
              FROM food_items f 
              JOIN categories c ON f.category_id = c.category_id 
              WHERE f.availability = 'available' 
              ORDER BY f.food_id DESC LIMIT 8";
$foodResult = $conn->query($foodQuery);
?>

<!-- Hero Banner -->
<section class="hero">
    <div class="hero-container">
        <div class="hero-content">
            <span class="hero-tag"><i class="fa-solid fa-fire"></i> Hot & Fresh Delivery</span>
            <h1 class="hero-title">Delicious Food Delivered To Your <span>Doorstep</span></h1>
            <p class="hero-desc">Craving something tasty? Explore our carefully curated menu featuring mouthwatering burgers, authentic pizzas, fragrant rice meals, and divine desserts.</p>
            <div class="hero-cta">
                <a href="menu.php" class="btn btn-primary"><i class="fa-solid fa-utensils"></i> Explore Menu</a>
                <a href="#featured" class="btn btn-outline"><i class="fa-solid fa-star"></i> Featured Items</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=700&q=80" alt="Delicious Food Feast">
        </div>
    </div>
</section>

<div class="container">
    <!-- Value Propositions -->
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fa-solid fa-truck-fast"></i></div>
            <h3>Super Fast Delivery</h3>
            <p>Hot, delicious meals delivered right at your door in 30 minutes.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fa-solid fa-seedling"></i></div>
            <h3>100% Fresh Ingredients</h3>
            <p>Prepared daily by expert chefs using the freshest local produce.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fa-solid fa-wallet"></i></div>
            <h3>Easy & Safe Payment</h3>
            <p>Pay smoothly via Cash on Delivery, Bank Transfer, or Online Card.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fa-solid fa-headset"></i></div>
            <h3>24/7 Dedicated Support</h3>
            <p>Friendly support team always ready to guide and assist your orders.</p>
        </div>
    </div>

    <!-- Browse Categories Section -->
    <div style="margin: 50px 0 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px;">
            <div>
                <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Explore Categories</h2>
                <p style="color: var(--text-muted);">Pick from your favorite categories</p>
            </div>
            <a href="menu.php" style="color: var(--primary); font-weight: 600;">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px;">
            <?php while ($cat = $catResult->fetch_assoc()): ?>
                <a href="menu.php?cat_id=<?php echo $cat['category_id']; ?>" style="background: #fff; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center; display: block; transition: var(--transition);">
                    <div style="font-size: 2rem; color: var(--primary); margin-bottom: 10px;">
                        <?php
                            $catName = strtolower($cat['category_name']);
                            if (strpos($catName, 'rice') !== false) echo '<i class="fa-solid fa-bowl-rice"></i>';
                            elseif (strpos($catName, 'pizza') !== false) echo '<i class="fa-solid fa-pizza-slice"></i>';
                            elseif (strpos($catName, 'burger') !== false) echo '<i class="fa-solid fa-burger"></i>';
                            elseif (strpos($catName, 'beverage') !== false || strpos($catName, 'drink') !== false) echo '<i class="fa-solid fa-martini-glass-citrus"></i>';
                            elseif (strpos($catName, 'dessert') !== false || strpos($catName, 'cake') !== false) echo '<i class="fa-solid fa-cake-candles"></i>';
                            else echo '<i class="fa-solid fa-bowl-food"></i>';
                        ?>
                    </div>
                    <h4 style="font-size: 1rem; color: var(--text-main);"><?php echo htmlspecialchars($cat['category_name']); ?></h4>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Featured Food Section -->
    <div id="featured" style="margin-top: 60px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px;">
            <div>
                <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Featured Dishes</h2>
                <p style="color: var(--text-muted);">Loved by hundreds of happy foodies</p>
            </div>
            <a href="menu.php" style="color: var(--primary); font-weight: 600;">Full Menu <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="food-grid">
            <?php 
            if ($foodResult && $foodResult->num_rows > 0):
                while ($food = $foodResult->fetch_assoc()):
                    $imgUrl = !empty($food['image']) ? "assets/images/" . $food['image'] : "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=500&q=80";
            ?>
                <div class="food-card">
                    <div class="food-img-container">
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($food['food_name']); ?>" class="food-img" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=500&q=80'">
                        <span class="food-badge"><?php echo htmlspecialchars($food['category_name']); ?></span>
                    </div>
                    <div class="food-details">
                        <h3 class="food-name"><?php echo htmlspecialchars($food['food_name']); ?></h3>
                        <p class="food-desc"><?php echo htmlspecialchars($food['description']); ?></p>
                        <div class="food-footer">
                            <span class="food-price">Rs. <?php echo number_format($food['price'], 2); ?></span>
                            <button onclick="addToCart(<?php echo $food['food_id']; ?>)" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-cart-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <p>No featured dishes available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

