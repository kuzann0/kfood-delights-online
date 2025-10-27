<nav class="navbar">
    <div class="logo">
        <a href="index.php">
            <img src="images/logo.png" alt="K-Food Delight">
            K-Food Delight
        </a>
    </div>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="menu_products.php">Menu</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>
        <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="cart.php" class="cart-link">
                <i class="fas fa-shopping-cart"></i>
                <?php 
                if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                    echo '<span class="cart-count">'.count($_SESSION['cart']).'</span>';
                }
                ?>
            </a></li>
            <li><a href="my_orders.php">My Orders</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="loginpage.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>