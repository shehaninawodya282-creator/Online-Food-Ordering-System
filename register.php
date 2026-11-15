<?php
// register.php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $conn = getDBConnection();
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'An account with this email address already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'customer'; // Default registration role

            $ins = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param("sssss", $name, $email, $phone, $hashedPassword, $role);
            
            if ($ins->execute()) {
                $newUserId = $ins->insert_id;
                // Auto create empty cart for user
                $cartStmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
                $cartStmt->bind_param("i", $newUserId);
                $cartStmt->execute();

                // Set session
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;

                header("Location: index.php");
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = "Register - QuickBite";
require_once 'includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <h2>Create an Account</h2>
        <p class="auth-subtitle">Join QuickBite for quick ordering and food tracking</p>

        <?php if (!empty($error)): ?>
            <div style="background: #ffeaa7; color: #d63031; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="john@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="0771234567" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="At least 6 characters" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-type password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 15px;">
                <i class="fa-solid fa-user-plus"></i> Register
            </button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

