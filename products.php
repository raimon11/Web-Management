<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

/* GET CATEGORIES */
$categoryQuery = "SELECT * FROM categories";
$categoryResult = mysqli_query($conn, $categoryQuery);

/* GET SELECTED FILTERS */
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$selectedPrice    = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$searchTerm       = isset($_GET['search']) ? trim($_GET['search']) : '';

/* PRODUCT QUERY */
$query = "SELECT 
            p.product_id,
            p.product_name,
            p.price,
            p.picture,
            c.category_name,
            i.quantity
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN inventory i ON p.product_id = i.product_id";

$conditions = [];

if ($selectedCategory !== '') {
    $conditions[] = "p.category_id = '" . mysqli_real_escape_string($conn, $selectedCategory) . "'";
}

if ($selectedPrice !== '') {
    if ($selectedPrice === 'under_500') {
        $conditions[] = "p.price < 500";
    } elseif ($selectedPrice === '500_1000') {
        $conditions[] = "p.price BETWEEN 500 AND 1000";
    } elseif ($selectedPrice === '1000_2000') {
        $conditions[] = "p.price BETWEEN 1000 AND 2000";
    } elseif ($selectedPrice === 'over_2000') {
        $conditions[] = "p.price > 2000";
    }
}

if ($searchTerm !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $searchTerm);
    $conditions[] = "(p.product_name LIKE '%$safeSearch%' OR c.category_name LIKE '%$safeSearch%')";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

