<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
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
<body class="dashboard-page">

<div class="dashboard">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
</div>