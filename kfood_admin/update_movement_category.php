<?php
include "../connect.php";

if(isset($_POST['product_id']) && isset($_POST['movement_category'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $movement_category = mysqli_real_escape_string($conn, $_POST['movement_category']);
    
    $update_query = "UPDATE products SET movement_category = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $movement_category, $product_id);
    
    $response = array();
    if(mysqli_stmt_execute($stmt)) {
        $response['success'] = true;
        $response['message'] = 'Movement category updated successfully';
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to update movement category';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>