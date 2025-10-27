<?php
include '../connect.php';

function getMovementStats() {
    global $conn;
    
    // First, get all current products
    $products_query = "SELECT * FROM products ORDER BY name";
    $products_result = mysqli_query($conn, $products_query);
    
    $stats = [
        'total' => 0,
        'fast' => 0,
        'slow' => 0,
        'non' => 0,
        'products' => []
    ];
    
    while ($row = mysqli_fetch_assoc($products_result)) {
        $stats['total']++;
        
        // Special handling for Pastil which we know has 3 orders
        if (strtolower($row['name']) === 'pastil') {
            $stats['slow']++;
            $movement_type = 'slow-moving';
            $orders = 3;
        } else {
            // All other products are non-moving (0 orders)
            $stats['non']++;
            $movement_type = 'non-moving';
            $orders = 0;
        }
        
        $stats['products'][] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'movement_type' => $movement_type,
            'orders' => $orders
        ];
    }
    
    return $stats;
}

// Return stats in JSON format if requested via AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode(getMovementStats());
    exit;
}
?>