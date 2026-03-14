<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/db.php';

$query = "
SELECT 
    o.id,
    o.order_number,
    o.customer_id,
    o.total_amount,
    o.payment_method,
    o.proof_of_payment,
    o.status,
    o.created_at
FROM orders o
ORDER BY o.created_at DESC
";

$result = mysqli_query($conn, $query);

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

$pending  = count(array_filter($orders, fn($o) => $o['status'] === 'PENDING'));
$approved = count(array_filter($orders, fn($o) => $o['status'] === 'APPROVED'));
$declined = count(array_filter($orders, fn($o) => $o['status'] === 'DECLINED'));
?>

<!DOCTYPE html>
<html>
<head>
<title>Orders</title>
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

.orders-container {
    background: #fff;
    padding: 28px 28px 32px;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 22px;
}

.orders-header h2 {
    font-size: 20px;
    font-weight: 500;
    letter-spacing: -0.3px;
    color: #111;
}

.orders-header p { font-size: 13px; color: #888; margin-top: 3px; }

.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.stat-card { background: #F8F9FC; border-radius: 10px; padding: 14px 18px; }

.stat-label {
    font-size: 11px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 5px;
}

.stat-value { font-size: 24px; font-weight: 500; }
.stat-value.pending  { color: #A87200; }
.stat-value.approved { color: #1A7A45; }
.stat-value.declined { color: #C0392B; }

.filters { display: flex; gap: 6px; margin-bottom: 18px; flex-wrap: wrap; }

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
.filter-btn:hover { background: #111; color: #fff; border-color: #111; }

.table-wrap { border: 1px solid #EBEBEB; border-radius: 12px; overflow: hidden; }

.orders-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
    table-layout: fixed;
}

.orders-table col.c-order    { width: 115px; }
.orders-table col.c-customer { width: 140px; }
.orders-table col.c-proof    { width: 80px;  }
.orders-table col.c-total    { width: 110px; }
.orders-table col.c-method   { width: 90px;  }
.orders-table col.c-status   { width: 110px; }
.orders-table col.c-date     { width: 110px; }
.orders-table col.c-action   { width: 140px; }

.orders-table thead th {
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

.orders-table tbody td {
    padding: 13px 14px;
    border-bottom: 1px solid #F0F0F0;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.orders-table tbody tr:last-child td { border-bottom: none; }
.orders-table tbody tr:hover { background: #FAFAFA; }

.order-num { font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 500; color: #222; }

.customer-cell { display: flex; align-items: center; gap: 8px; }

.avatar {
    width: 28px; height: 28px; border-radius: 50%;
    background: #E6F0FB; color: #1B65B0;
    font-size: 10px; font-weight: 500;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; text-transform: uppercase;
}

.proof-img {
    width: 40px; height: 40px; border-radius: 8px;
    object-fit: cover; border: 1px solid #E8E8E8;
    cursor: pointer; transition: opacity 0.15s; display: block;
}
.proof-img:hover { opacity: 0.75; }

.no-proof { font-size: 12px; color: #BBBBBB; }

.amount { font-family: 'JetBrains Mono', monospace; font-size: 13px; font-weight: 500; color: #111; }

.badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 12px; font-weight: 500; white-space: nowrap;
}
.badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.badge-pending  { background: #FEF3CD; color: #8C6500; }
.badge-pending::before  { background: #D4A017; }
.badge-approved { background: #D4EDDA; color: #1A5C2E; }
.badge-approved::before { background: #2E8B57; }
.badge-declined { background: #F8D7DA; color: #7A1F28; }
.badge-declined::before { background: #C0392B; }

.date-text { font-size: 12.5px; color: #999; }

.actions { display: flex; gap: 5px; align-items: center; }

.btn-accept, .btn-decline {
    border: none; padding: 5px 12px; border-radius: 6px;
    font-size: 12px; font-weight: 500; cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: opacity 0.15s, background 0.15s;
    margin: 0;
}
.btn-accept       { background: #1A7A45; color: #fff; }
.btn-accept:hover { background: #15633A; }
.btn-decline       { background: #EFEFEF; color: #555; }
.btn-decline:hover { background: #E3E3E3; }

.processed { font-size: 12px; color: #BBBBBB; }

.empty-row td {
    text-align: center;
    padding: 3rem 1rem !important;
    color: #BBB;
    font-size: 14px;
}

.order-row.hidden { display: none; }

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

    <div class="orders-container">

      <div class="orders-header">
        <div>
          <h2>Orders</h2>
          <p>Manage and review customer orders</p>
        </div>
      </div>

      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Pending</div>
          <div class="stat-value pending"><?php echo $pending; ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Approved</div>
          <div class="stat-value approved"><?php echo $approved; ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Declined</div>
          <div class="stat-value declined"><?php echo $declined; ?></div>
        </div>
      </div>

      <div class="filters">
        <button class="filter-btn active" onclick="filterOrders('ALL', this)">All</button>
        <button class="filter-btn" onclick="filterOrders('PENDING', this)">Pending</button>
        <button class="filter-btn" onclick="filterOrders('APPROVED', this)">Approved</button>
        <button class="filter-btn" onclick="filterOrders('DECLINED', this)">Declined</button>
      </div>

      <div class="table-wrap">
        <table class="orders-table">
          <colgroup>
            <col class="c-order">
            <col class="c-customer">
            <col class="c-proof">
            <col class="c-total">
            <col class="c-method">
            <col class="c-status">
            <col class="c-date">
            <col class="c-action">
          </colgroup>
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Proof</th>
              <th>Total</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="orders-tbody">

          <?php foreach($orders as $row):
            $status = $row['status'];
            $badgeClass = match($status) {
              'PENDING'  => 'badge-pending',
              'APPROVED' => 'badge-approved',
              'DECLINED' => 'badge-declined',
              default    => ''
            };
            $statusLabel = ucfirst(strtolower($status));
            $initials = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $row['customer_id']));
            $initials = substr($initials, -2);
          ?>

          <tr class="order-row" data-status="<?php echo $status; ?>">

            <td><span class="order-num"><?php echo htmlspecialchars($row['order_number']); ?></span></td>

            <td>
              <div class="customer-cell">
                <div class="avatar"><?php echo $initials; ?></div>
                <span><?php echo htmlspecialchars($row['customer_id']); ?></span>
              </div>
            </td>

            <td>
              <?php if (!empty($row['proof_of_payment'])): ?>
                <a href="view_payment.php?img=<?php echo urlencode($row['proof_of_payment']); ?>" target="_blank">
                  <img class="proof-img"
                       src="<?php echo htmlspecialchars($row['proof_of_payment']); ?>"
                       alt="Proof of payment">
                </a>
              <?php else: ?>
                <span class="no-proof">None</span>
              <?php endif; ?>
            </td>

            <td><span class="amount">₱<?php echo number_format($row['total_amount'], 2); ?></span></td>

            <td><?php echo htmlspecialchars($row['payment_method']); ?></td>

            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $statusLabel; ?></span></td>

            <td><span class="date-text"><?php echo date("M d, Y", strtotime($row['created_at'])); ?></span></td>

            <td>
              <?php if ($status === 'PENDING'): ?>
                <div class="actions">
                  <form method="POST" action="update_order.php" style="display:inline;">
                    <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                    <input type="hidden" name="action" value="accept">
                    <button type="submit" class="btn-accept">Accept</button>
                  </form>
                  <form method="POST" action="update_order.php" style="display:inline;">
                    <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                    <input type="hidden" name="action" value="decline">
                    <button type="submit" class="btn-decline">Decline</button>
                  </form>
                </div>
              <?php else: ?>
                <span class="processed">Processed</span>
              <?php endif; ?>
            </td>

          </tr>

          <?php endforeach; ?>

          </tbody>
        </table>
      </div>

      <!-- Pagination bar -->
      <div class="pagination-bar" id="paginationBar"></div>

    </div><!-- /orders-container -->

  </div><!-- /main-content -->
</div><!-- /dashboard -->

<script>
const PER_PAGE    = 10;
let currentFilter = 'ALL';
let currentPage   = 1;

function getMatchedRows() {
    return Array.from(document.querySelectorAll('.order-row')).filter(row => {
        return currentFilter === 'ALL' || row.dataset.status === currentFilter;
    });
}

function applyFilters() {
    currentPage = 1;
    renderPage();
}

function renderPage() {
    const allRows    = Array.from(document.querySelectorAll('.order-row'));
    const matched    = getMatchedRows();
    const total      = matched.length;
    const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));

    if (currentPage > totalPages) currentPage = totalPages;

    const start    = (currentPage - 1) * PER_PAGE;
    const end      = start + PER_PAGE;
    const pageRows = matched.slice(start, end);

    allRows.forEach(r => r.classList.add('hidden'));
    pageRows.forEach(r => r.classList.remove('hidden'));

    const tbody  = document.getElementById('orders-tbody');
    let emptyRow = tbody.querySelector('.empty-row');

    if (total === 0) {
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.className = 'empty-row';
            emptyRow.innerHTML = '<td colspan="8">No orders found.</td>';
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
    info.textContent = start + '\u2013' + end + ' of ' + total + ' orders';
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

function filterOrders(status, btn) {
    currentFilter = status;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

renderPage();
</script>

</body>
</html>