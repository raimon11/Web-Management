<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

$query = "
SELECT 
    id,
    order_number,
    status,
    total_amount,
    payment_method,
    delivery_address,
    created_at,
    proof_of_payment
FROM orders
WHERE customer_id = '$customer_id'
ORDER BY created_at DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders</title>
<link rel="stylesheet" href="./styles/index.css">

<style>

.orders-container{
    padding:20px;
}

.orders-table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:8px;
    overflow:hidden;
}

.orders-table th,
.orders-table td{
    padding:12px;
    text-align:left;
    border-bottom:1px solid #eee;
}

.orders-table th{
    background:#f5f5f5;
}

.status{
    padding:6px 10px;
    border-radius:6px;
    font-weight:bold;
    font-size:12px;
}

.status.pending{
    background:#fff3cd;
    color:#856404;
}

.status.approved{
    background:#d4edda;
    color:#155724;
}

.status.declined{
    background:#f8d7da;
    color:#721c24;
}

.proof-img{
    height:50px;
    border-radius:6px;
}

</style>

</head>
<body>

<div class="dashboard">

<?php include 'sidebar.php'; ?>

<div class="main-content">

<header class="topbar">
    <div>
        <h3>Hello, <strong><?php echo $_SESSION['first_name']; ?> <?php echo $_SESSION['last_name']; ?></strong></h3>
    </div>
</header>

<div class="orders-container">

<h2>My Orders</h2>

<table class="orders-table">

<thead>
<tr>
    <th>Order #</th>
    <th>Total</th>
    <th>Payment</th>
    <th>Address</th>
    <th>Status</th>
    <th>Date</th>
    <th>Proof</th>
</tr>
</thead>

<tbody>

<?php
if(mysqli_num_rows($result) > 0){

while($row = mysqli_fetch_assoc($result)){
?>

<tr>

<td><?php echo $row['order_number']; ?></td>

<td><?php echo $row['total_amount']; ?> PHP</td>

<td><?php echo $row['payment_method']; ?></td>

<td><?php echo $row['delivery_address']; ?></td>

<td>
<span class="status <?php echo strtolower($row['status']); ?>">
<?php echo $row['status']; ?>
</span>
</td>

<td><?php echo date("M d, Y H:i", strtotime($row['created_at'])); ?></td>

<td>

<?php if($row['proof_of_payment']){ ?>

<a href="<?php echo $row['proof_of_payment']; ?>" target="_blank">
<img src="<?php echo $row['proof_of_payment']; ?>" class="proof-img">
</a>

<?php }else{ ?>

N/A

<?php } ?>

</td>

</tr>

<?php
}

}else{
?>

<tr>
<td colspan="7" style="text-align:center;">No orders found</td>
</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>
</div>

</body>
</html>