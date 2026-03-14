<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/db.php';

$message = '';

// ── Handle Add ──────────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $category_name = trim($_POST['category_name']);
    if (empty($category_name)) {
        $message = ['type' => 'error', 'text' => 'Category name is required.'];
    } else {
        $check = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $check->bind_param("s", $category_name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = ['type' => 'error', 'text' => 'Category "' . htmlspecialchars($category_name) . '" already exists.'];
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, created_at) VALUES (?, NOW())");
            $stmt->bind_param("s", $category_name);
            if ($stmt->execute()) {
                $message = ['type' => 'success', 'text' => 'Category "' . htmlspecialchars($category_name) . '" added successfully!'];
            } else {
                $message = ['type' => 'error', 'text' => 'Database error: ' . $stmt->error];
            }
            $stmt->close();
        }
        $check->close();
    }
}

// ── Handle Edit ─────────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $cid  = intval($_POST['category_id']);
    $name = trim($_POST['category_name']);
    if (empty($name)) {
        $message = ['type' => 'error', 'text' => 'Category name cannot be empty.'];
    } else {
        $stmt = $conn->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
        $stmt->bind_param("si", $name, $cid);
        if ($stmt->execute()) {
            $message = ['type' => 'success', 'text' => 'Category updated successfully!'];
        } else {
            $message = ['type' => 'error', 'text' => 'Database error: ' . $stmt->error];
        }
        $stmt->close();
    }
}

// ── Handle Delete ────────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $cid  = intval($_POST['category_id']);
    $used = $conn->query("SELECT COUNT(*) as cnt FROM products WHERE category_id = $cid")->fetch_assoc();
    if ($used['cnt'] > 0) {
        $message = ['type' => 'error', 'text' => 'Cannot delete — ' . $used['cnt'] . ' product(s) are using this category.'];
    } else {
        if ($conn->query("DELETE FROM categories WHERE category_id = $cid")) {
            $message = ['type' => 'success', 'text' => 'Category deleted.'];
        } else {
            $message = ['type' => 'error', 'text' => 'Could not delete category.'];
        }
    }
}

// ── Stats ────────────────────────────────────────────────────────────────────
$total_cats  = $conn->query("SELECT COUNT(*) as c FROM categories")->fetch_assoc()['c'];
$total_prods = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$empty_cats  = $conn->query("SELECT COUNT(*) as c FROM categories c WHERE (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) = 0")->fetch_assoc()['c'];

