<?php
session_start();
require 'db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, password FROM users WHERE email = ?");
$stmt->bind_param("s",$email);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows === 1){

    $user = $result->fetch_assoc();

    if($password === $user['password']){

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['middle_name'] = $user['middle_name'];
        $_SESSION['last_name'] = $user['last_name'];

        header("Location: dashboard.php");
        exit();
    }
}

header("Location: index.php?error=1");
exit();
?>