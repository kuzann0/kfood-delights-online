<?php
session_start();
include "../connect.php";

// Function to display new products table
function getNewProducts($conn) {
    $sql = "SELECT * FROM new_products ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    return $result;
}

// Process Add Product Form
if(isset($_POST['add_product'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $markup_value = mysqli_real_escape_string($conn, $_POST['markup_value']);
    $category_id = mysqli_real_escape_string($conn, $_POST['category']);
    $unit_measurement = mysqli_real_escape_string($conn, $_POST['unit_measurement']);
    
    // Set initial stock to 0
    $initial_stock = 0;

    // Get category name
    $category_query = "SELECT category_name FROM product_categories WHERE id = ?";
    $stmt = mysqli_prepare($conn, $category_query);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $category_result = mysqli_stmt_get_result($stmt);
    $category_row = mysqli_fetch_assoc($category_result);
    $category_name = $category_row['category_name'];

    // Handle image upload
    $image = $_FILES['product_image']['name'];
    $image_tmp_name = $_FILES['product_image']['tmp_name'];
    $image_folder = '../uploaded_img/';
    
    // Generate unique filename
    $image_extension = pathinfo($image, PATHINFO_EXTENSION);
    $unique_image_name = uniqid() . '.' . $image_extension;
    $image_path = $image_folder . $unique_image_name;

    // Validate image
    $allowed_extensions = array('jpg', 'jpeg', 'png');
    if(!in_array(strtolower($image_extension), $allowed_extensions)) {
        echo "<script>
            showNotification('Error', 'Invalid image format. Please use JPG, JPEG, or PNG.', 'error');
        </script>";
        exit();
    }

    // Insert product into database
    $insert_query = "INSERT INTO new_products (product_name, markup_value, category_id, category_name, unit_measurement, image) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = mysqli_prepare($conn, $insert_query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        
        if (!mysqli_stmt_bind_param($stmt, "sdisss", $product_name, $markup_value, $category_id, $category_name, $unit_measurement, $unique_image_name)) {
            throw new Exception("Binding parameters failed: " . mysqli_stmt_error($stmt));
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
        }
        
        move_uploaded_file($image_tmp_name, $image_path);
        echo "<script>
            showNotification('Success', 'Product added successfully!', 'success');
            setTimeout(() => { window.location.href = 'admin_pg.php?section=menu-creation'; }, 1500);
        </script>";
    } catch (Exception $e) {
        echo "<script>
            showNotification('Error', '" . htmlspecialchars($e->getMessage()) . "', 'error');
        </script>";
    }
}

// Process Add Category
if(isset($_POST['add_category'])) {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    
    // Check if category already exists
    $check_query = "SELECT id FROM product_categories WHERE category_name = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $category_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) > 0) {
        echo "<script>
            showNotification('Error', 'Category already exists!', 'error');
        </script>";
    } else {
        $insert_query = "INSERT INTO product_categories (category_name) VALUES (?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "s", $category_name);
        
        if(mysqli_stmt_execute($stmt)) {
            echo "<script>
                showNotification('Success', 'Category added successfully!', 'success');
                setTimeout(() => { window.location.href = 'admin_pg.php?section=menu-creation'; }, 1500);
            </script>";
        } else {
            echo "<script>
                showNotification('Error', 'Failed to add category. Please try again.', 'error');
            </script>";
        }
    }
}

// Add stylesheet for notifications
echo "<style>
    #notifHeader {
        color: #333333;
    }
    
    [data-theme='dark'] #notifHeader {
        color: #ffffff;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
    }

    .verification-modal {
        background-color: #ffffff;
        margin: 5% auto;
        padding: 25px;
        width: 90%;
        max-width: 800px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }

    [data-theme='dark'] .verification-modal {
        background-color: #2a2d3a;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e5e7eb;
    }

    [data-theme='dark'] .modal-header {
        border-bottom-color: rgba(255,255,255,0.1);
    }

    .modal-header h3 {
        color: #1a1a1a;
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    [data-theme='dark'] .modal-header h3 {
        color: #ffffff;
    }

    .modal-header h3 i {
        color: #FF7F50;
    }

    .modal .close {
        color: #666;
        font-size: 24px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }

    [data-theme='dark'] .modal .close {
        color: #ffffff;
    }

    .modal .close:hover {
        background-color: rgba(255,127,80,0.1);
        color: #FF7F50;
    }

    .verification-list {
        max-height: 600px;
        overflow-y: auto;
        padding-right: 10px;
    }

    .verification-list::-webkit-scrollbar {
        width: 8px;
    }

    .verification-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .verification-list::-webkit-scrollbar-thumb {
        background: #FF7F50;
        border-radius: 4px;
    }

    [data-theme='dark'] .verification-list::-webkit-scrollbar-track {
        background: #32364a;
    }

    [data-theme='dark'] .verification-list::-webkit-scrollbar-thumb {
        background: #FF7F50;
    }
</style>";

// Test database connection
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Database connection failed');
} else {
    error_log('Database connection successful');
}

// Function to check if admin has permission for a section
function hasPermission($conn, $userId, $section) {
    $query = "SELECT {$section}_access FROM user_permissions WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row ? $row["{$section}_access"] == 1 : false;
}

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['role_id'])) {
    // Clear any lingering session data
    session_unset();
    session_destroy();
    header("Location: ../loginpage.php");
    exit();
}

// Clear any old notification flags if this isn't a fresh login
if (!isset($_SESSION['just_logged_in']) || $_SESSION['just_logged_in'] !== true) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    echo "<script>sessionStorage.removeItem('alertShown');</script>";
}

// Allow both super admin (role_id = 1) and admin (role_id = 2)
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    header("Location: ../unauthorized.php");
    exit();
}

// Set admin flag for UI customization
$is_super_admin = ($_SESSION['role_id'] == 1);

// Debug POST data
if(!empty($_POST)) {
    error_log('POST data received: ' . print_r($_POST, true));
}

// User Creation Functionality
if(isset($_POST['createUser'])) {
    // Debug information
    $debug_file = __DIR__ . '/form_submissions.log';
    $debug_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'POST' => $_POST,
        'GET' => $_GET,
        'FILES' => $_FILES,
        'SESSION' => $_SESSION ?? 'No session'
    ];
    
    // Log to debug file
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Form submission:\n" . 
                     print_r($debug_data, true) . "\n\n", FILE_APPEND);
    
    error_log('Create user form submitted');
    error_log('POST data: ' . print_r($_POST, true));

    // Verify database connection first
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Connection failed: " . $conn->connect_error);
    }
    error_log("Database connection verified");

    // Test database with a simple query
    $test_query = $conn->query("SELECT 1 FROM users LIMIT 1");
    if (!$test_query) {
        error_log("Database test query failed: " . $conn->error);
        die("Database error: " . $conn->error);
    }
    error_log("Database test query successful");

    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $roleId = isset($_POST['roleId']) ? (int)$_POST['roleId'] : 4;

    error_log("Form data received - Username: $username, Role: $roleId, Email: $email");

    try {
        // Validate required fields
        if(empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
            throw new Exception("All fields are required");
        }

        // Validate role ID
        if(!in_array($roleId, [2, 3, 4])) {
            throw new Exception("Invalid role selected");
        }

        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if($stmt->get_result()->num_rows > 0) {
            throw new Exception("Username already exists");
        }

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists");
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            error_log("Starting user creation process");
            
            // Start transaction
            $conn->begin_transaction();
            error_log("Transaction started");

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            error_log("Password hashed successfully");

            // Log the values being inserted
            error_log("Inserting user with values: " . json_encode([
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'username' => $username,
                'Email' => $email,
                'role_id' => $roleId
            ]));

            // Insert new user with exact column names from database
            $query = "INSERT INTO users (Id, FirstName, LastName, username, Email, Password, role_id) "
                   . "VALUES (NULL, ?, ?, ?, ?, ?, ?)"; 
            error_log("SQL Query: $query");

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error . " Query: " . $query);
            }
            error_log("Statement prepared successfully");

            if (!$stmt->bind_param("sssssi", $firstName, $lastName, $username, $email, $hashedPassword, $roleId)) {
                throw new Exception("Parameter binding failed: " . $stmt->error);
            }
            error_log("Parameters bound successfully");
            
            error_log("Executing statement with values: " . 
                      "firstName=$firstName, lastName=$lastName, " . 
                      "username=$username, email=$email, roleId=$roleId");

            $success = $stmt->execute();
            if (!$success) {
                error_log("Execute failed with error: " . $stmt->error);
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $affected_rows = $stmt->affected_rows;
            $insert_id = $stmt->insert_id;
            error_log("Statement execution results - Affected rows: $affected_rows, Insert ID: $insert_id");

            if ($affected_rows <= 0) {
                throw new Exception("No rows were inserted");
            }

            error_log("User inserted successfully with ID: " . $insert_id);

            // Get the newly inserted user's ID
            $newUserId = $stmt->insert_id;

            // Log the successful insertion
            error_log("New user created - ID: $newUserId, Username: $username, Role: $roleId");

            // Commit the transaction
            $conn->commit();
            error_log("Transaction committed successfully");

            // Set a session message for the notification
            $_SESSION['message'] = 'User account created successfully';
            $_SESSION['message_type'] = 'success';

            // Redirect to prevent form resubmission
            header("Location: admin_pg.php?section=roles&success=1");
            exit();
        } catch (Exception $e) {
            // Rollback the transaction on error
            $conn->rollback();
            error_log("Failed to create user: " . $e->getMessage());
            throw new Exception("Failed to create user account: " . $e->getMessage());
        }

    } catch (Exception $e) {
        echo "<script>showNotification('Error', '" . htmlspecialchars($e->getMessage()) . "', 'error');</script>";
    }
}

// Menu Creation Functionality
// Product management code removed

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $delete_query = mysqli_query($conn, "DELETE FROM `products` WHERE id = $delete_id ") or die('query failed');
   if($delete_query){
      $message[] = 'product has been deleted';
   }else{
      $message[] = 'product could not be deleted';
   }
}

if(isset($_POST['update_product'])){
   $update_p_id = $_POST['update_p_id'];
   $update_p_name = $_POST['update_p_name'];
   $update_p_price = $_POST['update_p_price'];
   
   // Check if a new image was uploaded
   if(!empty($_FILES['update_p_image']['name'])){
      $update_p_image = $_FILES['update_p_image']['name'];
      $update_p_image_tmp_name = $_FILES['update_p_image']['tmp_name'];
      $update_p_image_folder = '../uploaded_img/'.$update_p_image;
      
      // Include image in update
      $update_query = mysqli_query($conn, "UPDATE `products` SET name = '$update_p_name', category = '$update_p_category', price = '$update_p_price', image = '$update_p_image' WHERE id = '$update_p_id'");
      
      if($update_query){
         move_uploaded_file($update_p_image_tmp_name, $update_p_image_folder);
         header('Location: admin_pg.php?section=menu-creation&action=update');
         exit();
      }else{
         echo "<script>alert('Error: Product could not be updated');</script>";
      }
   } else {
      // Update without changing the image
      $update_query = mysqli_query($conn, "UPDATE `products` SET name = '$update_p_name', price = '$update_p_price' WHERE id = '$update_p_id'");
      
      if($update_query){
         header('Location: admin_pg.php?section=menu-creation&action=update');
         exit();
      }else{
         header('Location: admin_pg.php?section=menu-creation&action=error');
         exit();
      }
   }
   
   // Prevent duplicate form submission
   header('Location: admin_pg.php?section=menu-creation');
   exit();
}

