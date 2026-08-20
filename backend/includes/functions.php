<?php
/**
 * FRESH SCENTS - Functions za Msaada (Helper Functions)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Tuma majibu ya JSON na kusimamisha script */
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Safisha maandishi kutoka kwa mtumiaji */
function clean($str) {
    return trim(htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'));
}

/** Tengeneza order code ya kipekee, mfano FS-8X2K91 */
function generate_order_code($pdo) {
    do {
        $code = 'FS-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    return $code;
}

/** Angalia kama admin ameingia */
function require_admin_login() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

/** Angalia kama admin ni super admin */
function is_super_admin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
}

/** Angalia kama customer ameingia (kwa API) */
function current_customer_id() {
    return $_SESSION['customer_id'] ?? null;
}

/** Pata mpangilio (setting) mmoja kutoka database */
function get_setting($pdo, $key, $default = '') {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    $cache[$key] = $row ? $row['setting_value'] : $default;
    return $cache[$key];
}

/** Sasisha mpangilio */
function set_setting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

/** Fomati fedha kwa TZS */
function money($amount) {
    return number_format((float)$amount, 0) . ' TZS';
}

/** Fomati tarehe kwa Kiswahili (rahisi) */
function fdate($datetime) {
    if (!$datetime) return '-';
    return date('d/m/Y H:i', strtotime($datetime));
}

/** Ujumbe wa hali ya oda kwa Kiswahili kutegemea aina ya usafirishaji */
function status_message_sw($status, $delivery_type) {
    $messages = [
        'pending'    => 'Ombi lako limepokelewa, linasubiri kuthibitishwa.',
        'confirmed'  => 'Ombi lako limethibitishwa, tunaandaa bidhaa yako.',
        'processing' => 'Bidhaa yako iko tayarishwa kwa ajili yako.',
        'completed'  => $delivery_type === 'delivery'
            ? 'Mzigo wako umekamilika, mtu wa delivery atakufikia haraka iwezekanavyo.'
            : 'Mzigo wako umekamilika, tafadhari fika dukani.',
        'cancelled'  => 'Samahani, ombi lako limesitishwa. Wasiliana na duka kwa maelezo zaidi.',
    ];
    return $messages[$status] ?? '';
}

/** Validate namba ya simu rahisi ya Tanzania */
function valid_phone($phone) {
    return (bool) preg_match('/^0[67]\d{8}$/', $phone);
}
