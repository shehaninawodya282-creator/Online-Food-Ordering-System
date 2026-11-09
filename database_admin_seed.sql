
-- OPTIONAL SEED ADMIN ACCOUNT
-- Run this if you want a ready-to-use Admin Account
-- Email: admin@quickbite.com
-- Password: admin123

USE online_food_ordering_system;

INSERT INTO users (name, email, phone, password, role)
VALUES ('System Admin', 'admin@quickbite.com', '0770000000', '$2y$10$6Y6PUlfruEaw7TYGP.AVD.PJWAA826nvBMUZIPZz2XtAxw9a.aXLy', 'admin')
ON DUPLICATE KEY UPDATE password = '$2y$10$6Y6PUlfruEaw7TYGP.AVD.PJWAA826nvBMUZIPZz2XtAxw9a.aXLy', role = 'admin';

-- Alternatively, you can register through register.php and change your role in admin/users.php or in phpMyAdmin.

