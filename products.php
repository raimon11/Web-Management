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
<link rel="stylesheet" href="./styles/design.css">
</head>
<body>

<div class="dashboard">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <!-- Main Content -->
 
        <header class="topbar">
            <div>
                <h3>Hello, <strong>COZIEST</strong></h3>
                <p>COZIEST</p>
            </div>
            <img class="avatar" src="https://tinyurl.com/4z6wxw6b" width="50" height="50" alt="">
        </header>

        <!-- Products -->
        <section class="units">
            <h3>Products</h3>

            <div class="unit-grid">
                <div class="unit-card">
                    <img src="visual/Stickers.jpg">
                    <div class="unit-info">
                        <h4>STICKERS</h4>
                        <p>100PHP</p>
                    </div>
                </div>

                <div class="unit-card">
                    <img src="visual/mugs.jpg">
                    <div class="unit-info">
                        <h4>MUG</h4>
                        <p>120PHP</p>
                    </div>
                </div>

                <div class="unit-card">
                    <img src="visual/polaroid.jpg">
                    <div class="unit-info">
                        <h4>POLAROID</h4>
                        <p>20PHP</p>
                    </div>
                </div>

                <div class="unit-card">
                    <img src="visual/Bags.jpg">
                    <div class="unit-info">
                        <h4>BAG</h4>
                        <p>250PHP</p>
                    </div>
                </div>
                <div class="unit-card">
                    <img src="visual/Banner.jpg">
                    <div class="unit-info">
                        <h4>BANNER</h4>
                        <p>400PHP</p>
                    </div>
                </div>
                <div class="unit-card">
                    <img src="visual/bigger glass tumbler.jpg">
                    <div class="unit-info">
                        <h4>GLASS TUMBLER</h4>
                        <p>100PHP</p>
                    </div>
                </div>
                <div class="unit-card">
                    <img src="visual/bouquet.jpg">
                    <div class="unit-info">
                        <h4>BOUQUET</h4>
                        <p>300PHP</p>
                    </div>
                </div>
                <div class="unit-card">
                    <img src="visual/brush.jpg">
                    <div class="unit-info">
                        <h4>BRUSH</h4>
                        <p>80PHP</p>
                    </div>
                </div>
                <div class="unit-card">
                    <img src="visual/Calendar.jpg">
                    <div class="unit-info">
                        <h4>CALENDAR</h4>
                        <p>220PHP</p>
                    </div>
                </div>
                <div class="unit-card">
                    <img src="visual/caps.jpg">
                    <div class="unit-info">
                        <h4>CAPS</h4>
                        <p>200PHP</p>
                    </div>
                </div>
                <div class="unit-card">
                    <img src="visual/Gift box.jpg">
                    <div class="unit-info">
                        <h4>GIFT BOX</h4>
                        <p>30PHP</p>
                    </div>
                </div>
                <div class="unit-card">
                    <img src="visual/katinko.jpg">
                    <div class="unit-info">
                        <h4>KATINKO</h4>
                        <p>150PHP</p>
                    </div>
                </div>
                <div class="unit-card">
                    <img src="visual/keychainpic laminated.jpg">
                    <div class="unit-info">
                        <h4>KEYCHAIN LAMINATED</h4>
                        <p>30PHP</p>
                    </div>
                </div>
                <div class="unit-card">
                    <img src="visual/keychain landscape.jpg">
                    <div class="unit-info">
                        <h4>KEYCHAIN LANDSCAPE</h4>
                        <p>50PHP</p>
                    </div>
                </div>
            </div>

        </section>

    </main>

</div>

</body>
</html>