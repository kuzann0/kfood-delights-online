document.addEventListener('DOMContentLoaded', function() {
    const movementSelect = document.getElementById('movementTypeFilter');
    const movementCard = document.getElementById('movementTypeCard');
    const inventorySection = document.getElementById('inventory-section');

    // Remove hidden class if it exists
    if (inventorySection && inventorySection.classList.contains('hidden')) {
        inventorySection.classList.remove('hidden');
    }

    const MOVEMENT_STATES = {
        'all': {
            class: 'info',
            icon: 'fas fa-chart-pie',
            label: 'Moving Product',
            desc: 'All product movements',
            count: 4
        },
        'fast-moving': {
            class: 'success',
            icon: 'fas fa-chart-line',
            label: 'Fast Moving',
            desc: 'High demand items',
            count: 0
        },
        'slow-moving': {
            class: 'warning',
            icon: 'fas fa-clock',
            label: 'Slow Moving',
            desc: 'Regular demand items',
            count: 1
        },
        'non-moving': {
            class: 'danger',
            icon: 'fas fa-stop-circle',
            label: 'Non Moving',
            desc: 'Low demand items',
            count: 3
        }
    };

    function updateMovementCard(selectedMovement) {
        if (!movementCard) return;

        // Remove all state classes
        movementCard.classList.remove('success', 'warning', 'danger', 'info');
        
        const state = MOVEMENT_STATES[selectedMovement];
        if (!state) return;

        // Update card state
        movementCard.classList.add(state.class);

        // Update icon
        const iconElement = movementCard.querySelector('.stat-icon i');
        if (iconElement) {
            iconElement.className = state.icon;
        }

        // Update text elements
        const labelElement = movementCard.querySelector('.movement-label');
        if (labelElement) {
            labelElement.textContent = state.label;
        }

        const descElement = movementCard.querySelector('.movement-desc');
        if (descElement) {
            descElement.textContent = state.desc;
        }

        const countElement = movementCard.querySelector('.movement-count');
        if (countElement) {
            countElement.textContent = state.count;
        }

        // Filter table rows
        const rows = document.querySelectorAll('.inventory-table tbody tr');
        rows.forEach(row => {
            const movement = row.dataset.movement;
            row.style.display = (selectedMovement === 'all' || movement === selectedMovement) ? '' : 'none';
        });
    }

    if (movementSelect) {
        // Set initial state
        const defaultMovement = movementSelect.value || 'all';
        updateMovementCard(defaultMovement);
        
        // Update on movement type change
        movementSelect.addEventListener('change', function() {
            updateMovementCard(this.value);
        });
    }
});