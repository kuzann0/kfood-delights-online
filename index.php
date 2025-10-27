<?php
    require_once "connect.php";
    require_once "Session.php";
    
    // Check if user is logged in and is a customer
    if (isset($_SESSION['user_id']) && $_SESSION['role_id'] != 4) {
        // If not a customer, redirect to appropriate dashboard
        switch($_SESSION['role_id']) {
            case 3: // Crew
                header("Location: kfood_crew/dashboard.php");
                exit();
            case 2: // Admin
                header("Location: kfood_admin/admin_pg.php");
                exit();
            case 1: // Super Admin
                header("Location: kfood_admin/SA_login.php");
                exit();
        }
    }
    
    $isLoggedIn = isset($_SESSION['user_id']);
    
    // Check for logout message
    $logoutMessage = '';
    if (isset($_COOKIE['logout_message'])) {
        $logoutMessage = $_COOKIE['logout_message'];
        setcookie('logout_message', '', time() - 3600, '/'); // Clear the cookie
    }
    
    // Fetch products
    $select_products = mysqli_query($conn, "SELECT * FROM `products`");
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="K-Food Delight - Experience authentic Korean fusion cuisine in Quezon City">
        <title>K-FOOD DELIGHT</title>
    <style>
        .explore-menu-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            background: linear-gradient(45deg, #FF6B6B, #FFB75E);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .explore-menu-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .explore-menu-btn i {
            margin-left: 5px;
            transition: transform 0.3s ease;
        }
        
        .explore-menu-btn:hover i {
            transform: translateX(5px);
        }
    </style>
    <link rel="stylesheet" href="css/modern-style.css">
    <link rel="stylesheet" href="css/new-style.css">
    <link rel="stylesheet" href="css/preloader.css">
    <link rel="stylesheet" href="css/contact-style.css">
    <link rel="stylesheet" href="css/navbar-modern.css">
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/cart-simple-new.css">
    <link rel="stylesheet" href="css/orders-modal.css">
    <link rel="stylesheet" href="css/cart-notifications.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/favicon.png">
</head>
<body>
 
<div id="pre-loader"></div>

    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <button class="mobile-menu-btn" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <img src="images/logo.png" alt="K-Food Logo" class="nav-logo">
                    <a href="#home">K-Food Delight</a>
                </div>
            </div>

            <div class="nav-center">
                <div class="nav-links">
                    <a href="#home" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                    <a href="#order" class="nav-link">
                        <i class="fas fa-utensils"></i>
                        <span>Menu</span>
                    </a>
                    <a href="#aboutus" class="nav-link">
                        <i class="fas fa-info-circle"></i>
                        <span>About</span>
                    </a>
                    <a href="#contacts" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Contact</span>
                    </a>
                </div>
            </div>

            <div class="nav-right">
                <button id="cartBtn" class="cart-button" aria-label="Shopping cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Cart</span>
                    <span class="cart-count">0</span>
                </button>
                <?php if (isset($_SESSION['user_id'])): ?>
                <button id="myOrdersBtn" class="my-orders-button" aria-label="My Orders">
                    <i class="fas fa-clipboard-list"></i>
                    <span>My Orders</span>
                </button>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="profile-menu">
                    <button class="user-button" id="profileBtn">
                        <div class="user-button-content">
                            <?php
                                if(isset($_SESSION['email'])){
                                    $email = $_SESSION['email'];
                                    $stmt = $conn->prepare("SELECT firstName, lastName, profile_picture FROM users WHERE email = ?");
                                    $stmt->bind_param("s", $email);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($row = $result->fetch_assoc()) {
                                        $fullName = htmlspecialchars($row['firstName']) . ' ' . htmlspecialchars($row['lastName']);
                                        $_SESSION['full_name'] = $fullName;
                                        $_SESSION['profile_picture'] = $row['profile_picture'];
                                        if (!empty($row['profile_picture']) && file_exists('uploaded_img/' . $row['profile_picture'])) {
                                            echo '<img src="uploaded_img/' . htmlspecialchars($row['profile_picture']) . '" alt="Profile" class="profile-icon profile-picture">';
                                        } else {
                                            echo '<i class="fas fa-user-circle profile-icon"></i>';
                                        }
                                    }
                                    $stmt->close();
                                }
                            ?>
                            <span class="username"><?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : ''; ?></span>
                        </div>
                    </button>
                    <div class="profile-dropdown" id="profileMenu">
                        <div class="profile-header">
                            <?php
                                if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture']) && file_exists('uploaded_img/' . $_SESSION['profile_picture'])) {
                                    echo '<img src="uploaded_img/' . htmlspecialchars($_SESSION['profile_picture']) . '" alt="Profile" class="dropdown-icon profile-picture">';
                                } else {
                                    echo '<i class="fas fa-user-circle dropdown-icon"></i>';
                                }
                            ?>
                            <div class="profile-info">
                                <h4><?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : ''; ?></h4>
                            </div>
                        </div>
                        <div class="profile-links">
                            <a href="profile.php" class="profile-link">
                                <i class="fas fa-user-edit"></i> Edit Profile
                            </a>
                            <a href="order_history.php" class="profile-link">
                                <i class="fas fa-history"></i> Order History
                            </a>
                            <a href="#" class="profile-link">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <a href="logout.php" class="profile-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <button class="user-button" id="Userbtn">
                    <i class="fas fa-user"></i> Login
                </button>
                <?php endif; ?>
            </div>
        </div>

        <button class="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<?php if ($logoutMessage): ?>
