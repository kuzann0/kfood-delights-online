// Movement Type Constants
const MOVEMENT_TYPES = {
    ALL: 'all',
    FAST: 'fast-moving',
    SLOW: 'slow-moving',
    NON: 'non-moving'
};

// Stock Thresholds
const STOCK_THRESHOLDS = {
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

// Movement Card States
const CARD_STATES = {
    'all': {
        class: 'info',
        icon: 'chart-pie',
        label: 'All Moving',
        desc: 'All product movements'
    },
    'fast-moving': {
        class: 'success',
        icon: 'chart-line',
        label: 'Fast Moving',
        desc: 'High demand items'
    },
    'slow-moving': {
        class: 'warning',
        icon: 'clock',
        label: 'Slow Moving',
        desc: 'Regular demand items'
    },
    'non-moving': {
        class: 'danger',
        icon: 'stop-circle',
        label: 'Non Moving',
        desc: 'Low demand items'
    }
};

class InventoryMovement {
    constructor() {
        this.movementSelect = document.getElementById('movementTypeFilter');
        this.movementCard = document.querySelector('.movement-card');
        this.countElement = document.querySelector('.movement-count');
        
        this.initializeEventListeners();
        this.updateMovementDisplay('all');
    }

    initializeEventListeners() {
        if (this.movementSelect) {
            this.movementSelect.addEventListener('change', () => {
                this.updateMovementDisplay(this.movementSelect.value);
                this.filterProducts();
            });
        }
    }

    updateMovementDisplay(type) {
        if (!this.movementCard) return;

        // Remove existing state classes
        this.movementCard.classList.remove('info', 'success', 'warning', 'danger');
        
        const state = CARD_STATES[type];
        if (!state) return;

        // Update card state
        this.movementCard.classList.add(state.class);
        
        // Update icon
        const icon = this.movementCard.querySelector('.stat-icon i');
        if (icon) {
            icon.className = `fas fa-${state.icon}`;
        }

        // Update labels
        const label = this.movementCard.querySelector('.movement-label');
        const desc = this.movementCard.querySelector('.movement-desc');
        
        if (label) label.textContent = state.label;
        if (desc) desc.textContent = state.desc;

        // Update count based on movement type
        this.updateCount(type);
    }

    updateCount(type) {
        if (!this.countElement) return;

        // These counts should match your actual data
        const counts = {
            'all': 4,              // Total products
            'fast-moving': 0,      // Products with >10 orders/month
            'slow-moving': 1,      // Products with 3-10 orders/month
            'non-moving': 3        // Products with 0-2 orders/month
        };

        this.countElement.textContent = counts[type] || 0;
    }

    filterProducts() {
        const rows = document.querySelectorAll('.inventory-table tbody tr');
        const selectedMovement = this.movementSelect.value;

        rows.forEach(row => {
            const movement = row.dataset.movement;
            row.style.display = (selectedMovement === 'all' || movement === selectedMovement) ? '' : 'none';
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const inventoryMovement = new InventoryMovement();
});
