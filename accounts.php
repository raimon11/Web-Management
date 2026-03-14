<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$message = '';
if (isset($_POST['submit'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = in_array($_POST['role'], ['User', 'Admin']) ? $_POST['role'] : 'User';
    $delivery_address = trim($_POST['delivery_address']); // new

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = ['type' => 'error', 'text' => 'All fields except Middle Name and Delivery Address are required.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = ['type' => 'error', 'text' => 'Invalid email format.'];
    } elseif ($password !== $confirm_password) {
        $message = ['type' => 'error', 'text' => 'Passwords do not match.'];
    } else {
      $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, email, password, role, delivery_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
      $stmt->bind_param("sssssss", $first_name, $middle_name, $last_name, $email, $password, $role, $delivery_address);
  }
}

$user_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Accounts</title>
<link rel="stylesheet" href="./styles/index.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  /* ── Reset & Base ── */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body.dashboard-page {
    font-family: 'DM Sans', sans-serif;
    background: #f5f3f0;
    color: #1a1a1a;
  }

  /* ── Layout ── */
  .dashboard { display: flex; min-height: 100vh; }

  .main-content {
    flex: 1;
    padding: 36px 40px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 32px;
    max-width: 100%;
  }

  /* ── Page Header ── */
  .page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    border-bottom: 1.5px solid #e8e2da;
    padding-bottom: 20px;
  }

  .page-header h1 {
    font-size: 26px;
    font-weight: 600;
    letter-spacing: -0.5px;
    color: #1a1a1a;
  }

  .page-header p {
    font-size: 13px;
    color: #888;
    margin-top: 3px;
  }

  .user-count-badge {
    background: #d11d1d;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    letter-spacing: 0.3px;
  }

  /* ── Alert / Message ── */
  .alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 16px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
  }

  .alert-error {
    background: #fff1f1;
    color: #a32d2d;
    border: 1px solid #f7c1c1;
  }

  .alert-success {
    background: #f0faf3;
    color: #1b6b35;
    border: 1px solid #a8ddb8;
  }

  .alert-icon {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
  }

  .alert-error .alert-icon { background: #e24b4a; color: #fff; }
  .alert-success .alert-icon { background: #2e9e52; color: #fff; }

  /* ── Form Card ── */
  .form-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e8e2da;
    padding: 28px 32px;
  }

  .form-card h2 {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .form-card h2::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 18px;
    background: #d11d1d;
    border-radius: 2px;
  }

  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .form-group.full { grid-column: 1 / -1; }
  .form-group.half { grid-column: span 1; }

  .form-group label {
    font-size: 12px;
    font-weight: 600;
    color: #555;
    letter-spacing: 0.4px;
    text-transform: uppercase;
  }

  .form-group input {
    padding: 10px 14px;
    border: 1.5px solid #e0dbd3;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    color: #1a1a1a;
    background: #faf9f7;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    width: 100%;
  }

  .form-group input:focus {
    outline: none;
    border-color: #d11d1d;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(209, 29, 29, 0.08);
  }

  .form-group input::placeholder { color: #bbb; }

  .form-group select {
    padding: 10px 14px;
    border: 1.5px solid #e0dbd3;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    color: #1a1a1a;
    background: #faf9f7 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23999' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E") no-repeat right 14px center;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    width: 100%;
    transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
  }

  .form-group select:focus {
    outline: none;
    border-color: #d11d1d;
    background-color: #fff;
    box-shadow: 0 0 0 3px rgba(209, 29, 29, 0.08);
  }

  .role-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
  }

  .role-admin { background: #fbe8e8; color: #a32d2d; border: 1px solid #f7c1c1; }
  .role-user  { background: #eef4ff; color: #185fa5; border: 1px solid #c0d8f5; }

  .form-divider {
    grid-column: 1 / -1;
    height: 1px;
    background: #f0ece6;
    margin: 4px 0;
  }

  .form-footer {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    margin-top: 6px;
  }

  .btn-submit {
    background: #d11d1d;
    color: #fff;
    border: none;
    padding: 11px 28px;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
    display: flex;
    align-items: center;
    gap: 7px;
  }

  .btn-submit:hover { background: #b01818; }
  .btn-submit:active { transform: scale(0.98); }

  .btn-submit svg { width: 16px; height: 16px; fill: currentColor; }

  /* ── Table Section ── */

  .table-header {
    padding: 20px 24px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f0ece6;
  }

  .table-header h2 {
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .table-header h2::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 18px;
    background: #d11d1d;
    border-radius: 2px;
  }

  .table-search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #faf9f7;
    border-radius: 8px;
    transition: border-color 0.2s;
  }

  .table-search input {
    border: none;
    background: transparent;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    color: #1a1a1a;
    outline: none;
    width: 180px;
  }

  .table-search input::placeholder { color: #aaa; }

  .table-wrapper {
    width: 100%;
    overflow-x: auto;      /* horizontal scroll if table too wide */
    overflow-y: auto;      /* vertical scroll if table too tall */
    max-height: calc(100vh - 280px); /* dynamic height based on viewport */
    border-top: 1px solid #e8e2da;
    border-bottom: 1px solid #e8e2da;
  }

  .table-section {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e8e2da;
    display: flex;
    flex-direction: column;
    flex: 1; /* allows it to grow and fill available space */
    min-height: 0; /* important for flex containers with scrollable content */
}

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
  }

  thead {
      position: sticky;
      top: 0;
      background: #faf9f7; /* same as your header bg */
      z-index: 1;
  }

  thead tr {
    background: #faf9f7;
    border-bottom: 1.5px solid #eee9e2;
  }

  thead th {
    padding: 11px 20px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #888;
    white-space: nowrap;
  }

  tbody tr {
    border-bottom: 1px solid #f5f2ee;
    transition: background 0.15s;
  }

  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: #fdf9f8; }

  tbody td {
    padding: 13px 20px;
    color: #2a2a2a;
    vertical-align: middle;
  }

  /* ID cell */
  .cell-id {
    font-family: 'DM Mono', monospace;
    font-size: 12px;
    color: #999 !important;
  }

  /* Avatar + name cell */
  .cell-name {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .avatar-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #fbe8e8;
    color: #d11d1d;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    letter-spacing: 0.5px;
  }

  .name-text { font-weight: 500; }

  /* Email cell */
  .cell-email { color: #555 !important; }

  /* Date cell */
  .cell-date {
    font-size: 13px;
    color: #999 !important;
    white-space: nowrap;
  }

  /* Actions */
  .cell-actions { display: flex; gap: 6px; }

  .btn-action {
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s;
    border: 1.5px solid transparent;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  .btn-edit {
    background: #eef4ff;
    color: #185fa5;
    border-color: #c0d8f5;
  }

  .btn-edit:hover {
    background: #ddeafc;
    border-color: #85b7eb;
  }

  .btn-delete {
    background: #fff1f1;
    color: #a32d2d;
    border-color: #f7c1c1;
  }

  .btn-delete:hover {
    background: #fde3e3;
    border-color: #f09595;
  }

  /* Empty state */
  .empty-state {
    padding: 48px 24px;
    text-align: center;
    color: #aaa;
  }

  .empty-state svg {
    width: 40px;
    height: 40px;
    opacity: 0.25;
    margin-bottom: 12px;
  }

  .empty-state p { font-size: 14px; }

  /* ── Responsive ── */
  @media (max-width: 900px) {
    .main-content { padding: 20px; }
    .form-grid { grid-template-columns: 1fr 1fr; }
    .form-group.full { grid-column: 1 / -1; }
  }

  @media (max-width: 600px) {
    .form-grid { grid-template-columns: 1fr; }
    .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .table-header { flex-direction: column; align-items: flex-start; gap: 10px; }
  }
</style>
</head>
<body class="dashboard-page">
<div class="dashboard">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1>User Accounts</h1>
        <p>Manage system users and their access</p>
      </div>
      <?php if (isset($user_result) && $user_result): ?>
        <span class="user-count-badge"><?php echo $user_result->num_rows; ?> users</span>
      <?php endif; ?>
    </div>

    <!-- Alert Message -->
    <?php if ($message): ?>
      <div class="alert alert-<?php echo $message['type']; ?>">
        <span class="alert-icon"><?php echo $message['type'] === 'success' ? '✓' : '!'; ?></span>
        <?php echo htmlspecialchars($message['text']); ?>
      </div>
    <?php endif; ?>

    <!-- Add User Form -->
    <div class="form-card">
      <h2>Add New User</h2>
      <form action="accounts.php" method="post">
        <div class="form-grid">

          <!-- Name Row -->
          <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" placeholder="e.g. Juan" required>
          </div>
          <div class="form-group">
            <label for="middle_name">Middle Name <span style="color:#bbb;font-weight:400;">(optional)</span></label>
            <input type="text" name="middle_name" id="middle_name" placeholder="e.g. Santos">
          </div>
          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" placeholder="e.g. Dela Cruz" required>
          </div>

          <!-- Email + Address + Role -->
          <div class="form-group" style="grid-column: span 3;">
            <label for="delivery-address">Delivery Address</label>
            <input type="text" name="delivery-address" id="delivery-address" placeholder="Enter your delivery address" required>
          </div>
          <div class="form-group" style="grid-column: span 2;">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" placeholder="user@example.com" required>
          </div>
          <div class="form-group">
            <label for="role">Role</label>
            <select name="role" id="role" required>
              <option value="User" selected>User</option>
              <option value="Admin">Admin</option>
            </select>
          </div>

          <div class="form-divider"></div>

          <!-- Password Row -->
          <div class="form-group half">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter password" required>
          </div>
          <div class="form-group half">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat password" required>
          </div>

          <div class="form-footer">
            <button type="submit" name="submit" class="btn-submit">
              <svg viewBox="0 0 16 16"><path d="M8 1a7 7 0 1 1 0 14A7 7 0 0 1 8 1zm0 1a6 6 0 1 0 0 12A6 6 0 0 0 8 2zm0 3a1 1 0 0 1 1 1v1h1a1 1 0 0 1 0 2H9v1a1 1 0 0 1-2 0V9H6a1 1 0 0 1 0-2h1V6a1 1 0 0 1 1-1z"/></svg>
              Add User
            </button>
          </div>

        </div>
      </form>
    </div>

    <!-- Users Table -->
    <div class="table-section">
      <div class="table-header">
        <h2>Accounts List</h2>
        <div class="table-search">
          <input type="text" id="tableSearch" placeholder="Search users…" oninput="filterTable(this.value)">
        </div>
      </div>

      <div class="table-wrapper">
        <table id="usersTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Delivery Address</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (isset($user_result) && $user_result->num_rows > 0): ?>
            <?php while($user = $user_result->fetch_assoc()): ?>
              <?php
                $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                $fullName = $user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name'];
                $created = date('M d, Y', strtotime($user['created_at']));
              ?>
              <tr>
                <td class="cell-id">#<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></td>
                <td>
                  <div class="cell-name">
                    <div class="avatar-circle"><?php echo htmlspecialchars($initials); ?></div>
                    <span class="name-text"><?php echo htmlspecialchars($fullName); ?></span>
                  </div>
                </td>
                <td class="cell-email"><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                  <?php $role = isset($user['role']) ? $user['role'] : 'User'; ?>
                  <span class="role-badge <?php echo $role === 'Admin' ? 'role-admin' : 'role-user'; ?>">
                    <?php echo htmlspecialchars($role); ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars($user['delivery_address']); ?></td>
                <td class="cell-date"><?php echo $created; ?></td>
                <td>
                  <div class="cell-actions">
                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-action btn-edit">
                      <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/></svg>
                      Edit
                    </a>
                    <?php if ($user['id'] != 1) : ?>
                        <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn-action btn-delete" 
                          onclick="return confirm('Delete <?php echo htmlspecialchars($user['first_name']); ?>? This cannot be undone.');">
                            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                            </svg>
                            Delete
                        </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5">
                <div class="empty-state">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                  <p>No users found. Add your first user above.</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /main-content -->
</div><!-- /dashboard -->

<script>
function filterTable(query) {
  var rows = document.querySelectorAll('#usersTable tbody tr');
  var q = query.toLowerCase().trim();
  rows.forEach(function(row) {
    var text = row.textContent.toLowerCase();
    row.style.display = (!q || text.includes(q)) ? '' : 'none';
  });
}
</script>

</body>
</html>