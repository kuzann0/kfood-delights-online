<?php
include '../connect.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Debug endpoint to show current order counts
$query = "SELECT 
    p.name,
    COUNT(DISTINCT o.id) as order_count
FROM products p
LEFT JOIN order_items oi ON p.name = oi.item_name
LEFT JOIN orders o ON oi.order_id = o.id 
    AND o.status = 'completed' 
    AND o.payment_status = 'verified'
GROUP BY p.name";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$data = array();
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
?>