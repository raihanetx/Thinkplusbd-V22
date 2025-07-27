<?php
header('Content-Type: application/json');
$products_file_path = __DIR__ . '/products.json';

if (file_exists($products_file_path)) {
    $json_data = file_get_contents($products_file_path);
    $products = json_decode($json_data, true);

    foreach ($products as &$product) {
        if (isset($product['discount'])) {
            $discount = $product['discount'];
            $original_price = $product['price'];
            if ($discount['type'] === 'percentage') {
                $product['discounted_price'] = $original_price - ($original_price * $discount['value'] / 100);
            } else {
                $product['discounted_price'] = $original_price - $discount['value'];
            }
        }
    }
    echo json_encode($products);
} else {
    echo json_encode([]);
}
?>
