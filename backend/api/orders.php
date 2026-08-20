<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? 'create');

// ---------------------------------------------------------
// TENGENEZA OMBI / ODA MPYA
// ---------------------------------------------------------
if ($action === 'create') {
    $raw = json_decode(file_get_contents('php://input'), true);
    $data = $raw ?: $_POST;

    $items = $data['items'] ?? [];
    $delivery_type = ($data['delivery_type'] ?? 'pickup') === 'delivery' ? 'delivery' : 'pickup';
    $notes = clean($data['notes'] ?? '');

    $guest_name = clean($data['guest_name'] ?? '');
    $guest_phone = clean($data['guest_phone'] ?? '');
    $guest_address = clean($data['guest_address'] ?? '');

    $customer_id = current_customer_id();

    if (!$items || !is_array($items)) {
        json_response(['success'=>false, 'message'=>'Hakuna bidhaa katika oda yako.']);
    }
    if (!$customer_id) {
        if (!$guest_name || !$guest_phone) {
            json_response(['success'=>false, 'message'=>'Tafadhali jaza jina na namba ya simu.']);
        }
        if (!valid_phone($guest_phone)) {
            json_response(['success'=>false, 'message'=>'Namba ya simu si sahihi. Tumia mfano 0621002091.']);
        }
    }
    if ($delivery_type === 'delivery' && !$guest_address && !$customer_id) {
        json_response(['success'=>false, 'message'=>'Tafadhali andika anuani ya kufikishiwa mzigo.']);
    }

    $pdo->beginTransaction();
    try {
        $total = 0;
        $validatedItems = [];
        foreach ($items as $it) {
            $vid = (int)($it['variant_id'] ?? 0);
            $qty = max(1, (int)($it['qty'] ?? 1));
            $stmt = $pdo->prepare("SELECT pv.*, p.name AS product_name, p.status AS product_status
                FROM product_variants pv JOIN products p ON pv.product_id = p.id
                WHERE pv.id=?");
            $stmt->execute([$vid]);
            $v = $stmt->fetch();
            if (!$v || $v['product_status'] !== 'active') continue;
            $subtotal = $v['price'] * $qty;
            $total += $subtotal;
            $validatedItems[] = [
                'product_id' => $v['product_id'],
                'variant_id' => $v['id'],
                'product_name' => $v['product_name'],
                'ml' => $v['ml'],
                'unit_price' => $v['price'],
                'qty' => $qty,
                'subtotal' => $subtotal,
            ];
        }

        if (!$validatedItems) {
            $pdo->rollBack();
            json_response(['success'=>false, 'message'=>'Bidhaa ulizochagua hazipatikani tena.']);
        }

        $order_code = generate_order_code($pdo);
        $payment_status = $delivery_type === 'delivery' ? 'unpaid' : 'not_required';

        $stmt = $pdo->prepare("INSERT INTO orders (order_code, customer_id, guest_name, guest_phone, guest_address, delivery_type, status, payment_status, total_amount, notes)
            VALUES (?,?,?,?,?,?, 'pending', ?, ?, ?)");
        $stmt->execute([$order_code, $customer_id, $guest_name, $guest_phone, $guest_address, $delivery_type, $payment_status, $total, $notes]);
        $orderId = $pdo->lastInsertId();

        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, product_name, ml, unit_price, qty, subtotal) VALUES (?,?,?,?,?,?,?,?)");
        foreach ($validatedItems as $vi) {
            $itemStmt->execute([$orderId, $vi['product_id'], $vi['variant_id'], $vi['product_name'], $vi['ml'], $vi['unit_price'], $vi['qty'], $vi['subtotal']]);
        }

        $pdo->commit();

        $payment_phone = get_setting($pdo, 'payment_phone', '0621002091');
        $payment_name = get_setting($pdo, 'payment_name', '');
        $paymentMessage = $delivery_type === 'delivery'
            ? "Oda yako imepokelewa. Lipia kulipia namba hii $payment_phone jina $payment_name, ukishalipia tuma jina na namba ya muamala kwa uthibitisho."
            : '';

        json_response([
            'success' => true,
            'order_code' => $order_code,
            'total_amount' => $total,
            'delivery_type' => $delivery_type,
            'requires_payment' => $delivery_type === 'delivery',
            'payment_message' => $paymentMessage,
            'payment_phone' => $payment_phone,
            'payment_name' => $payment_name,
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success'=>false, 'message'=>'Hitilafu imetokea, jaribu tena.'], 500);
    }
}

// ---------------------------------------------------------
// TUMA TAARIFA ZA MALIPO (baada ya mteja kulipia)
// ---------------------------------------------------------
if ($action === 'submit_payment') {
    $raw = json_decode(file_get_contents('php://input'), true);
    $data = $raw ?: $_POST;

    $order_code = clean($data['order_code'] ?? '');
    $phone = clean($data['phone'] ?? '');
    $payment_name = clean($data['payment_name'] ?? '');
    $transaction_id = clean($data['transaction_id'] ?? '');

    if (!$order_code || !$payment_name || !$transaction_id) {
        json_response(['success'=>false, 'message'=>'Tafadhali jaza jina na namba ya muamala.']);
    }

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ?");
    $stmt->execute([$order_code]);
    $order = $stmt->fetch();

    if (!$order) json_response(['success'=>false, 'message'=>'Oda haikupatikana. Hakikisha namba ya oda ni sahihi.']);
    if ($order['delivery_type'] !== 'delivery') json_response(['success'=>false, 'message'=>'Oda hii haihitaji malipo (ni ya kuchukua dukani).']);

    $pdo->prepare("UPDATE orders SET payment_status='pending_confirmation', payment_name=?, payment_transaction_id=?, payment_phone=?, payment_submitted_at=NOW() WHERE id=?")
        ->execute([$payment_name, $transaction_id, $phone, $order['id']]);

    json_response(['success'=>true, 'message'=>'Ahsante! Taarifa za malipo zimetumwa, zinasubiri uthibitisho wa duka.']);
}

json_response(['success'=>false, 'message'=>'Ombi halijaeleweka.'], 400);
