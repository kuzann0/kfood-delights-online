<?php
// Ensure clean output buffer
ob_start();
include '../connect.php';

function getMovementData($category) {
    global $conn;
    
    // Query to get successful orders - removed JOIN with new_products
    $query = "SELECT 
        p.*,
        (
            SELECT COUNT(DISTINCT o.id)
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id
            WHERE oi.item_name = p.name 
            AND o.status = 'completed'
            AND o.payment_status = 'verified'
        ) as successful_orders
    FROM products p";

    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        die(mysqli_error($conn));
    }
    
    $non_moving = [];
    $slow_moving = [];
    $fast_moving = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $successful_orders = (int)$row['successful_orders'];
        
        // Debug output
        error_log("Product: {$row['name']}, Successful Orders: {$successful_orders}");
        
        // Categorize based on successful orders
        if ($successful_orders > 10) {
            $fast_moving[] = $row;
        } elseif ($successful_orders >= 3 && $successful_orders <= 10) {
            $slow_moving[] = $row;
        } else {
            $non_moving[] = $row;
        }
    }
    
    $response = [
        'success' => true,
        'counts' => [
            'non-moving' => count($non_moving),
            'slow-moving' => count($slow_moving),
            'fast-moving' => count($fast_moving)
        ],
        'products' => [],
        'selectedCategory' => $category
    ];
    
    // Return products based on category
    switch ($category) {
        case 'non-moving':
            $response['products'] = $non_moving;
            break;
        case 'slow-moving':
            $response['products'] = $slow_moving;
            break;
        case 'fast-moving':
            $response['products'] = $fast_moving;
            break;
    }
    
    // Clear any previous output
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
}

// Handle the request
if (isset($_GET['category'])) {
    getMovementData($_GET['category']);
}
?>