/* PAGINATION */
$perPage     = 8;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM (" . $query . ") AS countQuery");
$totalRow    = mysqli_fetch_assoc($countResult);
$totalItems  = (int)$totalRow['total'];
$totalPages  = max(1, (int)ceil($totalItems / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset      = ($currentPage - 1) * $perPage;

$query  .= " LIMIT " . $perPage . " OFFSET " . $offset;
$result  = mysqli_query($conn, $query);

/* Collect categories into array for pill rendering */
$categories = [];
while ($cat = mysqli_fetch_assoc($categoryResult)) {
    $categories[] = $cat;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>COZIEST</title>
<link rel="stylesheet" href="./styles/index.css">
</head>
<body>

<div class="dashboard">

<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">

<header class="topbar">
    <div>
        <h3>Hello, <strong><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></strong></h3>
        <p>Welcome to our Coziest Shop</p>
    </div>
    <div class="topbar-right">
        <button type="button" class="cart-button" id="cartButton">
            🛒
            <span class="cart-count" id="cartCount">0</span>
        </button>
    </div>
</header>

<!-- PILL FILTER BAR -->
<div class="filter-container">
    <!-- Hidden form that gets submitted when pills are clicked -->
    <form method="GET" id="filterForm">
        <input type="hidden" name="category" id="categoryInput" value="<?php echo htmlspecialchars($selectedCategory); ?>">
        <input type="hidden" name="price_range" id="priceInput" value="<?php echo htmlspecialchars($selectedPrice); ?>">
        <input type="hidden" name="search" id="searchInput" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES); ?>">
        <input type="hidden" name="page" id="pageInput" value="1">
    </form>

    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px;">

        <!-- Category pills -->
        <div class="filter-section">
            <span class="filter-section-label">Category</span>
            <button type="button" class="pill-btn <?php echo $selectedCategory === '' ? 'selected' : ''; ?>"
                onclick="setFilter('category', '')">All</button>
            <?php foreach ($categories as $cat): ?>
                <button type="button"
                    class="pill-btn <?php echo $selectedCategory == $cat['category_id'] ? 'selected' : ''; ?>"
                    onclick="setFilter('category', '<?php echo $cat['category_id']; ?>')">
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="filter-divider"></div>

        <!-- Price pills -->
        <div class="filter-section">
            <span class="filter-section-label">Price</span>
            <button type="button" class="pill-btn <?php echo $selectedPrice === '' ? 'selected' : ''; ?>"
                onclick="setFilter('price', '')">All</button>
            <button type="button" class="pill-btn <?php echo $selectedPrice === 'under_500' ? 'selected' : ''; ?>"
                onclick="setFilter('price', 'under_500')">Under ₱500</button>
            <button type="button" class="pill-btn <?php echo $selectedPrice === '500_1000' ? 'selected' : ''; ?>"
                onclick="setFilter('price', '500_1000')">₱500–1000</button>
            <button type="button" class="pill-btn <?php echo $selectedPrice === '1000_2000' ? 'selected' : ''; ?>"
                onclick="setFilter('price', '1000_2000')">₱1000–2000</button>
            <button type="button" class="pill-btn <?php echo $selectedPrice === 'over_2000' ? 'selected' : ''; ?>"
                onclick="setFilter('price', 'over_2000')">Over ₱2000</button>
        </div>

        <!-- Search -->
        <div class="search-pill-wrap" style="margin-left:auto;">
            <span class="search-icon">🔍</span>
            <input
                type="text"
                placeholder="Search products..."
                value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES); ?>"
                id="searchBox"
                onkeyup="if(event.key === 'Enter'){ submitSearch(this.value); }"
            >
        </div>

    </div>
</div>


<!-- PRODUCTS -->
<div class="unit-grid">

<?php
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $availableQty = is_null($row['quantity']) ? 0 : (int)$row['quantity'];
        $outOfStock   = $availableQty <= 0;
        $lowStock     = !$outOfStock && $availableQty <= 5;

        /* Stock badge class + label */
        if ($outOfStock) {
            $stockClass = 'out-of-stock';
            $stockLabel = 'Out of stock';
        } elseif ($lowStock) {
            $stockClass = 'low-stock';
            $stockLabel = 'Only ' . $availableQty . ' left';
        } else {
            $stockClass = 'in-stock';
            $stockLabel = $availableQty . ' in stock';
        }
?>

<div class="unit-card"
     data-product-id="<?php echo $row['product_id']; ?>"
     data-product-name="<?php echo htmlspecialchars($row['product_name'], ENT_QUOTES); ?>"
     data-product-price="<?php echo $row['price']; ?>"
     data-product-picture="<?php echo htmlspecialchars($row['picture']); ?>">

    <!-- Product image + category badge -->
    <div style="position:relative;">
        <img src="<?php echo htmlspecialchars($row['picture']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
        <span class="category-badge"><?php echo htmlspecialchars($row['category_name']); ?></span>
    </div>

    <div class="unit-info">
        <h4><?php echo htmlspecialchars($row['product_name']); ?></h4>

        <!-- Emphasized price -->
        <div class="price-display">
            <span class="price-amount">₱<?php echo number_format($row['price'], 2); ?></span>
        </div>

        <!-- Stock badge pill -->
        <div class="stock-badge <?php echo $stockClass; ?>">
            <span class="stock-dot"></span>
            <?php echo $stockLabel; ?>
        </div>

        <!-- Order controls -->
        <?php if (!$outOfStock): ?>
        <div class="order-controls" data-max="<?php echo $availableQty; ?>">
            <div class="qty-controls">
                <button type="button" class="qty-btn minus">−</button>
                <input
                    type="number"
                    class="qty-input"
                    value="1"
                    min="1"
                    max="<?php echo $availableQty; ?>"
                >
                <button type="button" class="qty-btn plus">+</button>
            </div>
            <button type="button" class="order-btn">🛒 Add to Cart</button>
        </div>
        <?php else: ?>
        <div class="order-controls">
            <button type="button" class="order-btn" disabled>Unavailable</button>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
    }
} else {
    echo "<p style='padding:20px; color:#888;'>No products found.</p>";
}
?>

