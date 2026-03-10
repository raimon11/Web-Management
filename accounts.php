<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include your database connection
include 'db.php';

// Handle form submission
$message = '';
if (isset($_POST['submit'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields except Middle Name are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Insert into database (you may want to hash passwords in real apps)
        $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, email, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $first_name, $middle_name, $last_name, $email, $password);

        if ($stmt->execute()) {
            $message = "User added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all users for table
$user_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add New User</title>
<link rel="stylesheet" href="./styles/design.css">
<link rel="stylesheet" href="./styles/forms.css">
<style>
  table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
      font-family: Arial, sans-serif;
  }
  table th, table td {
      padding: 10px;
      border: 1px solid #ccc;
      text-align: left;
  }
  td {
    color: black;
  }
  table th {
      background-color: #d11d1d;
  }
  .action-links a {
      margin-right: 10px;
      text-decoration: none;
      color: #007bff;
  }
  .action-links a:hover {
      text-decoration: underline;
  }
</style>
</head>
<body>

<div class="dashboard">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="form-container">
      <h1>Add New User</h1>
      <?php 
      if($message) {
          $class = strpos($message, 'successfully') !== false ? 'message success' : 'message';
          echo "<p class='$class'>$message</p>";
      }
      ?>

      <form action="accounts.php" method="post">
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" id="first_name" required>

        <label for="middle_name">Middle Name:</label>
        <input type="text" name="middle_name" id="middle_name">

        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" id="last_name" required>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Password:</label>
        <input type="text" name="password" id="password" required>

        <label for="confirm_password">Confirm Password:</label>
        <input type="text" name="confirm_password" id="confirm_password" required>

        <input type="submit" name="submit" value="Add User">
      </form>

      

    </div>
    <!-- Users Table -->
    <h2>Accounts List</h2>
    <table>
    <thead>
        <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Created At</th>
        <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($user_result->num_rows > 0): ?>
        <?php while($user = $user_result->fetch_assoc()): ?>
            <tr>
            <td><?php echo $user['id']; ?></td>
            <td><?php echo $user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name']; ?></td>
            <td><?php echo $user['email']; ?></td>
            <td><?php echo $user['created_at']; ?></td>
            <td class="action-links">
                <a href="edit_user.php?id=<?php echo $user['id']; ?>">Edit</a>
                <a href="delete_user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
            </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5">No users found.</td></tr>
    <?php endif; ?>
    </tbody>
    </table>

  </div>
</div>

</body>
</html>