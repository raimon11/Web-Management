<?php
include 'db.php';

$product_id = $_POST['product_id'];
$qty = $_POST['quantity'];
$action = $_POST['action'];

if($action == "add"){

$query = "
UPDATE inventory
SET quantity = quantity + $qty,
created_at = NOW()
WHERE product_id = '$product_id'
";

}

if($action == "deduct"){

$query = "
UPDATE inventory
SET quantity = quantity - $qty,
created_at = NOW()
WHERE product_id = '$product_id'
";

}

mysqli_query($conn,$query);

header("Location: inventory.php");
exit();