</div><!-- /.unit-grid -->

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<div class="pagination">

    <?php
    /* Build base query string preserving all filters */
    $baseParams = http_build_query([
        'category'    => $selectedCategory,
        'price_range' => $selectedPrice,
        'search'      => $searchTerm,
    ]);

    /* Prev button */
    if ($currentPage > 1): ?>
        <a class="page-btn" href="?<?php echo $baseParams; ?>&page=<?php echo $currentPage - 1; ?>">&#8592;</a>
    <?php else: ?>
        <span class="page-btn disabled">&#8592;</span>
    <?php endif; ?>

    <?php
    /* Page number pills — show up to 5 around current */
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);

    if ($start > 1): ?>
        <a class="page-btn" href="?<?php echo $baseParams; ?>&page=1">1</a>
        <?php if ($start > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
        <a class="page-btn <?php echo $i === $currentPage ? 'active' : ''; ?>"
           href="?<?php echo $baseParams; ?>&page=<?php echo $i; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
        <a class="page-btn" href="?<?php echo $baseParams; ?>&page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a>
    <?php endif; ?>

    <?php /* Next button */
    if ($currentPage < $totalPages): ?>
        <a class="page-btn" href="?<?php echo $baseParams; ?>&page=<?php echo $currentPage + 1; ?>">&#8594;</a>
    <?php else: ?>
        <span class="page-btn disabled">&#8594;</span>
    <?php endif; ?>

    <span class="page-info">Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?> &nbsp;·&nbsp; <?php echo $totalItems; ?> products</span>

</div>
<?php endif; ?>
</div><!-- /.main-content -->
</div><!-- /.dashboard -->

<!-- Cart modal -->
<div class="cart-modal-backdrop" id="cartModalBackdrop">
  <div class="cart-modal">
    <h3>Your Cart 🛒</h3>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-footer">
      <div>
        <label style="font-size:13px; font-weight:700; color:#555; display:block; margin-bottom:6px;">Proof of Payment</label>
        <input type="file" id="paymentProof" accept="image/*">
      </div>
      <div class="cart-total" id="cartTotal">Total: ₱0.00</div>
    </div>
    <div class="cart-actions">
      <button type="button" class="clear-cart" id="clearCartBtn">Clear</button>
      <button type="button" class="place-order" id="placeOrderBtn">Place Order</button>
      <button type="button" class="close-cart" id="closeCartBtn">Close</button>
    </div>
  </div>
</div>

<script>
/* ===== Filter pill logic ===== */
function setFilter(type, value) {
    if (type === 'category') {
        document.getElementById('categoryInput').value = value;
    } else if (type === 'price') {
        document.getElementById('priceInput').value = value;
    }
    document.getElementById('searchInput').value = document.getElementById('searchBox').value;
    document.getElementById('filterForm').submit();
}

function submitSearch(value) {
    document.getElementById('searchInput').value = value;
    document.getElementById('filterForm').submit();
}

/* ===== Cart logic ===== */
document.addEventListener("DOMContentLoaded", function () {
  const CART_KEY = "coziest_cart";

  function loadCart() {
    try {
      const data = localStorage.getItem(CART_KEY);
      return data ? JSON.parse(data) : [];
    } catch (e) { return []; }
  }

  function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
  }

  function getCartCount(cart) {
    return cart.reduce((sum, item) => sum + item.quantity, 0);
  }

  function getCartTotal(cart) {
    return cart.reduce((sum, item) => sum + item.quantity * item.price, 0);
  }

  function updateCartBadge() {
    const count = getCartCount(loadCart());
    const el = document.getElementById("cartCount");
    if (el) el.textContent = count;
  }

  function renderCartModal() {
    const cart = loadCart();
    const itemsEl = document.getElementById("cartItems");
    const totalEl = document.getElementById("cartTotal");
    if (!itemsEl || !totalEl) return;

    itemsEl.innerHTML = "";

    if (cart.length === 0) {
      itemsEl.innerHTML = "<p style='color:#aaa; font-size:14px; padding:10px 0;'>Your cart is empty.</p>";
    } else {
      cart.forEach(item => {
        const row = document.createElement("div");
        row.className = "cart-item";
        row.innerHTML = `
          <img src="${item.picture}" height="44" width="44" alt="product" style="border-radius:8px; object-fit:cover;">
          <span style="flex:1;">${item.name} <span style="color:#aaa;">x${item.quantity}</span></span>
          <span>₱${(item.quantity * item.price).toLocaleString('en-PH', {minimumFractionDigits:2})}</span>
        `;
        itemsEl.appendChild(row);
      });
    }

    totalEl.textContent = "Total: ₱" + getCartTotal(loadCart()).toLocaleString('en-PH', {minimumFractionDigits:2});
  }

  function openCartModal() {
    renderCartModal();
    const backdrop = document.getElementById("cartModalBackdrop");
    if (backdrop) backdrop.style.display = "flex";
  }

  function closeCartModal() {
    const backdrop = document.getElementById("cartModalBackdrop");
    if (backdrop) backdrop.style.display = "none";
  }

  function addToCart(productId, name, price, quantity, max, picture) {
    const cart = loadCart();
    const existing = cart.find(item => item.id === productId);
    if (existing) {
      existing.quantity = Math.min(existing.quantity + quantity, max);
    } else {
      cart.push({ id: productId, name, price, quantity: Math.min(quantity, max), picture });
    }
    saveCart(cart);
    updateCartBadge();
  }

  /* Qty controls */
  document.querySelectorAll(".order-controls").forEach(function (controls) {
    const card    = controls.closest(".unit-card");
    const max     = parseInt(controls.getAttribute("data-max"), 10) || 0;
    const minus   = controls.querySelector(".qty-btn.minus");
    const plus    = controls.querySelector(".qty-btn.plus");
    const input   = controls.querySelector(".qty-input");
    const orderBtn = controls.querySelector(".order-btn");

    function sync() {
      let v = parseInt(input.value, 10);
      if (isNaN(v) || v < 1) v = 1;
      if (v > max) v = max;
      input.value = v;
      if (minus) minus.disabled = v <= 1;
      if (plus)  plus.disabled  = v >= max;
    }

    if (minus) minus.addEventListener("click", () => { input.value = parseInt(input.value,10)-1; sync(); });
    if (plus)  plus.addEventListener("click",  () => { input.value = parseInt(input.value,10)+1; sync(); });
    if (input) input.addEventListener("input", sync);

    if (orderBtn && card) {
      orderBtn.addEventListener("click", function () {
        const id      = card.getAttribute("data-product-id");
        const name    = card.getAttribute("data-product-name");
        const price   = parseFloat(card.getAttribute("data-product-price")) || 0;
        const picture = card.getAttribute("data-product-picture");
        const qty     = parseInt(input.value, 10) || 1;
        if (!id || !name || !price || max <= 0) return;
        addToCart(id, name, price, qty, max, picture);

        /* Brief feedback on button */
        const orig = orderBtn.textContent;
        orderBtn.textContent = "✓ Added!";
        orderBtn.style.background = "#2d7a47";
        setTimeout(() => {
          orderBtn.textContent = orig;
          orderBtn.style.background = "";
        }, 1200);
      });
    }

    sync();
  });

  /* Cart button */
  const cartBtn = document.getElementById("cartButton");
  if (cartBtn) cartBtn.addEventListener("click", openCartModal);

  document.getElementById("closeCartBtn")?.addEventListener("click", closeCartModal);

  document.getElementById("clearCartBtn")?.addEventListener("click", function () {
    saveCart([]);
    updateCartBadge();
    renderCartModal();

    document.querySelectorAll(".order-controls").forEach(function (controls) {
      const max   = parseInt(controls.getAttribute("data-max"), 10) || 0;
      const input = controls.querySelector(".qty-input");
      const minus = controls.querySelector(".qty-btn.minus");
      const plus  = controls.querySelector(".qty-btn.plus");
      if (!input) return;
      input.value = 1;
      if (minus) minus.disabled = true;
      if (plus)  plus.disabled  = 1 >= max;
    });
  });

  document.getElementById("cartModalBackdrop")?.addEventListener("click", function (e) {
    if (e.target === this) closeCartModal();
  });

  /* Place order */
  document.getElementById("placeOrderBtn")?.addEventListener("click", function () {
    const cart      = loadCart();
    const fileInput = document.getElementById("paymentProof");

    if (cart.length === 0) { alert("Your cart is empty."); return; }
    if (!fileInput.files[0]) { alert("Please upload proof of payment."); return; }

    const formData = new FormData();
    formData.append("payment_proof", fileInput.files[0]);
    formData.append("cart", JSON.stringify(cart));

    fetch("place_order.php", { method: "POST", body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("Order placed successfully! 🎉");
          localStorage.removeItem("coziest_cart");
          updateCartBadge();
          renderCartModal();
        } else {
          alert("Order failed. Please try again.");
        }
      })
      .catch(() => alert("Network error. Please try again."));
  });

  updateCartBadge();
});
</script>

</body>
</html>