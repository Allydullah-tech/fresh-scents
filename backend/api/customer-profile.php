<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if (!current_customer_id()) {
    json_response(['success'=>false, 'message'=>'Tafadhali ingia kwenye akaunti yako kwanza.'], 401);
}
$customerId = current_customer_id();

if ($action === 'update_info') {
    $raw = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $full_name = clean($raw['full_name'] ?? '');
    $email = clean($raw['email'] ?? '');
    $address = clean($raw['address'] ?? '');

    if (!$full_name) json_response(['success'=>false, 'message'=>'Jina haliwezi kuwa tupu.']);

    $pdo->prepare("UPDATE customers SET full_name=?, email=?, address=? WHERE id=?")->execute([$full_name, $email, $address, $customerId]);
    $_SESSION['customer_name'] = $full_name;
    json_response(['success'=>true, 'message'=>'Taarifa zako zimesasishwa kikamilifu.']);
}

if ($action === 'update_password') {
    $raw = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $current = $raw['current_password'] ?? '';
    $new = $raw['new_password'] ?? '';
    $confirm = $raw['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id=?");
    $stmt->execute([$customerId]);
    $c = $stmt->fetch();

    if (!password_verify($current, $c['password_hash'])) json_response(['success'=>false, 'message'=>'Password ya sasa si sahihi.']);
    if (strlen($new) < 6) json_response(['success'=>false, 'message'=>'Password mpya lazima iwe na herufi/namba angalau 6.']);
    if ($new !== $confirm) json_response(['success'=>false, 'message'=>'Password mpya hazifanani.']);

    $pdo->prepare("UPDATE customers SET password_hash=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $customerId]);
    json_response(['success'=>true, 'message'=>'Password imebadilishwa kikamilifu.']);
}

if ($action === 'update_security') {
    $raw = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $sec_q = clean($raw['security_question'] ?? '');
    $sec_a = clean($raw['security_answer'] ?? '');

    if (!$sec_q || !$sec_a) json_response(['success'=>false, 'message'=>'Jaza swali na jibu la usalama.']);

    $pdo->prepare("UPDATE customers SET security_question=?, security_answer_hash=? WHERE id=?")
        ->execute([$sec_q, password_hash(strtolower(trim($sec_a)), PASSWORD_DEFAULT), $customerId]);
    json_response(['success'=>true, 'message'=>'Swali la usalama limesasishwa.']);
}

if ($action === 'my_orders') {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id=? ORDER BY created_at DESC");
    $stmt->execute([$customerId]);
    $orders = $stmt->fetchAll();
    foreach ($orders as &$o) {
        $o['status_message'] = status_message_sw($o['status'], $o['delivery_type']);
    }
    json_response(['success'=>true, 'orders'=>$orders]);
}

json_response(['success'=>false, 'message'=>'Ombi halijaeleweka.'], 400);
