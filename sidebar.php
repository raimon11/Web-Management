<!-- Sidebar -->
<aside class="sidebar">
    <h2 class="logo">COZIEST</h2>

    <div class="menu">
    <nav>
        <a href="home.php">Home</a>
    </nav>

    <nav>
        <a href="products.php">Product</a>
    </nav>

    <nav>
        <a href="info.php" class="active">Info</a>
    </nav>

    <nav>
        <a href="message.php">Message</a>
    </nav>

    <nav>
        <a href="settings.php">Settings</a>
    </nav>

    <nav>
        <a href="accounts.php">Accounts</a>
    </nav>

    <nav>
        <a href="logout.php">Logout</a>
    </nav>

    </div>

    <div class="profile">
        <img src="https://tinyurl.com/4z6wxw6b" width="50" height="50" alt="">
        <div>
            <h4>COZIEST</h4>
            <p>Welcome <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] ?></p>
        </div>
    </div>
</aside>