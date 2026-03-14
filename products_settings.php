<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$message = '';

// ── Handle Add Product ──────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_name = trim($_POST['product_name']);
    $category_id  = intval($_POST['category_id']);
    $price        = floatval($_POST['price']);
    $picture      = '';

    // Handle image upload
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $ftype   = mime_content_type($_FILES['picture']['tmp_name']);
        if (in_array($ftype, $allowed)) {
            $ext      = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $dest     = 'uploads/products/' . $filename;
            if (!is_dir('uploads/products')) mkdir('uploads/products', 0755, true);
            if (move_uploaded_file($_FILES['picture']['tmp_name'], $dest)) {
                $picture = $dest;
            } else {
                $message = ['type' => 'error', 'text' => 'Failed to upload image.'];
            }
        } else {
            $message = ['type' => 'error', 'text' => 'Invalid image format. Use JPG, PNG, GIF, or WEBP.'];
        }
    }

    if (!$message) {
        if (empty($product_name) || $price <= 0) {
            $message = ['type' => 'error', 'text' => 'Product name and a valid price are required.'];
        } else {
            $stmt = $conn->prepare("INSERT INTO products (category_id, product_name, price, picture) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $category_id, $product_name, $price, $picture);
            if ($stmt->execute()) {
                $message = ['type' => 'success', 'text' => 'Product "' . htmlspecialchars($product_name) . '" added successfully!'];
            } else {
                $message = ['type' => 'error', 'text' => 'Database error: ' . $stmt->error];
            }
            $stmt->close();
        }
    }
}

// ── Handle Delete Product ───────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $pid = intval($_POST['product_id']);
    // Optionally delete the image file
    $res = $conn->query("SELECT picture FROM products WHERE product_id = $pid");
    if ($row = $res->fetch_assoc()) {
        if ($row['picture'] && file_exists($row['picture'])) unlink($row['picture']);
    }
    if ($conn->query("DELETE FROM products WHERE product_id = $pid")) {
        $message = ['type' => 'success', 'text' => 'Product deleted.'];
    } else {
        $message = ['type' => 'error', 'text' => 'Could not delete product.'];
    }
}

// ── Fetch categories for dropdown ──────────────────────────────
$cat_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = [];
if ($cat_result) {
    while ($c = $cat_result->fetch_assoc()) $categories[] = $c;
}

// ── Fetch all products ─────────────────────────────────────────
$products_result = $conn->query("
    SELECT p.product_id, p.product_name, p.price, p.picture, p.category_id,
           c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.product_id DESC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products — Settings</title>
<link rel="stylesheet" href="./styles/index.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ── Base ─────────────────────────────────────────────────── */
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

/* ── Page Header ─────────────────────────────────────────── */
.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  border-bottom: 1.5px solid #e8e2da;
  padding-bottom: 20px;
}

.page-header-left h1 {
  font-size: 26px;
  font-weight: 600;
  letter-spacing: -0.5px;
}

.page-header-left p {
  font-size: 13px;
  color: #888;
  margin-top: 3px;
}

.count-badge {
  background: #d11d1d;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 20px;
}

/* ── Alert ───────────────────────────────────────────────── */
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

/* ── Two-Column Layout ───────────────────────────────────── */
.layout-cols {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 24px;
  align-items: start;
}

/* ── Form Card ───────────────────────────────────────────── */
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

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
}

.form-group:last-of-type { margin-bottom: 0; }

.form-group label {
  font-size: 11px;
  font-weight: 700;
  color: #666;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select {
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

.form-group select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23999' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #d11d1d;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(209,29,29,0.08);
}

.form-group input::placeholder { color: #bbb; }

/* Image upload zone */
.upload-zone {
  border: 2px dashed #e0dbd3;
  border-radius: 10px;
  padding: 20px;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
  position: relative;
  background: #faf9f7;
}

.upload-zone:hover, .upload-zone.dragover {
  border-color: #d11d1d;
  background: #fff8f8;
}

.upload-zone input[type="file"] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}

.upload-icon {
  width: 36px; height: 36px;
  background: #fbe8e8;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 10px;
}