// Get admin details based on role
$admin_data = [];
if ($_SESSION['role_id'] == 1) {
    // Super admin data
    $admin_id = $_SESSION['admin_id'];
    $query = "SELECT username, full_name FROM super_admin_users WHERE super_admin_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_data = $result->fetch_assoc();
} else {
    // Regular admin data
    $admin_data = [
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['firstName'] . ' ' . $_SESSION['lastName']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/inventory.css">
    <link rel="stylesheet" href="css/inventory-dark.css">
    <link rel="stylesheet" href="css/table-dark.css">
    <link rel="stylesheet" href="css/role-features.css">
    <link rel="stylesheet" href="css/inventory-stats.css">
    <link rel="stylesheet" href="css/theme-variables.css">
    <link rel="stylesheet" href="css/dark-mode-components.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <link rel="stylesheet" href="css/dark-mode-text.css">
    <link rel="stylesheet" href="css/dark-mode-override.css">
    <link rel="stylesheet" href="css/dark-mode-final.css">
    <link rel="stylesheet" href="css/verification.css">
    <link rel="stylesheet" href="css/verification-enhanced.css">
    <link rel="stylesheet" href="css/completion-chart.css">
    <link rel="stylesheet" href="css/unified-status-badges.css">
    <link rel="stylesheet" href="css/image-upload.css">
    <link rel="stylesheet" href="css/adm-style.css">
    <link rel="stylesheet" href="css/admin-enhanced.css">
    <link rel="stylesheet" href="css/movement-dropdown.css">
    <link rel="stylesheet" href="css/category-modal.css">
    <link rel="stylesheet" href="css/movement-stats.css">
    <style>
        .table-responsive {
            margin-top: 50px !important;
        }

        /* Search Styles */
        .search-container {
            margin-left: auto;
            display: flex;
            align-items: center;
        }

        .search-input {
            padding: 8px 12px;
            border: 1px solid #CBD5E0;
            border-radius: 6px;
            font-size: 14px;
            width: 250px;
            transition: all 0.3s ease;
            background: #ffffff;
            color: #2D3748;
        }

        .search-input::placeholder {
            color: #A0AEC0;
        }

        .search-input:focus {
            outline: none;
            border-color: #FF7F50;
            box-shadow: 0 0 0 2px rgba(255, 127, 80, 0.2);
        }

        [data-theme="dark"] .search-input {
            background: #2a2d3a;
            border-color: #454b60;
            color: #e2e8f0;
        }

        [data-theme="dark"] .search-input::placeholder {
            color: #718096;
        }

        .products-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: #F7FAFC;
            border-bottom: 1px solid #E2E8F0;
        }

        .table-header h3 {
            color: #2D3748;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .products-table th {
            background: #F7FAFC;
            color: #4A5568;
            font-weight: 600;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 2px solid #E2E8F0;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
        }

        .products-table td {
            padding: 12px 16px;
            color: #2D3748;
            border-bottom: 1px solid #E2E8F0;
            background: #ffffff;
        }

        .products-table tbody tr:hover td {
            background: #F7FAFC;
        }

        .products-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Dark mode overrides */
        [data-theme="dark"] .table-header {
            background: #2a2d3a;
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .table-header h3 {
            color: #ffffff;
        }

        [data-theme="dark"] .products-table {
            background: #1a1c23;
            border-color: #2d3748;
        }

        [data-theme="dark"] .products-table th {
            background: #2d3748;
            color: #e2e8f0;
            border-bottom-color: #4a5568;
        }

        [data-theme="dark"] .products-table td {
            background: #1a1c23;
            color: #e2e8f0;
            border-bottom-color: #2d3748;
        }

        [data-theme="dark"] .products-table tbody tr:hover td {
            background: #2d3748;
        }
    </style>
    <script src="js/notifications.js"></script>
    <script>
        // Enhanced search functionality for newly added products
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('productSearch');
            
            if (searchInput) {
                const searchProducts = () => {
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    const tableBody = document.querySelector('.products-table tbody');
                    const rows = tableBody.getElementsByTagName('tr');
                    let hasVisibleRows = false;
                    
                    for (let row of rows) {
                        if (row.cells.length <= 1) continue; // Skip if it's a "No products found" row
                        
                        const productName = row.cells[1].textContent.toLowerCase(); // Product Name column
                        const categoryName = row.cells[4].textContent.toLowerCase(); // Category Name column
                        const id = row.cells[0].textContent.toLowerCase(); // ID column
                        const markupValue = row.cells[2].textContent.toLowerCase(); // Markup Value column
                        const unitMeasurement = row.cells[5].textContent.toLowerCase(); // Unit Measurement column
                        
                        const matchesSearch = productName.includes(searchTerm) || 
                                           categoryName.includes(searchTerm) ||
                                           id.includes(searchTerm) ||
                                           markupValue.includes(searchTerm) ||
                                           unitMeasurement.includes(searchTerm);
                        
                        row.style.display = matchesSearch ? '' : 'none';
                        if (matchesSearch) hasVisibleRows = true;
                    }
                    
                    // Show "No results found" if no matches
                    const existingNoResults = tableBody.querySelector('.no-results-row');
                    if (!hasVisibleRows) {
                        if (!existingNoResults) {
                            const noResultsRow = document.createElement('tr');
                            noResultsRow.className = 'no-results-row';
                            noResultsRow.innerHTML = '<td colspan="8" style="text-align: center;">No matching products found</td>';
                            tableBody.appendChild(noResultsRow);
                        } else {
                            existingNoResults.style.display = '';
                        }
                    } else if (existingNoResults) {
                        existingNoResults.style.display = 'none';
                    }
                };

                // Add event listeners for real-time search
                searchInput.addEventListener('input', searchProducts);
                searchInput.addEventListener('keyup', searchProducts);
                
                // Clear search when clicking the clear button (x) in the search input
                searchInput.addEventListener('search', searchProducts);
            }
        });

        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const productFilter = document.getElementById('productFilter');
            if (productFilter) {
                productFilter.addEventListener('change', function() {
                    const selectedProduct = this.value;
                    const rows = document.querySelectorAll('.restock-table tbody tr');
                    
                    rows.forEach(row => {
                        const productCell = row.querySelector('td:nth-child(2)');
                        if (!productCell) return; // Skip if no product cell (like in "no records" row)
                        
                        if (!selectedProduct || productCell.textContent.trim() === selectedProduct) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });

        function updateRestockTable(newRecord) {
            const table = document.querySelector('.restock-table tbody');
            if (!table) return;

            // Remove "No records" message if it exists
            if (table.innerHTML.includes('No restocking records found')) {
                table.innerHTML = '';
            }

            // Create new row
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${newRecord.date}</td>
                <td>${newRecord.product_name}</td>
                <td>${newRecord.restock_quantity}</td>
                <td>₱${parseFloat(newRecord.cost_per_unit).toFixed(2)}</td>
                <td>₱${parseFloat(newRecord.final_price).toFixed(2)}</td>
                <td>${newRecord.expiration_date}</td>
            `;

            // Insert at the top of the table
            table.insertBefore(row, table.firstChild);
        }

        async // Function to update stock with FIFO method
function updateStockFIFO(productId, quantity, conn) {
    // Get restocking records ordered by expiration date (oldest first)
    $stmt = $conn->prepare("
        SELECT id, product_id, restock_quantity, current_stock, expiration_date 
        FROM restocking 
        WHERE product_id = ? AND restock_quantity > 0 
        ORDER BY expiration_date ASC"
    );
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $remainingQuantity = $quantity;
    $updates = [];
    
    while($row = $result->fetch_assoc() && $remainingQuantity > 0) {
        $availableQuantity = $row['restock_quantity'];
        $deductQuantity = min($availableQuantity, $remainingQuantity);
        
        // Update restocking record
        $newQuantity = $availableQuantity - $deductQuantity;
        $updates[] = [
            'id' => $row['id'],
            'quantity' => $newQuantity
        ];
        
        $remainingQuantity -= $deductQuantity;
    }
    
    // Apply updates
    foreach($updates as $update) {
        $stmt = $conn->prepare("
            UPDATE restocking 
            SET restock_quantity = ? 
            WHERE id = ?"
        );
        $stmt->bind_param("ii", $update['quantity'], $update['id']);
        $stmt->execute();
    }
    
    // Update total stock in products table
    $stmt = $conn->prepare("
        UPDATE products 
        SET stock = stock - ? 
        WHERE id = ?"
    );
    $stmt->bind_param("ii", $quantity, $productId);
    $stmt->execute();
    
    // Record in stock history
    $stmt = $conn->prepare("
        INSERT INTO stock_history (product_id, type, quantity, date) 
        VALUES (?, 'deduct', ?, NOW())"
    );
    $stmt->bind_param("ii", $productId, $quantity);
    $stmt->execute();
}

function submitRestock(event) {
            event.preventDefault();
            
            try {
                const form = event.target;
                const formData = new FormData();
                
                // Get form values
                const product = document.getElementById('product');
                const quantity = document.getElementById('quantity');
                const unitCost = document.getElementById('unitCost');
                const expirationDate = document.getElementById('expirationDate');
                
                // Validate input
                if (!product.value || !quantity.value || !unitCost.value || !expirationDate.value) {
                    throw new Error('Please fill in all fields');
                }
                if (parseFloat(quantity.value) <= 0) {
                    throw new Error('Quantity must be greater than 0');
                }
                if (parseFloat(unitCost.value) <= 0) {
                    throw new Error('Unit cost must be greater than 0');
                }
                const selectedDate = new Date(expirationDate.value);
                if (selectedDate <= new Date()) {
                    throw new Error('Expiration date must be in the future');
                }
                
                formData.append('product_id', product.value);
                formData.append('restock_quantity', quantity.value);
                formData.append('cost_per_unit', unitCost.value);
                formData.append('expiration_date', expirationDate.value);
                formData.append('action', 'restock_product');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
            
                if (data.success) {
                    // Create record for the table with data from server
                    const newRecord = {
                        date: new Date().toLocaleString(),
                        product_name: data.product_name,
                        restock_quantity: data.restock_quantity,
                        cost_per_unit: unitCost.value,
                        final_price: data.final_price,
                        expiration_date: new Date(expirationDate.value).toLocaleDateString()
                    };

                    // Update the table with the new record
                    updateRestockTable(newRecord);

                    // Show success notification
                    const successMsg = document.createElement('div');
                    successMsg.className = 'notification-toast success';
                    successMsg.innerHTML = `
                        <div class="notification-content">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <div class="notification-title">Success</div>
                                <div class="notification-message">${data.message}</div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(successMsg);

                    // Animate notification in
                    setTimeout(() => successMsg.classList.add('show'), 100);
                    
                    // Remove notification after delay
                    setTimeout(() => {
                        successMsg.classList.remove('show');
                        setTimeout(() => successMsg.remove(), 300);
                    }, 3000);

                    // Reset form with smooth animation
                    form.classList.add('form-reset');
                    setTimeout(() => {
                        form.reset();
                        form.classList.remove('form-reset');
                    }, 300);
                } else {
                    throw new Error(data.message || 'Failed to record restock');
                }
                
                return false;
            } catch (error) {
                console.error('Error submitting form:', error);
                
                // Show error notification
                const errorMsg = document.createElement('div');
                errorMsg.className = 'notification-toast error';
                errorMsg.innerHTML = `
                    <div class="notification-content">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <div class="notification-title">Error</div>
                            <div class="notification-message">${error.message}</div>
                        </div>
                    </div>
                `;
                document.body.appendChild(errorMsg);

                // Animate notification in
                setTimeout(() => errorMsg.classList.add('show'), 100);
                
                // Remove notification after delay
                setTimeout(() => {
                    errorMsg.classList.remove('show');
                    setTimeout(() => errorMsg.remove(), 300);
                }, 3000);
                
                return false;
            }
        }
    </script>
    <style>
        /* Notification Toast Styles */
        .notification-toast {
            position: fixed;
            top: 20px;
            right: -300px; /* Start off-screen */
            width: 280px;
            padding: 15px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease-out;
            z-index: 1000;
        }

        [data-theme="dark"] .notification-toast {
            background: #2a2d3a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .notification-toast.show {
            transform: translateX(-320px);
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-content i {
            font-size: 20px;
        }

        .notification-toast.success i {
            color: #4CAF50;
        }

        .notification-toast.error i {
            color: #f44336;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: #1a1a1a;
        }

        [data-theme="dark"] .notification-title {
            color: #ffffff;
        }

        .notification-message {
            font-size: 0.9em;
            color: #666666;
        }

        [data-theme="dark"] .notification-message {
            color: #b0b0b0;
        }

        /* Form Reset Animation */
        .form-reset {
            opacity: 0.5;
            transform: scale(0.98);
            transition: all 0.3s ease;
        }

        /* Base Styles */
        body {
            overflow-x: hidden;
        }

        /* Critical Layout Fixes */
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
            overflow-x: hidden;
            box-sizing: border-box;
        }

        .content-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
        }

        /* Dashboard and Inventory Width Fixes */
        #dashboard-section,
        .inventory-section,
        .stats-container,
        .charts-container,
        .table-responsive,
        .product-inventory {
            width: 100% !important;
            max-width: none !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            box-sizing: border-box !important;
        }

        /* Stats Cards Grid */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            padding: 0;
        }

        /* Charts Container */
        .charts-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 0;
        }

        /* Inventory Table */
        .table-responsive {
            overflow-x: auto;
            margin: 0;
            padding: 0;
        }

        .main-content > .header-content,
        .main-content > h2 {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--bg-primary);
            margin: 0;
            padding: 1.5rem 2rem;
            width: 100%;
            box-sizing: border-box;
            border-bottom: 1px solid rgba(255, 127, 80, 0.1);
        }

        /* Product Inventory Search and Filters */
        .search-container,
        .filter-container {
            width: 100%;
            margin-bottom: 1rem;
        }

        /* Product Table */
        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        #landing-settings-section,
        #user-roles-section,
        #user-accounts-section,
        #orders-section,
        .section-container {
            margin-top: 2rem !important;
            padding: 2rem !important;
            width: 100% !important;
            max-width: 1600px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            box-sizing: border-box !important;
            position: relative !important;
        }

        .section-wrapper {
            padding: 2rem;
            width: 100%;
            box-sizing: border-box;
        }

        [data-theme="dark"] .main-content > .header-content,
        [data-theme="dark"] .main-content > h2 {
            background: #1a1c23;
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
    </style>
    <script src="js/products-menu.js"></script>
    <link rel="stylesheet" href="css/enhanced-notifications.css">
    <script>
        // Image Preview
        document.getElementById('product_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Category Modal
        function showAddCategoryModal() {
            // Create modal HTML
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Add New Category</h3>
                        <span class="close">&times;</span>
                    </div>
                    <form id="categoryForm" method="POST">
                        <div class="form-group">
                            <label for="category_name">Category Name</label>
                            <input type="text" id="category_name" name="category_name" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="add_category" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Add Category
                            </button>
                        </div>
                    </form>
                </div>
            `;

            // Add modal to body
            document.body.appendChild(modal);

            // Close modal functionality
            const closeBtn = modal.querySelector('.close');
            closeBtn.onclick = function() {
                modal.remove();
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.remove();
                }
            }
        }
    </script>
    <link rel="stylesheet" href="css/edit-form.css">
    <link rel="stylesheet" href="css/restock.css">
    <link rel="stylesheet" href="css/sidebar-enhanced.css">
    <link rel="stylesheet" href="css/user-roles.css">
    <link rel="stylesheet" href="css/products-menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Layout Structure */
        .admin-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
            position: relative;
            overflow-x: hidden;
        }

        .admin-sidebar {
            position: fixed;
            width: 240px;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
            background: #1a1c23;
        }

        /* Section and Container Fixes */
        #landing-settings-section,
        #user-roles-section,
        #user-accounts-section,
        #orders-section,
        .section-container {
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            overflow: visible;
            box-sizing: border-box;
        }

        /* Main Content Wrapper Fix */
        .main-content {
            flex: 1;
            margin-left: 240px;
            padding: 2rem;
            width: calc(100% - 240px);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Section and Container Styles */
        .menu-creation-container,
        .section-container {
            position: relative;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        [data-theme="dark"] .menu-creation-container,
        [data-theme="dark"] .section-container {
            background: #2a2d3a;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        /* Section Layout */
        #landing-settings-section,
        #user-roles-section,
        #user-accounts-section,
        #orders-section,
        #products-section {
            width: 100%;
            margin-bottom: 2rem;
        }

        /* Content Layout */
        .content-wrapper {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
            overflow: hidden;
        }

        /* Table Container */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 1rem 0;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] .table-responsive {
            background: #32364a;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Section Header */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] .section-header {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        /* Form Layout Styles */
        .form-container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .form-section {
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
        }

        [data-theme="dark"] .form-section {
            background: #2a2d3a;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        @media screen and (max-width: 1024px) {
            .form-row {
                flex-direction: column;
            }
        }

        .add-product-layout {
            width: 100%;
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .add-product-form {
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-row-landscape {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
            width: 100%;
        }

        .input-group {
            flex: 1;
            min-width: 0;
        }

        .input-group.full-width {
            flex: 1;
            width: 100%;
        }

        .upload-section {
            margin-top: 1.5rem;
        }

        [data-theme="dark"] .add-product-form {
            background: #2a2d3a;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .form-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-header h2 {
            color: #FF7F50;
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }

        .form-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 1.5rem;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        [data-theme="dark"] .form-group label {
            color: #ffffff;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group select {
            background: #32364a;
            border-color: #454b60;
            color: #ffffff;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FF7F50;
            box-shadow: 0 0 0 3px rgba(255, 127, 80, 0.2);
        }

        .category-select-wrapper {
            display: flex;
            gap: 0.5rem;
        }

        .add-category-btn {
            background: #FF7F50;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-category-btn:hover {
            background: #ff6b3d;
            transform: translateY(-2px);
        }

        .file-input-container {
            border: 2px dashed #ddd;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .file-input-container {
            border-color: #454b60;
        }

        .file-input-container:hover {
            border-color: #FF7F50;
        }

        .file-input-ui {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .file-input-ui i {
            font-size: 2rem;
            color: #FF7F50;
        }

        .image-preview {
            margin-top: 1rem;
            max-width: 200px;
            margin: 1rem auto 0;
        }

        .image-preview img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn-primary {
            background: #FF7F50;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #ff6b3d;
            transform: translateY(-2px);
        }

        /* Product Cards Grid */
        .product-cards-container {
            margin-top: 3rem;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .product-card {
            background: #2a2d3a;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-details {
            padding: 1.5rem;
        }

        .product-details h3 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 1.2rem;
            font-weight: 600;
        }

        [data-theme="dark"] .product-details h3 {
            color: #ffffff;
        }

        .product-details p {
            margin: 0.25rem 0;
            color: #666;
            font-size: 0.9rem;
        }

        [data-theme="dark"] .product-details p {
            color: #b0b0b0;
        }

        .category {
            color: #FF7F50 !important;
            font-weight: 500;
        }

        .markup {
            font-weight: 600;
        }


        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }

        .verification-modal {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 25px;
            width: 90%;
            max-width: 800px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }

        [data-theme="dark"] .verification-modal {
            background-color: #2a2d3a;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        [data-theme="dark"] .modal-header {
            border-bottom-color: rgba(255,255,255,0.1);
        }

        .modal-header h3 {
            color: #1a1a1a;
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        [data-theme="dark"] .modal-header h3 {
            color: #ffffff;
        }

        .modal-header h3 i {
            color: #FF7F50;
        }

        .modal .close {
            color: #666;
            font-size: 24px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        [data-theme="dark"] .modal .close {
            color: #ffffff;
        }

        .modal .close:hover {
            background-color: rgba(255,127,80,0.1);
            color: #FF7F50;
        }

        .verification-list {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .verification-list::-webkit-scrollbar {
            width: 8px;
        }

        .verification-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .verification-list::-webkit-scrollbar-thumb {
            background: #FF7F50;
            border-radius: 4px;
        }

        [data-theme="dark"] .verification-list::-webkit-scrollbar-track {
            background: #32364a;
        }

        [data-theme="dark"] .verification-list::-webkit-scrollbar-thumb {
            background: #FF7F50;
        }

        /* User Accounts Table Styles */
        .table-responsive {
            overflow-x: auto;
            max-width: 100%;
            margin: 1rem 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .user-accounts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            min-width: 800px; /* Ensures table doesn't get too squished */
        }
        
        .user-accounts-table th {
            background-color: #f8f9fa;
            color: #1a1a1a;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }
        
        .user-accounts-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
        }
        
        .user-accounts-table tr:hover {
            background-color: #f9fafb;
        }

        /* Wrapper to prevent horizontal overflow */
        .content-wrapper {
            max-width: 100%;
            overflow-x: hidden;
            padding: 0 1rem;
        }
        
        [data-theme="dark"] .user-accounts-table {
            background: #2a2d3a;
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-theme="dark"] .user-accounts-table th {
            background-color: #32364a;
            color: #ffffff;
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-theme="dark"] .user-accounts-table td {
            color: rgba(255, 255, 255, 0.8);
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-theme="dark"] .user-accounts-table tr:hover {
            background-color: #32364a;
        }

        /* Role Badge Styles */
        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .role-badge.admin {
            background-color: #FFF3E0;
            color: #FF7F50;
        }

        .role-badge.customer {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        [data-theme="dark"] .role-badge.admin {
            background-color: rgba(255, 127, 80, 0.2);
            color: #FF9F50;
        }

        [data-theme="dark"] .role-badge.customer {
            background-color: rgba(46, 125, 50, 0.2);
            color: #4CAF50;
        }

        /* Consistent Header Layout */
        #inventory-section .header-content,
        #orders-section .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 24px;
        }

        /* Header Icons Layout */
        .header-icons {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-right: 8px;
        }
        
        .theme-toggle, .notification-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            margin: 0;
        }

        .theme-toggle i, .notification-icon i {
            font-size: 20px;
            color: #FF7F50;
        }

        .profile-section {
            margin-left: 8px;
        }

        /* Role Card Styles */
        .role-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        [data-theme='dark'] .role-card {
            background: #2a2d3a;
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(255, 127, 80, 0.1);
        }

        .role-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: rgba(255, 127, 80, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .role-icon i {
            font-size: 24px;
            color: #FF7F50;
        }

        .role-title {
            font-size: 28px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
            letter-spacing: 0.2px;
        }

        [data-theme='dark'] .role-title {
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        h3.role-title {
            color: #1a1a1a;
        }

        [data-theme='dark'] h3.role-title {
            color: #ffffff !important;
        }

        .role-description {
            color: #6B7280;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        [data-theme='dark'] .role-description {
            color: rgba(255, 255, 255, 0.7);
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0 0 25px 0;
            text-align: left;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            color: #4B5563;
            font-size: 14px;
        }

        [data-theme='dark'] .feature-item {
            color: rgba(255, 255, 255, 0.8);
        }

        .feature-item i {
            color: #10B981;
            margin-right: 10px;
            font-size: 16px;
        }

        .create-button {
            background: #FF7F50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            width: fit-content;
            margin: 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .create-button:hover {
            background: #ff6b3d;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 127, 80, 0.2);
        }

        [data-theme='dark'] .create-button {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Enhanced Inventory Table Styles */
        .inventory-table-container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin: 1rem 0;
            overflow: hidden;
        }

        .inventory-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.95rem;
        }

        .inventory-table th {
            background: #f8f9fa;
            color: #2d3748;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .inventory-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
            vertical-align: middle;
        }
        
        .inventory-table td:nth-child(5) { /* UOM column */
            font-size: 0.9rem;
        }

        .inventory-table tbody tr:hover {
            background-color: #f7fafc;
        }

        /* Filter Styles */
        .filter-container {
            margin: 1rem 0;
        }

        .filter-select {
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            min-width: 200px;
            background-color: #ffffff;
            color: #2d3748;
            font-size: 0.9rem;
        }

        [data-theme="dark"] .filter-select {
            background-color: #2a2d3a;
            border-color: #4a5568;
            color: #e2e8f0;
        }

        .filter-select:focus {
            outline: none;
            border-color: #FF7F50;
            box-shadow: 0 0 0 2px rgba(255, 127, 80, 0.2);
        }

        .inventory-table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-name {
            font-weight: 600;
            color: #2d3748;
        }

        .stock-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .stock-badge.sufficient {
            background-color: #c6f6d5;
            color: #2f855a;
        }

        .stock-badge.low {
            background-color: #feebc8;
            color: #c05621;
        }

        .stock-badge.critical {
            background-color: #fed7d7;
            color: #c53030;
        }



        .uom-badge {
            background: #e2e8f0;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #4a5568;
        }

        /* Dark Mode Inventory Styles */
        [data-theme="dark"] .inventory-table-container {
            background: #1a1c23;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            border: 1px solid #2d3748;
        }

        [data-theme="dark"] .inventory-table th {
            background: #2d3748;
            color: #e2e8f0;
            border-bottom-color: #4a5568;
        }

        [data-theme="dark"] .inventory-table td {
            border-bottom-color: #4a5568;
            color: #e2e8f0;
        }

        [data-theme="dark"] .inventory-table tbody tr:hover {
            background-color: #2d3748;
        }

        [data-theme="dark"] .product-name {
            color: #e2e8f0;
        }

        [data-theme="dark"] .uom-badge {
            background: #4a5568;
            color: #e2e8f0;
        }

        [data-theme="dark"] table {
            background: #1a1c23;
            color: #e2e8f0;
        }

        [data-theme="dark"] th {
            background-color: #2d3748 !important;
            color: #e2e8f0;
            border-bottom: 1px solid #4a5568;
        }

        [data-theme="dark"] td {
            border-color: #2d3748;
            color: #e2e8f0;
        }

        [data-theme="dark"] tr:hover {
            background-color: #2d3748 !important;
        }

        [data-theme="dark"] .filter-select,
        [data-theme="dark"] #inventorySearch {
            background-color: #1a1c23;
            border: 1px solid #2d3748;
            color: #e2e8f0;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
        }

        [data-theme="dark"] .filter-select option {
            background-color: #1a1c23;
            color: #e2e8f0;
        }

        [data-theme="dark"] #inventorySearch::placeholder {
            color: #718096;
        }

        [data-theme="dark"] .status-badge.Sufficient {
            background-color: #2f855a;
            color: #e2e8f0;
        }

        [data-theme="dark"] .status-badge.Critical {
            background-color: #c53030;
            color: #e2e8f0;
        }

        [data-theme="dark"] .status-badge.Out {
            background-color: #2d3748;
            color: #e2e8f0;
        }

        [data-theme="dark"] .product-actions button {
            background-color: #2d3748;
            border: 1px solid #4a5568;
            color: #e2e8f0;
        }

        [data-theme="dark"] .product-actions button:hover {
            background-color: #4a5568;
        }
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px;
            max-width: 100%;
            margin: 0 auto;
            overflow: hidden;
        }

        @media screen and (max-width: 1200px) {
            .roles-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media screen and (max-width: 768px) {
            .roles-grid {
                grid-template-columns: 1fr;
            }
        }

        .feature-check {
            color: #10B981;
            margin-right: 8px;
        }

        [data-theme='dark'] .feature-check {
            color: #34D399;
        }

        /* Reset and Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
        }

        [data-theme="dark"] body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        /* Layout Structure */
        .admin-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: #1a1c23;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            overflow-y: auto;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 240px;
            padding: 2rem;
            width: calc(100% - 240px);
            min-height: 100vh;
            background: #f8f9fa;
            position: relative;
        }

        [data-theme="dark"] .main-content {
            background: var(--bg-primary);
        }

        /* Section Container */
        .section-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1rem;
            width: 100%;
        }

        /* Content Grid Layout */
        .content-grid {
            display: grid;
            gap: 2rem;
            width: 100%;
            max-width: 100%;
        }

        /* Main Content Container Styles */
        .main-content {
            position: relative;
            padding: 2rem;
            margin-left: 240px; /* Sidebar width */
            width: calc(100% - 240px);
            box-sizing: border-box;
            min-height: 100vh;
        }

        /* Header Spacing Fix */
        .main-content > h2,
        .main-content > .header-content {
            margin-bottom: 2rem;
            padding: 1rem 2rem;
            background: var(--bg-primary);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Section Container Styles */
        #landing-settings-section,
        #user-roles-section,
        #user-accounts-section,
        #orders-section {
            position: relative;
            width: 100%;
            max-width: 1600px; /* Maximum width for large screens */
            padding: 2rem;
            margin: 2rem auto 0; /* Added top margin */
            box-sizing: border-box;
        }

        /* Wrapper for all sections */
        .section-wrapper {
            position: relative;
            width: 100%;
            padding: 0 1rem;
            box-sizing: border-box;
        }

        .section-container {
            position: relative;
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
        }

        /* Card Container for Landing Settings and Hero Section */
        .branding-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            width: 100%;
            box-sizing: border-box;
        }

        .branding-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
        }

        /* Image upload containers */
        .image-upload-container {
            width: 100%;
            max-width: 100%;
            margin: 1rem 0;
            box-sizing: border-box;
        }

        .drop-zone {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        [data-theme="dark"] .section-container,
        [data-theme="dark"] .branding-card {
            background: #2a2d3a;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        /* Responsive adjustments */
        @media screen and (max-width: 1400px) {
            .main-content {
                padding: 1.5rem;
            }
            .section-container {
                padding: 1.5rem;
            }
        }

        @media screen and (max-width: 1024px) {
            .branding-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Sidebar and Menu Item Styles */
        .menu-item {
            position: relative;
            transition: all 0.3s ease;
            width: 100%;
        }

        .menu-item.active {
            background: rgba(255, 127, 80, 0.1);
        }

        .menu-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #FF7F50;
        }

        /* Submenu Styles */
        .submenu {
            padding-left: 2.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .menu-item.expanded .submenu {
            max-height: 500px;
            transition: max-height 0.3s ease-in;
        }

        .submenu-item {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .submenu-item:hover {
            color: #FF7F50;
            background: rgba(255, 127, 80, 0.1);
        }

        .submenu-item.active {
            color: #FF7F50;
            background: rgba(255, 127, 80, 0.15);
        }

        /* Menu Toggle Button */
        .menu-toggle {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            color: rgba(255, 255, 255, 0.8);
            background: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            background: rgba(255, 127, 80, 0.1);
            color: #FF7F50;
        }

        .menu-toggle i {
            transition: transform 0.3s ease;
        }

        .menu-item.expanded .menu-toggle i {
            transform: rotate(180deg);
        }

        .menu-item.active a {
            color: #FF7F50;
        }

        .menu-item:hover {
            background: rgba(255, 127, 80, 0.05);
        }

        /* Sidebar group header */
        .menu-header {
            font-size: 12px;
            color: #9CA3AF;
            text-transform: uppercase;
            padding: 10px 16px;
            font-weight: 600;
            letter-spacing: 0.6px;
        }

        .menu-separator {
            height: 1px;
            margin: 10px 0;
            background: rgba(255,255,255,0.04);
        }

        /* Ensure logout is visually separated and pinned near bottom if layout supports it */
        .logout-item {
            margin-top: 12px;
        }

        /* Global Font Styles */
        * {
            font-family: 'Inter', sans-serif;
        }



        /* Dashboard Styles */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 4px rgba(255, 127, 80, 0.1);
            transition: transform 0.2s;
            border: 1px solid rgba(255, 127, 80, 0.1);
            position: relative;
            overflow: hidden;
        }

        [data-theme="dark"] .stat-card {
            background: #2a2d3a;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FFB75E, #FF7F50);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 127, 80, 0.15);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: rgba(255, 127, 80, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FF7F50;
            font-size: 1.5rem;
        }

        .stat-info {
            flex: 1;
        }

        .stat-info h3 {
            font-size: 1.5rem;
            color: #FF6B3D;
            margin: 0 0 5px 0;
            font-weight: 700;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }

        [data-theme="dark"] .stat-info h3 {
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        [data-theme="dark"] .stat-info p {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .stat-info p {
            color: #4a4a4a;
            margin: 0;
            font-size: 0.95rem;
            font-weight: 500;
        }
        [data-theme="dark"] .stat-info p {
            color: rgba(255, 255, 255, 0.9);
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            padding: 4px 8px;
            border-radius: 15px;
        }

        .stat-trend.positive {
            background: #e8f5e9;
            color: #2e7d32;
            font-weight: 600;
            padding: 6px 12px;
        }

        [data-theme="dark"] .stat-trend.positive {
            background: rgba(46, 125, 50, 0.2);
            color: #4CAF50;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .stat-trend.negative {
            background: #ffebee;
            color: #c62828;
        }

        .charts-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: var(--bg-primary);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .chart-card {
            background: #262833;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FFB75E, #FF7F50);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            font-size: 1.25rem;
            color: #1a1a1a;
            margin: 0;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        [data-theme="dark"] .chart-header h3 {
            color: #ffffff;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
        }

        .chart-period {
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 5px;
            background: #32364a;
            color: rgba(255, 255, 255, 0.9);
        }

        .chart-legend {
            display: flex;
            gap: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.95rem;
            color: #4a4a4a;
            font-weight: 500;
        }
        [data-theme="dark"] .legend-item {
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme="dark"] .legend-item {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        [data-theme="dark"] .chart-header h3 {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 500;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .legend-color.completed {
            background: #4CAF50;
        }

        .legend-color.pending {
            background: #FFC107;
        }

        .completion-chart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            height: 100%;
        }

        .pie-chart-container {
            width: 200px;
            height: 200px;
            position: relative;
            margin: 20px auto;
        }

        .pie-chart {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            box-shadow: 0 0 20px rgba(255, 127, 80, 0.15);
        }

        .pie-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-primary);
            border-radius: 50%;
            width: 80%;
            height: 80%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .completion-percentage {
            font-size: 2.5rem;
            font-weight: 600;
            color: #FF7F50;
            line-height: 1;
            margin-bottom: 5px;
        }

        .completion-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-align: center;
            opacity: 0.8;
        }

        [data-theme="dark"] .pie-center {
            background: #1a1c23;
        }

        [data-theme="dark"] .completion-label {
            color: rgba(255, 255, 255, 0.7);
        }

        .progress-circle svg {
            width: 160px;
            height: 160px;
            transform: rotate(-90deg);
        }

        .progress-circle circle {
            fill: none;
            stroke-width: 8;
        }

        .progress-background {
            stroke: #e5e7eb;
        }
        [data-theme="dark"] .progress-background {
            stroke: #32364a;
        }

        .progress-bar {
            stroke: #FF7F50;
            stroke-dasharray: 440;
            stroke-dashoffset: 66; /* 440 * (1 - progress%) */
            stroke-width: 10;
            transition: stroke-dashoffset 0.5s ease;
        }

        [data-theme="dark"] .progress-bar {
            stroke: #FF7F50;
            filter: drop-shadow(0 0 4px rgba(255, 127, 80, 0.3));
        }

        .progress-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            background: transparent;
        }
        .progress-content h4 {
            color: #FF7F50;
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }
        .progress-content p {
            color: #6B7280;
            margin: 5px 0 0;
            font-size: 0.9rem;
        }
        [data-theme="dark"] .progress-content p {
            color: rgba(255, 255, 255, 0.7);
        }

        .progress-content h4 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        [data-theme="dark"] .progress-content h4 {
            color: rgba(255, 255, 255, 0.95) !important;
        }

        [data-theme="dark"] .progress-content p {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        [data-theme="dark"] .completion-rate {
            color: #FF7F50 !important;
            font-weight: 600;
            font-size: 2rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .progress-content p {
            margin: 5px 0 0;
            font-size: 0.9rem;
            color: #666;
        }

        .recent-orders {
            background: var(--bg-primary);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .recent-orders {
            background: #262833;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .recent-orders .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .recent-orders h3 {
            font-size: 1.2rem;
            color: var(--text-primary);
            margin: 0;
        }

        .view-all {
            color: #FF7F50;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .orders-section-container {
            max-width: 100%;
            overflow-x: hidden;
            padding: 1rem;
        }

        .dashboard-orders-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--bg-primary);
            border-radius: 10px;
            overflow: hidden;
            min-width: 800px; /* Prevents table from becoming too narrow */
        }

        .orders-table-wrapper {
            max-width: 100%;
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 1rem 0;
        }

        .dashboard-orders-table th,
        .dashboard-orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        [data-theme="dark"] .dashboard-orders-table {
            background-color: #262833;
        }

        [data-theme="dark"] .dashboard-orders-table tr:nth-child(even) {
            background-color: #2a2d3a;
        }

        [data-theme="dark"] .dashboard-orders-table tr:hover {
            background-color: #32364a;
            transition: background-color 0.2s ease;
        }

        [data-theme="dark"] .dashboard-orders-table th {
            background-color: #32364a;
            color: rgba(255, 255, 255, 1) !important;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            border-bottom: 2px solid rgba(255, 127, 80, 0.3);
            letter-spacing: 0.5px;
        }

        [data-theme="dark"] .dashboard-orders-table td {
            color: rgba(255, 255, 255, 0.7);
        }

        .dashboard-orders-table th {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #f8f9fa;
        }

        .dashboard-orders-table td {
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .dashboard-orders-table .status-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .dashboard-orders-table .status-badge.pending {
            background: rgba(255, 183, 94, 0.15);
            color: #FF9F50;
            font-weight: 600;
        }

        [data-theme="dark"] .dashboard-orders-table .status-badge.pending {
            background: rgba(255, 183, 94, 0.2);
            color: #FFB75E;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .dashboard-orders-table .status-badge.completed {
            background: rgba(255, 127, 80, 0.15);
            color: #FF7F50;
            font-weight: 600;
        }

        [data-theme="dark"] .dashboard-orders-table .status-badge.completed {
            background: rgba(255, 127, 80, 0.2);
            color: #FFB75E;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .dashboard-orders-table .status-badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* Restocking Styles */
        .restocking-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .restock-modal-content {
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] .restock-modal-content {
            background: #2a2d3a;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .restock-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eef0f5;
        }

        .restock-header h2 {
            color: #FF7F50;
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            color: #666;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: rgba(255, 127, 80, 0.1);
            color: #FF7F50;
        }

        .restock-form .form-row {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .restock-form .form-group {
            flex: 1;
        }

        .restock-form label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        [data-theme="dark"] .restock-form label {
            color: #ffffff;
        }

        .restock-form input,
        .restock-form select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .restock-form input,
        [data-theme="dark"] .restock-form select {
            background: #32364a;
            border-color: #454b60;
            color: #ffffff;
        }

        .stock-info {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        [data-theme="dark"] .stock-info {
            color: #b0b0b0;
        }

        .uom-display {
            margin-left: 0.5rem;
            color: #666;
        }

        .expiration-warning {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .expiration-status {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .expiration-status.good {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .expiration-status.warning {
            background: #fff3e0;
            color: #ef6c00;
        }

        .restock-records {
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] .restock-records {
            background: #2a2d3a;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .records-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .records-header h3 {
            color: #333;
            font-size: 1.4rem;
            margin: 0;
        }

        [data-theme="dark"] .records-header h3 {
            color: #ffffff;
        }

        .restock-table {
            width: 100%;
            border-collapse: collapse;
        }

        .restock-table th,
        .restock-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eef0f5;
        }

        .restock-table th {
            font-weight: 600;
            color: #333;
            background: #f8f9fa;
        }

        [data-theme="dark"] .restock-table th {
            background: #32364a;
            color: #ffffff;
            border-bottom-color: #454b60;
        }

        [data-theme="dark"] .restock-table td {
            border-bottom-color: #454b60;
            color: #ffffff;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-badge.status-pending {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-badge.status-completed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease-out;
            max-width: 350px;
        }

        .notification.success {
            background-color: #4CAF50;
            color: white;
        }

        .notification.error {
            background-color: #f44336;
            color: white;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification-icon {
            font-size: 20px;
        }

        .notification-message {
            flex-grow: 1;
            font-size: 14px;
        }

        .notification-close {
            background: none;
            border: none;
            color: currentColor;
            padding: 0;
            cursor: pointer;
            font-size: 20px;
            opacity: 0.8;
        }

        .notification-close:hover {
            opacity: 1;
        }

        @media (max-width: 1200px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
        }

        /* Enhanced Dashboard Styles */
        .stat-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 127, 80, 0.1);
        }

        .stat-icon {
            position: relative;
            overflow: hidden;
        }

        .stat-icon::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, rgba(255,255,255,0.2), transparent);
            transform: rotate(45deg);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        .chart-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 127, 80, 0.1);
            transition: all 0.3s ease;
            background: #ffffff;
            padding: 20px;
        }

        [data-theme="dark"] .chart-card {
            background: #2a2d3a;
        }

        .chart-content {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .revenue-chart .chart-content {
            background-color: #ffffff;
            border-radius: 8px;
        }

        [data-theme="dark"] .revenue-chart .chart-content {
            background-color: #2a2d3a;
        }

        .chart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(255, 127, 80, 0.1);
        }

        .chart-period {
            position: relative;
            background: linear-gradient(45deg, #FFB75E, #FF7F50);
            color: white;
            border: none;
            padding: 8px 30px 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
        }

        .chart-period::after {
            content: '▼';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
        }

        .legend-item {
            position: relative;
            padding-left: 20px;
        }

        .legend-color {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
        }

        .legend-color.completed {
            background: #FFB75E;
        }

        .legend-color.pending {
            background: #FF7F50;
        }

        .dashboard-orders-table tbody tr {
            transition: all 0.3s ease;
        }

        .dashboard-orders-table tbody tr:hover {
            background-color: rgba(255, 183, 94, 0.05);
        }

        .status-badge {
            position: relative;
            overflow: hidden;
        }

        .status-badge::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, rgba(255,255,255,0.2), transparent);
            transform: rotate(45deg);
            animation: shimmer 2s infinite;
        }

        /* All stat icons now use the same background color */
        .stats-container .stat-card .stat-icon { background: rgba(255, 127, 80, 0.15); }

        .recent-orders {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 127, 80, 0.1);
        }

        .recent-orders::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FFB75E, #FF7F50);
        }

        .view-all {
            position: relative;
            padding: 5px 15px;
            border-radius: 15px;
            background: linear-gradient(45deg, #FFB75E, #FF7F50);
            color: white !important;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .view-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(255, 127, 80, 0.2);
        }

        /* Ensure consistent search bar across all sections */
        .inventory-section .search-container,
        .orders-section .search-container {
            width: 250px !important;
            flex-shrink: 0;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .chart-card {
                margin-bottom: 20px;
            }
            
            .recent-orders {
                overflow-x: auto;
            }
        }

        /* Section Description Style */
        .section-description {
            font-size: 14px;
            color: #666;
            font-weight: 400;
            margin-top: 0;
            font-family: 'Inter', sans-serif;
        }

        /* Header and Profile Styles */
        [data-theme="dark"] .admin-panel-title {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        [data-theme="dark"] .dashboard-title {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
        }

        .main-header {
            background: var(--bg-primary);
            padding: 0 24px;
            box-shadow: none;
            position: relative;
            height: 48px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            width: 100%;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .theme-toggle, 
        .notification-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .theme-toggle i, 
        .notification-icon i {
            font-size: 20px;
            color: #FF7F50;
        }

        .profile-section {
            margin-left: 8px;
            display: flex;
            align-items: center;
        }

        [data-theme="dark"] .main-header {
            background: #1a1c23;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-content {
            display: flex;
            align-items: center;
            width: 100%;
            margin: 0 auto;
            padding: 0 16px;
            position: relative;
            gap: 16px;
        }

        .header-content > div {
            display: flex;
            align-items: center;
        }

        .search-container {
            flex: 1;
            margin: 0 24px;
        }
        
        .header-content > div:last-child {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .theme-toggle,
        .notification-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
        }
        
        .profile-section {
            margin-left: 8px;
        }
        
        .header-left {
            width: 200px;
            flex-shrink: 0;
        }

        .header-actions {
            width: 150px;
            flex-shrink: 0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
        }

        .header-content h1 {
            font-size: 15px;
            color: #1a1a1a;
            font-weight: 500;
            margin: 0;
            min-width: 100px;
        }

        .search-container {
            position: relative;
            width: 750px !important;
            flex-shrink: 0;
            margin: 0;
            margin-left: 24px;
        }

        .search-container input {
            width: 100%;
            padding: 6px 12px 6px 32px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 13px;
            color: #666;
            background: #f9fafb;
            transition: all 0.2s ease;
        }

        .search-container input:focus {
            background: #ffffff;
            border-color: #94a3b8;
            outline: none;
            box-shadow: none;
        }

        .search-container input:hover {
            border-color: #d1d5db;
        }

        .profile-info:hover {
            background: #f8fafc;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 14px;
            z-index: 1;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title i {
            font-size: 20px;
            color: #FF7F50;
            background: rgba(255, 127, 80, 0.1);
            padding: 8px;
            border-radius: 8px;
        }

        .header-content h1 {
            font-size: 20px;
            color: #000000 !important;
            font-weight: 600;
            margin: 0;
            font-family: 'Inter', sans-serif;
            letter-spacing: -0.5px;
        }

        [data-theme="dark"] .header-content h1 {
            color: #FFFFFF !important;
        }

        .enhanced-search {
            width: 750px !important;
            max-width: 750px !important;
            padding: 6px 12px 6px 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            font-size: 13px;
            color: var(--text-primary);
            background: rgba(45, 48, 58, 0.8);
            transition: all 0.3s ease;

        }

        [data-theme="dark"] .enhanced-search {
            background: #2d303a !important;
            color: rgba(255, 255, 255, 0.9) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
            transition: all 0.3s ease !important;
            box-shadow: 0 2px 8px rgba(255, 127, 80, 0.08) !important;
            margin-right: 300px !important;
        }

        .enhanced-search:focus {
            background: #ffffff !important;
            border-color: rgba(255, 127, 80, 0.3) !important;
            box-shadow: 0 4px 12px rgba(255, 127, 80, 0.12) !important;
        }

        .enhanced-search:hover {
            box-shadow: 0 0 0 2px rgba(255, 127, 80, 0.1);
            background: rgba(255, 127, 80, 0.05) !important;

            background: #ffffff !important;
            box-shadow: 0 4px 12px rgba(255, 127, 80, 0.15) !important;
        }

        .profile-section {
            display: flex;
            align-items: center;
            padding: 4px;
            margin-left: auto;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px 4px 4px;
            border-radius: 24px;
            transition: all 0.2s ease;
            background-color: var(--bg-tertiary);
        }

        [data-theme="dark"] .profile-info {
            background-color: #2d303a;
        }

        [data-theme="dark"] .admin-name {
            color: rgba(255, 255, 255, 0.9);
        }

        .profile-info:hover {
            background: rgba(255, 127, 80, 0.1);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(255, 127, 80, 0.1);
        }

        .profile-pic {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            transition: transform 0.2s ease;
        }

        .profile-info:hover {
            background-color: #ffe4d9;
        }
        
        .admin-name {
            transition: color 0.2s ease;
        }
        
        [data-theme="dark"] .profile-info:hover {
            background-color: rgba(255, 127, 80, 0.2);
        }

        .admin-name {
            font-size: 14px;
            color: #1a1a1a;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            margin-left: 4px;
            white-space: nowrap;
            letter-spacing: 0.2px;
        }

        [data-theme="dark"] .admin-name {
            color: #ffffff !important;
        }

        [data-theme="dark"] .profile-info:hover .admin-name {
            color: #FF7F50 !important;
        }
        [data-theme="dark"] .admin-name {
            color: #ffffff;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
        }

        /* Section Styles */
        .section-header {
            margin-bottom: 30px;
        }

        .section-header h2 {
            font-size: 24px;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
            font-family: 'Inter', sans-serif;
        }

        .section-description {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0;
        }

        .content-section {
            display: none;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 25px;
            margin: 20px;
        }

        [data-theme="dark"] .content-section {
            background: #2a2d3a;
        }
        .content-section.active {
            display: block;
        }
        #notificationContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .notification {
            background: #2a2d3a;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 10px;
            padding: 16px;
            width: 300px;
            display: flex;
            align-items: flex-start;
            animation: slideIn 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification.success {
            border-left: 4px solid #4CAF50;
            background-color: #f8fdf8;
        }

        .notification.error {
            border-left: 4px solid #f44336;
            background-color: #fef8f8;
        }

        .notification-icon {
            margin-right: 12px;
            font-size: 20px;
        }

        .notification.success .notification-icon {
            color: #4CAF50;
        }

        .notification.error .notification-icon {
            color: #f44336;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: #333;
        }

        .notification-message {
            color: #666;
            font-size: 14px;
        }

        .notification-close {
            background: transparent;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            position: absolute;
            right: 12px;
            top: 12px;
        }

        .notification-close:hover {
            color: #666;
        }

        .notification-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: #eee;
        }

        .notification-progress::after {
            content: '';
            position: absolute;
            left: 0;
            width: 100%;
            height: 100%;
            background: #ddd;
            animation: progress 3s linear;
        }

        /* Theme Toggle and Notification Styles */
        .theme-toggle, .notification-icon {
            padding: 6px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Notification Styles */
        #notificationDropdown {
            background: var(--bg-tertiary);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .notification-text {
            color: var(--text-primary);
            padding: 15px;
            text-align: center;
            font-size: 14px;
        }

        [data-theme='dark'] .notification-text {
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme='light'] .notification-text {
            color: #333;
        }

        /* Theme Toggle Styles */
        .theme-toggle {
            cursor: pointer;
            transition: all 0.3s ease;
            background: none;
            border: none;
            padding: 0;
            margin-left: 15px;
            margin-right: 15px;
            line-height: 1;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        .theme-toggle i {
            font-size: 20px;
            color: #FF7F50;
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(255, 127, 80, 0.2));
            display: inline-block;
            line-height: 1;
        }

        [data-theme="dark"] .theme-toggle i {
            color: #FF7F50;
        }

        .theme-toggle:active {
            transform: scale(0.95);
        }

        @keyframes progress {
            from { width: 100%; }
            to { width: 0%; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <script src="js/enhanced-notifications.js"></script>
    <script src="js/verification-handler-final2.js"></script>
    <div id="notificationContainer"></div>
    
    <!-- Verification Modal -->
    <div id="verificationModal" class="modal">
        <div class="modal-content verification-modal">
            <div class="modal-header">
                <h3><i class="fas fa-id-card"></i> ID Verification Requests</h3>
                <span class="close" onclick="closeVerificationModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="verification-list" id="verificationList">
                    <!-- Verification requests will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Out of Stock Top Notification -->
    <div id="outOfStockAlert" class="alert-modal">
        <div class="alert-content">
            <div class="alert-header">
                <i class="fas fa-exclamation-circle"></i>
                <span>Out of Stock Items Alert</span>
            </div>
            <div id="outOfStockList">
                <!-- Items will be inserted here dynamically -->
            </div>
        </div>
        <button onclick="closeOutOfStockModal()" class="alert-button">
            Acknowledge
        </button>
    </div>

    <!-- Critical Stock Alert -->
    <div id="criticalStockAlert" class="alert-modal">
        <div class="alert-content">
            <div class="alert-header">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Critical Stock Items Alert</span>
            </div>
            <div id="criticalStockList">
                <!-- Items will be inserted here dynamically -->
            </div>
        </div>
        <button onclick="closeCriticalStockModal()" class="alert-button">
            Acknowledge
        </button>
    </div>

    <style>
        .alert-modal {
            display: none;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 400px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        [data-theme="dark"] .alert-modal {
            background: #2a2d3a;
            border-color: rgba(255,255,255,0.1);
        }

        .alert-content {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        [data-theme="dark"] .alert-content {
            border-color: rgba(255,255,255,0.1);
        }

        .alert-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            gap: 8px;
        }

        .alert-header i {
            color: #FF7F50;
        }

        .alert-header span {
            font-weight: 600;
            color: #1a1a1a;
        }

        [data-theme="dark"] .alert-header span {
            color: rgba(255,255,255,0.9);
        }

        #outOfStockList {
            max-height: 150px;
            overflow-y: auto;
            margin-top: 10px;
            color: #4a5568;
        }

        [data-theme="dark"] #outOfStockList {
            color: rgba(255,255,255,0.7);
        }

        .alert-button {
            width: 100%;
            border: none;
            padding: 8px;
            background: #FF7F50;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .alert-button:hover {
            background: #ff6b3d;
        }

        /* Success notification styling */
        .notification.success {
            background-color: #ffffff;
            border-left: 4px solid #4CAF50;
        }

        [data-theme="dark"] .notification.success {
            background-color: #2a2d3a;
            border-left: 4px solid #4CAF50;
        }

        .notification-title {
            color: #1a1a1a;
        }

        [data-theme="dark"] .notification-title {
            color: rgba(255,255,255,0.9);
        }

        .notification-message {
            color: #4a5568;
        }

        [data-theme="dark"] .notification-message {
            color: rgba(255,255,255,0.7);
        }
    </style>

    <!-- Verification Modal -->
    <div id="verificationModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; width: 90%; max-width: 800px; border-radius: 8px;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3><i class="fas fa-id-card"></i> ID Verification Requests</h3>
                <span class="close" onclick="closeVerificationModal()" style="cursor: pointer; font-size: 24px;">&times;</span>
            </div>
            <div class="modal-body">
                <div id="verificationList" style="max-height: 600px; overflow-y: auto;">
                    <!-- Verification requests will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
    function showVerificationModal() {
        const modal = document.getElementById('verificationModal');
        modal.style.display = 'block';
        loadVerificationRequests();
    }

    function closeVerificationModal() {
        const modal = document.getElementById('verificationModal');
        modal.style.display = 'none';
    }

    function loadVerificationRequests() {
        fetch('get_verification_requests.php')
            .then(response => response.json())
            .then(data => {
                const verificationList = document.getElementById('verificationList');
                if (!data || data.length === 0) {
                    verificationList.innerHTML = '<div style="text-align: center; padding: 20px;">No pending verification requests</div>';
                    return;
                }

                verificationList.innerHTML = data.map(request => `
                    <div class="verification-item" data-user-id="${request.user_id}" style="padding: 15px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px;">
                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <img src="${request.profile_picture || '../images/user.png'}" style="width: 50px; height: 50px; border-radius: 50%;">
                            <div>
                                <h4 style="margin: 0;">${request.first_name} ${request.last_name}</h4>
                                <p style="margin: 5px 0; color: #666;">${new Date(request.verification_date).toLocaleString()}</p>
                            </div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <img src="../uploaded_img/${request.id_document}" style="max-width: 100%; border-radius: 4px;">
                        </div>
                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button onclick="handleVerification(${request.user_id}, 'approved')" style="background: #4CAF50; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Approve</button>
                            <button onclick="handleVerification(${request.user_id}, 'rejected')" style="background: #f44336; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Reject</button>
                        </div>
                    </div>
                `).join('');

                // Update pending count
                const pendingCount = document.querySelector('.pending-count');
                if (pendingCount) {
                    pendingCount.textContent = data.length;
                }
            });
    }

    function handleVerification(userId, status) {
        fetch('update_verification_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Success', `Verification ${status} successfully`, 'success');
                // Find and remove the verification item
                const verificationItem = document.querySelector(`.verification-item[data-user-id="${userId}"]`);
                if (verificationItem) {
                    verificationItem.remove();
                    // Check if there are any remaining verification items
                    const remainingItems = document.querySelectorAll('.verification-item');
                    if (remainingItems.length === 0) {
                        document.getElementById('verificationList').innerHTML = 
                            '<div style="text-align: center; padding: 20px;">No pending verification requests</div>';
                    }
                }
            } else {
                showNotification('Error', data.message || 'Failed to update verification status', 'error');
            }
        });
    }

    // Add click event listener for the verification button
    document.addEventListener('DOMContentLoaded', function() {
        const verificationBtn = document.getElementById('verificationRequestBtn');
        if (verificationBtn) {
            verificationBtn.addEventListener('click', showVerificationModal);
        }
    });
    </script>

    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.png" alt="Logo" class="logo">
            <h2>Admin Panel</h2>
        </div>
        <ul class="sidebar-menu">
            <!-- Dashboard -->
            <li class="menu-item" id="dashboard-item">
                <a href="#" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <div class="menu-separator"></div>

            <!-- Maintenance Group -->
            <li class="menu-header">Maintenance</li>
            <li class="menu-item" id="landing-item">
                <a href="#" data-section="landing">
                    <i class="fas fa-home"></i>
                    <span>Landing Settings</span>
                </a>
            </li>
            <li class="menu-item" id="roles-item">
                <a href="#" data-section="roles">
                    <i class="fas fa-user-shield"></i>
                    <span>User Roles</span>
                </a>
            </li>
            <li class="menu-item" id="accounts-item">
                <a href="#" data-section="accounts">
                    <i class="fas fa-users-cog"></i>
                    <span>User Accounts</span>
                </a>
            </li>
            <li class="menu-item" id="products-item">
                <a href="#" onclick="toggleProductsMenu(event)">
                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-utensils"></i>
                            <span>Products</span>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </a>
            </li>
            <div class="submenu-items" style="display: none;">
                <li class="menu-item submenu-item">
                    <a href="#" data-section="menu-creation" style="padding-left: 3.5rem;">
                        <i class="fas fa-plus"></i>
                        <span>Add Products</span>
                    </a>
                </li>
                <li class="menu-item submenu-item">
                    <a href="#" data-section="restocking" style="padding-left: 3.5rem;">
                        <i class="fas fa-box"></i>
                        <span>Restocking</span>
                    </a>
                </li>
            </div>

            <div class="menu-separator"></div>

            <!-- Monitoring Group -->
            <li class="menu-header">Monitoring</li>
            <li class="menu-item" id="inventory-item">
                <a href="#" data-section="inventory">
                    <i class="fas fa-boxes"></i>
                    <span>Inventory</span>
                </a>
            </li>
            <li class="menu-item" id="reports-item">
                <a href="#" data-section="reports">
                    <i class="fas fa-chart-line"></i>
                    <span>Sales Report</span>
                </a>
            </li>
            <li class="menu-item" id="orders-item">
                <a href="#" data-section="orders">
                    <i class="fas fa-shopping-basket"></i>
                    <span>Orders</span>
                </a>
            </li>

            <div class="menu-separator"></div>

            <!-- Logout (pinned to bottom visually by existing layout) -->
            <li class="menu-item logout-item">
                <a href="#" onclick="handleLogout(event)" style="cursor: pointer;">
                    <i class="fas fa-sign-out-alt"></i>
              <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
    <div id="pageOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); z-index: 999;"></div>
    <?php
    // Section handling
    $current_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
    $section_titles = [
        'dashboard' => 'Dashboard',
        'inventory' => 'Inventory',
        'menu-creation' => 'Menu Creation',
        'orders' => 'Orders',
        'accounts' => 'User Accounts',
        'user-roles' => 'User Roles',
        'sales-report' => 'Sales Report'
    ];
    $section_title = isset($section_titles[$current_section]) ? $section_titles[$current_section] : '';
    
    // Set section icon
    $section_icons = [
        'dashboard' => 'fas fa-chart-pie',
        'inventory' => 'fas fa-box',
        'menu-creation' => 'fas fa-utensils',
        'orders' => 'fas fa-shopping-cart',
        'user-accounts' => 'fas fa-users',
        'user-roles' => 'fas fa-user-tag',
        'sales-report' => 'fas fa-chart-line'
    ];
    $section_icon = isset($section_icons[$current_section]) ? $section_icons[$current_section] : 'fas fa-question';
    ?>
    <div class="main-content" id="mainContent">
        <div class="content-wrapper">
            <header class="main-header">
                <div class="header-content">
                <div class="header-left">
                    <div class="header-title" id="section-title">
                        <i class="<?php echo $section_icon; ?> dashboard-icon"></i>
                        <h1><?php echo $section_title; ?></h1>
                    </div>
                </div>
                <div class="search-container">
                    <i class="fas fa-search search-icon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #666; font-size: 12px;"></i>
                    <input type="text" placeholder="Search anything..." class="enhanced-search" style="width: 100%;">
                </div>
                <div style="display: flex; align-items: center; gap: 16px; justify-self: end;">
                    <!-- Dark Mode Toggle -->
                    <div class="theme-toggle" onclick="toggleDarkMode()" style="cursor: pointer; display: flex; align-items: center; padding: 0;">
                        <i class="fas fa-sun" id="themeIcon" style="font-size: 20px;"></i>
                    </div>
                    <!-- Notification Icon -->
                    <div class="notification-icon" style="position: relative; cursor: pointer; display: flex; align-items: center; padding: 0;" onclick="toggleNotifications()">
                        <i class="fas fa-bell" style="font-size: 20px; color: #FF7F50; filter: drop-shadow(0 2px 4px rgba(255, 127, 80, 0.2));"></i>
                        <span id="notifCount" class="notification-badge" style="position: absolute; top: -5px; right: -5px; background: #FF7F50; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(255, 127, 80, 0.2);">0</span>
                    </div>
                </div>
                <!-- Notification Dropdown -->
                <div id="notificationDropdown" style="display: none; position: absolute; top: 50px; right: 20px; width: 320px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000;">
                    <div style="padding: 15px; border-bottom: 1px solid #f0f0f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div id="notifHeader" style="font-weight: 600;">Notifications</div>
                            <button onclick="markAllAsRead()" id="markAllBtn" style="padding: 4px 8px; font-size: 12px; color: #FF7F50; background: none; border: 1px solid #FF7F50; border-radius: 4px; cursor: pointer; margin-left: 10px; display: none;">Mark all as read</button>
                        </div>
                    </div>
                    <div id="notificationList">
                        <div class="notification-text">No new notifications</div>
                    </div>
                    <!-- Ddsdadasdasddas -->
                </div>
                <div class="profile-section">
                    <a href="profile.php" class="profile-info" style="text-decoration: none; cursor: pointer;">
                        <img src="<?php 
                            if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])) {
                                echo '../uploaded_img/' . htmlspecialchars($_SESSION['profile_picture']);
                            } else {
                                echo '../images/user.png';
                            }
                        ?>" 
                        alt="Admin Profile" class="profile-pic">
                        <span class="admin-name"><?php 
                            if (isset($_SESSION['firstName']) && isset($_SESSION['lastName'])) {
                                echo htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']);
                            } else {
                                echo htmlspecialchars($admin_data['full_name'] ?? $_SESSION['username']);
                            }
                        ?></span>
                    </a>
                </div>
            </div>
        </header>

        <script>
            // Handle section visibility
            document.addEventListener('DOMContentLoaded', function() {
                const currentSection = '<?php echo $current_section; ?>';
                // Hide all sections first
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.add('hidden');
                });
                // Show the current section
                const activeSection = document.getElementById(currentSection + '-section');
                if (activeSection) {
                    activeSection.classList.remove('hidden');
                }
            });
        </script>

        <!-- Dashboard Section -->
        <section id="dashboard-section" class="content-section">
            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card" onclick="window.location.href='admin_pg.php?section=menu-creation'" style="cursor: pointer;">
                    <div class="stat-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_items = mysqli_query($conn, "SELECT COUNT(*) as count FROM products");
                        $items_count = mysqli_fetch_assoc($total_items)['count'];
                        ?>
                        <h3><?php echo $items_count; ?></h3>
                        <p>Total Items</p>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>12%</span>
                    </div>
                </div>

                <div class="stat-card" onclick="window.location.href='admin_pg.php?section=reports'" style="cursor: pointer;">
                    <div class="stat-icon">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_revenue = mysqli_query($conn, "SELECT SUM(total_price) as total FROM orders WHERE status = 'completed'");
                        $revenue = mysqli_fetch_assoc($total_revenue)['total'] ?? 0;
                        ?>
                        <h3>₱<?php echo number_format($revenue, 2); ?></h3>
                        <p>Total Sales</p>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>8%</span>
                    </div>
                </div>

                <div class="stat-card" onclick="window.location.href='admin_pg.php?section=orders'" style="cursor: pointer;">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_orders = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders");
                        $orders_count = mysqli_fetch_assoc($total_orders)['count'];
                        ?>
                        <h3><?php echo $orders_count; ?></h3>
                        <p>Total Orders</p>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>5%</span>
                    </div>
                </div>

                <div class="stat-card" onclick="window.location.href='admin_pg.php?section=accounts'" style="cursor: pointer;">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_customers = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role_id = 4");
                        $customers_count = mysqli_fetch_assoc($total_customers)['count'];
                        ?>
                        <h3><?php echo $customers_count; ?></h3>
                        <p>Total Customers</p>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>15%</span>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-container">
                <div class="chart-card revenue-chart">
                    <div class="chart-header">
                        <h3>Revenue Overview</h3>
                        <div class="chart-actions">
                            <select class="chart-period">
                                <option value="monthly">Monthly</option>
                                <option value="weekly">Weekly</option>
                                <option value="daily">Daily</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-content">
                        <?php
                        // Fetch monthly revenue data for the last 12 months
                        $monthly_revenue = array();
                        $monthly_labels = array();
                        
                        for ($i = 11; $i >= 0; $i--) {
                            $start_date = date('Y-m-01', strtotime("-$i months"));
                            $end_date = date('Y-m-t', strtotime("-$i months"));
                            
                            $query = "SELECT COALESCE(SUM(total_price), 0) as revenue 
                                     FROM orders 
                                     WHERE status = 'completed' 
                                     AND order_time BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
                            
                            $result = mysqli_query($conn, $query);
                            if (!$result) {
                                error_log("MySQL Error: " . mysqli_error($conn));
                            }
                            $row = mysqli_fetch_assoc($result);
                            
                            $monthly_revenue[] = floatval($row['revenue']);
                            $monthly_labels[] = date('M', strtotime($start_date));
                        }

                        // Debug output
                        error_log("Revenue Data: " . json_encode($monthly_revenue));
                        error_log("Labels Data: " . json_encode($monthly_labels));
                        ?>
                        <canvas id="revenueChart"></canvas>
                        <script>
                            // Store the data in variables
                            window.revenueData = <?php echo json_encode($monthly_revenue); ?>;
                            window.labelData = <?php echo json_encode($monthly_labels); ?>;
                        </script>
                    </div>
                </div>

                <div class="chart-card orders-chart">
                    <div class="chart-header">
                        <h3>Order Statistics</h3>
                        <div class="chart-legend">
                            <span class="legend-item">
                                <span class="legend-color" style="background: #FF7F50;"></span>
                                Completed
                            </span>
                            <span class="legend-item">
                                <span class="legend-color" style="background: #FFB75E;"></span>
                                Pending
                            </span>
                        </div>
                    </div>
                    <div class="chart-content">
                        <?php
                        $total_orders = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders");
                        $completed_orders = mysqli_query($conn, "SELECT COUNT(*) as completed FROM orders WHERE status = 'completed'");
                        $total_count = mysqli_fetch_assoc($total_orders)['total'];
                        $completed_count = mysqli_fetch_assoc($completed_orders)['completed'];
                        $completion_rate = $total_count > 0 ? round(($completed_count / $total_count) * 100, 1) : 0;
                        ?>
                        <div class="completion-chart">
                            <div class="pie-chart-container">
                                <div class="pie-chart" style="--completion-rate: <?php echo $completion_rate; ?>">
                                    <div class="pie-center">
                                        <span class="completion-percentage"><?php echo round($completion_rate); ?>%</span>
                                        <span class="completion-label">COMPLETION<br>RATE</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="recent-orders">
               
                <div class="orders-table-wrapper">
                    <table class="dashboard-orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_orders_query = mysqli_query($conn, "SELECT * FROM orders ORDER BY order_time DESC LIMIT 5");
                            while($order = mysqli_fetch_assoc($recent_orders_query)) {
                                $status_class = strtolower($order['status']);
                                ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($order['name']); ?></td>
                                    <td><?php echo $order['total_products']; ?> items</td>
                                    <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Inventory Section -->
        <section id="inventory-section" class="content-section hidden">
            <?php
            // Calculate inventory statistics
            $total_products = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
            $total_count = mysqli_fetch_assoc($total_products)['total'];

            $low_stock = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stock > 0 AND stock <= 10");
            $low_stock_count = mysqli_fetch_assoc($low_stock)['total'];

            $out_of_stock = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stock <= 0");
            $out_of_stock_count = mysqli_fetch_assoc($out_of_stock)['total'];

            // Calculate fast-moving products (products with most sales in last 30 days)
            $fast_moving = mysqli_query($conn, "SELECT COUNT(DISTINCT product_id) as total 
                FROM stock_history 
                WHERE type = 'deduct' 
                AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY product_id 
                HAVING SUM(quantity) >= 20");
            $fast_moving_count = mysqli_num_rows($fast_moving);
            ?>
            <!-- KPI Cards -->
            <div class="inventory-stats">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number"><?php echo $total_count; ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-chart">
                        <div class="chart-circle" style="--percent: <?php echo ($total_count > 0) ? 100 : 0; ?>%"></div>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number"><?php echo $low_stock_count; ?></div>
                        <div class="stat-label">Critical Stock</div>
                        <div class="stat-sublabel">Items need restock</div>
                    </div>
                    <div class="stat-chart">
                        <div class="chart-circle" style="--percent: <?php echo ($total_count > 0) ? ($low_stock_count / $total_count) * 100 : 0; ?>%"></div>
                    </div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number"><?php echo $out_of_stock_count; ?></div>
                        <div class="stat-label">Out of Stock</div>
                        <div class="stat-sublabel">Immediate attention needed</div>
                    </div>
                    <div class="stat-chart">
                        <div class="chart-circle" style="--percent: <?php echo ($total_count > 0) ? ($out_of_stock_count / $total_count) * 100 : 0; ?>%"></div>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon" id="movementIcon">
                        <i class="fas fa-chart-line" style="color: #FF7F50;"></i>
                    </div>
                    <div class="stat-details">
                        <div id="movementNumber" class="stat-number" style="color: #FF7F50;"><?php echo $fast_moving_count; ?></div>
                        <div id="movementLabel" class="stat-label">Moving Products</div>
                        <div id="movementSubLabel" class="stat-sublabel">All movement items</div>
                    </div>
                    <div class="stat-chart">
                        <div class="chart-circle" style="--percent: <?php echo ($total_count > 0) ? ($fast_moving_count / $total_count) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="inventory-table-container" style="margin-top: 0; position: relative;">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(to right, #FFB75E, #FF7F50);"></div>
                <div class="table-header dark-mode-header" style="margin-top: 5px; height: 50px; padding: 0.75rem 1rem; background: transparent;">
                    <div class="table-title">
                        <h3 class="inventory-title">Product Inventory</h3>
                    </div>
                    <div class="inventory-filters" style="padding: 12px; display: flex; align-items: center; position: relative; margin-bottom: 15px;">
                        <div class="search-box" style="position: relative; margin: 0 150px 0 auto;">
                            <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; font-size: 14px;"></i>
                            <input type="text" id="inventorySearch" placeholder="Search products..." class="dark-mode-search" style="box-shadow: 0 2px 15px rgba(255, 183, 94, 0.2); border-radius: 50px; width: 600px; padding-left: 40px;">
                        </div>
                        <div class="filter-group" style="margin-left: auto; padding-right: 20px; display: flex; gap: 10px;">
                            <select id="statusFilter" class="movement-dropdown">
                                <option value="all">All Status</option>
                                <option value="out">Out of Stock</option>
                                <option value="low">Critical Stock (≤10)</option>
                                <option value="in">Sufficient</option>
                            </select>
                            <select id="movementFilter" class="movement-dropdown" onchange="updateMovementCard(this.value)">
                                <option value="all">Moving Products</option>
                                <option value="fast">Fast Moving</option>
                                <option value="slow">Slow Moving</option>
                                <option value="non">Non Moving</option>
                            </select>
                            <script>
                                function updateMovementCard(value) {
                                    const movementIcon = document.getElementById('movementIcon').querySelector('i');
                                    const movementNumber = document.getElementById('movementNumber');
                                    const statLabel = document.getElementById('movementLabel');
                                    const statSubLabel = document.getElementById('movementSubLabel');
                                    const card = document.querySelector('.stat-card.success');
                                    
                                    switch(value) {
                                        case 'fast':
                                            movementIcon.className = 'fas fa-chart-line';
                                            movementIcon.style.color = '#4CAF50';
                                            movementNumber.style.color = '#4CAF50';
                                            statLabel.textContent = 'Fast Moving';
                                            statSubLabel.textContent = 'High demand items';
                                            break;
                                        case 'slow':
                                            movementIcon.className = 'fas fa-clock';
                                            movementIcon.style.color = '#FFB75E';
                                            movementNumber.style.color = '#FFB75E';
                                            statLabel.textContent = 'Slow Moving';
                                            statSubLabel.textContent = 'Regular demand items';
                                            break;
                                        case 'non':
                                            movementIcon.className = 'fas fa-stop';
                                            movementIcon.style.color = '#FF6B6B';
                                            movementNumber.style.color = '#FF6B6B';
                                            statLabel.textContent = 'Non Moving';
                                            statSubLabel.textContent = 'Low demand items';
                                            break;
                                        default:
                                            movementIcon.className = 'fas fa-chart-line';
                                            movementIcon.style.color = '#FF7F50';
                                            movementNumber.style.color = '#FF7F50';
                                            statLabel.textContent = 'Moving Products';
                                            statSubLabel.textContent = 'All movement items';
                                            break;
                                    }
                                }
                            </script>
                        </div>
                    </div>

                    <style>
                        /* Enhanced Inventory Filters */
                        .inventory-filters {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin-bottom: 16px;
                            padding: 0;
                            background: transparent;
                            gap: 16px;
                            width: 100%;
                        }

                        .search-box {
                            position: relative;
                            width: 800px;
                            margin-right: 300px;
                            background: #FFFFFF;
                        }

                        .search-box input {
                            width: 100%;
                            height: 32px;
                            padding: 6px 16px 6px 36px;
                            border: 1px solid #e5e7eb;
                            border-radius: 50px;
                            font-size: 13px;
                            background: #ffffff;
                            color: #555;
                            transition: all 0.2s ease;
                        }

                        .search-box input::placeholder {
                            color: #888;
                        }

                        .search-box i {
                            position: absolute;
                            left: 16px;
                            top: 50%;
                            transform: translateY(-50%);
                            color: #888;
                            font-size: 15px;
                        }

                        .search-box input:hover {
                            border-color: #FFB75E;
                            background: linear-gradient(to right, #ffffff, #fff0e6);
                            box-shadow: 0 2px 15px rgba(255, 183, 94, 0.3);
                        }

                        .search-box input:focus {
                            outline: none;
                            border-color: #FF7F50;
                            background: linear-gradient(to right, #ffffff, #ffe4d9);
                            box-shadow: 0 0 0 3px rgba(255, 127, 80, 0.1);
                        }

                        #inventorySearch {
                            width: 600px !important;
                            border-radius: 50px !important;
                            height: 32px;
                            padding: 6px 16px 6px 36px;
                            border: 1px solid #e5e7eb;
                            font-size: 13px;
                            background: #ffffff;
                            color: #555;
                            transition: all 0.2s ease;
                            margin-right: 300px;
                            box-shadow: 0 2px 15px rgba(255, 183, 94, 0.2);
                        }

                        .search-box i {
                            position: absolute;
                            left: 12px;
                            top: 50%;
                            transform: translateY(-50%);
                            color: #FF7F50;
                            opacity: 0.7;
                            font-size: 13px;
                        }

                        .filter-group {
                            display: flex;
                            gap: 12px;
                            align-items: center;
                            flex-shrink: 0;
                            margin-left: auto;
                            padding-right: 20px;
                        }

                        .filter-select {
                            height: 32px;
                            padding: 0 28px 0 12px;
                            border: 1px solid #e5e7eb;
                            border-radius: 20px;
                            font-size: 13px;
                            color: #666;
                            background: #ffffff;
                            cursor: pointer;
                            appearance: none;
                            min-width: 120px;
                            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 16 16'%3E%3Cpath fill='%23FF7F50' d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
                            background-repeat: no-repeat;
                            background-position: calc(100% - 10px) center;
                            transition: all 0.2s ease;
                        }

                        .filter-select:hover {
                            border-color: #FF7F50;
                            box-shadow: 0 0 0 4px rgba(255, 127, 80, 0.1);
                        }

                        .filter-select:focus {
                            outline: none;
                            border-color: #FF7F50;
                            box-shadow: 0 0 0 4px rgba(255, 127, 80, 0.15);
                        }
                    </style>
                </div>

                <div class="table-responsive">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>UOM</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $select_products = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");
                        if(mysqli_num_rows($select_products) > 0){
                            while($product = mysqli_fetch_assoc($select_products)){
                                // Determine stock status
                                $stock_status = '';
                                $status_class = '';
                                if($product['stock'] <= 0) {
                                    $stock_status = 'Out of Stock';
                                    $status_class = 'out-of-stock';
                                } else if($product['stock'] <= 10) {
                                    $stock_status = 'Critical Stock';
                                    $status_class = 'low-stock';
                                } else {
                                    $stock_status = 'Sufficient';
                                    $status_class = 'in-stock';
                                }
                        ?>
                            <tr>
                                <td><img src="../uploaded_img/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-img"></td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category'] ?? 'Uncategorized'; ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo $product['uom'] ?? 'N/A'; ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $stock_status; ?></span></td>
                            </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='7' class='no-products'>No products found</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stock Update Modal -->
            <div id="stockUpdateModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Update Stock</h3>
                        <span class="close-modal" onclick="closeStockModal()">&times;</span>
                    </div>
                    <form id="stockUpdateForm" method="POST">
                        <input type="hidden" id="product_id" name="product_id">
                        <div class="form-group">
                            <label for="current_stock">Current Stock</label>
                            <input type="number" id="current_stock" readonly>
                        </div>
                        <div class="form-group">
                            <label for="stock_change">Add/Remove Stock</label>
                            <input type="number" id="stock_change" name="stock_change" required>
                            <small>Use positive number to add stock, negative to remove</small>
                        </div>
                        <div class="form-buttons">
                            <button type="button" class="cancel-btn" onclick="closeStockModal()">Cancel</button>
                            <button type="submit" class="submit-btn">Update Stock</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>



        <!-- Menu Creation Section -->
        <section id="menu-creation-section" class="content-section hidden">
            <div class="menu-creation-container">
                <div class="add-product-layout">
                    <div class="form-side">
                        <div class="add-product-form">
                            <div class="form-header">
                                <h2>ADD PRODUCT</h2>
                            </div>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="form-content">
                                    <div class="form-row-landscape">
                                        <div class="input-group">
                                            <label for="product_name">Product Name</label>
                                            <input type="text" id="product_name" name="product_name" required>
                                        </div>
                                        <div class="input-group">
                                            <label for="markup_value">Mark up value</label>
                                            <input type="number" id="markup_value" name="markup_value" step="0.01" required>
                                        </div>
                                    </div>

                                    <div class="form-row-landscape">
                                        <div class="input-group">
                                            <label for="category">Category</label>
                                            <div class="category-select-wrapper">
                                                <select id="category" name="category" required>
                                                    <option value="">Select Category</option>
                                                    <?php
                                                    $query = "SELECT * FROM product_categories ORDER BY id";
                                                    $result = mysqli_query($conn, $query);
                                                    while($row = mysqli_fetch_assoc($result)) {
                                                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['category_name']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <button type="button" class="add-category-btn" onclick="showAddCategoryModal()">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="input-group">
                                            <label for="unit_measurement">Unit of Measurement</label>
                                            <select id="unit_measurement" name="unit_measurement" required>
                                                <option value="">Select UOM</option>
                                                <option value="piece">Piece</option>
                                                <option value="pack">Pack</option>
                                                <option value="kg">Kilogram</option>
                                                <option value="g">Gram</option>
                                                <option value="ml">Milliliter</option>
                                                <option value="l">Liter</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row-landscape upload-section">
                                        <div class="input-group full-width">
                                            <label for="product_image">Upload Image</label>
                                            <div class="file-input-container">
                                                <input type="file" id="product_image" name="product_image" accept="image/*" required>
                                                <div class="file-input-ui">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <span>Drop image here or click to upload</span>
                                                    <small>Supported formats: PNG, JPG, JPEG</small>
                                                </div>
                                                <div id="imagePreview" class="image-preview"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" name="add_product" class="btn btn-primary">
                                            <i class="fas fa-plus-circle"></i> Add Product
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Product Cards Container -->
                <div class="product-cards-container">
                    <div class="product-grid">
                        <?php
                        $products_query = "SELECT * FROM new_products ORDER BY created_at DESC";
                        $products_result = mysqli_query($conn, $products_query);
                        
                        while($product = mysqli_fetch_assoc($products_result)) {
                            echo '<div class="product-card">';
                            echo '<img src="../uploaded_img/' . htmlspecialchars($product['image']) . '" alt="' . htmlspecialchars($product['product_name']) . '">';
                            echo '<div class="product-details">';
                            echo '<h3>' . htmlspecialchars($product['product_name']) . '</h3>';
                            echo '<p class="category">' . htmlspecialchars($product['category_name']) . '</p>';
                            echo '<p class="markup">Markup: ₱' . htmlspecialchars($product['markup_value']) . '</p>';
                            echo '<p class="uom">UOM: ' . htmlspecialchars($product['unit_measurement']) . '</p>';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Restocking Section -->
        <section id="restocking-section" class="content-section hidden">
            <div class="restocking-container">
                <div class="products-table-section">
                    <div class="table-header">
                        <h3>Newly Added Products</h3>
                        <div class="search-container">
                            <input type="text" id="productSearch" placeholder="Search products..." class="search-input">
                        </div>
                    </div>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th width="40">ID</th>
                                <th>Product Name</th>
                                <th width="80">Markup Value</th>
                                <th width="80">Category ID</th>
                                <th>Category Name</th>
                                <th width="100">Unit Measurement</th>
                                <th width="130">Created At</th>
                                <th width="130">Updated At</th>
                            </tr>
                        </thead>
                            <tbody>
                                <?php
                                $new_products = getNewProducts($conn);
                                if(mysqli_num_rows($new_products) > 0) {
                                    while($row = mysqli_fetch_assoc($new_products)) {
                                        echo "<tr>";
                                        echo "<td style='text-align: center'>" . $row['id'] . "</td>";
                                        echo "<td>" . $row['product_name'] . "</td>";
                                        echo "<td>" . $row['markup_value'] . "</td>";
                                        echo "<td style='text-align: center'>" . $row['category_id'] . "</td>";
                                        echo "<td>" . $row['category_name'] . "</td>";
                                        echo "<td>" . $row['unit_measurement'] . "</td>";
                                        echo "<td>" . $row['created_at'] . "</td>";
                                        echo "<td>" . $row['updated_at'] . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' style='text-align: center'>No new products found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            </section>

        <!-- Inventory Section -->
                </form>
            </div>

            <!-- Add Category Modal -->
            <div id="addCategoryModal" class="modal">
                <div class="category-modal-content">
                    <div class="category-modal-header">
                        <h3>Add New Category</h3>
                        <button class="close" onclick="closeAddCategoryModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form id="addCategoryForm" onsubmit="return handleAddCategory(event)">
                        <div class="form-group">
                            <label for="newCategoryName">Category Name</label>
                            <input type="text" id="newCategoryName" name="categoryName" required 
                                   placeholder="Enter category name">
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeAddCategoryModal()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function showAddCategoryModal() {
                    document.getElementById('addCategoryModal').style.display = 'block';
                }

                function closeAddCategoryModal() {
                    document.getElementById('addCategoryModal').style.display = 'none';
                }

                async function handleAddCategory(event) {
                    event.preventDefault();
                    const categoryName = document.getElementById('newCategoryName').value;
                    
                    try {
                        const response = await fetch('add_category.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `categoryName=${encodeURIComponent(categoryName)}`
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            // Add new option to select
                            const select = document.getElementById('category');
                            const option = new Option(categoryName, data.categoryId);
                            select.add(option);
                            select.value = data.categoryId;
                            
                            // Close modal and show success message
                            closeAddCategoryModal();
                            showNotification('Success', 'Category added successfully');
                        } else {
                            showNotification('Error', data.message || 'Failed to add category');
                        }
                    } catch (error) {
                        showNotification('Error', 'Failed to add category');
                    }

                    return false;
                }
            </script>
        </section>

        <?php
        // Handle menu update notifications
        if (isset($_GET['section']) && $_GET['section'] === 'menu-creation' && isset($_GET['action'])) {
            $action = $_GET['action'];
            if ($action === 'update') {
                echo "<script>alert('Product updated successfully');</script>";
            } else if ($action === 'error') {
                echo "<script>alert('Error: Product could not be updated');</script>";
            }
        }
        ?>
        
        <!-- Dashboard Section -->
        <section id="dashboard-section" class="content-section">
            <div class="welcome-header">
                <h2>Dashboard</h2>
                <p>Welcome to your admin dashboard</p>
            </div>
        </section>

        <style>
            /* Dark mode styles for roles section modals */
            .modal {
                background-color: rgba(0, 0, 0, 0.7) !important;
            }
            .modal-content {
                background-color: #1a1a1a !important;
                color: #ffffff !important;
                border: 1px solid #333 !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5) !important;
            }
            
            /* Input fields dark mode */
            .modal-content input[type="text"],
            .modal-content input[type="email"],
            .modal-content input[type="password"],
            .modal-content #username,
            .modal-content #firstName,
            .modal-content #lastName,
            .modal-content #email,
            .modal-content #password,
            .modal-content #confirmPassword {
                background-color: #2d2d2d !important;
                color: #ffffff !important;
                border: 1px solid #444 !important;
                border-radius: 6px;
                padding: 8px 12px;
            }

            .modal-content input[type="text"]:focus,
            .modal-content input[type="email"]:focus,
            .modal-content input[type="password"]:focus,
            .modal-content #username:focus,
            .modal-content #firstName:focus,
            .modal-content #lastName:focus,
            .modal-content #email:focus,
            .modal-content #password:focus,
            .modal-content #confirmPassword:focus {
                border-color: #FF7F50 !important;
                box-shadow: 0 0 0 2px rgba(255, 127, 80, 0.2) !important;
                outline: none;
            }

            /* Admin note styles */
            .admin-note {
                background-color: #2d2d2d !important;
                border: 1px solid #444 !important;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
            }

            .note-content {
                display: flex;
                align-items: flex-start;
                gap: 10px;
            }

            .note-content i {
                color: #FF7F50;
                font-size: 18px;
                margin-top: 3px;
            }

            .note-content p {
                color: #b0b0b0 !important;
                margin: 0;
                font-size: 14px;
                line-height: 1.5;
            }

            /* Show admin note only for admin role */
            #adminPermissions {
                display: none;
            }

            [data-role="2"] #adminPermissions {
                display: block;
            }
            .modal-header {
                border-bottom: 1px solid #333 !important;
                background-color: #1a1a1a !important;
            }
            .modal-header h2 {
                color: #ffffff !important;
            }
            .form-group label {
                color: #ffffff !important;
            }
            .form-group input, 
            .form-group select,
            .form-group textarea {
                background-color: #2d2d2d !important;
                color: #ffffff !important;
                border: 1px solid #444 !important;
            }
            .form-group input:focus,
            .form-group select:focus,
            .form-group textarea:focus {
                border-color: #FF7F50 !important;
                box-shadow: 0 0 0 2px rgba(255, 127, 80, 0.2) !important;
            } 
            .close {
                color: #ffffff !important;
            }
            .close:hover {
                color: #FF7F50 !important;
            }
            .form-actions button {
                background: #2d2d2d !important;
                color: #ffffff !important;
                border: 1px solid #444 !important;
            }
            .form-actions button:hover {
                background: #3d3d3d !important;
            }
            .checkbox-group {
                background-color: #2d2d2d !important;
                border: 1px solid #444 !important;
                padding: 15px !important;
                border-radius: 4px !important;
            }
            .checkbox-group label {
                color: #ffffff !important;
            }
            .modal input[type="checkbox"] {
                background-color: #2d2d2d !important;
                border: 1px solid #444 !important;
            }
            .modal input[type="checkbox"]:checked {
                background-color: #FF7F50 !important;
                border-color: #FF7F50 !important;
            }
            .cancel-btn {
                background-color: #2d2d2d !important;
                color: #ffffff !important;
                border: 1px solid #444 !important;
            }
            .create-account-btn {
                background-color: #FF7F50 !important;
                color: #ffffff !important;
            }
        </style>

        <!-- User Roles Section -->
        <section id="roles-section" class="content-section hidden">
            <?php
            // Handle login state and notifications
            if (!isset($_SESSION['notifications_initialized'])) {
                // Initialize notification state
                $_SESSION['notifications_initialized'] = true;
                $_SESSION['last_notification_time'] = 0;
            }

            // Check if this is a fresh login (within last 5 seconds)
            $isFreshLogin = isset($_SESSION['login_timestamp']) && 
                           (time() - $_SESSION['login_timestamp']) <= 5;

            // Handle dashboard redirect on fresh login
            if (isset($_SESSION['show_dashboard']) && $_SESSION['show_dashboard'] === true) {
                echo "<script>
                    sessionStorage.removeItem('currentSection');
                    showSection('dashboard-section');
                </script>";
                unset($_SESSION['show_dashboard']);
            }

            // Show welcome message only on fresh login
            if ($isFreshLogin && !isset($_SESSION['welcome_shown'])) {
                if (isset($_SESSION['message'])) {
                    echo "<script>
                        alert('Welcome back! You have successfully logged in.');
                    </script>";
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                }
                $_SESSION['welcome_shown'] = true;
            }
            ?>
            
           

            <div class="role-cards">
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Administrator</h3>
                    <p>System management with full access to administrative features, including user management, inventory control, and system settings.</p>
                    <ul class="role-features">
                        <li><i class="fas fa-check"></i> Full system access</li>
                        <li><i class="fas fa-check"></i> User management</li>
                        <li><i class="fas fa-check"></i> Reports</li>
                    </ul>
                    <button class="create-btn" onclick="showCreateUserForm(2)">
                        <i class="fas fa-plus"></i> Create Admin
                    </button>
                </div>

                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Crew Member</h3>
                    <p>Staff account for order processing, inventory management, and customer service operations.</p>
                    <ul class="role-features">
                        <li><i class="fas fa-check"></i> Order management</li>
                        <li><i class="fas fa-check"></i> Inventory tracking</li>
                        <li><i class="fas fa-check"></i> Customer support</li>
                    </ul>
                    <button class="create-btn" onclick="showCreateUserForm(3)">
                        <i class="fas fa-plus"></i> Create Crew
                    </button>
                </div>

                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3>Customer</h3>
                    <p>Regular user account with access to ordering system, order tracking, and profile management.</p>
                    <ul class="role-features">
                        <li><i class="fas fa-check"></i> Place orders</li>
                        <li><i class="fas fa-check"></i> Track deliveries</li>
                        <li><i class="fas fa-check"></i> Manage profile</li>
                    </ul>
                    <button class="create-btn" onclick="showCreateUserForm(4)">
                        <i class="fas fa-plus"></i> Create Customer
                    </button>
                </div>
            </div>



            <!-- Create User Form Modal -->
            <script>
                function showCreateUserForm(roleId) {
                    document.getElementById('roleId').value = roleId;
                    const modal = document.getElementById('createUserModal');
                    modal.style.display = 'block';
                    modal.setAttribute('data-role', roleId);
                }

                function hideCreateUserForm() {
                    document.getElementById('createUserModal').style.display = 'none';
                    document.getElementById('createUserForm').reset();
                }

                function validateForm() {
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;
                    if (password !== confirmPassword) {
                        alert('Passwords do not match!');
                        return false;
                    }
                    return true;
                }
            </script>

            <div id="createUserModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Create New User</h3>
                        <span class="close-modal" onclick="hideCreateUserForm()">&times;</span>
                    </div>
                    <form id="createUserForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>?section=roles" onsubmit="return validateForm()">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="firstName" required 
                                       value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="lastName" required
                                       value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="password-input">
                                    <input type="password" id="password" name="password" required>
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirm Password</label>
                                <div class="password-input">
                                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="createUser" value="1">
                        <input type="hidden" name="roleId" id="roleId">

                        <!-- Admin Note Section -->
                        <div id="adminPermissions" class="permissions-section admin-note">
                            <div class="note-content">
                                <i class="fas fa-info-circle"></i>
                                <p>Important: Admin accounts have full system access. Ensure you follow security best practices and only grant admin privileges to trusted personnel.</p>
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button type="button" class="cancel-btn" onclick="hideCreateUserForm()">Cancel</button>
                            <button type="submit" name="createUser" class="submit-btn">Create Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Landing Settings Section -->
        <section id="landing-section" class="content-section hidden">
            

            <div class="settings-grid">
                <!-- Restaurant Branding -->
                <div class="settings-card">
                    <h3><i class="fas fa-store"></i> Restaurant Branding</h3>
                    <div class="form-group">
                        <label>Restaurant Name</label>
                        <input type="text" class="settings-input" id="restaurantName" placeholder="K-Food Delight">
                    </div>
                    <div class="form-group">
                        <label>Upload Logo</label>
                        <div class="upload-container">
                            <img id="logoPreview" src="../images/logo.png" alt="Logo Preview">
                            <input type="file" id="logoUpload" accept="image/*" class="file-input">
                            <div class="upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Drop logo here or <span class="browse-text">browse</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Upload Favicon</label>
                        <div class="upload-container small">
                            <img id="faviconPreview" src="../images/logo.png" alt="Favicon Preview">
                            <input type="file" id="faviconUpload" accept="image/*" class="file-input">
                            <div class="upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Drop favicon or <span class="browse-text">browse</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tagline</label>
                        <textarea class="settings-input" id="tagline" placeholder="Enter your restaurant's tagline"></textarea>
                    </div>
                </div>

                <!-- Hero Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-image"></i> Hero Section</h3>
                    <div class="form-group">
                        <label>Hero Title</label>
                        <input type="text" class="settings-input" id="heroTitle" placeholder="K-FOOD DELIGHTS">
                    </div>
                    <div class="form-group">
                        <label>Hero Subtitle</label>
                        <textarea class="settings-input" id="heroSubtitle" placeholder="Experience authentic Korean cuisine..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Background Image</label>
                        <div class="upload-container hero">
                            <img id="heroPreview" src="../images/lasagna.jpg" alt="Hero Preview">
                            <input type="file" id="heroUpload" accept="image/*" class="file-input">
                            <div class="upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Drop hero image or <span class="browse-text">browse</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- About Us Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-info-circle"></i> About Us Section</h3>
                    <div class="form-group">
                        <label>Restaurant Story</label>
                        <textarea class="settings-input" id="aboutUs" rows="5" placeholder="Tell your restaurant's story..."></textarea>
                    </div>
                    <div class="features-container">
                        <label>Features</label>
                        <div id="featuresList" class="features-list">
                            <!-- Features will be added here -->
                        </div>
                        <button type="button" class="add-feature-btn" onclick="addFeature()">
                            <i class="fas fa-plus"></i> Add Feature
                        </button>
                    </div>
                </div>

                <!-- Contact & Footer -->
                <div class="settings-card">
                    <h3><i class="fas fa-address-book"></i> Contact & Footer</h3>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea class="settings-input" id="address" placeholder="Your restaurant's address"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Phone</label>
                            <input type="tel" class="settings-input" id="phone" placeholder="Contact number">
                        </div>
                        <div class="form-group half">
                            <label>Email</label>
                            <input type="email" class="settings-input" id="email" placeholder="Contact email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Operating Hours</label>
                        <textarea class="settings-input" id="hours" placeholder="e.g., Mon-Fri: 9AM-10PM"></textarea>
                    </div>
                    <div class="social-links">
                        <label>Social Media Links</label>
                        <div class="form-group">
                            <div class="social-input">
                                <i class="fab fa-facebook"></i>
                                <input type="url" class="settings-input" id="facebook" placeholder="Facebook URL">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="social-input">
                                <i class="fab fa-instagram"></i>
                                <input type="url" class="settings-input" id="instagram" placeholder="Instagram URL">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="social-input">
                                <i class="fab fa-tiktok"></i>
                                <input type="url" class="settings-input" id="tiktok" placeholder="TikTok URL">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Theme & Design -->
                <div class="settings-card span-2">
                    <h3><i class="fas fa-paint-brush"></i> Theme & Design</h3>
                    <div class="form-row">
                        <div class="form-group third">
                            <label>Primary Color</label>
                            <input type="color" class="color-picker" id="primaryColor" value="#FF7F50">
                        </div>
                        <div class="form-group third">
                            <label>Secondary Color</label>
                            <input type="color" class="color-picker" id="secondaryColor" value="#FFB75E">
                        </div>
                        <div class="form-group third">
                            <label>Font Style</label>
                            <select class="settings-input" id="fontStyle">
                                <option value="Poppins">Poppins</option>
                                <option value="Roboto">Roboto</option>
                                <option value="Open Sans">Open Sans</option>
                                <option value="Montserrat">Montserrat</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Layout Style</label>
                            <select class="settings-input" id="layoutStyle">
                                <option value="centered">Centered</option>
                                <option value="full-width">Full Width</option>
                                <option value="boxed">Boxed</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label>Newsletter Signup</label>
                            <div class="toggle-switch">
                                <input type="checkbox" id="newsletterToggle">
                                <label for="newsletterToggle"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="save-section">
                <button type="button" class="save-settings" onclick="saveLandingSettings()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>

            <style>
                /* Landing Settings Styles */
                .settings-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                    padding: 20px;
                }

                .settings-card {
                    background: white;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }

                .settings-card.span-2 {
                    grid-column: span 2;
                }

                .settings-card h3 {
                    color: #333;
                    font-size: 18px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .settings-card h3 i {
                    color: #FF7F50;
                }

                .form-group {
                    margin-bottom: 20px;
                }

                .form-row {
                    display: flex;
                    gap: 20px;
                    margin-bottom: 20px;
                }

                .form-group.half {
                    flex: 1;
                }

                .form-group.third {
                    flex: 1;
                }

                label {
                    display: block;
                    margin-bottom: 8px;
                    color: #555;
                    font-weight: 500;
                }

                .settings-input {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    font-size: 14px;
                    transition: all 0.3s ease;
                }

                .settings-input:focus {
                    border-color: #FF7F50;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(255, 127, 80, 0.1);
                }

                textarea.settings-input {
                    resize: vertical;
                    min-height: 80px;
                }

                .upload-container {
                    border: 2px dashed #ddd;
                    border-radius: 8px;
                    padding: 20px;
                    text-align: center;
                    position: relative;
                    transition: all 0.3s ease;
                }

                .upload-container:hover {
                    border-color: #FF7F50;
                }

                .upload-container.small {
                    padding: 10px;
                }

                .upload-container.hero {
                    aspect-ratio: 16/9;
                }

                .upload-container img {
                    max-width: 100%;
                    max-height: 200px;
                    object-fit: contain;
                    margin-bottom: 10px;
                }

                .upload-container.small img {
                    max-height: 60px;
                }

                .upload-container.hero img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .file-input {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    opacity: 0;
                    cursor: pointer;
                }

                .upload-label {
                    color: #666;
                }

                .upload-label i {
                    font-size: 24px;
                    color: #FF7F50;
                    margin-bottom: 8px;
                }

                .browse-text {
                    color: #FF7F50;
                    text-decoration: underline;
                    cursor: pointer;
                }

.features-list {
                margin: 10px 0;
            }

            .total-cost-display {
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 6px;
                background: #f9fafb;
                font-size: 14px;
                min-height: 37px;
                display: flex;
                align-items: center;
            }

            [data-theme="dark"] .total-cost-display {
                background: #2d303a;
                border-color: rgba(255, 255, 255, 0.1);
                color: #fff;
            }                .feature-item {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 10px;
                }

                .add-feature-btn {
                    background: none;
                    border: 1px dashed #FF7F50;
                    color: #FF7F50;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .add-feature-btn:hover {
                    background: rgba(255, 127, 80, 0.1);
                }

                .social-input {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .social-input i {
                    font-size: 20px;
                    color: #FF7F50;
                    width: 24px;
                }

                .color-picker {
                    width: 100%;
                    height: 40px;
                    padding: 0;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                }

                .toggle-switch {
                    position: relative;
                    display: inline-block;
                    width: 60px;
                    height: 34px;
                }

                .toggle-switch input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }

                .toggle-switch label {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #ccc;
                    transition: .4s;
                    border-radius: 34px;
                }

                .toggle-switch label:before {
                    position: absolute;
                    content: "";
                    height: 26px;
                    width: 26px;
                    left: 4px;
                    bottom: 4px;
                    background-color: white;
                    transition: .4s;
                    border-radius: 50%;
                }

                .toggle-switch input:checked + label {
                    background-color: #FF7F50;
                }

                .toggle-switch input:checked + label:before {
                    transform: translateX(26px);
                }

                .save-section {
                    padding: 20px;
                    text-align: right;
                }

                .save-settings {
                    background: #FF7F50;
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 6px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }

                .save-settings:hover {
                    background: #ff6b3d;
                    transform: translateY(-1px);
                }

                .save-settings:active {
                    transform: translateY(0);
                }
            </style>
        </section>

        <!-- User Accounts Section -->
        <section id="accounts-section" class="content-section hidden">
            <!-- Users Table -->
            <div class="users-table-container">
                <h3>Registered Users</h3>
                <div class="table-actions" style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-right: auto;">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="userSearch" placeholder="Search users..." style="width: 300px;">
                        </div>
                    </div>
                    <select id="roleFilter" style="background: #FF7F50; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 500; appearance: none; -webkit-appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill=\"white\" height=\"24\" viewBox=\"0 0 24 24\" width=\"24\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M7 10l5 5 5-5z\"/></svg>'); background-repeat: no-repeat; background-position: right 8px center; min-width: 140px; transition: all 0.3s ease;">
                        <option value="all">All Roles</option>
                        <option value="2">Administrators</option>
                        <option value="3">Crew Members</option>
                        <option value="4">Customers</option>
                    </select>
                    <button onclick="showVerificationModal()" class="verification-btn" style="background: #FF7F50; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 500; transition: all 0.3s ease;">
                        <i class="fas fa-id-card"></i> ID Verification Requests
                        <span class="pending-count" style="background: white; color: #FF7F50; padding: 2px 6px; border-radius: 10px; font-size: 12px; font-weight: bold;">0</span>
                    </button>
                </div>

                <style>
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            background: #ffffff;
            transition: background-color 0.3s ease;
            visibility: visible !important;
            display: block !important;
        }
        .users-table {
            font-size: 14px;
            width: 100%;
            min-width: 1200px;
            background: transparent;
            visibility: visible !important;
            display: table !important;
        }
        .users-table th, .users-table td {
            padding: 12px 16px;
            white-space: nowrap;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.3s ease;
            visibility: visible !important;
            display: table-cell !important;
        }                [data-theme="dark"] .table-responsive {
                    background: #262833;
                    border-radius: 8px;
                    overflow: hidden;
                }

                [data-theme="dark"] .users-table {
                    border-collapse: separate;
                    border-spacing: 0 4px;
                    margin-top: -4px;
                }

                [data-theme="dark"] .users-table th {
                    background: #1E1E2D;
                    color: rgba(255, 255, 255, 0.9) !important;
                    font-weight: 500;
                    padding: 12px 16px;
                    font-size: 0.85rem;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border: none;
                }

                [data-theme="dark"] .users-table td {
                    background: #1A1B25;
                    color: rgba(255, 255, 255, 0.8);
                    padding: 16px;
                    border: none;
                }

                [data-theme="dark"] .users-table tr td:first-child {
                    border-top-left-radius: 8px;
                    border-bottom-left-radius: 8px;
                }

                [data-theme="dark"] .users-table tr td:last-child {
                    border-top-right-radius: 8px;
                    border-bottom-right-radius: 8px;
                }

                [data-theme="dark"] .users-table tr:hover td {
                    background: #1E1E2D;
                    transition: background-color 0.2s ease;
                }

                [data-theme="dark"] .role-badge {
                    padding: 6px 12px;
                    border-radius: 6px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                [data-theme="dark"] .role-badge.role-2 {
                    background: rgba(255, 127, 80, 0.15);
                    color: #FF7F50;
                }

                [data-theme="dark"] .role-badge.role-3 {
                    background: rgba(99, 102, 241, 0.15);
                    color: #818cf8;
                }

                [data-theme="dark"] .role-badge.role-4 {
                    background: rgba(16, 185, 129, 0.15);
                    color: #10b981;
                }

                .users-table-container {
                    position: relative;
                    padding: 24px;
                    border-radius: 12px;
                    margin: 15px 0;
                    border: 1px solid rgba(255, 255, 255, 0.05);
                }

                [data-theme="dark"] .users-table-container {
                    background: #1A1B25;
                    box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.1);
                }

                .users-table-container::before {
                    content: '';
                    position: absolute;
                    top: -1px;
                    left: -1px;
                    right: -1px;
                    height: 3px;
                    background: linear-gradient(90deg, #FF7F50, #FFB75E);
                    border-radius: 12px 12px 0 0;
                }

                [data-theme="dark"] .users-table-container h3 {
                    color: #ffffff;
                    margin-bottom: 24px;
                    font-size: 1.25rem;
                    font-weight: 600;
                    letter-spacing: 0.3px;
                }

        .table-actions {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .verification-btn {
            background: #FF7F50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .verification-btn:hover {
            background: #ff6b3d;
            transform: translateY(-1px);
        }

        .pending-count {
            background: #fff;
            color: #FF7F50;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }

        .verification-modal {
            width: 90%;
            max-width: 800px;
        }

        .verification-filters {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .verification-list {
            max-height: 600px;
            overflow-y: auto;
        }

        /* Orders table column widths */
        .orders-table .order-id-col { width: 100px; }
        .orders-table .customer-col { width: 200px; }
        .orders-table .address-col { width: 200px; }
        .orders-table .payment-col { width: 120px; }
        .orders-table .items-col { width: 200px; }
        .orders-table .total-col { width: 100px; }
        .orders-table .status-col { width: 100px; }
        .orders-table .time-col { width: 150px; }
        .orders-table .actions-col { width: 100px; }

        .verification-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .verification-details {
            flex: 1;
        }

        .verification-actions {
            display: flex;
            gap: 10px;
        }

        .approve-btn, .reject-btn {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .approve-btn {
            background: #4CAF50;
            color: white;
        }

        .reject-btn {
            background: #f44336;
            color: white;
        }

        .verification-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        [data-theme="dark"] .verification-modal {
            background: #2a2d3a;
        }

        [data-theme="dark"] .verification-filters {
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .verification-item {
            border-color: rgba(255, 255, 255, 0.1);
        }                [data-theme="dark"] .table-actions input,
                [data-theme="dark"] .table-actions select {
                    background: #151521;
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    color: #ffffff;
                    border-radius: 8px;
                    padding: 10px 15px;
                }

                [data-theme="dark"] .table-responsive {
                    background: transparent;
                }

                [data-theme="dark"] .role-badge.role-2 {
                    background: rgba(255, 127, 80, 0.2);
                    color: #FFB75E;
                }

                [data-theme="dark"] .role-badge {
                    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
                }

                [data-theme="dark"] .search-box input {
                    background: #151521;
                    border: 1px solid #2B2B40;
                    color: #ffffff;
                    border-radius: 6px;
                    padding: 10px 15px 10px 40px;
                }

                [data-theme="dark"] .search-box i {
                    color: #ffffff;
                }

                [data-theme="dark"] .role-filter {
                    background: #151521;
                    border: 1px solid #2B2B40;
                    color: #ffffff;
                    border-radius: 6px;
                    padding: 10px 15px;
                }

                /* Enhanced dropdown and button styles */
                #roleFilter {
                    box-shadow: 0 2px 4px rgba(255, 127, 80, 0.1);
                }

                #roleFilter:hover {
                    background: #ff6b3d;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(255, 127, 80, 0.2);
                }

                #roleFilter:focus {
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(255, 127, 80, 0.3);
                }

                /* Dark mode styles for the enhanced dropdown */
                [data-theme="dark"] #roleFilter {
                    background: #FF7F50;
                    color: white;
                    border: none;
                }

                [data-theme="dark"] #roleFilter:hover {
                    background: #ff6b3d;
                }

                [data-theme="dark"] #roleFilter option {
                    background: #2a2d3a;
                    color: white;
                    padding: 8px;
                }

                .verification-btn:hover {
                    background: #ff6b3d !important;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(255, 127, 80, 0.2);
                }

                [data-theme="dark"] .users-table-container h3 {
                    color: #ffffff;
                }
                .users-table td img {
                    display: block;
                    margin: 0 auto;
                }
                .users-table .actions {
                    width: 100px;
                }
            </style>
            <div class="table-responsive">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Profile</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users_query = "SELECT 
                                          u.id,
                                          u.FirstName,
                                          u.LastName,
                                          u.username,
                                          u.Email,
                                          u.phone,
                                          u.address,
                                          u.profile_picture,
                                          u.role_id,
                                          CASE 
                                              WHEN u.role_id = 1 THEN 'Super Admin'
                                              WHEN u.role_id = 2 THEN 'Administrator'
                                              WHEN u.role_id = 3 THEN 'Crew Member'
                                              WHEN u.role_id = 4 THEN 'Customer'
                                              ELSE 'Unknown'
                                          END as role_name
                                          FROM users u
                                          ORDER BY u.role_id ASC, u.id DESC";
                            $users_result = mysqli_query($conn, $users_query);

                            while($user = mysqli_fetch_assoc($users_result)) {
                                echo "<tr data-role='{$user['role_id']}'>";
                                echo "<td>{$user['id']}</td>";
                                echo "<td>";
                                if ($user['profile_picture']) {
                                    echo "<img src='../uploaded_img/" . htmlspecialchars($user['profile_picture']) . "' alt='Profile' style='width: 40px; height: 40px; border-radius: 50%; object-fit: cover;'>";
                                } else {
                                    echo "<img src='../images/user.png' alt='Default Profile' style='width: 40px; height: 40px; border-radius: 50%; object-fit: cover;'>";
                                }
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($user['FirstName']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['LastName']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['Email']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['phone'] ?: 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($user['address'] ?: 'N/A') . "</td>";
                                echo "<td><span class='role-badge role-{$user['role_id']}'>" . htmlspecialchars($user['role_name']) . "</span></td>";
                                echo "<td class='actions'>";
                                if($user['role_id'] != 1) { // Don't show actions for super admin
                                    echo "<button class='action-btn edit-btn' onclick='editUser({$user['id']})'><i class='fas fa-edit'></i></button>";
                                    echo "<button class='action-btn delete-btn' onclick='deleteUser({$user['id']})'><i class='fas fa-trash'></i></button>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Orders Section -->
        <section id="orders-section" class="content-section hidden">
            

            <div class="orders-container">
                <!-- Order Filters -->
                <div class="filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="orderSearch" placeholder="Search orders...">
                    </div>
                    <select id="statusFilter" class="status-filter">
                        <option value="all">All Status</option>
                        <option value="pending" selected>Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th class="order-id-col">Order ID</th>
                                <th class="customer-col">Customer</th>
                                <th class="address-col">Delivery Address</th>
                                <th class="payment-col">Payment</th>
                                <th class="items-col">Ordered Items</th>
                                <th class="total-col">Total</th>
                                <th class="status-col">Status</th>
                                <th class="time-col">Order Time</th>
                                <th class="actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Join with users table to get profile picture and user details
                        $select_orders = mysqli_query($conn, "
                            SELECT 
                                o.*,
                                u.profile_picture,
                                u.FirstName,
                                u.LastName,
                                u.Id as user_id,
                                o.item_name,
                                CONCAT(o.total_products, ' items') as quantity_text
                            FROM orders o 
                            LEFT JOIN users u ON o.name = CONCAT(u.FirstName, ' ', u.LastName)
                            ORDER BY o.order_time DESC
                        ");

                        if (mysqli_num_rows($select_orders) > 0) {
                            while ($row = mysqli_fetch_assoc($select_orders)) {
                                $statusClass = strtolower($row['status']);
                                ?>
                                <tr data-order-id="<?php echo $row['id']; ?>">
                                    <td>
                                        <span class="order-id">#<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td class="customer-info">
                                        <div class="customer-profile">
                                            <?php if ($row['profile_picture']): ?>
                                                <img src="../uploaded_img/<?php echo htmlspecialchars($row['profile_picture']); ?>" alt="Profile" onerror="this.src='../images/user.png'">
                                            <?php else: ?>
                                                <img src="../images/user.png" alt="Default Profile">
                                            <?php endif; ?>
                                            <div class="customer-details">
                                                <?php
                                                $name_parts = explode(' ', $row['name']);
                                                $firstName = array_shift($name_parts);
                                                $lastName = implode(' ', $name_parts);
                                                ?>
                                                <span class="customer-name"><?php echo htmlspecialchars($firstName); ?></span>
                                                <span class="customer-lastname"><?php echo htmlspecialchars($lastName); ?></span>
                                                <small class="customer-id">ID: <?php echo $row['user_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                                    <td>
                                        <span class="payment-badge <?php echo $row['method']; ?>">
                                            <?php echo $row['method'] === 'cod' ? 'COD' : 'GCash'; ?>
                                        </span>
                                    </td>
                                    <td class="order-items-column">
                                        <div class="order-items-details">
                                            <?php if (!empty($row['item_name'])): ?>
                                                <div class="item-name"><?php echo htmlspecialchars($row['item_name']); ?></div>
                                                <div class="item-quantity"><?php echo htmlspecialchars($row['quantity_text']); ?></div>
                                            <?php else: ?>
                                                <div class="item-quantity"><?php echo htmlspecialchars($row['quantity_text']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>₱<?php echo number_format($row['total_price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($row['order_time'])); ?></td>
                                    <td class="actions">
                                        <button class="action-btn view-btn" onclick="viewOrder(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <button class="action-btn complete-btn" onclick="completeOrder(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="9" class="no-orders">No orders found</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Order Details Modal -->
            <div id="orderModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Order Details</h3>
                        <span class="close-modal">&times;</span>
                    </div>

            <!-- Verification Requests Modal -->
            <div id="verificationModal" class="modal">
                <div class="modal-content verification-modal">
                    <div class="modal-header">
                        <h3><i class="fas fa-id-card"></i> ID Verification Requests</h3>
                        <span class="close-modal" onclick="closeVerificationModal()">&times;</span>
                    </div>
                    <div class="verification-filters">
                        <select id="verificationFilter">
                            <option value="all">All Requests</option>
                            <option value="pending" selected>Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="verification-list" id="verificationList">
                        <!-- Verification requests will be loaded here -->
                    </div>
                </div>
            </div>
                    <div class="modal-body">
                        <!-- Order details will be loaded here -->
                    </div>
                </div>
            </div>
        </section>

        <style>
        .orders-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }

        .search-box {
            flex: 1;
            max-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .search-box i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .status-filter {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 150px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        .orders-table th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #ddd;
        }

        .orders-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .orders-table td {
            font-size: 0.9rem;
            color: #444;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px;
        }

        .customer-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .customer-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .customer-name {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
            line-height: 1.2;
        }

        .customer-lastname {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.2;
        }

        .customer-id {
            color: #888;
            font-size: 0.8rem;
            margin-top: 2px;
        }

        .payment-badge.cod {
            background-color: #e3f2fd;
            color: #1976d2;
            font-size: 0.85rem;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .order-id {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #555;
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .order-items-column {
            max-width: 200px;
            padding: 12px 15px;
        }

        .order-items-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .item-name {
            font-weight: 500;
            color: #333;
            font-size: 0.95rem;
        }

        .item-quantity {
            color: #666;
            font-size: 0.85rem;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            display: inline-block;
            text-align: center;
        }

        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-badge.completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-badge.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .payment-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            background: #e9ecef;
        }

        .payment-badge.cod {
            background-color: #e3f2fd;
            color: #0d47a1;
        }

        .payment-badge.gcash {
            background-color: #2196f3;
            color: white;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .action-btn {
            padding: 4px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            font-size: 13px;
            margin: 0 2px;
        }
        
        .action-btn:hover {
            background: rgba(0,0,0,0.05);
        }

        .action-btn i {
            font-size: 14px;
        }

        .action-btn.view-btn {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .action-btn.complete-btn {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        .no-orders {
            text-align: center;
            color: #666;
            padding: 20px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .verification-btn:hover {
            background: #ff6b3d !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(255,127,80,0.2);
        }

        [data-theme="dark"] .verification-btn {
            background: #FF7F50 !important;
            color: white !important;
        }

        [data-theme="dark"] .verification-btn:hover {
            background: #ff6b3d !important;
            box-shadow: 0 2px 5px rgba(255,127,80,0.4);
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 20px;
        }

        .close-modal {
            cursor: pointer;
            font-size: 1.5rem;
        }

        .close-modal:hover {
            color: #666;
        }
        </style>
            
        </section>

        <!-- Settings Section -->
        <section id="settings-section" class="content-section hidden">
            <h2>Settings</h2>
            <p>Manage system preferences.</p>
            <label class="switch">
                <input type="checkbox" id="dark-mode-toggle" onclick="toggleDarkMode()">
                <span class="slider"></span>
            </label>
            <p id="dark-mode-status">Dark mode is off</p>
        </section>
    </div>

    <script>
        // Settings section functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Settings initialization code here
                });
            }
        });

        // Notification function
        // Function to update section title and icon
        function updateSectionTitle(section) {
            const titleElement = document.querySelector('.header-title h1');
            const iconElement = document.querySelector('.header-title i');
            
            // Define section titles and icons
            const sectionInfo = {
                'dashboard': { title: 'Dashboard', icon: 'fas fa-chart-pie' },
                'inventory': { title: 'Inventory Management', icon: 'fas fa-boxes' },
                'menu-creation': { title: 'Products', icon: 'fas fa-utensils' },
                'roles': { title: 'User Roles', icon: 'fas fa-user-shield' },
                'accounts': { title: 'User Accounts', icon: 'fas fa-users-cog' },
                'reports': { title: 'Reports', icon: 'fas fa-chart-line' },
                'orders': { title: 'Order Management', icon: 'fas fa-shopping-basket' },
                'landing': { title: 'Landing Settings', icon: 'fas fa-home' }
            };

            // Store the current section in sessionStorage
            sessionStorage.setItem('currentSection', section);

            const info = sectionInfo[section] || sectionInfo['dashboard'];
            
            // Update title and icon
            titleElement.textContent = info.title;
            iconElement.className = info.icon;
        }

        // Add event listeners to menu items
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.menu-item a');
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.getAttribute('data-section');
                    if (section) {
                        updateSectionTitle(section);
                    }
                });
            });
        });



        // Form validation function
        function validateForm() {
            const form = document.getElementById('createUserForm');
            const password = form.querySelector('#password').value;
            const confirmPassword = form.querySelector('#confirmPassword').value;
            const roleId = form.querySelector('#roleId').value;
            const firstName = form.querySelector('#firstName').value;
            const lastName = form.querySelector('#lastName').value;
            const username = form.querySelector('#username').value;
            const email = form.querySelector('#email').value;

            console.log('Form submission started');
            console.log('Form data:', {
                firstName: firstName,
                lastName: lastName,
                username: username,
                email: email,
                roleId: roleId
            });

            if (!firstName || !lastName || !username || !email || !password || !confirmPassword || !roleId) {
                showNotification('Error', 'All fields are required', 'error');
                return false;
            }

            if (password !== confirmPassword) {
                showNotification('Error', 'Passwords do not match', 'error');
                return false;
            }

            // Add hidden input for debug purposes
            const debug = document.createElement('input');
            debug.type = 'hidden';
            debug.name = 'debug_info';
            debug.value = JSON.stringify({
                formSubmitted: true,
                timestamp: new Date().toISOString()
            });
            form.appendChild(debug);

            return true;
        }

        // Function to show create user form
        function showCreateUserForm(roleId) {
            document.getElementById('roleId').value = roleId;
            document.getElementById('createUserModal').style.display = 'block';
            
            // Show/hide admin permissions section based on role
            const adminPermissions = document.getElementById('adminPermissions');
            if(adminPermissions) {
                adminPermissions.style.display = roleId === 2 ? 'block' : 'none';
            }
        }

        // Function to hide create user form
        function hideCreateUserForm() {
            document.getElementById('createUserModal').style.display = 'none';
            document.getElementById('createUserForm').reset();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('createUserModal');
            if (event.target === modal) {
                hideCreateUserForm();
            }
        }

        function showSection(sectionId) {
            // Map section IDs to their corresponding icons and titles
            const sectionMap = {
                'dashboard': { icon: 'chart-pie', title: 'Dashboard' },
                'inventory': { icon: 'boxes', title: 'Inventory Management' },
                'menu-creation': { icon: 'plus', title: 'Add New Product' },
                'restocking': { icon: 'box', title: 'Products' },
                'orders': { icon: 'shopping-cart', title: 'Orders' },
                'roles': { icon: 'user-shield', title: 'User Roles' },
                'accounts': { icon: 'users-cog', title: 'User Accounts' },
                'landing': { icon: 'home', title: 'Landing Settings' },
                'reports': { icon: 'chart-line', title: 'Reports & Analytics' }
            };

            // Get section info
            const sectionInfo = sectionMap[sectionId] || sectionMap['dashboard'];

            // Update header title - IMPORTANT: This must be done first
            const headerTitleDiv = document.getElementById('section-title');
            if (headerTitleDiv) {
                headerTitleDiv.innerHTML = `
                    <i class="fas fa-${sectionInfo.icon}"></i>
                    <h1>${sectionInfo.title}</h1>
                `;
                headerTitleDiv.style.display = 'flex';
            }

            // Remove active class from all sections and hide them
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
                section.style.display = 'none';
            });
            
            // Remove active class from all menu items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });

            // Add active class to current menu item
            const menuItem = document.getElementById(`${sectionId}-item`);
            if (menuItem) {
                menuItem.classList.add('active');
            }
            
            // Show current section
            const fullSectionId = sectionId.endsWith('-section') ? sectionId : `${sectionId}-section`;
            const sectionToShow = document.getElementById(fullSectionId);
            if (sectionToShow) {
                sectionToShow.classList.add('active');
                sectionToShow.style.display = 'block';
                
                // Update verification count when showing User Accounts section
                if (sectionId === 'accounts') {
                    updateVerificationCount();
                }
            }
            
            // Update URL without reloading
            const newUrl = window.location.pathname + '?section=' + sectionId;
            window.history.pushState({ section: sectionId }, '', newUrl);
        }

        // Initialize dashboard as default section
        // Stock Management Functions
function updateStock(productId) {
    // Fetch current stock information
    $.ajax({
        url: 'get_product_stock.php',
        type: 'GET',
        data: { id: productId },
        success: function(response) {
            const product = JSON.parse(response);
            document.getElementById('product_id').value = product.id;
            document.getElementById('current_stock').value = product.stock;
            document.getElementById('stockUpdateModal').style.display = 'block';
        },
        error: function() {
            showNotification('Error', 'Failed to fetch product details', 'error');
        }
    });
}

function closeStockModal() {
    document.getElementById('stockUpdateModal').style.display = 'none';
    document.getElementById('stockUpdateForm').reset();
}

// Handle stock update form submission
document.getElementById('stockUpdateForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    $.ajax({
        url: 'update_product_stock.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if(result.success) {
                    showNotification('Success', 'Stock updated successfully', 'success');
                    closeStockModal();
                    // Reload the page to reflect changes
                    location.reload();
                } else {
                    showNotification('Error', result.error || 'Failed to update stock', 'error');
                }
            } catch(e) {
                notifications.showError('Error', 'Unexpected error occurred');
            }
        },
        error: function() {
            showNotification('Error', 'Failed to update stock', 'error');
        }
    });
});

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section');
            
            // Get stored section from sessionStorage or default to dashboard
            let storedSection = sessionStorage.getItem('currentSection');
            if (!storedSection && !sessionStorage.getItem('initialized')) {
                storedSection = 'dashboard-section';
                sessionStorage.setItem('initialized', 'true');
            }
            const currentSection = section || storedSection || 'dashboard';
            
            // Show the appropriate section and update header
            showSection(currentSection);
            
            // If no specific section is requested, update URL to dashboard
            if (!section && !storedSection) {
                const newUrl = window.location.pathname + '?section=dashboard';
                window.history.pushState({ section: 'dashboard' }, '', newUrl);
            }

            // Handle browser back/forward navigation
            window.addEventListener('popstate', function(event) {
                const params = new URLSearchParams(window.location.search);
                const section = params.get('section') || 'dashboard';
                showSection(section);
            });            // Add popstate event listener to handle browser back/forward
            window.addEventListener('popstate', function(event) {
                const urlParams = new URLSearchParams(window.location.search);
                const section = urlParams.get('section') || 'dashboard';
                showSection(section);
            });
        });

        // Theme persistence
        function setTheme(isDark) {
            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }

        function loadTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            setTheme(savedTheme === 'dark');
        }

        // Call this on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTheme();
        });

        function toggleDarkMode() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme === 'dark');
        }

        // Close edit form
        document.getElementById('close-edit').onclick = () => {
            document.querySelector('.edit-form-container').style.display = 'none';
        }
    </script>
     <script>
        // Redirect function
        function navigateTo(page) {
            window.location.href = page;
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/header-manager.js"></script>
<script>
    // Initialize Revenue Chart
    document.addEventListener('DOMContentLoaded', function() {
        const revenueChart = document.getElementById('revenueChart');
        if (revenueChart) {
            // Get the current month and year
            const now = new Date();
            const currentMonth = now.getMonth();
            const currentYear = now.getFullYear();

            // Generate labels for the last 12 months
            // Get the real data from the canvas element
            const revenueData = JSON.parse(revenueChart.dataset.revenue);
            const monthLabels = JSON.parse(revenueChart.dataset.labels);

            new Chart(revenueChart, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Revenue',
                        data: revenueData,
                        fill: true,
                        borderColor: '#FF7F50',
                        backgroundColor: 'rgba(255, 183, 94, 0.1)',
                        tension: 0.4,
                        borderWidth: 2,
                        pointBackgroundColor: '#FF7F50',
                        pointBorderColor: '#FF7F50',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: '#FF7F50',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.raw.toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            border: {
                                display: false
                            },
                            ticks: {
                                padding: 10,
                                color: '#666',
                                font: {
                                    size: 11
                                },
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            },
                            ticks: {
                                padding: 10,
                                color: '#666',
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    elements: {
                        line: {
                            borderJoinStyle: 'round'
                        }
                    },
                    layout: {
                        padding: {
                            top: 20,
                            right: 20,
                            bottom: 20,
                            left: 20
                        }
                    }
                }
            });
        }

        // Add event listener for period change
        const chartPeriod = document.querySelector('.chart-period');
        if (chartPeriod) {
            chartPeriod.addEventListener('change', function(e) {
                const period = e.target.value;
                fetch(`get_revenue_data.php?period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        // Update the chart with new data
                        chart.data.labels = data.labels;
                        chart.data.datasets[0].data = data.revenue;
                        chart.update();
                    })
                    .catch(error => console.error('Error:', error));
            });
        }
    });
