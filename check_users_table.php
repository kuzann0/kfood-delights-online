<?php
include 'connect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking users table...\n\n";

// First check if the table exists
$check_table = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_table->num_rows == 0) {
    die("The users table does not exist in the database!\n");
}

// Get table structure
$columns = $conn->query("SHOW COLUMNS FROM users");
if (!$columns) {
    die("Error getting table structure: " . $conn->error . "\n");
}

echo "Users table structure:\n";
while ($col = $columns->fetch_assoc()) {
    echo "Column: " . $col['Field'] . "\n";
    echo "Type: " . $col['Type'] . "\n";
    echo "Null: " . $col['Null'] . "\n";
    echo "Key: " . $col['Key'] . "\n";
    echo "Default: " . ($col['Default'] ?? 'NULL') . "\n";
    echo "Extra: " . $col['Extra'] . "\n\n";
}

// Count users in the table
$count = $conn->query("SELECT COUNT(*) as total FROM users");
if ($count) {
    $total = $count->fetch_assoc()['total'];
    echo "\nTotal users in database: " . $total . "\n";

    // Show a sample of users if any exist
    if ($total > 0) {
        echo "\nSample of users:\n";
        $sample = $conn->query("SELECT id, username, role_id FROM users LIMIT 5");
        while ($user = $sample->fetch_assoc()) {
            echo json_encode($user) . "\n";
        }
    }
} else {
    echo "\nError counting users: " . $conn->error . "\n";
}
?>