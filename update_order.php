<?php
include 'db.php';

$order_id = $_POST['order_id'];
$action = $_POST['action'];

mysqli_begin_transaction($conn);

try{

if($action == "accept"){

    /* UPDATE ORDER STATUS */
    $updateOrder = "
    UPDATE orders
    SET status='APPROVED',
    updated_at = NOW()
    WHERE id='$order_id'
    ";

    mysqli_query($conn,$updateOrder);


    /* GET ORDER ITEMS */
    $itemsQuery = "
    SELECT product_id, quantity
    FROM order_items
    WHERE order_id='$order_id'
    ";

    $itemsResult = mysqli_query($conn,$itemsQuery);

    while($item = mysqli_fetch_assoc($itemsResult)){

        $product_id = $item['product_id'];
        $qty = $item['quantity'];

        /* DEDUCT INVENTORY */
        $updateStock = "
        UPDATE inventory
        SET quantity = GREATEST(quantity - $qty,0)
        WHERE product_id='$product_id'
        ";

        mysqli_query($conn,$updateStock);
    }

}

elseif($action == "decline"){

    $updateOrder = "
    UPDATE orders
    SET status='DECLINED',
    updated_at = NOW()
    WHERE id='$order_id'
    ";

    mysqli_query($conn,$updateOrder);

}

mysqli_commit($conn);

}catch(Exception $e){

mysqli_rollback($conn);

}

header("Location: orders.php");
exit();