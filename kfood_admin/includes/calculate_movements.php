<?php
function calculateMovements($conn) {
    // Get orders from last 30 days
    $query = "SELECT 
        p.id,
        p.name,
        COALESCE(
            (
                SELECT COUNT(DISTINCT o.id)
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.item_name = p.name 
                AND o.status = 'completed'
                AND o.payment_status = 'verified'
                AND o.order_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ),
            0
        ) as order_count
    FROM products p";

    $result = mysqli_query($conn, $query);
    $movements = [
        'total' => 0,
        'fast' => 0,
        'slow' => 0,
        'non' => 0,
        'products' => []
    ];

    while ($row = mysqli_fetch_assoc($result)) {
        $movements['total']++;
        $order_count = (int)$row['order_count'];
        
        // Categorize based on orders
        if ($order_count > 10) {
            $category = 'fast-moving';
            $movements['fast']++;
        } elseif ($order_count >= 3) {
            $category = 'slow-moving';
            $movements['slow']++;
        } else {
            $category = 'non-moving';
            $movements['non']++;
        }
        
        $movements['products'][] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'category' => $category,
            'orders' => $order_count
        ];
    }
    
    return $movements;
}