<script>
// Order search functionality
document.getElementById('orderSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.orders-table tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Status filter functionality
document.getElementById('statusFilter')?.addEventListener('change', function(e) {
    const status = e.target.value;
    document.querySelectorAll('.orders-table tbody tr').forEach(row => {
        if (status === 'all') {
            row.style.display = '';
        } else {
            const rowStatus = row.querySelector('.status-badge').textContent.toLowerCase();
            row.style.display = rowStatus === status ? '' : 'none';
        }
    });
});

// Complete order function
function completeOrder(orderId) {
    if (!confirm('Are you sure you want to complete this order?')) return;

    $.ajax({
        url: 'complet_orders.php',
        type: 'GET',
        data: { id: orderId },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    showNotification('Success', 'Order completed successfully!', 'success');
                    // Update the status badge
                    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                    const statusBadge = row.querySelector('.status-badge');
                    statusBadge.className = 'status-badge completed';
                    statusBadge.textContent = 'Completed';
                    // Remove the complete button
                    row.querySelector('.complete-btn')?.remove();
                } else {
                    showNotification('Error', result.error || 'Unable to complete the order.', 'error');
                }
            } catch (e) {
                notifications.showError('Error', 'Unexpected error occurred');
            }
        },
        error: function() {
            showNotification('Error', 'Failed to send request', 'error');
        }
    });
}

