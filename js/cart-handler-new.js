// Update cart count in nav bar
function updateCartCount() {
    const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    const count = cart.reduce((total, item) => total + parseInt(item.quantity), 0);
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = count;
        cartCount.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Function to update cart total price
function updateCartTotalPrice() {
    console.log('Updating cart total price...'); // Debug log
    const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    const cartItems = document.getElementById('cartItems');
    const cartSubtotal = document.getElementById('cartSubtotal');
    
    if (!cartItems || !cartSubtotal) {
        console.error('Required elements not found');
        return;
    }

    let subtotal = 0;
    let checkedItems = [];
    let selectedCount = 0;

    // Get all checked items
    const checkboxes = cartItems.querySelectorAll('.simple-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const itemId = checkbox.getAttribute('data-id');
            const cartItem = cart.find(item => item.id.toString() === itemId.toString());
            if (cartItem) {
                checkedItems.push(cartItem);
                subtotal += parseFloat(cartItem.price) * parseInt(cartItem.quantity);
                selectedCount++;
            }
        }
    });

    console.log('Checked items:', checkedItems); // Debug log
    console.log('Subtotal:', subtotal); // Debug log

    // Store checked items for checkout
    sessionStorage.setItem('checkedItems', JSON.stringify(checkedItems));
    sessionStorage.setItem('selectedItems', JSON.stringify(checkedItems.map(item => item.id)));

    // Update the display
    let itemizedList = '';
    checkedItems.forEach(item => {
        const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
        itemizedList += `
            <div class="subtotal-row item-detail">
                <div class="item-info">
                    <span class="item-name">${item.name}</span>
                    <span class="item-quantity">(${item.quantity}x)</span>
                </div>
                <span class="item-subtotal">₱${itemTotal.toFixed(2)}</span>
            </div>
        `;
    });

    cartSubtotal.innerHTML = `
        <div class="subtotal-breakdown">
            <div class="breakdown-header">
                <span>Order Summary (${selectedCount} items)</span>
            </div>
            <div class="itemized-list">
                ${itemizedList || '<div class="no-items-selected">No items selected</div>'}
            </div>
            ${selectedCount > 0 ? `
                <div class="subtotal-divider"></div>
                <div class="subtotal-final">
                    <span>Total Amount:</span>
                    <span class="total-amount">₱${subtotal.toFixed(2)}</span>
                </div>
            ` : ''}
        </div>
    `;

    // Enable/disable checkout button based on selection
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.disabled = selectedCount === 0;
        if (selectedCount === 0) {
            checkoutBtn.classList.add('disabled');
        } else {
            checkoutBtn.classList.remove('disabled');
        }
    }
}

// Note: Using global showNotification function from notifications.js

