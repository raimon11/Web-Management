<?php

if(!isset($_GET['img'])){
    echo "Image not found";
    exit();
}

$image = basename($_GET['img']);

?>

<!DOCTYPE html>
<html>
<head>
<title>Payment Proof</title>

<style>

body{
background:#f4f6f9;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
font-family:Arial;
}

img{
max-width:90%;
max-height:90%;
border-radius:10px;
box-shadow:0 10px 30px rgba(0,0,0,0.2);
}

</style>

</head>
<body>

<img src="uploads/proof_of_payment/<?php echo $image; ?>">

</body>
</html>