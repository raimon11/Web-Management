<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$message = '';
$product = null;

// ── Fetch product by ID ───────────────────────────────────────
if (!isset($_GET['id']) || !($pid = intval($_GET['id']))) {
    header("Location: products.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT product_id, product_name, price, picture, category_id 
    FROM products 
    WHERE product_id = ?
");
$stmt->bind_param("i", $pid);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    header("Location: products.php");
    exit();
}
$stmt->close();

// ── Handle Update Product ─────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $product_name = trim($_POST['product_name']);
    $category_id  = intval($_POST['category_id']);
    $price        = floatval($_POST['price']);
    $picture      = $product['picture']; // keep existing picture by default

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
                if ($product['picture'] && file_exists($product['picture'])) unlink($product['picture']);
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
            $stmt = $conn->prepare("
                UPDATE products 
                SET product_name = ?, category_id = ?, price = ?, picture = ? 
                WHERE product_id = ?
            ");
            $stmt->bind_param("sidsi", $product_name, $category_id, $price, $picture, $pid);
            if ($stmt->execute()) {
                $message = ['type' => 'success', 'text' => 'Product "' . htmlspecialchars($product_name) . '" updated successfully!'];
                $product['product_name'] = $product_name;
                $product['category_id']  = $category_id;
                $product['price']        = $price;
                $product['picture']      = $picture;
            } else {
                $message = ['type' => 'error', 'text' => 'Database error: ' . $stmt->error];
            }
            $stmt->close();
        }
    }
}

// ── Fetch categories for dropdown ──────────────────────────────
$cat_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = [];
if ($cat_result) {
    while ($c = $cat_result->fetch_assoc()) $categories[] = $c;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Product — Settings</title>
<link rel="stylesheet" href="./styles/index.css">
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f5f5;
    color: #333;
}

.dashboard {
    display: flex;
    min-height: 100vh;
}

/* Main Content */
.main-content {
    flex: 1;
    padding: 40px 30px;
}

.page-header h1 {
    font-size: 28px;
    margin-bottom: 5px;
}

.page-header p {
    color: #666;
    margin: 0;
}

.alert {
    padding: 12px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    font-weight: 500;
}

.alert-success {
    background-color: #e6f7e6;
    color: #2d7a2d;
}

.alert-error {
    background-color: #fde2e2;
    color: #a33a3a;
}

.alert-icon {
    margin-right: 10px;
    font-weight: bold;
}

/* Form Card */
.form-card {
    background-color: #fff;
    padding: 30px 25px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    max-width: 600px;
}

.form-card .card-heading {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 8px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
    transition: all 0.2s;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

/* Upload Zone */
.upload-zone {
    border: 2px dashed #ccc;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.upload-zone.dragover {
    border-color: #3498db;
    background-color: #f0f8ff;
}

.upload-zone input[type="file"] {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.upload-preview img {
    max-height: 120px;
    border-radius: 8px;
    margin-top: 10px;
    object-fit: cover;
}

/* Buttons */
.form-footer {
    display: flex;
    justify-content: space-between;
    margin-top: 25px;
}

.btn-primary {
    background-color: #3498db;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-primary:hover {
    background-color: #217dbb;
}

.btn-reset {
    background-color: #ccc;
    color: #333;
    padding: 10px 18px;
    border-radius: 6px;
    text-decoration: none;
    transition: background-color 0.2s;
}

.btn-reset:hover {
    background-color: #b3b3b3;
}
</style>
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="page-header">
      <h1>Edit Product</h1>
      <p>Modify product details</p>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-<?php echo $message['type'] === 'success' ? 'success' : 'error'; ?>">
        <span class="alert-icon"><?php echo $message['type'] === 'success' ? '✓' : '!'; ?></span>
        <?php echo $message['text']; ?>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <div class="card-heading">Edit Product</div>
      <form action="edit_product.php?id=<?php echo $product['product_id']; ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update">

        <div class="form-group">
          <label for="product_name">Product Name</label>
          <input type="text" name="product_name" id="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
        </div>

        <div class="form-group">
          <label for="category_id">Category</label>
          <select name="category_id" id="category_id">
            <option value="0">— No Category —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['category_id']; ?>" <?php echo $cat['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['category_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="price">Price (₱)</label>
          <input type="number" name="price" id="price" value="<?php echo number_format($product['price'], 2); ?>" step="0.01" min="0" required>
        </div>

        <div class="form-group">
          <label>Product Image</label>
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="picture" id="pictureInput" accept="image/*" onchange="previewImage(this)">
            <p><strong>Click to upload</strong> or drag & drop<br>JPG, PNG, GIF, WEBP</p>
            <div class="upload-preview" id="uploadPreview">
              <?php if ($product['picture'] && file_exists($product['picture'])): ?>
                <img id="previewImg" src="<?php echo htmlspecialchars($product['picture']); ?>" alt="Preview">
              <?php else: ?>
                <img id="previewImg" src="" alt="Preview">
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="form-footer">
          <a href="products.php" class="btn-reset">Back</a>
          <button type="submit" class="btn-primary">Update Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function previewImage(input) {
    var img = document.getElementById('previewImg');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { img.src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
    }
}

// Drag-and-drop effect
var zone = document.getElementById('uploadZone');
if (zone) {
    zone.addEventListener('dragover', function(e){ e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', function(){ zone.classList.remove('dragover'); });
    zone.addEventListener('drop', function(e){ e.preventDefault(); zone.classList.remove('dragover'); });
}
</script>

</body>
</html>