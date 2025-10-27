document.addEventListener('DOMContentLoaded', function() {
    // Set default movement type to 'all'
    const movementSelect = document.getElementById('movementTypeFilter');
    if (movementSelect) {
        movementSelect.value = 'all';
        updateMovementType('all');
    }
});