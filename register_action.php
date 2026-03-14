<?php
session_start();
include 'db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $delivery_address = trim($_POST['delivery_address']);
    $password = $_POST['password']; // Plain text
    $role = 'User';
    $is_verified = 0;
    $created_at = date('Y-m-d H:i:s');

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows > 0){
        header("Location: register.php?error=1");
        exit();
    }

    // Insert user into database
    $stmt = $conn->prepare(
        "INSERT INTO users (first_name, middle_name, last_name, email, delivery_address, password, is_verified, created_at, role) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssssiss", $first_name, $middle_name, $last_name, $email, $delivery_address, $password, $is_verified, $created_at, $role);

    if($stmt->execute()){
        header("Location: register.php?success=1");
        exit();
    } else {
        header("Location: register.php?error=1");
        exit();
    }
}
?>