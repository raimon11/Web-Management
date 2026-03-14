<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$message = '';
// Get user ID from query
if (!isset($_GET['id']) || !($user_id = intval($_GET['id']))) {
    header("Location: accounts.php");
    exit();
}

// Fetch existing user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: accounts.php");
    exit();
}
$user = $result->fetch_assoc();

// Handle form submission
if (isset($_POST['submit'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = in_array($_POST['role'], ['User', 'Admin']) ? $_POST['role'] : 'User';
    $delivery_address = trim($_POST['delivery_address']);

    // Optional password change
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $message = ['type' => 'error', 'text' => 'First name, last name, and email are required.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = ['type' => 'error', 'text' => 'Invalid email format.'];
    } elseif ($password && $password !== $confirm_password) {
        $message = ['type' => 'error', 'text' => 'Passwords do not match.'];
    } else {
        if ($password) {
            // Update password if provided
            $stmt = $conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, role=?, delivery_address=?, password=? WHERE id=?");
            $stmt->bind_param("sssssssi", $first_name, $middle_name, $last_name, $email, $role, $delivery_address, $password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, role=?, delivery_address=? WHERE id=?");
            $stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $email, $role, $delivery_address, $user_id);
        }

        if ($stmt->execute()) {
            $message = ['type' => 'success', 'text' => 'User updated successfully.'];
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $message = ['type' => 'error', 'text' => 'Failed to update user.'];
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User</title>
<link rel="stylesheet" href="./styles/index.css">
</head>
<body class="dashboard-page">
<div class="dashboard">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message['type']; ?>">
            <span class="alert-icon"><?php echo $message['type'] === 'success' ? '✓' : '!'; ?></span>
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <h2>Edit User</h2>
            <form action="edit_user.php?id=<?php echo $user['id']; ?>" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name <span style="color:#bbb;font-weight:400;">(optional)</span></label>
                        <input type="text" name="middle_name" id="middle_name" value="<?php echo htmlspecialchars($user['middle_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>

                    <div class="form-group" style="grid-column: span 3;">
                        <label for="delivery-address">Delivery Address</label>
                        <input type="text" name="delivery_address" id="delivery-address" value="<?php echo htmlspecialchars($user['delivery_address']); ?>" required>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select name="role" id="role" required>
                            <option value="User" <?php echo $user['role'] === 'User' ? 'selected' : ''; ?>>User</option>
                            <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-divider"></div>

                    <div class="form-group half">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" placeholder="Enter new password">
                    </div>
                    <div class="form-group half">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat new password">
                    </div>

                    <div class="form-footer">
                        <button type="submit" name="submit" class="btn-submit">Update User</button>
                    </div>

                </div>
            </form>
        </div>

    </div>
</div>
</body>
</html>