// ── Fetch categories ─────────────────────────────────────────────────────────
$result = $conn->query("
    SELECT c.category_id, c.category_name, c.created_at,
           COUNT(p.product_id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.category_id
    GROUP BY c.category_id
    ORDER BY c.created_at DESC
");

$categories_data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories_data[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categories — Settings</title>
<link rel="stylesheet" href="./styles/index.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body.dashboard-page {
  font-family: 'DM Sans', sans-serif;
  background: #f5f3f0;
  color: #1a1a1a;
}

.dashboard { display: flex; min-height: 100vh; }

.main-content {
  flex: 1;
  padding: 36px 40px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  border-bottom: 1.5px solid #e8e2da;
  padding-bottom: 20px;
}

.page-header-left h1 { font-size: 26px; font-weight: 600; letter-spacing: -0.5px; }
.page-header-left p  { font-size: 13px; color: #888; margin-top: 3px; }

.count-badge {
  background: #d11d1d;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 20px;
}

.alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 13px 16px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
}

.alert-error   { background: #fff1f1; color: #a32d2d; border: 1px solid #f7c1c1; }
.alert-success { background: #f0faf3; color: #1b6b35; border: 1px solid #a8ddb8; }

.alert-icon {
  width: 18px; height: 18px;
  border-radius: 50%;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.alert-error   .alert-icon { background: #e24b4a; color: #fff; }
.alert-success .alert-icon { background: #2e9e52; color: #fff; }

.layout-cols {
  display: grid;
  grid-template-columns: 340px 1fr;
  gap: 24px;
  align-items: start;
}

.form-card {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e8e2da;
  padding: 26px 28px;
  position: sticky;
  top: 24px;
}

.card-heading {
  font-size: 15px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-heading::before {
  content: '';
  display: inline-block;
  width: 4px; height: 18px;
  background: #d11d1d;
  border-radius: 2px;
}

.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }

.form-group label {
  font-size: 11px; font-weight: 700; color: #666;
  letter-spacing: 0.5px; text-transform: uppercase;
}

.form-group input[type="text"] {
  padding: 10px 14px;
  border: 1.5px solid #e0dbd3;
  border-radius: 8px;
  font-size: 14px;
  font-family: 'DM Sans', sans-serif;
  color: #1a1a1a;
  background: #faf9f7;
  width: 100%;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}

.form-group input[type="text"]:focus {
  outline: none;
  border-color: #d11d1d;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(209,29,29,0.08);
}

.form-group input::placeholder { color: #bbb; }
.hint { font-size: 12px; color: #aaa; margin-top: 2px; }

.char-counter {
  font-size: 12px; color: #bbb;
  text-align: right;
  margin-top: -10px; margin-bottom: 14px;
  transition: color 0.2s;
}
.char-counter.warn { color: #d11d1d; }

.form-footer { margin-top: 20px; display: flex; gap: 10px; }

.btn-primary {
  flex: 1;
  background: #d11d1d; color: #fff;
  border: none; padding: 11px 20px;
  border-radius: 8px; font-size: 14px;
  font-family: 'DM Sans', sans-serif; font-weight: 600;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 7px;
  transition: background 0.2s, transform 0.1s;
  margin: 0;
}
.btn-primary:hover  { background: #b01818; }
.btn-primary:active { transform: scale(0.98); }
.btn-primary svg { width: 15px; height: 15px; fill: currentColor; }

.btn-reset {
  background: #f5f3f0; color: #555;
  border: 1.5px solid #e0dbd3;
  padding: 11px 16px; border-radius: 8px;
  font-size: 13px; font-family: 'DM Sans', sans-serif; font-weight: 500;
  cursor: pointer; transition: background 0.2s; margin: 0;
}
.btn-reset:hover { background: #ede9e4; }

.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 4px;
}

.stat-card {
  background: #faf9f7;
  border: 1px solid #ede9e2;
  border-radius: 10px;
  padding: 14px 16px;
}

.stat-card .stat-label {
  font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.5px;
  color: #aaa; margin-bottom: 6px;
}

.stat-card .stat-value {
  font-size: 22px; font-weight: 600;
  color: #1a1a1a; font-family: 'DM Mono', monospace;
}
.stat-card .stat-value.red { color: #d11d1d; }

.table-card {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e8e2da;
  overflow: hidden;
}

.table-toolbar {
  padding: 18px 22px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #f0ece6;
  flex-wrap: wrap;
  gap: 10px;
}

.table-toolbar h2 {
  font-size: 15px; font-weight: 600;
  display: flex; align-items: center; gap: 8px;
}

.table-toolbar h2::before {
  content: '';
  display: inline-block;
  width: 4px; height: 18px;
  background: #d11d1d; border-radius: 2px;
}

.search-box {
  display: flex; align-items: center; gap: 7px;
  background: #faf9f7;
  border: 1.5px solid #e0dbd3;
  border-radius: 8px; padding: 7px 12px;
  transition: border-color 0.2s;
}
.search-box:focus-within { border-color: #d11d1d; box-shadow: 0 0 0 3px rgba(209,29,29,0.07); }
.search-box svg { width: 14px; height: 14px; opacity: 0.4; flex-shrink: 0; }
.search-box input {
  border: none; background: transparent;
  font-size: 13px; font-family: 'DM Sans', sans-serif;
  color: #1a1a1a; outline: none; width: 180px;
}
.search-box input::placeholder { color: #aaa; }

.table-wrap { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; font-size: 14px; }

thead tr { background: #faf9f7; border-bottom: 1.5px solid #eee9e2; }

thead th {
  padding: 10px 18px;
  text-align: left;
  font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.6px;
  color: #888; white-space: nowrap;
}

tbody tr { border-bottom: 1px solid #f5f2ee; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #fdf9f8; }
tbody tr.editing { background: #fffbf5 !important; }
tbody tr.cat-row.hidden { display: none; }

tbody td { padding: 12px 18px; color: #2a2a2a; vertical-align: middle; }

.cell-id { font-family: 'DM Mono', monospace; font-size: 12px; color: #bbb !important; }

.cat-dot {
  width: 10px; height: 10px; border-radius: 50%;
  display: inline-block; margin-right: 8px; flex-shrink: 0;
}

.cell-name { display: flex; align-items: center; }
.name-view { font-weight: 500; display: flex; align-items: center; }

.inline-edit-input {
  padding: 7px 12px;
  border: 1.5px solid #d11d1d;
  border-radius: 7px; font-size: 14px;
  font-family: 'DM Sans', sans-serif; color: #1a1a1a;
  background: #fff; outline: none;
  box-shadow: 0 0 0 3px rgba(209,29,29,0.08);
  width: 220px; display: none;
}

.prod-count {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 20px;
  font-size: 12px; font-weight: 600;
}
.prod-count.has-products { background: #eef4ff; color: #185fa5; border: 1px solid #c0d8f5; }
.prod-count.no-products  { background: #f5f3f0; color: #aaa; border: 1px solid #e8e2da; }

.cell-date { font-size: 13px; color: #999 !important; white-space: nowrap; }

.cell-actions { display: flex; gap: 6px; align-items: center; }

.btn-action {
  padding: 5px 11px; border-radius: 6px;
  font-size: 12px; font-weight: 600;
  text-decoration: none; transition: all 0.15s;
  border: 1.5px solid transparent;
  display: inline-flex; align-items: center; gap: 4px;
  cursor: pointer; font-family: 'DM Sans', sans-serif; background: none;
  margin: 0;
}

.btn-edit   { background: #eef4ff; color: #185fa5; border-color: #c0d8f5; }
.btn-edit:hover   { background: #ddeafc; border-color: #85b7eb; }

.btn-save   { background: #f0faf3; color: #1b6b35; border-color: #a8ddb8; display: none; }
.btn-save:hover   { background: #dff2e7; }

.btn-cancel { background: #f5f3f0; color: #555; border-color: #e0dbd3; display: none; }
.btn-cancel:hover { background: #ede9e4; }

.btn-delete { background: #fff1f1; color: #a32d2d; border-color: #f7c1c1; }
.btn-delete:hover { background: #fde3e3; border-color: #f09595; }

.empty-state {
  padding: 52px 24px; text-align: center; color: #bbb;
}
.empty-state svg {
  width: 44px; height: 44px; opacity: 0.2;
  margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;
}
.empty-state p { font-size: 14px; }

/* ── PAGINATION ── */
.pagination-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
  padding: 14px 22px;
  border-top: 1px solid #f0ece6;
}

.pg-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 32px; height: 32px;
  padding: 0 10px;
  border-radius: 999px;
  border: 1.5px solid #e0dbd3;
  background: #fff;
  color: #555;
  font-size: 13px; font-weight: 500;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer; transition: all 0.15s;
  margin: 0;
}

.pg-btn:hover:not(.disabled):not(.active) {
  border-color: #d11d1d;
  color: #d11d1d;
  background: #fff5f5;
}

.pg-btn.active {
  background: #d11d1d;
  border-color: #d11d1d;
  color: #fff;
  cursor: default;
}

.pg-btn.disabled {
  opacity: 0.3;
  cursor: not-allowed;
  pointer-events: none;
}

.pg-ellipsis {
  font-size: 13px; color: #bbb;
  padding: 0 2px;
  font-family: 'DM Sans', sans-serif;
}

.pg-info {
  margin-left: auto;
  font-size: 12px; color: #aaa;
  font-family: 'DM Sans', sans-serif;
}

@media (max-width: 960px) {
  .layout-cols { grid-template-columns: 1fr; }
  .form-card { position: static; }
  .main-content { padding: 20px; }
  .stats-row { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 520px) {
  .stats-row { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
}
</style>
</head>
<body class="dashboard-page">
<div class="dashboard">
  <?php require_once __DIR__ . '/sidebar.php'; ?>

  <div class="main-content">

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header-left">
        <h1>Categories</h1>
        <p>Organise your products into categories</p>
      </div>
      <span class="count-badge"><?php echo count($categories_data); ?> categories</span>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
      <div class="alert alert-<?php echo $message['type']; ?>">
        <span class="alert-icon"><?php echo $message['type'] === 'success' ? '✓' : '!'; ?></span>
        <?php echo $message['text']; ?>
      </div>
    <?php endif; ?>

    <!-- Two-column layout -->
    <div class="layout-cols">

      <!-- Add Category Form -->
      <div>
        <div class="form-card">
          <div class="card-heading">Add New Category</div>

          <form action="categories_settings.php" method="post" id="addForm">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
              <label for="category_name">Category Name</label>
              <input
                type="text"
                name="category_name"
                id="category_name"
                placeholder="e.g. Beverages"
                maxlength="60"
                oninput="updateCounter(this)"
                autocomplete="off"
                required>
              <div class="char-counter" id="charCounter">0 / 60</div>
              <span class="hint">Must be unique. Max 60 characters.</span>
            </div>

            <div class="form-footer">
              <button type="reset" class="btn-reset"
                onclick="document.getElementById('charCounter').textContent='0 / 60';
                         document.getElementById('charCounter').classList.remove('warn');">Clear</button>
              <button type="submit" class="btn-primary">
                <svg viewBox="0 0 16 16"><path d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2zm0 1a5 5 0 1 1 0 10A5 5 0 0 1 8 3zm0 2.5a.5.5 0 0 0-.5.5v2H5.5a.5.5 0 0 0 0 1H7.5v2a.5.5 0 0 0 1 0V9.5h2a.5.5 0 0 0 0-1H8.5V6a.5.5 0 0 0-.5-.5z"/></svg>
                Add Category
              </button>
            </div>
          </form>
        </div>

        <!-- Stats -->
        <div class="stats-row" style="margin-top:16px;">
          <div class="stat-card">
            <div class="stat-label">Total</div>
            <div class="stat-value red"><?php echo $total_cats; ?></div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Products</div>
            <div class="stat-value"><?php echo $total_prods; ?></div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Empty</div>
            <div class="stat-value"><?php echo $empty_cats; ?></div>
          </div>
        </div>
      </div>

      <!-- Categories Table -->
      <div class="table-card">
        <div class="table-toolbar">
          <h2>Category List</h2>
          <div class="search-box">
            <svg viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
            </svg>
            <input type="text" id="searchInput" placeholder="Search categories…" oninput="onSearch(this.value)">
          </div>
        </div>

        <div class="table-wrap">
          <table id="catsTable">
            <thead>
              <tr>
                <th style="width:60px;">ID</th>
                <th>Category Name</th>
                <th>Products</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="catsTbody">
            <?php
              $dot_colors = ['#d11d1d','#185fa5','#1a7a3e','#b07d1a','#7c3aed','#0e7490','#be185d','#92400e','#065f46','#4338ca'];
              $color_i = 0;
              if (count($categories_data) > 0):
                foreach ($categories_data as $cat):
                  $color   = $dot_colors[$color_i % count($dot_colors)];
                  $color_i++;
                  $created = date('M d, Y', strtotime($cat['created_at']));
            ?>
              <tr class="cat-row" id="row-<?php echo $cat['category_id']; ?>"
                  data-name="<?php echo strtolower(htmlspecialchars($cat['category_name'])); ?>">

                <td class="cell-id">#<?php echo str_pad($cat['category_id'], 3, '0', STR_PAD_LEFT); ?></td>

                <td class="cell-name">
                  <span class="name-view" id="view-<?php echo $cat['category_id']; ?>">
                    <span class="cat-dot" style="background:<?php echo $color; ?>;"></span>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                  </span>
                  <input
                    type="text"
                    class="inline-edit-input"
                    id="input-<?php echo $cat['category_id']; ?>"
                    value="<?php echo htmlspecialchars($cat['category_name']); ?>"
                    maxlength="60">
                </td>

                <td>
                  <span class="prod-count <?php echo $cat['product_count'] > 0 ? 'has-products' : 'no-products'; ?>">
                    <?php echo $cat['product_count']; ?> <?php echo $cat['product_count'] == 1 ? 'product' : 'products'; ?>
                  </span>
                </td>

                <td class="cell-date"><?php echo $created; ?></td>

                <td>
                  <div class="cell-actions" id="actions-<?php echo $cat['category_id']; ?>">
                    <button type="button" class="btn-action btn-edit" id="editBtn-<?php echo $cat['category_id']; ?>"
                      onclick="startEdit(<?php echo $cat['category_id']; ?>)">
                      <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor"><path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/></svg>
                      Edit
                    </button>

                    <form method="post" style="display:inline;"
                      onsubmit="return confirmDelete(<?php echo $cat['product_count']; ?>, '<?php echo addslashes($cat['category_name']); ?>');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                      <button type="submit" class="btn-action btn-delete" id="deleteBtn-<?php echo $cat['category_id']; ?>">
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                        Delete
                      </button>
                    </form>

                    <form method="post" style="display:inline;" id="saveForm-<?php echo $cat['category_id']; ?>">
                      <input type="hidden" name="action" value="edit">
                      <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                      <input type="hidden" name="category_name" id="saveVal-<?php echo $cat['category_id']; ?>" value="">
                      <button type="submit" class="btn-action btn-save" id="saveBtn-<?php echo $cat['category_id']; ?>">
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>
                        Save
                      </button>
                    </form>

                    <button type="button" class="btn-action btn-cancel" id="cancelBtn-<?php echo $cat['category_id']; ?>"
                      onclick="cancelEdit(<?php echo $cat['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['category_name'])); ?>')">
                      Cancel
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5">
                  <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
                    </svg>
                    <p>No categories yet. Add your first one using the form.</p>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination bar lives inside the table card -->
        <div class="pagination-bar" id="paginationBar"></div>

      </div><!-- /table-card -->

    </div><!-- /layout-cols -->
  </div><!-- /main-content -->
</div><!-- /dashboard -->

<script>
const PER_PAGE    = 8;
let currentSearch = '';
let currentPage   = 1;

/* ── Pagination ───────────────────────────────────────────────── */
function getMatchedRows() {
    return Array.from(document.querySelectorAll('.cat-row')).filter(row => {
        return !currentSearch || row.dataset.name.includes(currentSearch.toLowerCase());
    });
}

function onSearch(val) {
    currentSearch = val.trim();
    currentPage   = 1;
    renderPage();
}

function renderPage() {
    const allRows    = Array.from(document.querySelectorAll('.cat-row'));
    const matched    = getMatchedRows();
    const total      = matched.length;
    const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));

    if (currentPage > totalPages) currentPage = totalPages;

    const start    = (currentPage - 1) * PER_PAGE;
    const end      = start + PER_PAGE;
    const pageRows = matched.slice(start, end);

    allRows.forEach(r  => r.classList.add('hidden'));
    pageRows.forEach(r => r.classList.remove('hidden'));

    /* empty state */
    const tbody  = document.getElementById('catsTbody');
    let emptyRow = tbody.querySelector('.empty-state-row');

    if (total === 0) {
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.className = 'empty-state-row';
            emptyRow.innerHTML = '<td colspan="5"><div class="empty-state"><p>No categories match your search.</p></div></td>';
            tbody.appendChild(emptyRow);
        }
    } else {
        if (emptyRow) emptyRow.remove();
    }

    renderPagination(totalPages, total);
}

function renderPagination(totalPages, total) {
    const bar = document.getElementById('paginationBar');
    bar.innerHTML = '';

    if (totalPages <= 1) return;

    const start = (currentPage - 1) * PER_PAGE + 1;
    const end   = Math.min(currentPage * PER_PAGE, total);

    /* Prev */
    bar.appendChild(makeBtn('&#8592;', currentPage === 1, false, () => { currentPage--; renderPage(); }));

    const rangeStart = Math.max(1, currentPage - 2);
    const rangeEnd   = Math.min(totalPages, currentPage + 2);

    if (rangeStart > 1) {
        bar.appendChild(makeBtn('1', false, false, () => { currentPage = 1; renderPage(); }));
        if (rangeStart > 2) bar.appendChild(makeEllipsis());
    }

    for (let i = rangeStart; i <= rangeEnd; i++) {
        (function(page) {
            bar.appendChild(makeBtn(page, false, page === currentPage, () => { currentPage = page; renderPage(); }));
        })(i);
    }

    if (rangeEnd < totalPages) {
        if (rangeEnd < totalPages - 1) bar.appendChild(makeEllipsis());
        bar.appendChild(makeBtn(totalPages, false, false, () => { currentPage = totalPages; renderPage(); }));
    }

    /* Next */
    bar.appendChild(makeBtn('&#8594;', currentPage === totalPages, false, () => { currentPage++; renderPage(); }));

    /* Info */
    const info = document.createElement('span');
    info.className = 'pg-info';
    info.textContent = start + '\u2013' + end + ' of ' + total + ' categories';
    bar.appendChild(info);
}

function makeBtn(label, disabled, active, onClick) {
    const btn = document.createElement('button');
    btn.className = 'pg-btn' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
    btn.innerHTML = label;
    btn.type = 'button';
    if (!disabled && !active) btn.addEventListener('click', onClick);
    return btn;
}

function makeEllipsis() {
    const span = document.createElement('span');
    span.className = 'pg-ellipsis';
    span.textContent = '\u2026';
    return span;
}

/* ── Character counter ────────────────────────────────────────── */
function updateCounter(input) {
    var counter = document.getElementById('charCounter');
    var len = input.value.length;
    counter.textContent = len + ' / 60';
    counter.classList.toggle('warn', len >= 50);
}

/* ── Inline edit ──────────────────────────────────────────────── */
function startEdit(id) {
    document.getElementById('view-'      + id).style.display   = 'none';
    document.getElementById('input-'     + id).style.display   = 'block';
    document.getElementById('editBtn-'   + id).style.display   = 'none';
    document.getElementById('deleteBtn-' + id).closest('form').style.display = 'none';
    document.getElementById('saveBtn-'   + id).style.display   = 'inline-flex';
    document.getElementById('cancelBtn-' + id).style.display   = 'inline-flex';
    document.getElementById('row-'       + id).classList.add('editing');

    var input = document.getElementById('input-' + id);
    input.focus();
    input.select();

    document.getElementById('saveForm-' + id).onsubmit = function() {
        var val = input.value.trim();
        if (!val) { input.focus(); return false; }
        document.getElementById('saveVal-' + id).value = val;
        return true;
    };
}

function cancelEdit(id, original) {
    document.getElementById('input-'     + id).value            = original;
    document.getElementById('view-'      + id).style.display    = 'flex';
    document.getElementById('input-'     + id).style.display    = 'none';
    document.getElementById('editBtn-'   + id).style.display    = 'inline-flex';
    document.getElementById('deleteBtn-' + id).closest('form').style.display = 'inline';
    document.getElementById('saveBtn-'   + id).style.display    = 'none';
    document.getElementById('cancelBtn-' + id).style.display    = 'none';
    document.getElementById('row-'       + id).classList.remove('editing');
}

/* ── Delete guard ─────────────────────────────────────────────── */
function confirmDelete(count, name) {
    if (count > 0) {
        alert('Cannot delete "' + name + '" \u2014 it has ' + count + ' product(s) assigned.\nReassign or delete those products first.');
        return false;
    }
    return confirm('Delete category "' + name + '"? This cannot be undone.');
}

/* Initial render */
renderPage();
</script>

</body>
</html>