<div class="notification success fade-out">
    <i class="fas fa-check-circle"></i>
    <?php echo htmlspecialchars($logoutMessage); ?>
</div>
<?php endif; ?>

<div class="hero-section" id="home">
    <div class="hero-content">
        <h1 class="hero-title">K-FOOD DELIGHTS</h1>
        <p class="hero-subtitle">Experience the perfect blend of traditional flavors and modern creativity</p>
        <a href="menu_products.php" class="explore-menu-btn">
            Explore Our Menu
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>
       
   
   
    <section class="menu-section" id="order">
        <div class="menu-container">
            <h2 class="menu-title">
                <i class="fas fa-utensils"></i>
                Our Menu
            </h2>
            <p class="menu-subtitle">Discover our selection of delicious K-FOOD DELIGHTS</p>
            
            <div class="menu-categories">
                <button class="category-btn active" data-category="all">Best Sellers</button>
                <?php
                // Fetch unique categories
                $category_query = "SELECT DISTINCT category_name FROM product_categories ORDER BY id";
                $category_result = mysqli_query($conn, $category_query);
                while ($category = mysqli_fetch_assoc($category_result)) {
                    $cat_name = htmlspecialchars($category['category_name']);
                    $cat_data = strtolower(str_replace(' ', '-', $category['category_name']));
                    echo "<button class='category-btn' data-category='{$cat_data}'>{$cat_name}</button>";
                }
                ?>
            </div>

        <div class="products-grid">
            <?php
            // Fetch products with their category names
            $select_products = mysqli_query($conn, "SELECT p.*, pc.category_name 
                FROM `products` p 
                LEFT JOIN `product_categories` pc ON p.category_id = pc.id");
            if (mysqli_num_rows($select_products) > 0) {
                while ($row = mysqli_fetch_assoc($select_products)) {
                    $category_class = strtolower(str_replace(' ', '-', $row['category_name']));
            ?>
            <div class="product-card" data-id="<?php echo $row['id']; ?>" data-category="<?php echo $category_class; ?>">
                <div class="product-image">
                    <img src="uploaded_img/<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" />
                    <?php if (isset($row['is_popular']) && $row['is_popular']): ?>
                        <div class="product-badge">Popular</div>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                    <?php if (isset($row['description']) && $row['description']): ?>
                        <p class="product-description"><?php echo htmlspecialchars($row['description']); ?></p>
                    <?php endif; ?>
                    <div class="product-footer">
                        <div class="product-price">₱<?php echo number_format($row['price'], 2); ?></div>
                        <button class="add-to-cart-btn">
                            <i class="fas fa-shopping-cart"></i>
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
                echo "<p>No products available at the moment.</p>";
            }
            ?>           
        </section>     
    </div> <!-- Close products-grid -->
    </section> <!-- Close menu-section -->

    <section class="about-section" id="aboutus">
        <div class="about-container">
            <h2 class="about-title">
                <i class="fas fa-store"></i>
                About Us
            </h2>
            <div class="about-text">
                <p>
                    K-Food Delight, in Philam, Quezon City, offers delicious homemade Filipino-fusion cuisine. Owners Michelle J. Javillo and Krystal E. Selisana create unique dishes, from lasagna and sushi to their signature spicy chicken pastil. We cater to students, families, professionals, and anyone craving exciting flavors. We're passionate about providing high-quality food and memorable dining experiences, and we strive to be Quezon City's leading provider of innovative Filipino-fusion dishes.
                </p>
            </div>
            <div class="restaurant-features">
                <div class="feature">
                    <i class="fas fa-utensils"></i>
                    <span>Unique Fusion Dishes</span>
                </div>
                <div class="feature">
                    <i class="fas fa-star"></i>
                    <span>Quality Service</span>
                </div>
                <div class="feature">
                    <i class="fas fa-heart"></i>
                    <span>Made with Love</span>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-section" id="contacts">
        <div class="contact-container">
            <h2 class="contact-title">
                <i class="fas fa-envelope"></i>
                Contact
            </h2>
            <div class="contact-content simple-layout">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>123 Philam, Quezon City</p>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <p>+63 912 345 6789</p>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <p>info@kfooddelight.com</p>
                    </div>
                </div>
            </div>
    </section>
    <div id="ordersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-clipboard-list"></i> Your Orders</h2>
                <button id="closeOrdersBtn" class="close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="orders-list">
                <!-- Orders will be loaded here dynamically -->
            </div>
        </div>
    </div>

    <div id="cartModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-shopping-cart"></i> Your Cart</h2>
                <button id="closeCartBtn" class="close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="cartItems" class="cart-items"></div>
            <div class="cart-summary">
                <div id="cartSubtotal"></div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button id="checkoutBtn" class="checkout-btn">
                        <i class="fas fa-check"></i> Proceed to Checkout
                    </button>
                <?php else: ?>
                    <button id="checkoutBtn" class="checkout-btn" onclick="window.location.href='loginpage.php'">
                        <i class="fas fa-sign-in-alt"></i> Login to Checkout
                    </button>
                    <p class="login-notice">Please log in to complete your order</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <button id="scrollToTopBtn" class="scroll-top-btn" title="Go to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>K-Food Delight</h3>
                <p>Experience the perfect blend of Korean and Filipino cuisine.</p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#order">Menu</a></li>
                    <li><a href="#aboutus">About Us</a></li>
                    <li><a href="#contacts">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Operating Hours</h3>
                <ul class="hours-list">
                    <li>Monday - Friday: 10:00 AM - 9:00 PM</li>
                    <li>Saturday: 11:00 AM - 9:00 PM</li>
                    <li>Sunday: 11:00 AM - 8:00 PM</li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Newsletter</h3>
                <p>Subscribe to get special offers and updates!</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Enter your email">
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 K-Food Delight. All rights reserved.</p>
        </div>
    </footer>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Login button handler
        const Userbtn = document.getElementById("Userbtn");
        if (Userbtn) {
            Userbtn.addEventListener("click", function() {
                window.location.href = 'loginpage.php';
            });
        }

        // Profile menu handler
        const profileBtn = document.getElementById("profileBtn");
        const profileMenu = document.getElementById("profileMenu");
        
        if (profileBtn && profileMenu) {
            profileBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                profileMenu.classList.toggle("show");
            });

            document.addEventListener("click", function(e) {
                if (!profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
                    profileMenu.classList.remove("show");
                }
            });
        }

        // Checkout button handler
        const checkoutBtn = document.getElementById("checkoutBtn");
        if (checkoutBtn) {
            checkoutBtn.addEventListener("click", function() {
                <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = 'loginpage.php';
                <?php else: ?>
                window.location.href = 'checkout.php';
                <?php endif; ?>
            });
        }
    });

    // Preloader
    window.addEventListener('load', function() {
        var loader = document.getElementById('pre-loader');
        if (loader) {
            loader.classList.add('fade-out');
            setTimeout(function() {
                loader.style.display = 'none'; 
            }, 1000); 
        }
    });
    </script>

    <script>
        // Category filtering
        document.addEventListener('DOMContentLoaded', function() {
            const categoryButtons = document.querySelectorAll('.category-btn');
            const productCards = document.querySelectorAll('.product-card');

            categoryButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Remove active class from all buttons
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    button.classList.add('active');

                    const category = button.getAttribute('data-category');

                    // Show all products if 'all' is selected, otherwise filter by category
                    productCards.forEach(card => {
                        if (category === 'all' || card.getAttribute('data-category') === category) {
                            card.style.display = 'block';
                            // Add animation class
                            card.classList.add('fade-in');
                        } else {
                            card.style.display = 'none';
                            card.classList.remove('fade-in');
                        }
                    });
                });
            });
        });
    </script>

    <style>
        /* Animation for products */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        .coming-soon-message {
            text-align: center;
            padding: 40px;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 10px;
            margin: 20px 0;
        }

        .coming-soon-message i {
            font-size: 2rem;
            color: #FF6B6B;
            margin-bottom: 10px;
        }

        .coming-soon-message p {
            color: #666;
            font-size: 1.1rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Category button styles */
        .menu-categories {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            background: #f5f5f5;
            color: #333;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .category-btn:hover {
            background: #FF7F50;
            color: white;
            transform: translateY(-2px);
        }

        .category-btn.active {
            background: #FF7F50;
            color: white;
            box-shadow: 0 4px 12px rgba(255, 127, 80, 0.2);
        }
    </style>

    <script src="js/cartItemTemplate.js"></script>
    <script src="js/cart-handler-new.js"></script>
    <script src="js/script.js"></script>
    <script src="js/smooth-scroll.js"></script>
    <script src="js/customer_PR.js"></script>
    <script src="js/orders-modal.js"></script>
    <script src="js/order-placement.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

</body>
</html>