.upload-icon svg { width: 18px; height: 18px; fill: #d11d1d; }

.upload-zone p {
  font-size: 13px;
  color: #888;
  line-height: 1.5;
}

.upload-zone strong { color: #d11d1d; font-weight: 600; }

.upload-preview {
  display: none;
  margin-top: 10px;
}

.upload-preview img {
  width: 60px; height: 60px;
  object-fit: cover;
  border-radius: 8px;
  border: 1.5px solid #e8e2da;
}

.form-footer {
  margin-top: 20px;
  display: flex;
  gap: 10px;
}

.btn-primary {
  flex: 1;
  background: #d11d1d;
  color: #fff;
  border: none;
  padding: 11px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-family: 'DM Sans', sans-serif;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  transition: background 0.2s, transform 0.1s;
}

.btn-primary:hover { background: #b01818; }
.btn-primary:active { transform: scale(0.98); }
.btn-primary svg { width: 15px; height: 15px; fill: currentColor; }

.btn-reset {
  background: #f5f3f0;
  color: #555;
  border: 1.5px solid #e0dbd3;
  padding: 11px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-family: 'DM Sans', sans-serif;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-reset:hover { background: #ede9e4; }

/* ── Products Table Card ──────────────────────────────────── */
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
  font-size: 15px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.table-toolbar h2::before {
  content: '';
  display: inline-block;
  width: 4px; height: 18px;
  background: #d11d1d;
  border-radius: 2px;
}

.toolbar-right { display: flex; align-items: center; gap: 10px; }

.search-box {
  display: flex;
  align-items: center;
  gap: 7px;
  background: #faf9f7;
  border: 1.5px solid #e0dbd3;
  border-radius: 8px;
  padding: 7px 12px;
  transition: border-color 0.2s;
}

.search-box:focus-within { border-color: #d11d1d; box-shadow: 0 0 0 3px rgba(209,29,29,0.07); }
.search-box svg { width: 14px; height: 14px; opacity: 0.4; flex-shrink: 0; }
.search-box input {
  border: none; background: transparent;
  font-size: 13px; font-family: 'DM Sans', sans-serif;
  color: #1a1a1a; outline: none; width: 160px;
}
.search-box input::placeholder { color: #aaa; }

.filter-select {
  padding: 7px 28px 7px 12px;
  border: 1.5px solid #e0dbd3;
  border-radius: 8px;
  font-size: 13px;
  font-family: 'DM Sans', sans-serif;
  color: #555;
  background: #faf9f7 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23999' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E") no-repeat right 10px center;
  appearance: none;
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s;
}
.filter-select:focus { border-color: #d11d1d; }

.table-wrap { overflow-x: auto; }

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

thead tr {
  background: #faf9f7;
  border-bottom: 1.5px solid #eee9e2;
}

thead th {
  padding: 10px 18px;
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
  padding: 12px 18px;
  color: #2a2a2a;
  vertical-align: middle;
}

/* Product image thumbnail */
.product-thumb {
  width: 44px; height: 44px;
  border-radius: 8px;
  object-fit: cover;
  border: 1px solid #ede9e2;
  background: #f5f3f0;
}

.thumb-placeholder {
  width: 44px; height: 44px;
  border-radius: 8px;
  background: #f5f3f0;
  border: 1px solid #ede9e2;
  display: flex; align-items: center; justify-content: center;
}
.thumb-placeholder svg { width: 20px; height: 20px; opacity: 0.25; }

/* Product name cell */
.cell-product { display: flex; align-items: center; gap: 12px; }
.product-name  { font-weight: 500; }
.product-id    { font-size: 12px; color: #aaa; font-family: 'DM Mono', monospace; }

/* Price */
.cell-price {
  font-family: 'DM Mono', monospace;
  font-size: 14px;
  font-weight: 500;
  color: #1a7a3e !important;
}

/* Category badge */
.cat-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  background: #f5f0ea;
  color: #7a5c35;
  border: 1px solid #e8ddd0;
}

/* Action buttons */
.cell-actions { display: flex; gap: 6px; }

.btn-action {
  padding: 5px 11px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.15s;
  border: 1.5px solid transparent;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  font-family: 'DM Sans', sans-serif;
}

.btn-edit   { background: #eef4ff; color: #185fa5; border-color: #c0d8f5; }
.btn-edit:hover   { background: #ddeafc; border-color: #85b7eb; }
.btn-delete { background: #fff1f1; color: #a32d2d; border-color: #f7c1c1; }
.btn-delete:hover { background: #fde3e3; border-color: #f09595; }

/* Empty state */
.empty-state {
  padding: 52px 24px;
  text-align: center;
  color: #bbb;
}
.empty-state svg { width: 44px; height: 44px; opacity: 0.2; margin-bottom: 12px; }
.empty-state p { font-size: 14px; }

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 1100px) {
  .layout-cols { grid-template-columns: 320px 1fr; }
}
@media (max-width: 860px) {
  .layout-cols { grid-template-columns: 1fr; }
  .form-card { position: static; }
  .main-content { padding: 20px; }
}
</style>
</head>
<body class="dashboard-page">
<div class="dashboard">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header-left">
        <h1>Products</h1>
        <p>Manage your product catalogue</p>
      </div>
      <?php if ($products_result): ?>
        <span class="count-badge"><?php echo $products_result->num_rows; ?> products</span>
      <?php endif; ?>
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

      <!-- ── Add Product Form ── -->
      <div class="form-card">
        <div class="card-heading">Add New Product</div>

        <form action="products.php" method="post" enctype="multipart/form-data" id="addForm">
          <input type="hidden" name="action" value="add">

          <div class="form-group">
            <label for="product_name">Product Name</label>
            <input type="text" name="product_name" id="product_name" placeholder="e.g. Espresso Blend" required>
          </div>

          <div class="form-group">
            <label for="category_id">Category</label>
            <select name="category_id" id="category_id">
              <option value="0">— No Category —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>">
                  <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="price">Price (₱)</label>
            <input type="number" name="price" id="price" placeholder="0.00" step="0.01" min="0" required>
          </div>

          <div class="form-group">
            <label>Product Image</label>
            <div class="upload-zone" id="uploadZone">
              <input type="file" name="picture" id="pictureInput" accept="image/*" onchange="previewImage(this)">
              <div class="upload-icon">
                <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/><line x1="12" y1="3" x2="12" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              </div>
              <p><strong>Click to upload</strong> or drag & drop<br>JPG, PNG, GIF, WEBP</p>
              <div class="upload-preview" id="uploadPreview">
                <img id="previewImg" src="" alt="Preview">
              </div>
            </div>
          </div>

          <div class="form-footer">
            <button type="reset" class="btn-reset" onclick="clearPreview()">Clear</button>
            <button type="submit" class="btn-primary">
              <svg viewBox="0 0 16 16"><path d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2zm0 1a5 5 0 1 1 0 10A5 5 0 0 1 8 3zm0 2.5a.5.5 0 0 0-.5.5v2H5.5a.5.5 0 0 0 0 1H7.5v2a.5.5 0 0 0 1 0V9.5h2a.5.5 0 0 0 0-1H8.5V6a.5.5 0 0 0-.5-.5z"/></svg>
              Add Product
            </button>
          </div>
        </form>
      </div>

      <!-- ── Products Table ── -->
      <div class="table-card">
        <div class="table-toolbar">
          <h2>Product List</h2>
          <div class="toolbar-right">
            <select class="filter-select" id="catFilter" onchange="filterTable()">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['category_name']); ?>">
                  <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="search-box">
              <svg viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
              </svg>
              <input type="text" id="searchInput" placeholder="Search products…" oninput="filterTable()">
            </div>
          </div>
        </div>

        <div class="table-wrap">
          <table id="productsTable">
            <thead>
              <tr>
                <th style="width:52px;">Image</th>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($products_result && $products_result->num_rows > 0): ?>
              <?php while ($p = $products_result->fetch_assoc()): ?>
              <tr data-category="<?php echo htmlspecialchars($p['category_name'] ?? ''); ?>">
                <td>
                  <?php if (!empty($p['picture']) && file_exists($p['picture'])): ?>
                    <img class="product-thumb" src="<?php echo htmlspecialchars($p['picture']); ?>" alt="<?php echo htmlspecialchars($p['product_name']); ?>">
                  <?php else: ?>
                    <div class="thumb-placeholder">
                      <svg viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>
                      </svg>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="cell-product">
                    <div>
                      <div class="product-name"><?php echo htmlspecialchars($p['product_name']); ?></div>
                      <div class="product-id">#<?php echo str_pad($p['product_id'], 4, '0', STR_PAD_LEFT); ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if (!empty($p['category_name'])): ?>
                    <span class="cat-badge"><?php echo htmlspecialchars($p['category_name']); ?></span>
                  <?php else: ?>
                    <span style="color:#bbb;font-size:13px;">—</span>
                  <?php endif; ?>
                </td>
                <td class="cell-price">₱<?php echo number_format($p['price'], 2); ?></td>
                <td>
                  <div class="cell-actions">
                    <a href="edit_product.php?id=<?php echo $p['product_id']; ?>">
                      <button type="button" class="btn-action btn-edit">
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor"><path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/></svg>
                        Edit
                      </button>
                    </a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete \'<?php echo addslashes($p['product_name']); ?>\'? This cannot be undone.');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                      <button type="submit" class="btn-action btn-delete">
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                        Delete
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5">
                  <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p>No products yet. Add your first product using the form.</p>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- /table-card -->

    </div><!-- /layout-cols -->

  </div><!-- /main-content -->
</div><!-- /dashboard -->

<script>
// Live search + category filter
function filterTable() {
  var query = document.getElementById('searchInput').value.toLowerCase().trim();
  var cat   = document.getElementById('catFilter').value.toLowerCase().trim();
  var rows  = document.querySelectorAll('#productsTable tbody tr');
  rows.forEach(function(row) {
    var text    = row.textContent.toLowerCase();
    var rowCat  = (row.dataset.category || '').toLowerCase();
    var matchQ  = !query || text.includes(query);
    var matchC  = !cat   || rowCat === cat;
    row.style.display = (matchQ && matchC) ? '' : 'none';
  });
}

// Image preview
function previewImage(input) {
  var preview = document.getElementById('uploadPreview');
  var img     = document.getElementById('previewImg');
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      img.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function clearPreview() {
  document.getElementById('uploadPreview').style.display = 'none';
  document.getElementById('previewImg').src = '';
}

// Drag-over effect on upload zone
var zone = document.getElementById('uploadZone');
if (zone) {
  zone.addEventListener('dragover',  function(e){ e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', function()  { zone.classList.remove('dragover'); });
  zone.addEventListener('drop',      function(e){ e.preventDefault(); zone.classList.remove('dragover'); });
}
</script>

</body>
</html>