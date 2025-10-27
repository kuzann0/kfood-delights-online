// Handle order summary display
function loadOrderSummary() {
    console.log('Loading order summary...');
    
    // First check for buyNowItem
    const buyNowItem = JSON.parse(sessionStorage.getItem('buyNowItem') || 'null');
    // Then check for regular cart items
    const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
    const cartItems = JSON.parse(sessionStorage.getItem('cart') || '[]');
    
    const orderItems = document.getElementById('orderItems');
    const totalAmountElement = document.querySelector('.total-amount');
    const totalSpan = document.getElementById('totalAmount');
    
    console.log('Buy Now Item:', buyNowItem);
    console.log('Selected Items:', selectedItems);
    console.log('Cart Items:', cartItems);
    
    if (!orderItems) {
        console.log('Order items container not found');
        return;
    }
    
    // If we have a buyNowItem, use that directly
    const selectedCartItems = buyNowItem ? [buyNowItem] : cartItems.filter(item => selectedItems.includes(item.id));
    console.log('Selected cart items for display:', selectedCartItems);
    
    if (selectedCartItems.length === 0) {
        orderItems.innerHTML = '<div class="empty-cart">No items selected for checkout</div>';
        if (totalAmountElement) totalAmountElement.textContent = '₱0.00';
        if (totalSpan) totalSpan.textContent = '₱0.00';
        return;
    }

    let html = '<div class="cart-breakdown">';
    let total = 0;

    selectedCartItems.forEach(item => {
        const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
        total += itemTotal;
        html += `
            <div class="breakdown-item">
                <div class="item-detail">
                    <span class="item-name">${item.name} (${item.quantity})</span>
                    <span class="item-dots"></span>
                    <span class="item-price">₱${itemTotal.toFixed(2)}</span>
                </div>
            </div>
        `;
    });

    html += `
        <div class="breakdown-divider"></div>
        <div class="breakdown-total">
            <span class="total-label">Total:</span>
            <span class="total-amount">₱${total.toFixed(2)}</span>
        </div>
    </div>`;

    orderItems.innerHTML = html;

    // Update all total amount displays
    const allTotalDisplays = document.querySelectorAll('.total-amount');
    allTotalDisplays.forEach(display => {
        display.textContent = `₱${total.toFixed(2)}`;
    });

    // Update total span
    if (totalSpan) {
        totalSpan.textContent = `₱${total.toFixed(2)}`;
    }

    // Update GCash amount if modal exists
    const gcashAmountSpan = document.getElementById('gcashAmount');
    if (gcashAmountSpan) {
        gcashAmountSpan.textContent = total.toFixed(2);
    }
}

// Load order summary when page loads and whenever cart changes
document.addEventListener('DOMContentLoaded', loadOrderSummary);
window.addEventListener('storage', function(e) {
    if (e.key === 'cart' || e.key === 'selectedItems') {
        loadOrderSummary();
    }
});