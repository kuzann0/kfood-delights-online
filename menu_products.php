<?php
session_start();
require_once 'connect.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Products - K-FOOD DELIGHTS</title>
    <link rel="stylesheet" href="css/modern-style.css">
    <link rel="stylesheet" href="css/menu-products.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/quantity-modal.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/cart-handler.js" defer></script>
    <script src="js/order-handler.js" defer></script>
</head>
<body>


    <div class="menu-container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <div class="menu-header">
            <h1>Our Menu</h1>
            <p>Discover our selection of delicious K-FOOD DELIGHTS</p>
        </div>

        <div class="category-filter">
            <button class="filter-btn active" data-category="all">All</button>
            <?php
            $category_query = "SELECT DISTINCT category_name FROM product_categories ORDER BY category_name";
            $category_result = mysqli_query($conn, $category_query);
            while($category = mysqli_fetch_assoc($category_result)) {
                echo '<button class="filter-btn" data-category="'.strtolower($category['category_name']).'">'.$category['category_name'].'</button>';
            }
            ?>
        </div>

        <div class="products-grid">
            <?php
            $products_query = "SELECT p.*, pc.category_name 
                             FROM products p 
                             JOIN product_categories pc ON p.category_id = pc.id 
                             WHERE p.stock > 0 
                             ORDER BY p.name";
            $products_result = mysqli_query($conn, $products_query);

            while($product = mysqli_fetch_assoc($products_result)) {
                ?>
                <div class="product-card" data-category="<?php echo strtolower($product['category_name']); ?>">
                    <div class="product-image">
                        <img src="uploaded_img/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo $product['name']; ?></h3>
                        <p class="category"><?php echo $product['category_name']; ?></p>
                        <p class="price">₱<?php echo number_format($product['price'], 2); ?></p>
                        <button class="buy-now-btn" data-product-id="<?php echo $product['id']; ?>">
                            Buy Now
                        </button>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <!-- Quantity Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="quantity-modal" id="quantityModal">
            <h3>Select Quantity</h3>
            <div class="stock-info">Available Stock: <span id="availableStock">0</span></div>
            <div class="quantity-controls">
                <button class="quantity-btn" id="decreaseBtn">-</button>
                <input type="text" class="quantity-input" id="quantityInput" value="1">
                <button class="quantity-btn" id="increaseBtn">+</button>
            </div>
            <div class="modal-buttons">
                <button class="modal-btn cancel-btn" id="cancelBtn">Cancel</button>
                <button class="modal-btn checkout-btn" id="checkoutBtn">Checkout</button>
            </div>
            <div class="error-message" id="errorMessage"></div>
        </div>
    </div>

    <script>
        // Category filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                const category = button.dataset.category;
                
                // Update active button
                document.querySelector('.filter-btn.active').classList.remove('active');
                button.classList.add('active');
                
                // Filter products
                document.querySelectorAll('.product-card').forEach(product => {
                    if (category === 'all' || product.dataset.category === category) {
                        product.style.display = 'block';
                    } else {
                        product.style.display = 'none';
                    }
                });
            });
        });

        // Modal Elements
        const modalOverlay = document.getElementById('modalOverlay');
        const quantityModal = document.getElementById('quantityModal');
        const quantityInput = document.getElementById('quantityInput');
        const decreaseBtn = document.getElementById('decreaseBtn');
        const increaseBtn = document.getElementById('increaseBtn');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const availableStockSpan = document.getElementById('availableStock');
        const errorMessage = document.getElementById('errorMessage');

        let currentProductId = null;
        let maxStock = 0;

        // Buy Now functionality
        document.querySelectorAll('.buy-now-btn').forEach(button => {
            button.addEventListener('click', async () => {
                currentProductId = button.dataset.productId;
                
                // Get product stock
                try {
                    const response = await fetch(`get_product_stock.php?product_id=${currentProductId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        maxStock = parseInt(data.stock);
                        availableStockSpan.textContent = maxStock;
                        quantityInput.value = '1';
                        showModal();
                    } else {
                        alert('Error: Could not fetch product stock');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error: Could not fetch product stock');
                }
            });
        });

        // Modal Controls
        function showModal() {
            modalOverlay.style.display = 'block';
            quantityModal.style.display = 'block';
            errorMessage.style.display = 'none';
        }

        function hideModal() {
            modalOverlay.style.display = 'none';
            quantityModal.style.display = 'none';
            currentProductId = null;
        }

        // Quantity Controls
        decreaseBtn.addEventListener('click', () => {
            let value = parseInt(quantityInput.value) || 0;
            if (value > 1) {
                quantityInput.value = value - 1;
            }
            validateQuantity();
        });

        increaseBtn.addEventListener('click', () => {
            let value = parseInt(quantityInput.value) || 0;
            if (value < maxStock) {
                quantityInput.value = value + 1;
            }
            validateQuantity();
        });

        quantityInput.addEventListener('input', (e) => {
            // Remove any non-numeric characters
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            validateQuantity();
        });

        function validateQuantity() {
            let value = parseInt(quantityInput.value) || 0;
            
            if (value > maxStock) {
                quantityInput.value = maxStock;
                errorMessage.textContent = `Maximum available stock is ${maxStock}`;
                errorMessage.style.display = 'block';
            } else if (value < 1) {
                quantityInput.value = 1;
                errorMessage.textContent = 'Minimum quantity is 1';
                errorMessage.style.display = 'block';
            } else {
                errorMessage.style.display = 'none';
            }
        }

        // Cancel button
        cancelBtn.addEventListener('click', hideModal);

        // Checkout button
        checkoutBtn.addEventListener('click', async () => {
            try {
                // Check if user is logged in
                const response = await fetch('check_login.php');
                const data = await response.json();

                if (!data.loggedIn) {
                    alert('Please login first to proceed with checkout');
                    window.location.href = 'loginpage.php';
                    return;
                }

                // Get product details
                const quantity = parseInt(quantityInput.value);
                const productCard = document.querySelector(`[data-product-id="${currentProductId}"]`).closest('.product-card');
                const name = productCard.querySelector('h3').textContent;
                const priceText = productCard.querySelector('.price').textContent;
                const price = parseFloat(priceText.replace('₱', ''));
                const image = productCard.querySelector('img').getAttribute('src').split('/').pop();

                // Prepare cart item
                const cartItem = {
                    id: currentProductId,
                    name: name,
                    price: price,
                    image: image,
                    quantity: quantity
                };

                // Store in session storage
                let cart = [cartItem]; // Only this item
                sessionStorage.setItem('cart', JSON.stringify(cart));
                sessionStorage.setItem('selectedItems', JSON.stringify([currentProductId]));
                sessionStorage.setItem('checkoutItems', JSON.stringify([cartItem]));

                hideModal(); // Hide the quantity modal
                window.location.href = 'checkout.php';
            } catch (error) {
                console.error('Error:', error);
                alert('Error: Could not process checkout');
            }
        });

        // Close modal when clicking outside
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                hideModal();
            }
        });
    </script>
</body>
</html>