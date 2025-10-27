<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
$quantity = (int)$_POST['quantity'];

// Validate quantity
if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

// Get product details
$query = "SELECT name, price, image, stock FROM products WHERE id = '$product_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => mysqli_error($conn),
        'query' => $query
    ]);
    exit;
}

if ($row = mysqli_fetch_assoc($result)) {
    // Check if quantity is available
    if ($quantity > $row['stock']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Not enough stock available',
            'available_stock' => $row['stock']
        ]);
        exit;
    }

    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    // Add/update item in cart
    $_SESSION['cart'][$product_id] = $quantity;

    // Return success with product details
    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart successfully',
        'product_name' => $row['name'],
        'price' => $row['price'],
        'image' => $row['image'],
        'quantity' => $quantity
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
}

mysqli_close($conn);
?>