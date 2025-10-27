document.addEventListener('DOMContentLoaded', function() {
    const movementSelect = document.getElementById('movementTypeFilter');
    
    if (movementSelect) {
        // First, fetch debug info
        fetch('debug_orders.php')
            .then(response => response.json())
            .then(data => {
                console.log('Current order counts:', data);
            })
            .catch(error => console.error('Debug fetch error:', error));

        movementSelect.addEventListener('change', function() {
            console.log('Movement type changed to:', this.value);
            updateMovementDisplay(this.value);
        });
        
        // Initial load with non-moving selected
        movementSelect.value = 'non-moving';
        updateMovementDisplay('non-moving');
    }
});

function updateMovementDisplay(category) {
    console.log('Updating display for category:', category);
    
    // Fetch movement data
    fetch(`get_movement_stats.php?category=${category}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            
            if (data.error) {
                console.error('Server error:', data.error);
                return;
            }
            
            // Update movement card count
            const movementCard = document.getElementById('movementTypeCard');
            if (movementCard) {
                // Update count based on selected category
                const countElement = movementCard.querySelector('.movement-count');
                if (countElement) {
                    countElement.textContent = data.counts[category];
                }
                
                // Update card label and description
                const labelElement = movementCard.querySelector('.movement-label');
                const descElement = movementCard.querySelector('.movement-desc');
                if (labelElement && descElement) {
                    switch(category) {
                        case 'non-moving':
                            labelElement.textContent = 'Non-Moving';
                            descElement.textContent = '0-2 units/month';
                            break;
                        case 'slow-moving':
                            labelElement.textContent = 'Slow-Moving';
                            descElement.textContent = '3-10 units/month';
                            break;
                        case 'fast-moving':
                            labelElement.textContent = 'Fast-Moving';
                            descElement.textContent = '>10 units/month';
                            break;
                    }
                }
            }
            
            // Update table rows
            const tableBody = document.querySelector('.inventory-table tbody');
            if (tableBody && data.products) {
                const allRows = tableBody.querySelectorAll('tr');
                allRows.forEach(row => row.style.display = 'none');
                
                // Show only products for current category
                if (data.products.length > 0) {
                    const productNames = data.products.map(p => p.name.trim().toLowerCase());
                    allRows.forEach(row => {
                        const productName = row.querySelector('td:nth-child(2)').textContent.trim().toLowerCase();
                        if (productNames.includes(productName)) {
                            row.style.display = '';
                        }
                    });
                }
            }
        })
        .catch(error => console.error('Error fetching movement data:', error));
}