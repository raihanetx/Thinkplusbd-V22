<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$coupons_file_path = __DIR__ . '/coupons.json';

if (!isset($input['code']) || empty(trim($input['code']))) {
    echo json_encode(['success' => false, 'message' => 'Coupon code is required.']);
    exit();
}

$code_to_delete = strtoupper(trim($input['code']));

if (!file_exists($coupons_file_path)) {
    echo json_encode(['success' => false, 'message' => 'No coupons file found.']);
    exit();
}

$json_data = file_get_contents($coupons_file_path);
$coupons = json_decode($json_data, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Error reading coupons data.']);
    exit();
}

$coupon_found = false;
$updated_coupons = [];
foreach ($coupons as $coupon) {
    if (strtoupper($coupon['code']) !== $code_to_delete) {
        $updated_coupons[] = $coupon;
    } else {
        $coupon_found = true;
    }
}

if (!$coupon_found) {
    echo json_encode(['success' => false, 'message' => 'Coupon code not found.']);
    exit();
}

$json_data = json_encode(array_values($updated_coupons), JSON_PRETTY_PRINT);
file_put_contents($coupons_file_path, $json_data);

echo json_encode(['success' => true]);
?>
