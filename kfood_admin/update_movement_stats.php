<?php
include "../connect.php";

function getMovementStats() {
    global $conn;
    
    // Query to get completed orders in the last 30 days
    $query = "SELECT 
        p.id,
        p.name,
        COALESCE(
            (SELECT SUM(oi.quantity)
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id
            WHERE oi.item_name = p.name 
            AND o.status = 'completed'
            AND o.payment_status = 'verified'
            AND o.order_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY oi.item_name), 0
        ) as monthly_orders
    FROM products p";
    
    $result = mysqli_query($conn, $query);
    
    $stats = array(
        'non_moving' => 0,
        'slow_moving' => 0,
        'fast_moving' => 0,
        'products' => array()
    );
    
    while ($row = mysqli_fetch_assoc($result)) {
        $monthly_orders = (int)$row['monthly_orders'];
        
        if ($monthly_orders > 10) {
            $stats['fast_moving']++;
            $category = 'fast-moving';
        } elseif ($monthly_orders >= 3) {
            $stats['slow_moving']++;
            $category = 'slow-moving';
        } else {
            $stats['non_moving']++;
            $category = 'non-moving';
        }
        
        // Update product's movement category in database
        mysqli_query($conn, "UPDATE products SET movement_category = '$category' WHERE id = " . $row['id']);
    }
    
    return $stats;
}

// Update movement status when called
$stats = getMovementStats();

// Return stats as JSON
header('Content-Type: application/json');
echo json_encode($stats);
?>