<?php
require_once 'connect.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$product_id = mysqli_real_escape_string($conn, $_GET['product_id']);

// First, check if the product exists and get all relevant information
$query = "SELECT p.*, pc.category_name 
          FROM products p 
          JOIN product_categories pc ON p.category_id = pc.id 
          WHERE p.id = '$product_id'";

$result = mysqli_query($conn, $query);

if (!$result) {
    // If there's a query error, return the error information
    echo json_encode([
        'success' => false, 
        'message' => 'Database error',
        'error' => mysqli_error($conn),
        'query' => $query
    ]);
    exit;
}

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'stock' => $row['stock'],
        'name' => $row['name'],
        'price' => $row['price'],
        'category' => $row['category_name']
    ]);
} else {
    // If no product found, return detailed error
    echo json_encode([
        'success' => false,
        'message' => 'Product not found',
        'product_id' => $product_id,
        'query' => $query
    ]);
}

mysqli_close($conn);
?>
