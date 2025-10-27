document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all movement category selects
    document.querySelectorAll('.movement-category-select').forEach(select => {
        select.addEventListener('change', function() {
            const productId = this.dataset.productId;
            const newCategory = this.value;
            
            updateMovementCategory(productId, newCategory, this);
        });
    });
});

function updateMovementCategory(productId, category, selectElement) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('movement_category', category);

    fetch('update_movement_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update stock level indicators based on new category
            updateStockLevelIndicators(selectElement.closest('tr'), category);
            
            // Show success notification
            showNotification('Success', 'Movement category updated successfully', 'success');
        } else {
            showNotification('Error', 'Failed to update movement category', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error', 'Failed to update movement category', 'error');
    });
}

function updateStockLevelIndicators(row, category) {
    const stockCell = row.querySelector('.stock-cell');
    const stockValue = parseInt(stockCell.dataset.stock);
    let status = '';
    let statusClass = '';

    switch(category) {
        case 'fast-moving':
            if (stockValue === 0) {
                status = 'Out of Stock';
                statusClass = 'out-of-stock';
            } else if (stockValue <= 10) {
                status = 'Critical';
                statusClass = 'critical';
            } else if (stockValue <= 50) {
                status = 'Sufficient';
                statusClass = 'sufficient';
            } else {
                status = 'Overstocked';
                statusClass = 'overstocked';
            }
            break;
        
        case 'slow-moving':
            if (stockValue === 0) {
                status = 'Out of Stock';
                statusClass = 'out-of-stock';
            } else if (stockValue <= 5) {
                status = 'Critical';
                statusClass = 'critical';
            } else if (stockValue <= 20) {
                status = 'Sufficient';
                statusClass = 'sufficient';
            } else {
                status = 'Overstocked';
                statusClass = 'overstocked';
            }
            break;
        
        case 'non-moving':
            if (stockValue === 0) {
                status = 'Out of Stock';
                statusClass = 'out-of-stock';
            } else if (stockValue <= 2) {
                status = 'Critical';
                statusClass = 'critical';
            } else {
                status = 'Overstocked';
                statusClass = 'overstocked';
            }
            break;
    }

    // Update the status badge
    const statusBadge = row.querySelector('.status-badge');
    statusBadge.textContent = status;
    statusBadge.className = `status-badge ${statusClass}`;
}

function showNotification(title, message, type) {
    const notification = document.createElement('div');
    notification.className = `notification-toast ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <div>
                <div class="notification-title">${title}</div>
                <div class="notification-message">${message}</div>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add('show'), 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}