// View order details
function viewOrder(orderId) {
    const modal = document.getElementById('orderModal');
    const modalBody = modal.querySelector('.modal-body');
    
    // Show loading state
    modalBody.innerHTML = '<div class="loading">Loading...</div>';
    modal.style.display = 'block';

    // Fetch order details
    $.ajax({
        url: 'get_order_details.php',
        type: 'GET',
        data: { id: orderId },
        success: function(response) {
            try {
                const order = JSON.parse(response);
                modalBody.innerHTML = `
                    <div class="order-details">
                        <div class="order-header">
                            <h4>Order #${order.id}</h4>
                            <span class="status-badge ${order.status}">${order.status}</span>
                        </div>
                        <div class="customer-details">
                            <h5>Customer Information</h5>
                            <p><strong>Name:</strong> ${order.name}</p>
                            <p><strong>Address:</strong> ${order.address}</p>
                            <p><strong>Payment Method:</strong> ${order.method}</p>
                        </div>
                        <div class="order-items">
                            <h5>Order Summary</h5>
                            <p><strong>Total Items:</strong> ${order.total_products}</p>
                            <p><strong>Total Amount:</strong> ₱${order.total_price}</p>
                            <p><strong>Order Date:</strong> ${new Date(order.order_time).toLocaleString()}</p>
                        </div>
                    </div>
                `;
            } catch (e) {
                modalBody.innerHTML = '<div class="error">Error loading order details</div>';
            }
        },
        error: function() {
            modalBody.innerHTML = '<div class="error">Failed to load order details</div>';
        }
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('orderModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Close modal with × button
document.querySelector('.close-modal')?.addEventListener('click', function() {
    document.getElementById('orderModal').style.display = 'none';
});
</script>

<script src="js/user-roles.js"></script>

<script>


// Function to handle AJAX responses
function handleAjaxResponse(response, action) {
    if (response.success) {
        showNotification('Success', `Item ${action} successfully`, 'success');
    } else {
        showNotification('Error', response.error || `Failed to ${action} item`, 'error');
    }
}

// Update message handling for form submissions
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        // Don't prevent form submission, but prepare for notification
        const action = this.querySelector('[name="add_product"]') ? 'created' : 
                      this.querySelector('[name="update_product"]') ? 'updated' : 'processed';
        
        // We'll show notification after the form submits and page reloads
        sessionStorage.setItem('pendingNotification', JSON.stringify({
            action: action,
            timestamp: Date.now()
        }));
    });
});

