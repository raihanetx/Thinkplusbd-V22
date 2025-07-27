<?php
header('Content-Type: application/json');

// Function to get products
function get_products() {
    $products_file_path = __DIR__ . '/products.json';
    if (!file_exists($products_file_path)) {
        return [];
    }
    $json_data = file_get_contents($products_file_path);
    return json_decode($json_data, true);
}

// Function to save products
function save_products($products) {
    $products_file_path = __DIR__ . '/products.json';
    $json_data = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($products_file_path, $json_data);
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get form data
$scope = $_POST['discount_scope'] ?? null;
$discount_type = $_POST['discount_type'] ?? null;
$discount_value = isset($_POST['discount_value']) ? (float)$_POST['discount_value'] : 0;
$category_name = $_POST['category_name'] ?? null;
$product_ids = $_POST['product_ids'] ?? [];

// Validation
if (!$scope || !$discount_type || $discount_value <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

$products = get_products();
$updated_count = 0;

foreach ($products as &$product) {
    $apply = false;
    switch ($scope) {
        case 'all':
            $apply = true;
            break;
        case 'category':
            if ($product['category'] === $category_name) {
                $apply = true;
            }
            break;
        case 'specific':
            if (in_array($product['id'], $product_ids)) {
                $apply = true;
            }
            break;
    }

    if ($apply) {
        $product['discount'] = [
            'type' => $discount_type,
            'value' => $discount_value
        ];
        $updated_count++;
    }
}

save_products($products);

if ($updated_count > 0) {
    echo json_encode(['success' => true, 'message' => "Successfully applied discount to {$updated_count} products."]);
} else {
    echo json_encode(['success' => false, 'message' => 'No products matched the criteria. No discounts were applied.']);
}
?>
