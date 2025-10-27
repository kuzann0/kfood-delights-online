<?php
include "../connect.php";

if(isset($_GET['username'])) {
    $username = trim($_GET['username']);
    
    // Check in users table
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check in super_admin_users table as well
    $stmt2 = $conn->prepare("SELECT super_admin_id FROM super_admin_users WHERE username = ?");
    $stmt2->bind_param("s", $username);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    // If username exists in either table
    if($result->num_rows > 0 || $result2->num_rows > 0) {
        echo json_encode(['available' => false]);
    } else {
        echo json_encode(['available' => true]);
    }
} else {
    echo json_encode(['error' => 'No username provided']);
}
?>