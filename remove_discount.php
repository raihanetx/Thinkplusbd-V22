<?php
header('Content-Type: application/json');

function get_products() {
    $products_file_path = __DIR__ . '/products.json';
    if (!file_exists($products_file_path)) {
        return [];
    }
    $json_data = file_get_contents($products_file_path);
    return json_decode($json_data, true);
}

function save_products($products) {
    $products_file_path = __DIR__ . '/products.json';
    $json_data = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($products_file_path, $json_data);
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = $input['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
    exit();
}

$products = get_products();
$product_found = false;

foreach ($products as &$product) {
    if ($product['id'] == $product_id) {
        if (isset($product['discount'])) {
            unset($product['discount']);
            $product_found = true;
        }
        break;
    }
}

if ($product_found) {
    save_products($products);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found or no discount to remove.']);
}
?>
