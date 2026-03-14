<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit();
}

$customer_id = $_SESSION['user_id'];

/* GET CART */
if (!isset($_POST['cart'])) {
    echo json_encode(["success" => false, "message" => "No cart data"]);
    exit();
}

$data = json_decode($_POST['cart'], true);

if (!$data || count($data) == 0) {
    echo json_encode(["success" => false, "message" => "Cart is empty"]);
    exit();
}

/* GET USER INFO */
$stmtUser = $conn->prepare("
    SELECT delivery_address
    FROM users
    WHERE id = ?
");
$stmtUser->bind_param("i", $customer_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit();
}

$delivery_address = $user['delivery_address'];

/* HANDLE FILE UPLOAD */
$proof_of_payment = "";
if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === 0) {
    $uploadDir = "uploads/proof_of_payment/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filename = time() . "_" . basename($_FILES["payment_proof"]["name"]);
    $targetFile = $uploadDir . $filename;

    if (move_uploaded_file($_FILES["payment_proof"]["tmp_name"], $targetFile)) {
        $proof_of_payment = $targetFile;
    }
}

mysqli_begin_transaction($conn);

try {
    $order_number = "ORD-" . time();
    $total = 0;
    foreach ($data as $item) $total += $item['price'] * $item['quantity'];

    // Insert order using prepared statement
    $stmtOrder = $conn->prepare("
        INSERT INTO orders 
        (customer_id, order_number, status, total_amount, payment_method, delivery_address, created_at, proof_of_payment)
        VALUES (?, ?, 'PENDING', ?, 'ONLINE', ?, NOW(), ?)
    ");
    $stmtOrder->bind_param("isdss", $customer_id, $order_number, $total, $delivery_address, $proof_of_payment);
    $stmtOrder->execute();
    $order_id = $stmtOrder->insert_id;

    // Insert order items
    $stmtItem = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    foreach ($data as $item) {
        $stmtItem->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        $stmtItem->execute();
    }

    mysqli_commit($conn);
    echo json_encode(["success" => true, "message" => "Order placed successfully"]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["success" => false, "message" => "Failed to place order"]);
}
?>