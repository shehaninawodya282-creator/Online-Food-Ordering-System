<?php
// admin/users.php
$adminTitle = "User Management";
require_once 'header.php';

$conn = getDBConnection();

$msg = '';
$error = '';

// Change user role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $targetUserId = intval($_POST['user_id'] ?? 0);
    $newRole = $_POST['role'] ?? 'customer';

    if (in_array($newRole, ['customer', 'admin', 'staff'])) {
        $upd = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $upd->bind_param("si", $newRole, $targetUserId);
        if ($upd->execute()) {
            $msg = "User role updated successfully!";
        }
    }
}

// Fetch all registered users
$users = $conn->query("SELECT user_id, name, email, phone, role, created_at FROM users ORDER BY user_id DESC");
?>

<?php if (!empty($msg)): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px;">
        <i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="admin-table-card">
    <h3><span><i class="fa-solid fa-users"></i> Registered System Users</span></h3>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Registered At</th>
                    <th>Change Role</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $u['user_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                        <td>
                            <span class="status-badge" style="
                                <?php 
                                    if ($u['role'] === 'admin') echo 'background:#fef2f2; color:#b91c1c;';
                                    elseif ($u['role'] === 'staff') echo 'background:#eff6ff; color:#1d4ed8;';
                                    else echo 'background:#f1f5f9; color:#475569;';
                                ?>
                            ">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date("M d, Y", strtotime($u['created_at'])); ?></td>
                        <td>
                            <form method="POST" action="users.php" style="display: flex; gap: 6px;">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                <select name="role" class="form-control" style="padding: 4px 8px; font-size: 0.8rem; width: auto;">
                                    <option value="customer" <?php echo $u['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="staff" <?php echo $u['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <button type="submit" class="btn btn-outline btn-sm">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>