// Check for pending notifications on page load
window.addEventListener('load', function() {
    const pending = sessionStorage.getItem('pendingNotification');
    if (pending) {
        const {action, timestamp} = JSON.parse(pending);
        if (Date.now() - timestamp < 1000) { // Only show if recent
            showNotification('Success', `Item ${action} successfully`, 'success');
        }
        sessionStorage.removeItem('pendingNotification');
    }
});
</script>

        <script>
            function showOutOfStockModal() {
                // Check if modal has already been shown in this session
                if (sessionStorage.getItem('outOfStockModalShown')) {
                    return;
                }

                const outOfStockList = document.getElementById('outOfStockList');
                const tableRows = document.querySelectorAll('.inventory-table tbody tr');
                let outOfStockItems = [];
                
                // Don't show overlay or blur until we confirm there are out-of-stock items

                tableRows.forEach(row => {
                    // Clean up the stock text and handle potential NaN values
                    const stockText = row.querySelector('td:nth-child(4)').textContent.trim();
                    const stock = parseInt(stockText);
                    
                    // Check if stock is actually 0 (not NaN) and add to outOfStockItems
                    if (!isNaN(stock) && stock === 0) {
                        const productName = row.querySelector('td:nth-child(2)').textContent.trim();
                        const category = row.querySelector('td:nth-child(3)').textContent.trim();
                        outOfStockItems.push({ name: productName, category: category });
                    }
                });

                // Only show modal and effects if there are actually out of stock items
                if (outOfStockItems.length > 0) {
                    // Now show overlay and blur effects
                    document.getElementById('pageOverlay').style.display = 'block';
                    document.getElementById('mainContent').style.filter = 'blur(4px)';
                    const alert = document.getElementById('outOfStockAlert');
                    
                    let listHTML = '';
                    outOfStockItems.forEach(item => {
                        listHTML += `
                            <div style="padding: 8px 0; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f0f0f0;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-weight: 500;">${item.name}</span>
                                    <span style="color: #666; font-size: 0.9em;"> - ${item.category}</span>
                                </div>
                                <span style="color: #FF4444; font-size: 0.85em;">Out of Stock</span>
                            </div>`;
                    });
                    outOfStockList.innerHTML = listHTML;
                    alert.style.display = 'block';
                }
            }

            function closeOutOfStockModal() {
                const alert = document.getElementById('outOfStockAlert');
                const overlay = document.getElementById('pageOverlay');
                const mainContent = document.getElementById('mainContent');
                
                // Set flag in sessionStorage to prevent reshowing
                sessionStorage.setItem('outOfStockModalShown', 'true');
                
                alert.style.opacity = '0';
                overlay.style.opacity = '0';
                mainContent.style.filter = 'none';
                
                setTimeout(() => {
                    alert.style.display = 'none';
                    overlay.style.display = 'none';
                    alert.style.opacity = '1';
                    overlay.style.opacity = '1';
                }, 300);
            }

            function closeCriticalStockModal() {
                const alert = document.getElementById('criticalStockAlert');
                const overlay = document.getElementById('pageOverlay');
                const mainContent = document.getElementById('mainContent');
                
                // Set flag in sessionStorage to prevent reshowing
                sessionStorage.setItem('criticalStockModalShown', 'true');
                
                alert.style.opacity = '0';
                overlay.style.opacity = '0';
                mainContent.style.filter = 'none';
                
                setTimeout(() => {
                    alert.style.display = 'none';
                    overlay.style.display = 'none';
                    alert.style.opacity = '1';
                    overlay.style.opacity = '1';
                }, 300);
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Show the modal when page loads
                showOutOfStockModal();

                const categoryFilter = document.getElementById('categoryFilter');
                const statusFilter = document.getElementById('statusFilter');
                const tableRows = document.querySelectorAll('.inventory-table tbody tr');

                function filterTable() {
                    const selectedCategory = categoryFilter.value;
                    const selectedStatus = statusFilter.value;

                    tableRows.forEach(row => {
                        const category = row.querySelector('td:nth-child(3)').textContent.trim();
                        const stockCell = row.querySelector('td:nth-child(4)');
                        const statusCell = row.querySelector('td:nth-child(6) .status-badge');
                        const stock = parseInt(stockCell.textContent);
                        const status = statusCell.textContent.trim();

                        let showRow = true;

                        if (selectedCategory !== 'all' && category !== selectedCategory) {
                            showRow = false;
                        }

                        if (selectedStatus !== 'all') {
                            switch(selectedStatus) {
                                case 'out':
                                    if (status !== 'Out of Stock') showRow = false;
                                    break;
                                case 'low':
                                    if (stock > 10 || stock <= 0) showRow = false;
                                    break;
                                case 'in':
                                    if (stock <= 0) showRow = false;
                                    break;
                            }
                        }

                        row.style.display = showRow ? '' : 'none';
                    });
                }

                categoryFilter.addEventListener('change', filterTable);
                statusFilter.addEventListener('change', filterTable);

                // Add search functionality
                const searchInput = document.getElementById('inventorySearch');
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();

                    tableRows.forEach(row => {
                        const productName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                        const category = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                        const showRow = productName.includes(searchTerm) || category.includes(searchTerm);
                        
                        // Consider current filter selections
                        if (showRow) {
                            filterTable();
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });

            function handleLogout(event) {
                event.preventDefault();
                window.location.href = '../logout.php';
            }

            function toggleNotifications() {
                const dropdown = document.getElementById('notificationDropdown');
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                if (dropdown.style.display === 'block') {
                    updateNotifications();
                }
            }

            // Store read notifications
            let readNotifications = new Set();

            function markAllAsRead() {
                // Clear all notifications
                document.getElementById('notifCount').style.display = 'none';
                document.getElementById('notificationList').innerHTML = '<div style="padding: 15px; color: #666; text-align: center;">No new notifications</div>';
                document.getElementById('markAllBtn').style.display = 'none';
                
                // Make an AJAX call to store read status in session
                fetch('mark_notifications_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'mark_all_read' })
                });
                
                // Clear local notifications
                readNotifications = new Set();
                // Store in session storage to persist during page navigation
                sessionStorage.setItem('notificationsRead', 'true');
            }

            function updateNotifications() {
                // Check if notifications were marked as read
                if (sessionStorage.getItem('notificationsRead') === 'true') {
                    document.getElementById('notifCount').style.display = 'none';
                    document.getElementById('notificationList').innerHTML = '<div style="padding: 15px; color: #666; text-align: center;">No new notifications</div>';
                    document.getElementById('markAllBtn').style.display = 'none';
                    return;
                }

                const tableRows = document.querySelectorAll('.inventory-table tbody tr');
                let notifications = [];
                let count = 0;

                tableRows.forEach(row => {
                    const productName = row.querySelector('td:nth-child(2)').textContent.trim();
                    const category = row.querySelector('td:nth-child(3)').textContent.trim();
                    const stock = parseInt(row.querySelector('td:nth-child(4)').textContent);
                    
                    if (stock === 0) {
                        notifications.push({ name: productName, category: category, type: 'out', stock: stock });
                        count++;
                    } else if (stock <= 10 && stock > 0) {
                        notifications.push({ name: productName, category: category, type: 'critical', stock: stock });
                        count++;
                    }
                });

                // Update notification count
                document.getElementById('notifCount').textContent = count;
                
                // Update notification list
                const notificationList = document.getElementById('notificationList');
                let listHTML = '';
                
                // Filter out read notifications
                notifications = notifications.filter(item => !readNotifications.has(item.name));
                
                // Update notification count badge
                const notifCount = document.getElementById('notifCount');
                if (notifications.length > 0) {
                    notifCount.textContent = notifications.length;
                    notifCount.style.display = 'flex';
                    document.getElementById('markAllBtn').style.display = 'block';
                } else {
                    notifCount.style.display = 'none';
                    document.getElementById('markAllBtn').style.display = 'none';
                }

                if (notifications.length === 0) {
                    const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
                    listHTML = `<div style="padding: 15px; color: ${isDarkMode ? '#e1e1e1' : '#666'}; text-align: center; background: ${isDarkMode ? '#1e2028' : 'white'};">No new notifications</div>`;
                } else {
                    const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
                    notifications.forEach(item => {
                        const isOutOfStock = item.type === 'out';
                        const isRead = readNotifications.has(item.name);
                        
                        // Define colors based on theme
                        let backgroundColor, titleColor, categoryColor, statusColor, borderColor;
                        
                        if (isDarkMode) {
                            backgroundColor = '#1e2028';
                            titleColor = '#e1e1e1';
                            categoryColor = '#b0b0b0';
                            statusColor = isOutOfStock ? '#ff6b6b' : '#ffb84d';
                            borderColor = '#2a2a33';
                        } else {
                            backgroundColor = isRead ? '#f8f9fa' : (isOutOfStock ? '#fff5f5' : '#fff8e6');
                            titleColor = '#333';
                            categoryColor = '#666';
                            statusColor = isRead ? '#999' : (isOutOfStock ? '#dc3545' : '#ffa500');
                            borderColor = '#f0f0f0';
                        }
                        
                        const status = isOutOfStock ? 'Out of Stock' : 'Critical Stock';
                        
                        listHTML += `
                            <div class="notification-item" style="padding: 12px 15px; border-bottom: 1px solid ${borderColor}; background: ${backgroundColor};">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 500; color: ${titleColor};">${item.name}</div>
                                        <div style="font-size: 0.85em; color: ${categoryColor};">${item.category}</div>
                                    </div>
                                    <div style="color: ${statusColor}; font-size: 0.85em; font-weight: 500;">${status}</div>
                                </div>
                            </div>`;
                    });
                }
                
                notificationList.innerHTML = listHTML;
            }

            // Initial update of notifications
            document.addEventListener('DOMContentLoaded', function() {
                // Check if notifications were marked as read
                if (sessionStorage.getItem('notificationsRead') === 'true') {
                    document.getElementById('notifCount').style.display = 'none';
                    document.getElementById('notificationList').innerHTML = '<div style="padding: 15px; color: #666; text-align: center;">No new notifications</div>';
                    document.getElementById('markAllBtn').style.display = 'none';
                } else {
                    updateNotifications();
                }
            });

            // Theme Management System
            const ThemeManager = {
                STORAGE_KEY: 'admin-theme-preference',
                DARK_THEME: 'dark',
                LIGHT_THEME: 'light',

                init() {
                    this.htmlElement = document.documentElement;
                    this.themeIcon = document.getElementById('themeIcon');
                    this.setupSystemPreferenceListener();
                    this.loadAndApplyTheme();
                },

                setupSystemPreferenceListener() {
                    window.matchMedia('(prefers-color-scheme: dark)').addListener(e => {
                        if (!localStorage.getItem(this.STORAGE_KEY)) {
                            this.setTheme(e.matches ? this.DARK_THEME : this.LIGHT_THEME, true);
                        }
                    });
                },

                loadAndApplyTheme() {
                    const savedTheme = localStorage.getItem(this.STORAGE_KEY);
                    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const themeToApply = savedTheme || (systemPrefersDark ? this.DARK_THEME : this.LIGHT_THEME);
                    
                    this.setTheme(themeToApply, true);
                },

                setTheme(theme, isInitial = false) {
                    const isDark = theme === this.DARK_THEME;
                    
                    // Ensure table content remains visible
                    const tables = document.querySelectorAll('.users-table');
                    tables.forEach(table => {
                        // Force table to remain visible
                        table.style.visibility = 'visible';
                        table.style.display = 'table';
                        
                        // Ensure all cells are visible
                        table.querySelectorAll('th, td').forEach(cell => {
                            cell.style.visibility = 'visible';
                            cell.style.display = 'table-cell';
                        });
                    });
                    
                    // Update DOM
                    if (isDark) {
                        this.htmlElement.setAttribute('data-theme', 'dark');
                        this.themeIcon.className = 'fas fa-sun';
                        document.body.style.backgroundColor = 'var(--bg-primary)';
                    } else {
                        this.htmlElement.removeAttribute('data-theme');
                        this.themeIcon.className = 'fas fa-moon';
                        document.body.style.backgroundColor = '#ffffff';
                    }

                    // Save preference
                    localStorage.setItem(this.STORAGE_KEY, theme);

                    // Update charts if they exist
                    if (window.revenueChart) {
                        window.revenueChart.update();
                    }

                    // Force table redraw after theme change
                    requestAnimationFrame(() => {
                        tables.forEach((table, tableIndex) => {
                            const rows = table.querySelectorAll('tr');
                            rows.forEach((row, rowIndex) => {
                                const state = tableStates[tableIndex].rows[rowIndex];
                                if (state) {
                                    row.style.display = state.display;
                                    if (state.innerHTML && row.innerHTML !== state.innerHTML) {
                                        row.innerHTML = state.innerHTML;
                                    }
                                }
                            });
                        });
                        
                        // Trigger reflow
                        tables.forEach(table => {
                            table.style.display = 'none';
                            table.offsetHeight; // Force reflow
                            table.style.display = '';
                        });
                    });

                    // Preserve and redraw table content
                    requestAnimationFrame(() => {
                        const tables = document.querySelectorAll('.users-table');
                        tables.forEach(table => {
                            // Cache the table content
                            const content = table.innerHTML;
                            
                            // Force reflow while preserving content
                            table.style.opacity = '0';
                            table.innerHTML = content;
                            table.offsetHeight;
                            table.style.opacity = '1';
                            
                            // Ensure all cells are visible
                            table.querySelectorAll('td').forEach(cell => {
                                cell.style.visibility = 'visible';
                                cell.style.display = '';
                            });
                        });
                    });

                    // Show notification unless it's the initial load
                    if (!isInitial) {
                        notifications.showSuccess(
                            'Theme Updated',
                            `Switched to ${isDark ? 'dark' : 'light'} mode`
                        );
                    }

                    // Dispatch theme change event
                    const event = new CustomEvent('themechange', { 
                        detail: { theme, isInitial } 
                    });
                    document.dispatchEvent(event);

                    // Update meta theme color for mobile browsers
                    const metaThemeColor = document.querySelector('meta[name="theme-color"]');
                    if (metaThemeColor) {
                        metaThemeColor.setAttribute('content', isDark ? '#1a1a1a' : '#ffffff');
                    }
                },

                toggle() {
                    const currentTheme = this.htmlElement.getAttribute('data-theme');
                    this.setTheme(currentTheme === this.DARK_THEME ? this.LIGHT_THEME : this.DARK_THEME);
                }
            };

            // Initialize theme management
            document.addEventListener('DOMContentLoaded', () => {
                ThemeManager.init();
                
                // Configure Chart.js defaults for dark mode
                const updateChartTheme = (isDark) => {
                    Chart.defaults.color = isDark ? 'rgba(255, 255, 255, 0.7)' : '#666666';
                    Chart.defaults.borderColor = isDark ? 'rgba(255, 255, 255, 0.1)' : '#e5e7eb';
                    Chart.defaults.backgroundColor = isDark ? 'rgba(255, 127, 80, 0.1)' : 'rgba(255, 183, 94, 0.1)';
                    
                    // Update existing charts
                    Chart.instances.forEach(chart => {
                        chart.options.scales.x.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : '#e5e7eb';
                        chart.options.scales.y.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : '#e5e7eb';
                        chart.options.scales.x.ticks.color = isDark ? 'rgba(255, 255, 255, 0.7)' : '#666666';
                        chart.options.scales.y.ticks.color = isDark ? 'rgba(255, 255, 255, 0.7)' : '#666666';
                        chart.update();
                    });
                };

                // Listen for theme changes
                document.addEventListener('themechange', (e) => {
                    updateChartTheme(e.detail.theme === 'dark');
                });

                // Initial chart theme setup
                updateChartTheme(document.documentElement.getAttribute('data-theme') === 'dark');
            });

            // Theme toggle function
            function toggleDarkMode() {
                ThemeManager.toggle();
            }

        // Initialize table and theme
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure table is visible on load
            const tables = document.querySelectorAll('.users-table');
            tables.forEach(table => {
                table.style.visibility = 'visible';
                table.style.display = 'table';
                table.querySelectorAll('th, td').forEach(cell => {
                    cell.style.visibility = 'visible';
                    cell.style.display = 'table-cell';
                });
            });

            // Get saved theme or system preference
            const savedTheme = localStorage.getItem('theme') || 
                             (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            const themeIcon = document.getElementById('themeIcon');                if (savedTheme === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    themeIcon.className = 'fas fa-sun';
                    document.body.style.backgroundColor = 'var(--background-primary)';
                }

                // Listen for system theme changes
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                    if (!localStorage.getItem('theme')) { // Only if user hasn't manually set theme
                        const newTheme = e.matches ? 'dark' : 'light';
                        document.documentElement.setAttribute('data-theme', newTheme);
                        themeIcon.className = `fas fa-${newTheme === 'dark' ? 'sun' : 'moon'}`;
                        document.body.style.backgroundColor = newTheme === 'dark' ? 'var(--background-primary)' : '#ffffff';
                    }
                });

                // Add smooth transitions for theme changes
                const styleSheet = document.createElement('style');
                styleSheet.textContent = `
                    * {
                        transition: background-color 0.3s ease, 
                                  color 0.3s ease, 
                                  border-color 0.3s ease, 
                                  box-shadow 0.3s ease !important;
                    }
                `;
                document.head.appendChild(styleSheet);
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dropdown = document.getElementById('notificationDropdown');
                const notificationIcon = document.querySelector('.notification-icon');
                
                if (!notificationIcon.contains(event.target) && !dropdown.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            });
        </script>

        <!-- Landing Settings JavaScript -->
        <script>
            // Image upload preview functionality
            function setupImagePreview(inputId, previewId) {
                const input = document.getElementById(inputId);
                const preview = document.getElementById(previewId);

                if (input && preview) {
                    input.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                preview.src = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
            }

            // Initialize image previews
            document.addEventListener('DOMContentLoaded', function() {
                setupImagePreview('logoUpload', 'logoPreview');
                setupImagePreview('faviconUpload', 'faviconPreview');
                setupImagePreview('heroUpload', 'heroPreview');
            });

            // Feature management
            function addFeature() {
                const featuresList = document.getElementById('featuresList');
                const featureCount = featuresList.children.length;

                const featureItem = document.createElement('div');
                featureItem.className = 'feature-item';
                featureItem.innerHTML = `
                    <input type="text" class="settings-input" placeholder="Feature ${featureCount + 1}">
                    <button type="button" class="remove-feature" onclick="removeFeature(this)" style="background: none; border: none; color: #FF7F50; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                featuresList.appendChild(featureItem);
            }

            function removeFeature(button) {
                button.closest('.feature-item').remove();
            }

            // Save settings
            function saveLandingSettings() {
                const formData = new FormData();
                
                // Show loading notification
                notifications.showSuccess('Info', 'Saving settings...');

                // Add file uploads
                const logoFile = document.getElementById('logoUpload').files[0];
                const faviconFile = document.getElementById('faviconUpload').files[0];
                const heroFile = document.getElementById('heroUpload').files[0];

                if (logoFile) formData.append('logo', logoFile);
                if (faviconFile) formData.append('favicon', faviconFile);
                if (heroFile) formData.append('hero_image', heroFile);

                // Add text inputs
                const settings = {
                    branding: {
                        restaurantName: document.getElementById('restaurantName').value,
                        tagline: document.getElementById('tagline').value
                    },
                    hero: {
                        title: document.getElementById('heroTitle').value,
                        subtitle: document.getElementById('heroSubtitle').value
                    },
                    about: {
                        story: document.getElementById('aboutUs').value,
                        features: Array.from(document.querySelectorAll('#featuresList input')).map(input => input.value)
                    },
                    contact: {
                        address: document.getElementById('address').value,
                        phone: document.getElementById('phone').value,
                        email: document.getElementById('email').value,
                        hours: document.getElementById('hours').value,
                        social: {
                            facebook: document.getElementById('facebook').value,
                            instagram: document.getElementById('instagram').value,
                            tiktok: document.getElementById('tiktok').value
                        }
                    },
                    theme: {
                        primaryColor: document.getElementById('primaryColor').value,
                        secondaryColor: document.getElementById('secondaryColor').value,
                        fontStyle: document.getElementById('fontStyle').value,
                        layoutStyle: document.getElementById('layoutStyle').value,
                        newsletter: document.getElementById('newsletterToggle').checked
                    }
                };

                formData.append('settings', JSON.stringify(settings));

                // Send settings to server
                fetch('save_landing_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        notifications.showSuccess('Success', 'Landing page settings saved successfully');
                    } else {
                        throw new Error(data.message || 'Failed to save settings');
                    }
                })
                .catch(error => {
                    notifications.showError('Error', error.message || 'Failed to save settings');
                    console.error('Error:', error);
                });
                
                // Log the data being sent for debugging
                console.log('Settings being saved:', JSON.parse(formData.get('settings')));
            }

            // Load settings
            function loadLandingSettings() {
                fetch('get_landing_settings.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const settings = data.settings;
                            
                            // Update text inputs
                            document.getElementById('restaurantName').value = settings.branding?.restaurantName || '';
                            document.getElementById('tagline').value = settings.branding?.tagline || '';
                            document.getElementById('heroTitle').value = settings.hero?.title || '';
                            document.getElementById('heroSubtitle').value = settings.hero?.subtitle || '';
                            document.getElementById('aboutUs').value = settings.about?.story || '';
                            document.getElementById('address').value = settings.contact?.address || '';
                            document.getElementById('phone').value = settings.contact?.phone || '';
                            document.getElementById('email').value = settings.contact?.email || '';
                            document.getElementById('hours').value = settings.contact?.hours || '';
                            
                            // Update social media links
                            document.getElementById('facebook').value = settings.contact?.social?.facebook || '';
                            document.getElementById('instagram').value = settings.contact?.social?.instagram || '';
                            document.getElementById('tiktok').value = settings.contact?.social?.tiktok || '';
                            
                            // Update theme settings
                            document.getElementById('primaryColor').value = settings.theme?.primaryColor || '#FF7F50';
                            document.getElementById('secondaryColor').value = settings.theme?.secondaryColor || '#FFB75E';
                            document.getElementById('fontStyle').value = settings.theme?.fontStyle || 'Poppins';
                            document.getElementById('layoutStyle').value = settings.theme?.layoutStyle || 'centered';
                            document.getElementById('newsletterToggle').checked = settings.theme?.newsletter || false;
                            
                            // Update features
                            const featuresList = document.getElementById('featuresList');
                            featuresList.innerHTML = '';
                            settings.about?.features?.forEach(feature => {
                                const featureItem = document.createElement('div');
                                featureItem.className = 'feature-item';
                                featureItem.innerHTML = `
                                    <input type="text" class="settings-input" value="${feature}">
                                    <button type="button" class="remove-feature" onclick="removeFeature(this)" style="background: none; border: none; color: #FF7F50; cursor: pointer;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `;
                                featuresList.appendChild(featureItem);
                            });
                            
                            // Update image previews if URLs are provided
                            if (settings.branding?.logoUrl) {
                                document.getElementById('logoPreview').src = settings.branding.logoUrl;
                            }
                            if (settings.branding?.faviconUrl) {
                                document.getElementById('faviconPreview').src = settings.branding.faviconUrl;
                            }
                            if (settings.hero?.imageUrl) {
                                document.getElementById('heroPreview').src = settings.hero.imageUrl;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading settings:', error);
                        notifications.showError('Error', 'Failed to load settings');
                    });
            }

            // Load settings when the landing section is shown
            document.addEventListener('DOMContentLoaded', function() {
                const landingSection = document.getElementById('landing-section');
                if (landingSection) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                if (!landingSection.classList.contains('hidden')) {
                                    loadLandingSettings();
                                }
                            }
                        });
                    });

                    observer.observe(landingSection, { attributes: true });
                }
            });

            // Handle submenu interactions
            document.addEventListener('DOMContentLoaded', function() {
                // Prevent parent menu item from being clickable
                document.querySelectorAll('.menu-item.has-submenu > a').forEach(parentLink => {
                    parentLink.addEventListener('click', function(e) {
                        e.preventDefault();
                    });
                });
                
                // Add click handlers to submenu items
                document.querySelectorAll('.submenu a').forEach(submenuLink => {
                    submenuLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        const section = this.getAttribute('data-section');
                        if (section) {
                            // Remove active class from all submenu items
                            document.querySelectorAll('.submenu li').forEach(item => {
                                item.classList.remove('active');
                            });
                            
                            // Add active class to clicked submenu item
                            this.closest('.menu-item').classList.add('active');
                            
                            showSection(section);
                        }
                    });
                });
        </script>
    </body>
</html>