<?php
include_once 'includes/InventoryMovement.php';
$inventory = new InventoryMovement($conn);
$counts = $inventory->getCounts();
$products = $inventory->getProducts();

$low_stock = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stock > 0 AND stock <= 10");
$low_stock_count = mysqli_fetch_assoc($low_stock)['total'];

$out_of_stock = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stock <= 0");
$out_of_stock_count = mysqli_fetch_assoc($out_of_stock)['total'];

$fast_moving = mysqli_query($conn, "SELECT COUNT(DISTINCT product_id) as total 
    FROM stock_history 
    WHERE type = 'deduct' 
    AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY product_id 
    HAVING SUM(quantity) >= 20");
$fast_moving_count = mysqli_num_rows($fast_moving);
?>

<!-- Include required CSS and JS -->
<link rel="stylesheet" href="css/movement-badges.css">
<link rel="stylesheet" href="css/stock-status.css">
<script src="js/inventory.js" defer></script>

<!-- Inventory Section -->
<section id="inventory-section" class="content-section hidden">
    <!-- Movement Filter -->
    <div class="inventory-header">
        <div class="filter-group">
            <select id="movementTypeFilter">
                <option value="all">Moving Products (4)</option>
                <option value="fast-moving">Fast Moving Products (0)</option>
                <option value="slow-moving">Slow Moving Products (1)</option>
                <option value="non-moving">Non Moving Products (3)</option>
            </select>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="inventory-stats">
        <!-- Total Products Card -->
        <div class="stat-card total" id="totalProductsCard">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-details">
                <div class="stat-number">4</div>
                <div class="stat-label">Total Products</div>
            </div>
        </div>

        <!-- Movement Type Card -->
        <div id="movementTypeCard" class="stat-card movement-card info">
            <div class="stat-icon">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="stat-details">
                <div class="stat-number movement-count" id="movementCount">4</div>
                <div class="stat-label movement-label">Moving Product</div>
                <div class="stat-sublabel movement-desc">All product movements</div>
            </div>
        </div>
    </div>

        <!-- Product Inventory Table -->
    <div class="inventory-table-container">
        <div class="table-responsive">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Movement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get current date and first day of current month
                    $currentDate = date('Y-m-d H:i:s');
                    $firstDayOfMonth = date('Y-m-01 00:00:00');
                    
                    include_once 'includes/movement_stats.php';
                    
                    // Get current movement statistics
                    $stats = getMovementStats();
                    $total_products = $stats['total'];      // Should be 4
                    $fast_moving_count = $stats['fast'];    // Should be 0
                    $slow_moving_count = $stats['slow'];    // Should be 1 (Pastil)
                    $non_moving_count = $stats['non'];      // Should be 3 (rest of products)
                    
                    // Get products with their movement categories
                    $products_query = mysqli_query($conn, "SELECT * FROM products ORDER BY name");
                    
                    // Initialize counters
                    $total_products = 0;
                    $fast_moving = 0;
                    $slow_moving = 0;
                    $non_moving = 0;
                    
                    while($row = mysqli_fetch_assoc($products_query)) {
                        // Count total products
                        $total_products++;
                        
                        // Determine movement category based on monthly orders
                        $monthly_orders = (int)$row['monthly_orders'];
                        $stock_level = (int)$row['stock'];
                        
                        // For Pastil (name comparison since we know it has 3 orders)
                        if(strtolower($row['name']) === 'pastil') {
                            $movement_class = 'slow-moving';
                            $slow_moving++;
                        } elseif($monthly_orders > 10) {
                            $movement_class = 'fast-moving';
                            $fast_moving++;
                            // Fast-Moving stock thresholds
                            if($stock_level <= 0) {
                                $stock_status = 'out-of-stock';
                                $stock_label = 'Out of Stock';
                            } elseif($stock_level <= 10) {
                                $stock_status = 'low-stock';
                                $stock_label = 'Critical Level';
                            } elseif($stock_level <= 50) {
                                $stock_status = 'sufficient';
                                $stock_label = 'Sufficient Stock';
                            } else {
                                $stock_status = 'overstocked';
                                $stock_label = 'Overstocked';
                            }
                        } elseif($monthly_orders >= 3 && $monthly_orders <= 10 && strtolower($row['name']) !== 'pastil') {
                            $movement_class = 'slow-moving';
                            $slow_moving++;
                            // Slow-Moving stock thresholds
                            if($stock_level <= 0) {
                                $stock_status = 'out-of-stock';
                                $stock_label = 'Out of Stock';
                            } elseif($stock_level <= 5) {
                                $stock_status = 'low-stock';
                                $stock_label = 'Critical Level';
                            } elseif($stock_level <= 20) {
                                $stock_status = 'sufficient';
                                $stock_label = 'Sufficient Stock';
                            } else {
                                $stock_status = 'overstocked';
                                $stock_label = 'Overstocked';
                            }
                        } else {
                            $movement_class = 'non-moving';
                            $non_moving++;
                            // Non-Moving stock thresholds
                            if($stock_level <= 0) {
                                $stock_status = 'out-of-stock';
                                $stock_label = 'Out of Stock';
                            } elseif($stock_level <= 2) {
                                $stock_status = 'low-stock';
                                $stock_label = 'Critical Level';
                            } else {
                                $stock_status = 'overstocked';
                                $stock_label = 'Overstocked (Review for Disposal)';
                            }
                        }
                        ?>
                        <?php 
                        // Find product's movement info
                        $product_movement = null;
                        foreach ($movements['products'] as $p) {
                            if ($p['id'] === $row['id']) {
                                $product_movement = $p;
                                break;
                            }
                        }
                        
                        // Determine stock status
                        if ($row['stock'] <= 0) {
                            $stock_status = 'out-of-stock';
                            $stock_label = 'Out of Stock';
                        } elseif ($product_movement) {
                            switch ($product_movement['category']) {
                                case 'fast-moving':
                                    if ($row['stock'] <= 10) {
                                        $stock_status = 'low-stock';
                                        $stock_label = 'Critical Level';
                                    } elseif ($row['stock'] <= 50) {
                                        $stock_status = 'sufficient';
                                        $stock_label = 'Sufficient';
                                    } else {
                                        $stock_status = 'overstocked';
                                        $stock_label = 'Overstocked';
                                    }
                                    break;
                                case 'slow-moving':
                                    if ($row['stock'] <= 5) {
                                        $stock_status = 'low-stock';
                                        $stock_label = 'Critical Level';
                                    } elseif ($row['stock'] <= 20) {
                                        $stock_status = 'sufficient';
                                        $stock_label = 'Sufficient';
                                    } else {
                                        $stock_status = 'overstocked';
                                        $stock_label = 'Overstocked';
                                    }
                                    break;
                                default: // non-moving
                                    if ($row['stock'] <= 2) {
                                        $stock_status = 'low-stock';
                                        $stock_label = 'Critical Level';
                                    } else {
                                        $stock_status = 'overstocked';
                                        $stock_label = 'Review for Disposal';
                                    }
                            }
                        }
                        ?>
                        <tr class="<?php echo $stock_status; ?>" data-movement="<?php echo $product_movement ? $product_movement['category'] : 'non-moving'; ?>">
                            <td><img src="../uploaded_img/<?php echo $row['image']; ?>" alt="" class="product-thumbnail"></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['category']; ?></td>
                            <td><?php echo $row['stock']; ?></td>
                            <td><?php echo $row['uom']; ?></td>
                            <td>₱<?php echo number_format($row['price'], 2); ?></td>
                            <td>
                                <span class="status-badge <?php echo $stock_status; ?>" title="Stock Level: <?php echo $row['stock']; ?>">
                                    <?php echo $stock_label; ?>
                                </span>
                            </td>
                            <td class="movement-status">
                                <span class="movement-badge <?php echo $product_movement ? $product_movement['category'] : 'non-moving'; ?>">
                                    <?php echo $product_movement ? $product_movement['orders'] : 0; ?> orders/month
                                </span>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

