<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/db.php';

$query = "
SELECT 
    i.inventory_id,
    i.product_id,
    p.product_name,
    p.picture,
    i.quantity,
    i.created_at
FROM inventory i
JOIN products p ON i.product_id = p.product_id
ORDER BY p.product_name
";

$result = mysqli_query($conn, $query);

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}

$total_products = count($rows);
$low_stock      = count(array_filter($rows, fn($r) => $r['quantity'] <= 5 && $r['quantity'] > 0));
$out_of_stock   = count(array_filter($rows, fn($r) => $r['quantity'] == 0));
?>

<!DOCTYPE html>
<html>
<head>
<title>Inventory</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="./styles/index.css">

<style>

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'DM Sans', sans-serif;
    background: #F5F6FA;
    color: #1A1A1A;
}

/* ── CONTAINER ── */
.inventory-container {
    background: #fff;
    padding: 28px 28px 32px;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

/* ── HEADER ── */
.inventory-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 22px;
}

.inventory-header h2 {
    font-size: 20px;
    font-weight: 500;
    letter-spacing: -0.3px;
    color: #111;
}

.inventory-header p {
    font-size: 13px;
    color: #888;
    margin-top: 3px;
}

/* ── STATS ── */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.stat-card {
    background: #F8F9FC;
    border-radius: 10px;
    padding: 14px 18px;
}

.stat-label {
    font-size: 11px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 5px;
}

