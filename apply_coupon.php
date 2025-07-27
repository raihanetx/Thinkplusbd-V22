<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$coupon_code = isset($input['coupon_code']) ? trim($input['coupon_code']) : '';
$cart_items = isset($input['cart']) ? $input['cart'] : [];

if (empty($coupon_code) || empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$coupons_file_path = __DIR__ . '/coupons.json';
if (!file_exists($coupons_file_path)) {
    echo json_encode(['success' => false, 'message' => 'Coupon system not available.']);
    exit();
}

$coupons_json = file_get_contents($coupons_file_path);
$coupons = json_decode($coupons_json, true);
$found_coupon = null;

foreach ($coupons as $coupon) {
    if (strtoupper($coupon['code']) === strtoupper($coupon_code)) {
        $found_coupon = $coupon;
        break;
    }
}

if (!$found_coupon) {
    echo json_encode(['success' => false, 'message' => 'Invalid coupon code.']);
    exit();
}

$total_discount = 0;
$original_total = 0;
$applicable_items_total = 0;
$is_coupon_applied = false;

foreach ($cart_items as $item) {
    $original_total += $item['price'] * $item['quantity'];

    $is_applicable = false;
    $applies_to_all_products = empty($found_coupon['product_ids']) && empty($found_coupon['category']);
    $applies_to_category = !empty($found_coupon['category']) && in_array($item['category'], $found_coupon['category']);
    $applies_to_product = !empty($found_coupon['product_ids']) && in_array($item['id'], $found_coupon['product_ids']);

    if ($applies_to_all_products || $applies_to_category || $applies_to_product) {
        $is_applicable = true;
    }

    if ($is_applicable) {
        $is_coupon_applied = true;
        if ($found_coupon['discount_type'] === 'percentage') {
             $total_discount += ($item['price'] * $item['quantity'] * $found_coupon['discount_value']) / 100;
        } else { // Assumes 'fixed'
            // For fixed discount, it's usually applied once to the total of applicable items, not per item.
            // This logic might need adjustment based on business rules.
            // Current implementation: applies the fixed amount to the total cart if any item is applicable.
            $applicable_items_total += $item['price'] * $item['quantity'];
        }
    }
}

if ($found_coupon['discount_type'] === 'fixed' && $is_coupon_applied) {
    $total_discount = min($applicable_items_total, $found_coupon['discount_value']);
}

if (!$is_coupon_applied) {
    echo json_encode(['success' => false, 'message' => 'Coupon not applicable to items in cart.']);
    exit();
}

echo json_encode([
    'success' => true,
    'discount' => round($total_discount, 2),
    'new_total' => round($original_total - $total_discount, 2)
]);
?>
