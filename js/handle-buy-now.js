// Function to add item to cart and redirect to checkout
async function handleBuyNow(productId, quantity) {
    console.log('Handling buy now for product:', productId, 'quantity:', quantity);
    try {
        // First, add the item to the cart
        const response = await fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update session storage with the new cart item
            let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
            const existingItemIndex = cart.findIndex(item => item.id === productId);
            
            if (existingItemIndex !== -1) {
                cart[existingItemIndex].quantity = quantity;
            } else {
                cart.push({
                    id: productId,
                    name: data.product_name,
                    price: data.price,
                    quantity: quantity,
                    image: data.image
                });
            }
            
            sessionStorage.setItem('cart', JSON.stringify(cart));
            
            // Set this item as the only selected item for checkout
            sessionStorage.setItem('selectedItems', JSON.stringify([productId]));
            
            // Redirect to checkout
            window.location.href = 'checkout.php';
        } else {
            alert(data.message || 'Failed to add product to cart');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error occurred while processing your request');
    }
}