// Function to show cart notification
function showCartNotification(message) {
    // Remove any existing notifications first
    const existingNotifications = document.querySelectorAll('.cart-alert');
    existingNotifications.forEach(notif => notif.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'cart-alert';
    notification.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>${message}</span>
    `;

    // Add styles directly to the element
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #4CAF50;
        color: white;
        padding: 16px 24px;
        border-radius: 4px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 999999;
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: Arial, sans-serif;
        font-size: 16px;
        transform: translateX(110%);
        transition: transform 0.3s ease;
    `;

    // Add styles for the icon
    notification.querySelector('i').style.cssText = `
        font-size: 20px;
    `;

    // Add to document
    document.body.appendChild(notification);

    // Trigger animation
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Remove after delay
    setTimeout(() => {
        notification.style.transform = 'translateX(110%)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add to cart function
function addToCart(productCard) {
    console.log('addToCart called with:', productCard); // Debug log

    const id = productCard.dataset.id;
    const name = productCard.querySelector('.product-name').textContent;
    const price = parseFloat(productCard.querySelector('.product-price').textContent.replace('₱', ''));
    const image = productCard.querySelector('.product-image img').getAttribute('src').split('/').pop();

    console.log('Product details:', { id, name, price, image }); // Debug log

    let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    const existingItem = cart.find(item => item.id === id);

    try {
        if (existingItem) {
            existingItem.quantity++;
            console.log('Showing notification for increased quantity'); // Debug log
            showCartNotification(`Increased ${name} quantity in cart`);
        } else {
            cart.push({ id, name, price, image, quantity: 1 });
            console.log('Showing notification for new item'); // Debug log
            showCartNotification(`${name} added to cart`);
        }

        sessionStorage.setItem('cart', JSON.stringify(cart));
        updateCartCount();
    } catch (error) {
        console.error('Error in addToCart:', error); // Debug log for errors
    }
}

// Function to update cart display in modal
function updateCartDisplay() {
    console.log('Updating cart display'); // Debug log
    const cartItems = document.getElementById('cartItems');
    if (!cartItems) {
        console.error('Cart items container not found');
        return;
    }

    const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    console.log('Current cart:', cart); // Debug log

    if (cart.length === 0) {
        cartItems.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
        updateCartTotalPrice();
        return;
    }

    cartItems.innerHTML = '';
    cart.forEach(async (item) => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'cart-item';
        itemDiv.setAttribute('data-id', item.id);
        
        // Get stock information
        const response = await fetch(`get_product_stock.php?product_id=${item.id}`);
        const data = await response.json();
        const availableStock = data.success ? parseInt(data.stock) : 0;
        
        itemDiv.innerHTML = `
            <div class="checkbox-wrapper">
                <input type="checkbox" class="simple-checkbox" id="checkbox-${item.id}" data-id="${item.id}">
                <label class="checkbox-label" for="checkbox-${item.id}"></label>
            </div>
            <img src="uploaded_img/${item.image}" alt="${item.name}" class="cart-item-image">
            <div class="cart-item-details">
                <h3>${item.name}</h3>
                <div class="stock-info">Available Stock: ${availableStock}</div>
                <div class="price">₱${(item.price * item.quantity).toFixed(2)}</div>
                <div class="controls">
                    <button type="button" class="btn minus" data-id="${item.id}">-</button>
                    <input type="number" class="quantity-input" value="${item.quantity}" 
                           min="1" max="${availableStock}" data-id="${item.id}">
                    <button type="button" class="btn plus" data-id="${item.id}">+</button>
                    <button type="button" class="remove" data-id="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

        // Add checkbox event listener
        const checkbox = itemDiv.querySelector('.simple-checkbox');
        checkbox.addEventListener('change', (e) => {
            console.log('Checkbox changed:', e.target.checked);
            itemDiv.classList.toggle('selected', e.target.checked);
            updateCartTotalPrice();
        });

        // Add quantity input event listener
        const quantityInput = itemDiv.querySelector('.quantity-input');
        if (quantityInput) {
            quantityInput.addEventListener('input', async (e) => {
                const newValue = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = newValue;
                
                if (newValue) {
                    const cartItem = e.target.closest('.cart-item');
                    const id = cartItem.dataset.id;
                    let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
                    const cartItemIndex = cart.findIndex(item => item.id.toString() === id.toString());

                    if (cartItemIndex !== -1) {
                        const response = await fetch(`get_product_stock.php?product_id=${id}`);
                        const data = await response.json();
                        const availableStock = data.success ? parseInt(data.stock) : 0;
                        
                        // Validate and adjust quantity
                        let newQuantity = Math.max(1, Math.min(parseInt(newValue), availableStock));
                        
                        // Update cart and UI
                        cart[cartItemIndex].quantity = newQuantity;
                        e.target.value = newQuantity;
                        cartItem.querySelector('.price').textContent = `₱${(cart[cartItemIndex].price * newQuantity).toFixed(2)}`;
                        
                        // Show notification if quantity was adjusted
                        if (newQuantity !== parseInt(newValue)) {
                            showCartNotification(`Quantity adjusted to ${newQuantity} (maximum available stock)`);
                        }
                        
                        sessionStorage.setItem('cart', JSON.stringify(cart));
                        updateCartTotalPrice();
                        updateCartCount();
                    }
                }
            });

            // Prevent typing non-numeric characters
            quantityInput.addEventListener('keypress', (e) => {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }

        cartItems.appendChild(itemDiv);
    });
    
    updateCartTotalPrice();
}

// Initialize cart functionality
document.addEventListener('DOMContentLoaded', () => {
    // Load cart count on page load
    updateCartCount();

    // Add event delegation for all cart interactions
    document.body.addEventListener('click', (e) => {
        const target = e.target;

        // Handle add to cart button clicks
        const addToCartBtn = target.closest('.add-to-cart-btn');
        if (addToCartBtn) {
            const productCard = addToCartBtn.closest('.product-card');
            if (productCard) {
                addToCart(productCard);
            }
        }

        // Handle checkbox interactions
        if (target.classList.contains('simple-checkbox') || target.closest('.checkbox-wrapper')) {
            const cartItem = target.closest('.cart-item');
            const checkbox = cartItem ? cartItem.querySelector('.simple-checkbox') : null;
            
            if (checkbox) {
                // Toggle checkbox state
                if (target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                }
                cartItem.classList.toggle('selected', checkbox.checked);
                updateCartTotalPrice();
                console.log('Checkbox toggled:', checkbox.checked); // Debug log
            }
        }

        // Handle quantity buttons and remove
        if (target.closest('.cart-item')) {
            const cartItem = target.closest('.cart-item');
            const id = cartItem.dataset.id;

            if (target.classList.contains('plus') || target.classList.contains('minus') || 
                target.classList.contains('remove') || target.closest('.remove')) {
                e.preventDefault();
                e.stopPropagation();

                let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
                const cartItemIndex = cart.findIndex(item => item.id.toString() === id.toString());

                if (cartItemIndex !== -1) {
                    // Handle quantity input changes
            const quantityInput = cartItem.querySelector('.quantity-input');
            const updateQuantity = async (newQuantity) => {
                // Get current stock
                const response = await fetch(`get_product_stock.php?product_id=${id}`);
                const data = await response.json();
                const availableStock = data.success ? parseInt(data.stock) : 0;

                // Validate and adjust quantity
                newQuantity = Math.max(1, Math.min(newQuantity, availableStock));
                
                // Update cart and UI
                cart[cartItemIndex].quantity = newQuantity;
                quantityInput.value = newQuantity;
                cartItem.querySelector('.price').textContent = `₱${(cart[cartItemIndex].price * newQuantity).toFixed(2)}`;
                
                // Show notification if quantity was adjusted
                if (newQuantity !== parseInt(quantityInput.value)) {
                    showCartNotification(`Quantity adjusted to ${newQuantity} (maximum available stock)`);
                }
            };

            if (target.classList.contains('plus')) {
                updateQuantity(cart[cartItemIndex].quantity + 1);
            } else if (target.classList.contains('minus') && cart[cartItemIndex].quantity > 1) {
                updateQuantity(cart[cartItemIndex].quantity - 1);
            } else if (target.classList.contains('remove') || target.closest('.remove')) {
                        cart.splice(cartItemIndex, 1);
                        cartItem.remove();
                    }

                    sessionStorage.setItem('cart', JSON.stringify(cart));
                    updateCartTotalPrice();
                    updateCartCount();

                    if (cart.length === 0) {
                        document.getElementById('cartItems').innerHTML = '<div class="empty-cart">Your cart is empty</div>';
                    }
                }
            }
        }
    });

    // Cart modal handlers
    const cartBtn = document.getElementById('cartBtn');
    const cartModal = document.getElementById('cartModal');
    const closeCartBtn = document.getElementById('closeCartBtn');

    if (cartBtn && cartModal) {
        cartBtn.addEventListener('click', () => {
            cartModal.style.display = 'block';
            updateCartDisplay();
        });
    }

    if (closeCartBtn && cartModal) {
        closeCartBtn.addEventListener('click', () => {
            cartModal.style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === cartModal) {
                cartModal.style.display = 'none';
            }
        });
    }
});