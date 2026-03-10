<?php
session_start();

// Redirect if not logged in
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
<link rel="stylesheet" href="./styles/design.css">
</head>
<body>

<div class="dashboard">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
</div>