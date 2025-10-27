<?php
include '../connect.php';

// Calculate movement statistics
function getMovementStats() {
    global $conn;
    
    // Get completed orders in last 30 days
    $query = "SELECT 
        p.id,
        p.name,
        p.category,
        p.stock,
        p.price,
        p.image,
        COALESCE(
            (SELECT SUM(oi.quantity)
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id
            WHERE oi.item_name = p.name 
            AND o.status = 'completed'
            AND o.payment_status = 'verified'
            AND o.order_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)), 0
        ) as monthly_orders
    FROM products p";

    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [
            'success' => false,
            'error' => mysqli_error($conn)
        ];
    }

    $stats = [
        'counts' => [
            'non_moving' => 0,
            'slow_moving' => 0,
            'fast_moving' => 0
        ],
        'products' => [
            'non_moving' => [],
            'slow_moving' => [],
            'fast_moving' => []
        ]
    ];

    while ($row = mysqli_fetch_assoc($result)) {
        $monthly_orders = (int)$row['monthly_orders'];
        
        if ($monthly_orders > 10) {
            $stats['counts']['fast_moving']++;
            $stats['products']['fast_moving'][] = $row;
        } elseif ($monthly_orders >= 3) {
            $stats['counts']['slow_moving']++;
            $stats['products']['slow_moving'][] = $row;
        } else {
            $stats['counts']['non_moving']++;
            $stats['products']['non_moving'][] = $row;
        }
    }

    return [
        'success' => true,
        'data' => $stats
    ];
}

// Get movement stats for specific category
if (isset($_GET['category'])) {
    $stats = getMovementStats();
    if ($stats['success']) {
        $category = $_GET['category'];
        $response = [
            'count' => $stats['data']['counts'][$category] ?? 0,
            'products' => $stats['data']['products'][$category] ?? []
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $stats['error']]);
    }
}
?>