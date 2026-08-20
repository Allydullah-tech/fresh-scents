<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// MUHIMU: register.html/login.html hutuma data kama JSON (Content-Type: application/json),
// hivyo $_POST hubaki tupu daima kwa maombi hayo. Lazima tusome mwili halisi wa ombi (raw body).
$raw = json_decode(file_get_contents('php://input'), true);
$data = is_array($raw) ? $raw : $_POST;

$action = $_GET['action'] ?? ($data['action'] ?? '');

if ($action === 'register') {
    $full_name = clean($data['full_name'] ?? '');
    $phone = clean($data['phone'] ?? '');
    $email = clean($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $sec_q = clean($data['security_question'] ?? '');
    $sec_a = clean($data['security_answer'] ?? '');
    $address = clean($data['address'] ?? '');

    if (!$full_name || !$phone || !$password || !$sec_q || !$sec_a) {
        json_response(['success'=>false,'message'=>'Tafadhali jaza sehemu zote muhimu.']);
    }
    if (!valid_phone($phone)) {
        json_response(['success'=>false,'message'=>'Namba ya simu si sahihi. Tumia mfano 0621002091.']);
    }
    if (strlen($password) < 6) {
        json_response(['success'=>false,'message'=>'Password lazima iwe na herufi/namba angalau 6.']);
    }

    $chk = $pdo->prepare("SELECT id FROM customers WHERE phone=?");
    $chk->execute([$phone]);
    if ($chk->fetch()) {
        json_response(['success'=>false,'message'=>'Namba hii ya simu tayari imesajiliwa. Jaribu kuingia (login).']);
    }

    $stmt = $pdo->prepare("INSERT INTO customers (full_name, phone, email, password_hash, security_question, security_answer_hash, address) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$full_name, $phone, $email, password_hash($password, PASSWORD_DEFAULT), $sec_q, password_hash(strtolower(trim($sec_a)), PASSWORD_DEFAULT), $address]);

    $_SESSION['customer_id'] = $pdo->lastInsertId();
    $_SESSION['customer_name'] = $full_name;
    json_response(['success'=>true, 'message'=>'Umejisajili kikamilifu! Karibu ' . $full_name, 'customer'=>['id'=>$_SESSION['customer_id'],'full_name'=>$full_name,'phone'=>$phone]]);
}

if ($action === 'login') {
    $phone = clean($data['phone'] ?? '');
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone=?");
    $stmt->execute([$phone]);
    $c = $stmt->fetch();

    if (!$c || !password_verify($password, $c['password_hash'])) {
        json_response(['success'=>false,'message'=>'Namba ya simu au password si sahihi.']);
    }
    if ($c['status'] !== 'active') {
        json_response(['success'=>false,'message'=>'Akaunti yako imezimwa. Wasiliana na duka.']);
    }

    $_SESSION['customer_id'] = $c['id'];
    $_SESSION['customer_name'] = $c['full_name'];
    $pdo->prepare("INSERT INTO login_logs (user_type, user_id, ip_address) VALUES ('customer', ?, ?)")->execute([$c['id'], $_SERVER['REMOTE_ADDR'] ?? '']);

    json_response(['success'=>true, 'message'=>'Umeingia kikamilifu!', 'customer'=>['id'=>$c['id'],'full_name'=>$c['full_name'],'phone'=>$c['phone']]]);
}

if ($action === 'logout') {
    unset($_SESSION['customer_id'], $_SESSION['customer_name']);
    json_response(['success'=>true]);
}

if ($action === 'me') {
    if (!current_customer_id()) json_response(['success'=>false, 'logged_in'=>false]);
    $stmt = $pdo->prepare("SELECT id, full_name, phone, email, address FROM customers WHERE id=?");
    $stmt->execute([current_customer_id()]);
    $c = $stmt->fetch();
    if (!$c) json_response(['success'=>false, 'logged_in'=>false]);
    json_response(['success'=>true, 'logged_in'=>true, 'customer'=>$c]);
}

json_response(['success'=>false, 'message'=>'Ombi halijaeleweka.'], 400);
