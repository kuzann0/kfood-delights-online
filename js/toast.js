// Simple notification function
const showNotification = (function() {
    // Create a fixed container for notifications
    const container = document.createElement('div');
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 999999;
    `;
    document.body.appendChild(container);

    return function(message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.style.cssText = `
            background: #4CAF50;
            color: white;
            padding: 15px 25px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            font-family: Arial, sans-serif;
            font-size: 16px;
            min-width: 200px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        `;

        // Add content
        notification.innerHTML = `
            <i class="fas fa-check-circle" style="margin-right: 10px;"></i>
            <span>${message}</span>
        `;

        // Add to container
        container.appendChild(notification);

        // Force reflow
        notification.offsetHeight;

        // Show notification
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    };
})();

// For backward compatibility
window.showCartNotification = showNotification;