.stat-value        { font-size: 24px; font-weight: 500; color: #111; }
.stat-value.low    { color: #A87200; }
.stat-value.out    { color: #C0392B; }

/* ── SEARCH & FILTER ── */
.toolbar {
    display: flex;
    gap: 10px;
    margin-bottom: 18px;
    align-items: center;
    flex-wrap: wrap;
}

.search-wrap {
    position: relative;
    flex: 1;
    max-width: 300px;
}

.search-wrap svg {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #BBB;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 7px 12px 7px 34px;
    border: 1px solid #E5E5E5;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    color: #111;
    background: #FAFAFA;
    outline: none;
    transition: border 0.15s;
}

.search-input:focus { border-color: #AAAAAA; background: #fff; }

.filter-btn {
    border: 1px solid #E0E0E0;
    background: transparent;
    border-radius: 20px;
    padding: 5px 16px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    color: #777;
    transition: all 0.15s;
    margin: 0;
}

.filter-btn.active,
.filter-btn:hover {
    background: #111;
    color: #fff;
    border-color: #111;
}

/* ── TABLE ── */
.table-wrap {
    border: 1px solid #EBEBEB;
    border-radius: 12px;
    overflow: hidden;
}

.inventory-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
    table-layout: fixed;
}

.inventory-table col.c-product { width: 200px; }
.inventory-table col.c-image   { width: 80px;  }
.inventory-table col.c-stock   { width: 120px; }
.inventory-table col.c-updated { width: 120px; }
.inventory-table col.c-action  { width: 220px; }

.inventory-table thead th {
    background: #F8F9FC;
    padding: 10px 14px;
    text-align: left;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #999;
    border-bottom: 1px solid #EBEBEB;
}

.inventory-table tbody td {
    padding: 13px 14px;
    border-bottom: 1px solid #F0F0F0;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.inventory-table tbody tr:last-child td { border-bottom: none; }
.inventory-table tbody tr:hover { background: #FAFAFA; }

/* ── PRODUCT NAME ── */
.product-name {
    font-weight: 500;
    font-size: 13.5px;
    color: #111;
}

/* ── PRODUCT IMAGE ── */
.product-img {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #EBEBEB;
    display: block;
}

/* ── STOCK DISPLAY ── */
.stock-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stock-num {
    font-family: 'JetBrains Mono', monospace;
    font-size: 14px;
    font-weight: 500;
    color: #111;
}

.stock-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
}

.badge-ok  { background: #D4EDDA; color: #1A5C2E; }
.badge-low { background: #FEF3CD; color: #8C6500; }
.badge-out { background: #F8D7DA; color: #7A1F28; }

/* ── DATE ── */
.date-text {
    font-size: 12.5px;
    color: #999;
}

/* ── ACTION FORM ── */
.stock-form {
    display: flex;
    gap: 5px;
    align-items: center;
}

.qty-input {
    width: 64px;
    padding: 5px 8px;
    border: 1px solid #E0E0E0;
    border-radius: 6px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    text-align: center;
    color: #111;
    background: #FAFAFA;
    outline: none;
    transition: border 0.15s;
}

.qty-input:focus { border-color: #AAAAAA; background: #fff; }

.qty-input::-webkit-inner-spin-button,
.qty-input::-webkit-outer-spin-button { -webkit-appearance: none; }
.qty-input { -moz-appearance: textfield; }

.btn-add, .btn-deduct {
    border: none;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: opacity 0.15s, background 0.15s;
    margin: 0;
}

.btn-add          { background: #1A7A45; color: #fff; }
.btn-add:hover    { background: #15633A; }
.btn-deduct       { background: #EFEFEF; color: #555; }
.btn-deduct:hover { background: #E3E3E3; }

/* ── EMPTY ── */
.empty-row td {
    text-align: center;
    padding: 3rem 1rem !important;
    color: #BBB;
    font-size: 14px;
}

/* ── HIDDEN ── */
.inv-row.hidden { display: none; }

/* ── PAGINATION ── */
.pagination-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 20px;
}

.pg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 999px;
    border: 1px solid #E0E0E0;
    background: #fff;
    color: #555;
    font-size: 13px;
    font-weight: 500;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: all 0.15s;
    margin: 0;
    text-decoration: none;
}

.pg-btn:hover:not(.disabled):not(.active) {
    border-color: #111;
    color: #111;
    background: #F5F5F5;
}

.pg-btn.active {
    background: #111;
    border-color: #111;
    color: #fff;
    cursor: default;
}

.pg-btn.disabled {
    opacity: 0.3;
    cursor: not-allowed;
    pointer-events: none;
}

.pg-ellipsis {
    font-size: 13px;
    color: #BBB;
    padding: 0 4px;
    font-family: 'DM Sans', sans-serif;
}

.pg-info {
    margin-left: auto;
    font-size: 12px;
    color: #AAA;
    font-family: 'DM Sans', sans-serif;
}

</style>
</head>

<body class="dashboard-page">
<div class="dashboard">
  <?php require_once __DIR__ . '/sidebar.php'; ?>

  <div class="main-content">

    <div class="inventory-container">

      <!-- Header -->
      <div class="inventory-header">
        <div>
          <h2>Inventory</h2>
          <p>Track and manage your product stock levels</p>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Products</div>
          <div class="stat-value"><?php echo $total_products; ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Low Stock</div>
          <div class="stat-value low"><?php echo $low_stock; ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Out of Stock</div>
          <div class="stat-value out"><?php echo $out_of_stock; ?></div>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="toolbar">
        <div class="search-wrap">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input class="search-input" type="text" placeholder="Search products…" oninput="searchTable(this.value)">
        </div>
        <button class="filter-btn active" onclick="filterStock('ALL', this)">All</button>
        <button class="filter-btn" onclick="filterStock('LOW', this)">Low Stock</button>
        <button class="filter-btn" onclick="filterStock('OUT', this)">Out of Stock</button>
      </div>

      <!-- Table -->
      <div class="table-wrap">
        <table class="inventory-table">
          <colgroup>
            <col class="c-product">
            <col class="c-image">
            <col class="c-stock">
            <col class="c-updated">
            <col class="c-action">
          </colgroup>
          <thead>
            <tr>
              <th>Product</th>
              <th>Product Image</th>
              <th>Stock</th>
              <th>Last Updated</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="inv-tbody">

          <?php foreach($rows as $row):
            $qty = (int)$row['quantity'];

            if ($qty == 0) {
                $badgeClass = 'badge-out';
                $badgeLabel = 'Out of stock';
                $stockLevel = 'OUT';
            } elseif ($qty <= 5) {
                $badgeClass = 'badge-low';
                $badgeLabel = 'Low stock';
                $stockLevel = 'LOW';
            } else {
                $badgeClass = 'badge-ok';
                $badgeLabel = 'In stock';
                $stockLevel = 'OK';
            }
          ?>

          <tr class="inv-row"
              data-name="<?php echo strtolower(htmlspecialchars($row['product_name'])); ?>"
              data-level="<?php echo $stockLevel; ?>">

            <!-- Product Name -->
            <td><span class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></span></td>

            <!-- Image -->
            <td>
              <?php if (!empty($row['picture'])): ?>
                <img class="product-img"
                     src="<?php echo htmlspecialchars($row['picture']); ?>"
                     alt="<?php echo htmlspecialchars($row['product_name']); ?>">
              <?php else: ?>
                <div class="product-img" style="background:#F0F0F0;display:flex;align-items:center;justify-content:center;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CCC" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
              <?php endif; ?>
            </td>

            <!-- Stock -->
            <td>
              <div class="stock-cell">
                <span class="stock-num"><?php echo $qty; ?></span>
                <span class="stock-badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
              </div>
            </td>

            <!-- Last Updated -->
            <td><span class="date-text"><?php echo date("M d, Y", strtotime($row['created_at'])); ?></span></td>

            <!-- Action -->
            <td>
              <form method="POST" action="update_stock.php" class="stock-form">
                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                <input type="number" name="quantity" placeholder="Qty" required min="1" class="qty-input">
                <button type="submit" name="action" value="add" class="btn-add">Add</button>
                <button type="submit" name="action" value="deduct" class="btn-deduct">Deduct</button>
              </form>
            </td>

          </tr>

          <?php endforeach; ?>

          </tbody>
        </table>
      </div>

      <!-- Pagination bar -->
      <div class="pagination-bar" id="paginationBar"></div>

    </div><!-- /inventory-container -->

  </div><!-- /main-content -->
</div><!-- /dashboard -->

<script>
const PER_PAGE     = 7;
let currentFilter  = 'ALL';
let currentSearch  = '';
let currentPage    = 1;

/* Returns all rows that match current search + filter */
function getMatchedRows() {
    return Array.from(document.querySelectorAll('.inv-row')).filter(row => {
        const nameMatch  = row.dataset.name.includes(currentSearch.toLowerCase());
        const levelMatch = currentFilter === 'ALL' || row.dataset.level === currentFilter;
        return nameMatch && levelMatch;
    });
}

function applyFilters() {
    currentPage = 1;
    renderPage();
}

function renderPage() {
    const allRows   = Array.from(document.querySelectorAll('.inv-row'));
    const matched   = getMatchedRows();
    const total     = matched.length;
    const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));

    /* clamp page */
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * PER_PAGE;
    const end   = start + PER_PAGE;
    const pageRows = matched.slice(start, end);

    /* hide all, then show only current page's matched rows */
    allRows.forEach(r => r.classList.add('hidden'));
    pageRows.forEach(r => r.classList.remove('hidden'));

    /* empty state */
    const tbody   = document.getElementById('inv-tbody');
    let emptyRow  = tbody.querySelector('.empty-row');
    if (total === 0) {
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.className = 'empty-row';
            emptyRow.innerHTML = '<td colspan="5">No products found.</td>';
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
    const prev = makeBtn('&#8592;', currentPage === 1, false, () => { currentPage--; renderPage(); });
    bar.appendChild(prev);

    /* Page numbers — show up to 5 around current */
    const rangeStart = Math.max(1, currentPage - 2);
    const rangeEnd   = Math.min(totalPages, currentPage + 2);

    if (rangeStart > 1) {
        bar.appendChild(makeBtn('1', false, false, () => { currentPage = 1; renderPage(); }));
        if (rangeStart > 2) bar.appendChild(makeEllipsis());
    }

    for (let i = rangeStart; i <= rangeEnd; i++) {
        bar.appendChild(makeBtn(i, false, i === currentPage, () => {
            const page = parseInt(this.textContent);
            currentPage = page;
            renderPage();
        }));
    }

    /* Build numbered buttons separately to capture i correctly */
    bar.innerHTML = '';
    bar.appendChild(prev);

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
    const next = makeBtn('&#8594;', currentPage === totalPages, false, () => { currentPage++; renderPage(); });
    bar.appendChild(next);

    /* Info */
    const info = document.createElement('span');
    info.className = 'pg-info';
    info.textContent = start + '–' + end + ' of ' + total + ' products';
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
    span.textContent = '…';
    return span;
}

function filterStock(level, btn) {
    currentFilter = level;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

function searchTable(val) {
    currentSearch = val.trim();
    applyFilters();
}

/* Initial render */
renderPage();
</script>

</body>
</html>