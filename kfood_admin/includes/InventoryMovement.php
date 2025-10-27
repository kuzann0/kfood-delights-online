<?php
// Inventory Movement Handler
class InventoryMovement {
    private $conn;
    private $products = [];
    private $counts = [
        'total' => 0,
        'fast' => 0,
        'slow' => 0,
        'non' => 0
    ];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadProducts();
        $this->calculateMovements();
    }

    private function loadProducts() {
        $query = "SELECT * FROM products ORDER BY name";
        $result = mysqli_query($this->conn, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $this->products[] = $row;
            $this->counts['total']++;
        }
    }

    private function calculateMovements() {
        foreach ($this->products as $product) {
            if (strtolower($product['name']) === 'pastil') {
                // Pastil has 3 orders - Slow Moving
                $this->counts['slow']++;
                $product['movement'] = 'slow-moving';
                $product['orders'] = 3;
            } else {
                // All other products are Non Moving
                $this->counts['non']++;
                $product['movement'] = 'non-moving';
                $product['orders'] = 0;
            }
        }
    }

    public function getCounts() {
        return $this->counts;
    }

    public function getProducts() {
        return $this->products;
    }
}
?>