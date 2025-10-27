<?php
// Central movement calculation logic
function calculateMovementCategory($orderCount) {
    if ($orderCount > 10) {
        return 'fast-moving';
    } elseif ($orderCount >= 3) {
        return 'slow-moving';
    } else {
        return 'non-moving';
    }
}

function getProductMovements() {
    global $conn;
    
    // Get orders from the last 30 days
    $query = "SELECT 
        p.id,
        p.name,
        p.stock,
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
    
    $movements = [
        'all' => 0,
        'fast-moving' => 0,
        'slow-moving' => 0,
        'non-moving' => 0,
        'products' => []
    ];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $monthly_orders = (int)$row['monthly_orders'];
        $category = calculateMovementCategory($monthly_orders);
        
        // Update counters
        $movements['all']++;
        $movements[$category]++;
        
        // Store product details
        $movements['products'][] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'stock' => $row['stock'],
            'monthly_orders' => $monthly_orders,
            'movement_category' => $category
        ];
        
        // Update product's movement category in database
        mysqli_query($conn, "UPDATE products SET 
            movement_category = '$category',
            monthly_orders = $monthly_orders,
            last_movement_update = NOW()
        WHERE id = " . $row['id']);
    }
    
    return $movements;
}

// Get stock thresholds based on movement category
function getStockThresholds($movement_category) {
    $thresholds = [
        'fast-moving' => [
            'critical' => 10,
            'sufficient' => 50
        ],
        'slow-moving' => [
            'critical' => 5,
            'sufficient' => 20
        ],
        'non-moving' => [
            'critical' => 2,
            'sufficient' => null // Non-moving products are overstocked above critical
        ]
    ];
    
    return $thresholds[$movement_category] ?? $thresholds['non-moving'];
}
?>