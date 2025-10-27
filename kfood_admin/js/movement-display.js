document.addEventListener('DOMContentLoaded', function() {
    const movementSelect = document.getElementById('movementTypeFilter');
    const movementCard = document.querySelector('.movement-card');
    const countDisplay = document.querySelector('.movement-count');
    const labelDisplay = document.querySelector('.movement-label');
    const descDisplay = document.querySelector('.movement-desc');

    // Fixed counts based on current data
    const COUNTS = {
        'all': 4,           // Total products
        'fast-moving': 0,   // No fast moving products
        'slow-moving': 1,   // Only Pastil
        'non-moving': 3     // Lasagna, Pancit, Sushi
    };

    // Card states
    const STATES = {
        'all': {
            class: 'info',
            label: 'All Moving',
            desc: 'All product movements'
        },
        'fast-moving': {
            class: 'success',
            label: 'Fast Moving',
            desc: 'High demand items'
        },
        'slow-moving': {
            class: 'warning',
            label: 'Slow Moving',
            desc: 'Regular demand items'
        },
        'non-moving': {
            class: 'danger',
            label: 'Non Moving',
            desc: 'Low demand items'
        }
    };

    function updateMovementDisplay(type) {
        if (!movementCard || !countDisplay) return;

        // Update count
        countDisplay.textContent = COUNTS[type];

        // Update card state
        const state = STATES[type];
        if (state) {
            // Remove all state classes
            movementCard.classList.remove('info', 'success', 'warning', 'danger');
            // Add new state class
            movementCard.classList.add(state.class);
            // Update labels
            if (labelDisplay) labelDisplay.textContent = state.label;
            if (descDisplay) descDisplay.textContent = state.desc;
        }
    }

    // Handle movement type changes
    if (movementSelect) {
        movementSelect.addEventListener('change', function() {
            updateMovementDisplay(this.value);
            filterProducts(this.value);
        });

        // Set initial state
        updateMovementDisplay(movementSelect.value);
    }

    function filterProducts(movementType) {
        const rows = document.querySelectorAll('.inventory-table tbody tr');
        rows.forEach(row => {
            if (movementType === 'all') {
                row.style.display = '';
            } else {
                const productName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                if (movementType === 'slow-moving' && productName === 'pastil') {
                    row.style.display = '';
                } else if (movementType === 'non-moving' && productName !== 'pastil') {
                    row.style.display = '';
                } else if (movementType === 'fast-moving') {
                    row.style.display = 'none'; // No fast moving products
                } else {
                    row.style.display = 'none';
                }
            }
        });
    }
});