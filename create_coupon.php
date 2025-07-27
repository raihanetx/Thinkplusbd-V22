<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$coupons_file_path = __DIR__ . '/coupons.json';

// Function to read coupons
function get_coupons($path) {
    if (!file_exists($path) || filesize($path) === 0) {
        return [];
    }
    $json_data = file_get_contents($path);
    $coupons = json_decode($json_data, true);
    return json_last_error() === JSON_ERROR_NONE ? $coupons : [];
}

// Function to save coupons
function save_coupons($path, $coupons) {
    $json_data = json_encode($coupons, JSON_PRETTY_PRINT);
    file_put_contents($path, $json_data);
}

// Basic validation
if (!isset($input['code']) || empty(trim($input['code']))) {
    echo json_encode(['success' => false, 'message' => 'Coupon code is required.']);
    exit();
}
if (!isset($input['discount_value']) || !is_numeric($input['discount_value']) || $input['discount_value'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'A valid discount value is required.']);
    exit();
}

$code = strtoupper(trim($input['code']));
$is_edit = isset($input['is_edit']) && $input['is_edit'] === true;

$coupons = get_coupons($coupons_file_path);

// Check for duplicate code on create
if (!$is_edit) {
    foreach ($coupons as $coupon) {
        if (strtoupper($coupon['code']) === $code) {
            echo json_encode(['success' => false, 'message' => 'This coupon code already exists.']);
            exit();
        }
    }
}

$new_coupon_data = [
    'code' => $code,
    'discount_type' => $input['discount_type'] ?? 'percentage',
    'discount_value' => (float)$input['discount_value'],
    'category' => (isset($input['category']) && is_array($input['category'])) ? $input['category'] : [],
    'product_ids' => (isset($input['product_ids']) && is_array($input['product_ids'])) ? $input['product_ids'] : [],
];

if ($is_edit) {
    $coupon_found = false;
    foreach ($coupons as $index => $coupon) {
        if (strtoupper($coupon['code']) === $code) {
            $coupons[$index] = $new_coupon_data;
            $coupon_found = true;
            break;
        }
    }
    if (!$coupon_found) {
        echo json_encode(['success' => false, 'message' => 'Could not find the coupon to update.']);
        exit();
    }
} else {
    $coupons[] = $new_coupon_data;
}

save_coupons($coupons_file_path, $coupons);

echo json_encode(['success' => true]);
?>
