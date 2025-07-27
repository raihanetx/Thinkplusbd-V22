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
    $json_data = json_encode($products, JSON_PRETTY_PRINT);
    file_put_contents($products_file_path, $json_data);
}

$scope = $_POST['discount_scope'] ?? 'all';
$category_name = $_POST['category_name'] ?? null;
$product_ids = $_POST['product_ids'] ?? [];
$discount_type = $_POST['discount_type'] ?? 'percentage';
$discount_value = (float)($_POST['discount_value'] ?? 0);

if ($discount_value <= 0) {
    echo json_encode(['success' => false, 'message' => 'Discount value must be positive.']);
    exit;
}

$products = get_products();
$updated_products = 0;

foreach ($products as &$product) {
    $apply_discount = false;
    if ($scope === 'all') {
        $apply_discount = true;
    } elseif ($scope === 'category' && $product['category'] === $category_name) {
        $apply_discount = true;
    } elseif ($scope === 'specific' && in_array($product['id'], $product_ids)) {
        $apply_discount = true;
    }

    if ($apply_discount) {
        $product['discount'] = [
            'type' => $discount_type,
            'value' => $discount_value
        ];
        $updated_products++;
    }
}

save_products($products);

echo json_encode(['success' => true, 'message' => "$updated_products products updated."]);
?>
