async function getProductStock(productId) {
    try {
        const response = await fetch(`check_stock.php?product_id=${productId}`);
        const data = await response.json();
        return data.stock || 0;
    } catch (error) {
        console.error('Error checking stock:', error);
        return 0;
    }
}