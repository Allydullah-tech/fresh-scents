<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$order_code = clean($_GET['order_code'] ?? '');
$phone = clean($_GET['phone'] ?? '');

if (!$order_code && !$phone) {
    json_response(['success'=>false, 'message'=>'Tafadhali weka namba ya oda au namba ya simu.']);
}

if ($order_code) {
    $stmt = $pdo->prepare("SELECT o.*, c.full_name AS cust_name, c.phone AS cust_phone FROM orders o LEFT JOIN customers c ON o.customer_id=c.id WHERE o.order_code = ?");
    $stmt->execute([$order_code]);
    $orders = $stmt->fetch();
    $orders = $orders ? [$orders] : [];
} else {
    $stmt = $pdo->prepare("SELECT o.*, c.full_name AS cust_name, c.phone AS cust_phone FROM orders o LEFT JOIN customers c ON o.customer_id=c.id
        WHERE o.guest_phone = ? OR c.phone = ? ORDER BY o.created_at DESC LIMIT 20");
    $stmt->execute([$phone, $phone]);
    $orders = $stmt->fetchAll();
}

if (!$orders) {
    json_response(['success'=>false, 'message'=>'Hatujapata ombi lolote lenye taarifa hizo. Hakikisha umeweka namba sahihi.']);
}

$results = [];
foreach ($orders as $o) {
    $itemsStmt = $pdo->prepare("SELECT product_name, ml, unit_price, qty, subtotal FROM order_items WHERE order_id=?");
    $itemsStmt->execute([$o['id']]);

    $results[] = [
        'order_code' => $o['order_code'],
        'customer_name' => $o['cust_name'] ?: $o['guest_name'],
        'phone' => $o['cust_phone'] ?: $o['guest_phone'],
        'delivery_type' => $o['delivery_type'],
        'address' => $o['guest_address'],
        'status' => $o['status'],
        'status_label' => [
            'pending' => 'Inasubiri Uthibitisho',
            'confirmed' => 'Imethibitishwa',
            'processing' => 'Inaandaliwa',
            'completed' => 'Imekamilika',
            'cancelled' => 'Imesitishwa',
        ][$o['status']] ?? $o['status'],
        'status_message' => status_message_sw($o['status'], $o['delivery_type']),
        'payment_status' => $o['payment_status'],
        'payment_name' => $o['payment_name'],
        'payment_transaction_id' => $o['payment_transaction_id'],
        'total_amount' => $o['total_amount'],
        'created_at' => $o['created_at'],
        'completed_at' => $o['completed_at'],
        'items' => $itemsStmt->fetchAll(),
    ];
}

json_response(['success'=>true, 'orders'=>$results]);
