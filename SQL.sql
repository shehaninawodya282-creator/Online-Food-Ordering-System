
-- =========================================
-- ONLINE FOOD ORDERING SYSTEM
-- Database: online_food_ordering_system
-- =========================================

CREATE DATABASE IF NOT EXISTS online_food_ordering_system;

USE online_food_ordering_system;


-- =========================================
-- 1. USERS TABLE
-- Stores customers, administrators and staff
-- =========================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin', 'staff') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- 2. CATEGORIES TABLE
-- Stores food categories
-- =========================================

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- 3. FOOD_ITEMS TABLE
-- Stores food menu details
-- =========================================

CREATE TABLE food_items (
    food_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    food_name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    availability ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)
    REFERENCES categories(category_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
);


-- =========================================
-- 4. CART TABLE
-- Stores customer shopping carts
-- =========================================

CREATE TABLE cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);


-- =========================================
-- 5. CART_ITEMS TABLE
-- Stores items added to shopping carts
-- =========================================

CREATE TABLE cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,

    FOREIGN KEY (cart_id)
    REFERENCES cart(cart_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    FOREIGN KEY (food_id)
    REFERENCES food_items(food_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,

    UNIQUE (cart_id, food_id)
);


-- =========================================
-- 6. ORDERS TABLE
-- Stores customer order information
-- =========================================

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    delivery_address TEXT NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash_on_delivery', 'card', 'bank_transfer')
        NOT NULL DEFAULT 'cash_on_delivery',
    order_status ENUM(
        'Pending',
        'Confirmed',
        'Preparing',
        'Ready for Delivery',
        'Delivered',
        'Cancelled'
    ) DEFAULT 'Pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
);


-- =========================================
-- 7. ORDER_ITEMS TABLE
-- Stores individual items in each order
-- =========================================

CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (order_id)
    REFERENCES orders(order_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    FOREIGN KEY (food_id)
    REFERENCES food_items(food_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
);


-- =========================================
-- 8. PAYMENTS TABLE
-- Stores payment information
-- =========================================

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    payment_method ENUM(
        'cash_on_delivery',
        'card',
        'bank_transfer'
    ) NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM(
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ) DEFAULT 'Pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id)
    REFERENCES orders(order_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);


-- =========================================
-- 9. REVIEWS TABLE
-- Stores customer reviews and ratings
-- =========================================

CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    FOREIGN KEY (food_id)
    REFERENCES food_items(food_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    CHECK (rating BETWEEN 1 AND 5)
);


-- =========================================
-- SAMPLE DATA
-- =========================================

-- Insert food categories

INSERT INTO categories (category_name, description) VALUES
('Rice', 'Different rice dishes'),
('Pizza', 'Different types of pizza'),
('Burgers', 'Different types of burgers'),
('Beverages', 'Drinks and beverages'),
('Desserts', 'Sweet food and desserts');


-- Insert sample food items

INSERT INTO food_items
(category_id, food_name, description, price, image, availability)

SELECT
    c.category_id,
    f.food_name,
    f.description,
    f.price,
    f.image,
    'available'

FROM
(
    SELECT 'Rice' AS category_name, 'Chicken Rice' AS food_name, 'Delicious chicken fried rice' AS description, 850.00 AS price, 'chicken_rice.jpg' AS image
    UNION ALL
    SELECT 'Rice', 'Vegetable Rice', 'Fresh vegetable fried rice', 650.00, 'vegetable_rice.jpg'
    UNION ALL
    SELECT 'Rice', 'Chicken Biryani', 'Spicy chicken biryani rice', 1200.00, 'chicken_biryani.jpg'
    UNION ALL
    SELECT 'Rice', 'Seafood Fried Rice', 'Fried rice with fresh seafood', 1100.00, 'seafood_rice.jpg'
    UNION ALL
    SELECT 'Rice', 'Nasi Goreng', 'Traditional Indonesian fried rice', 1000.00, 'nasi_goreng.jpg'
    UNION ALL
    SELECT 'Rice', 'Egg Fried Rice', 'Fried rice with fresh eggs', 750.00, 'egg_rice.jpg'

    UNION ALL
    SELECT 'Pizza', 'Chicken Pizza', 'Chicken pizza with cheese', 1500.00, 'chicken_pizza.jpg'
    UNION ALL
    SELECT 'Pizza', 'Cheese Pizza', 'Delicious pizza with extra cheese', 1200.00, 'cheese_pizza.jpg'
    UNION ALL
    SELECT 'Pizza', 'Pepperoni Pizza', 'Pizza topped with pepperoni', 1600.00, 'pepperoni_pizza.jpg'
    UNION ALL
    SELECT 'Pizza', 'Vegetable Pizza', 'Pizza with fresh vegetables', 1100.00, 'vegetable_pizza.jpg'
    UNION ALL
    SELECT 'Pizza', 'BBQ Chicken Pizza', 'BBQ chicken pizza with cheese', 1700.00, 'bbq_pizza.jpg'

    UNION ALL
    SELECT 'Burgers', 'Cheese Burger', 'Burger with cheese and vegetables', 750.00, 'cheese_burger.jpg'
    UNION ALL
    SELECT 'Burgers', 'Double Cheese Burger', 'Double beef burger with cheese', 1100.00, 'double_burger.jpg'
    UNION ALL
    SELECT 'Burgers', 'Crispy Chicken Burger', 'Crispy chicken burger', 950.00, 'crispy_burger.jpg'
    UNION ALL
    SELECT 'Burgers', 'Veggie Burger', 'Healthy burger with fresh vegetables', 700.00, 'veggie_burger.jpg'
    UNION ALL
    SELECT 'Burgers', 'Fish Burger', 'Burger with crispy fish', 900.00, 'fish_burger.jpg'

    UNION ALL
    SELECT 'Beverages', 'Orange Juice', 'Fresh orange juice', 350.00, 'orange_juice.jpg'
    UNION ALL
    SELECT 'Beverages', 'Mango Juice', 'Fresh mango juice', 400.00, 'mango_juice.jpg'
    UNION ALL
    SELECT 'Beverages', 'Watermelon Juice', 'Fresh watermelon juice', 350.00, 'watermelon_juice.jpg'
    UNION ALL
    SELECT 'Beverages', 'Iced Coffee', 'Cold and refreshing iced coffee', 450.00, 'iced_coffee.jpg'
    UNION ALL
    SELECT 'Beverages', 'Chocolate Milkshake', 'Creamy chocolate milkshake', 550.00, 'chocolate_milkshake.jpg'

    UNION ALL
    SELECT 'Desserts', 'Chocolate Cake', 'Delicious chocolate cake', 500.00, 'chocolate_cake.jpg'
    UNION ALL
    SELECT 'Desserts', 'Cheesecake', 'Creamy strawberry cheesecake', 650.00, 'cheesecake.jpg'
    UNION ALL
    SELECT 'Desserts', 'Chocolate Ice Cream', 'Delicious chocolate ice cream', 400.00, 'chocolate_icecream.jpg'
    UNION ALL
    SELECT 'Desserts', 'Chocolate Brownie', 'Soft and tasty chocolate brownie', 500.00, 'brownie.jpg'
    UNION ALL
    SELECT 'Desserts', 'Chocolate Donut', 'Fresh donut with chocolate topping', 300.00, 'donut.jpg'

    UNION ALL
    SELECT 'Short Eats', 'French Fries', 'Crispy golden french fries', 450.00, 'french_fries.jpg'
    UNION ALL
    SELECT 'Short Eats', 'Chicken Nuggets', 'Crispy chicken nuggets', 600.00, 'chicken_nuggets.jpg'
    UNION ALL
    SELECT 'Short Eats', 'Chicken Wings', 'Spicy crispy chicken wings', 750.00, 'chicken_wings.jpg'
    UNION ALL
    SELECT 'Short Eats', 'Club Sandwich', 'Sandwich with chicken and vegetables', 650.00, 'club_sandwich.jpg'

) AS f

JOIN categories c
ON c.category_name = f.category_name

WHERE NOT EXISTS (
    SELECT 1
    FROM food_items old
    WHERE old.food_name = f.food_name
);


-- =========================================
-- END OF DATABASE
-- =========================================
