<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$keys = ['shop_name','shop_tagline','payment_phone','payment_name','shop_phone','shop_address','currency'];
$out = [];
foreach ($keys as $k) { $out[$k] = get_setting($pdo, $k); }

json_response(['success'=>true, 'settings'=>$out]);
