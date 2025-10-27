function updateMovementType(movementType) {
    // Get reference to DOM elements
    const stockRangeInfo = document.getElementById('stockRangeInfo');
    const movementCard = document.querySelector('.stat-card.movement-card');
    
    // Remove any existing color classes
    movementCard.classList.remove('info', 'success', 'warning', 'danger');

    // Update card icon, title, and appearance based on movement type
    const movementCardContent = {
        'all': {
            icon: 'chart-pie',
            title: 'All Moving',
            description: 'All product movements',
            color: '#2196F3',
            bgColor: 'rgba(33, 150, 243, 0.1)'
        },
        'fast-moving': {
            icon: 'chart-line',
            title: 'Fast-Moving',
            description: 'High demand items',
            color: '#4CAF50',
            bgColor: 'rgba(76, 175, 80, 0.1)'
        },
        'slow-moving': {
            icon: 'clock',
            title: 'Slow-Moving',
            description: 'Regular demand items',
            color: '#FF9800',
            bgColor: 'rgba(255, 152, 0, 0.1)'
        },
        'non-moving': {
            icon: 'pause-circle',
            title: 'Non-Moving',
            description: 'Low demand items',
            color: '#F44336',
            bgColor: 'rgba(244, 67, 54, 0.1)'
        }
    };

    // Update movement card content and styling
    const content = movementCardContent[movementType];
    movementCard.innerHTML = `
        <div class="stat-icon" style="background: ${content.bgColor}">
            <i class="fas fa-${content.icon}" style="color: ${content.color}"></i>
        </div>
        <div class="stat-info">
            <h3 style="color: ${content.color}">${content.title}</h3>
            <p>${content.description}</p>
        </div>
    `;
    
    // Add animation class
    movementCard.classList.add('card-update-animation');

    let ranges = {
        'all': [
            { range: 'All Product Movements', class: 'all-movements' }
        ],
        'fast-moving': [
            { range: '1-10: Critical', class: 'critical' },
            { range: '11-50: Sufficient', class: 'sufficient' },
            { range: '51+: Overstocked', class: 'overstocked' }
        ],
        'slow-moving': [
            { range: '1-5: Critical', class: 'critical' },
            { range: '6-20: Sufficient', class: 'sufficient' },
            { range: '21+: Overstocked', class: 'overstocked' }
        ],
        'non-moving': [
            { range: '1-2: Critical', class: 'critical' },
            { range: '3+: Overstocked', class: 'overstocked' }
        ]
    };

    // Update range badges
    stockRangeInfo.innerHTML = ranges[movementType].map(r => 
        `<div class="range-badge ${r.class}">${r.range}</div>`
    ).join('');

    // Update last card content
    const cardContent = {
        'fast-moving': {
            icon: 'chart-line',
            title: 'Fast-Moving',
            description: 'High demand items'
        },
        'slow-moving': {
            icon: 'clock',
            title: 'Slow-Moving',
            description: 'Regular demand items'
        },
        'non-moving': {
            icon: 'pause-circle',
            title: 'Non-Moving',
            description: 'Low demand items'
        }
    };

    // Handle 'all' movement type separately
    if (movementType === 'all') {
        lastCardIcon.className = 'fas fa-chart-pie';
        lastCardTitle.textContent = 'All Moving';
        lastCardDesc.textContent = 'All product movements';
    } else {
        lastCardIcon.className = `fas fa-${cardContent[movementType].icon}`;
        lastCardTitle.textContent = cardContent[movementType].title;
        lastCardDesc.textContent = cardContent[movementType].description;
    }

    // Update the inventory status badges based on the selected movement type
    updateInventoryStatuses(movementType);
}

function updateInventoryStatuses(movementType) {
    const rows = document.querySelectorAll('.inventory-table tbody tr');
    const thresholds = {
        'fast-moving': {
            critical: 10,
            sufficient: 50
        },
        'slow-moving': {
            critical: 5,
            sufficient: 20
        },
        'non-moving': {
            critical: 2
        }
    };

    rows.forEach(row => {
        const stockCell = row.querySelector('td:nth-child(4)');
        const statusBadge = row.querySelector('.status-badge');
        const stock = parseInt(stockCell.textContent);
        let status = '';
        let statusClass = '';

        if (stock <= 0) {
            status = 'Out of Stock';
            statusClass = 'out-of-stock';
        } else {
            const threshold = thresholds[movementType];
            if (stock <= threshold.critical) {
                status = 'Critical';
                statusClass = 'critical';
            } else if (!threshold.sufficient || stock <= threshold.sufficient) {
                status = 'Sufficient';
                statusClass = 'sufficient';
            } else {
                status = 'Overstocked';
                statusClass = 'overstocked';
            }
        }

        statusBadge.textContent = status;
        statusBadge.className = `status-badge ${statusClass}`;
    });
}