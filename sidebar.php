<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>
<style>
    .dropdown-menu{
        display:none;
        flex-direction:column;
        margin-left:20px;
    }

    .dropdown-menu a{
        padding:8px 10px;
        font-size:14px;
        text-decoration:none;
        color:#333;
    }

    .dropdown-menu a:hover{
        background:#f2f2f2;
    }

    .dropdown-menu.show{
        display:flex;
    }
</style>

<aside class="sidebar">
    <h2 class="logo">COZIEST</h2>
    <div class="menu">

        <?php if($role === 'User'): ?>
        <!-- Common Links for all users -->
        <nav>
            <a href="home.php" class="<?php echo $current_page === 'home.php' ? 'active' : ''; ?>">
                <span>🏠</span>
                <span>Home</span>
            </a>
        </nav>

        <nav>
            <a href="products.php" class="<?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
                <span>🛍️</span>
                <span>Shop</span>
            </a>
        </nav>

        <nav>
            <a href="info.php" class="<?php echo $current_page === 'info.php' ? 'active' : ''; ?>">
                <span>ℹ️</span>
                <span>Info</span>
            </a>
        </nav>

        <nav>
            <a href="message.php" class="<?php echo $current_page === 'message.php' ? 'active' : ''; ?>">
                <span>💬</span>
                <span>Message</span>
            </a>
        </nav>

        <nav>
            <a href="my_orders.php" class="<?php echo $current_page === 'my_orders.php' ? 'active' : ''; ?>">
                <span>📦</span>
                <span>My Orders</span>
            </a>
        </nav>
        <?php endif; ?>

        <?php if($role === 'Admin'): ?>
            <!-- Admin-only links -->
            <nav>
                <a href="orders.php" class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                    <span>📦</span>
                    <span>Orders</span>
                </a>
            </nav>

            <nav>
                <a href="all_messages.php" class="<?php echo $current_page === 'all_messages.php' ? 'active' : ''; ?>">
                    <span>💬</span>
                    <span>Messages</span>
                </a>
            </nav>

            <nav>
                <a href="inventory.php" class="<?php echo $current_page === 'inventory.php' ? 'active' : ''; ?>">
                    <span>📦</span>
                    <span>Inventory</span>
                </a>
            </nav>

            <nav>
                <a href="accounts.php" class="<?php echo $current_page === 'accounts.php' ? 'active' : ''; ?>">
                    <span>👤</span>
                    <span>Accounts</span>
                </a>
            </nav>

            <!-- SETTINGS DROPDOWN -->
            <nav class="dropdown">
                <a href="#" onclick="toggleSettings()">
                    <span>⚙️</span>
                    <span>Settings ▾</span>
                </a>

                <div id="settingsMenu" class="dropdown-menu">
                    <a href="categories_settings.php"
                       class="<?php echo $current_page === 'categories_settings.php' ? 'active' : ''; ?>">
                        📂 Category
                    </a>

                    <a href="products_settings.php"
                       class="<?php echo $current_page === 'products_settings.php' ? 'active' : ''; ?>">
                        📦 Products
                    </a>
                </div>
            </nav>
        <?php endif; ?>

        <nav>
            <a href="logout.php">
                <span>🚪</span>
                <span>Logout</span>
            </a>
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

<script>
function toggleSettings() {
    document.getElementById("settingsMenu").classList.toggle("show");
}
</script>