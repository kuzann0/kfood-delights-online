<?php
session_start();
include "../connect.php";

// Check if user is logged in and has crew role
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) { // Assuming role_id 3 is for crew
    header("Location: ../loginpage.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Inventory</title>
    <link rel="stylesheet" href="../kfood_admin/css/inventory.css">
    <link rel="stylesheet" href="../kfood_admin/css/inventory-dark.css">
    <link rel="stylesheet" href="../kfood_admin/css/table-dark.css">
    <link rel="stylesheet" href="../kfood_admin/css/inventory-stats.css">
    <link rel="stylesheet" href="../kfood_admin/css/theme-variables.css">
    <link rel="stylesheet" href="../kfood_admin/css/dark-mode-components.css">
    <link rel="stylesheet" href="../kfood_admin/css/dark-mode.css">
    <link rel="stylesheet" href="../kfood_admin/css/dark-mode-text.css">
    <link rel="stylesheet" href="../kfood_admin/css/dark-mode-override.css">
    <link rel="stylesheet" href="../kfood_admin/css/dark-mode-final.css">
    <link rel="stylesheet" href="../kfood_admin/css/verification.css">
    <link rel="stylesheet" href="../kfood_admin/css/unified-status-badges.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../kfood_admin/js/notifications.js"></script>
    <script src="../kfood_admin/js/movement-handler.js"></script>
</head>
<body>
    <div class="inventory-section">
        <div class="section-header">
            <h2>Inventory Management</h2>
            <div class="movement-type-filter">
                <label for="movementTypeFilter">Movement Type:</label>
                <select id="movementTypeFilter" onchange="handleMovementTypeChange(this.value)">
                    <option value="fast-moving">Fast Moving</option>
                    <option value="slow-moving">Slow Moving</option>
                    <option value="non-moving">Non Moving</option>
                </select>
            </div>
        </div>

        <!-- Movement Category Card -->
        <div class="movement-card" id="movementCard">
            <h3>Stock Level Ranges</h3>
            <div id="stockRangeInfo" class="range-info">
                <!-- This will be populated by JavaScript -->
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="table-responsive">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>Unit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT p.*, np.unit_measurement 
                             FROM products p 
                             JOIN new_products np ON p.name = np.product_name 
                             ORDER BY p.name";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $stock = $row['stock'];
                            $status = '';
                            $statusClass = '';

                            // Status will be determined by JavaScript based on movement type
                            if ($stock <= 0) {
                                $status = 'Out of Stock';
                                $statusClass = 'out-of-stock';
                            }

                            echo "<tr>
                                    <td>{$row['name']}</td>
                                    <td>{$row['category']}</td>
                                    <td>₱" . number_format($row['price'], 2) . "</td>
                                    <td>{$row['stock']}</td>
                                    <td>{$row['uom']}</td>
                                    <td><span class='status-badge {$statusClass}'>{$status}</span></td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='no-records'>No products found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        /* Additional Styles */
        .inventory-section {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .movement-type-filter {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .movement-type-filter select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .movement-card {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        [data-theme="dark"] .movement-card {
            background: #2a2d3a;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .range-info {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .range-badge {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .range-badge.critical {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .range-badge.sufficient {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .range-badge.overstocked {
            background-color: #fff3e0;
            color: #f57c00;
            border: 1px solid #ffcc80;
        }

        [data-theme="dark"] .range-badge.critical {
            background-color: rgba(198, 40, 40, 0.2);
            color: #ef5350;
            border-color: rgba(198, 40, 40, 0.4);
        }

        [data-theme="dark"] .range-badge.sufficient {
            background-color: rgba(46, 125, 50, 0.2);
            color: #81c784;
            border-color: rgba(46, 125, 50, 0.4);
        }

        [data-theme="dark"] .range-badge.overstocked {
            background-color: rgba(245, 124, 0, 0.2);
            color: #ffb74d;
            border-color: rgba(245, 124, 0, 0.4);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-badge.out-of-stock {
            background-color: #ffebee;
            color: #c62828;
        }

        [data-theme="dark"] .status-badge.out-of-stock {
            background-color: rgba(198, 40, 40, 0.2);
            color: #ef5350;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .inventory-table th,
        .inventory-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        [data-theme="dark"] .inventory-table th,
        [data-theme="dark"] .inventory-table td {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        .inventory-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        [data-theme="dark"] .inventory-table th {
            background-color: #32364a;
        }

        .inventory-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        [data-theme="dark"] .inventory-table tbody tr:hover {
            background-color: #32364a;
        }

        .no-records {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        [data-theme="dark"] .no-records {
            color: #b0b0b0;
        }
    </style>

    <script>
        // Initialize with default movement type
        document.addEventListener('DOMContentLoaded', function() {
            const defaultType = 'fast-moving';
            handleMovementTypeChange(defaultType);
            document.getElementById('movementTypeFilter').value = defaultType;
        });
    </script>
</body>
</html>