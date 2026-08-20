<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$featured = isset($_GET['featured']);
$search = clean($_GET['q'] ?? '');
$category = (int)($_GET['category'] ?? 0);

function attach_variants($pdo, &$product) {
    $stmt = $pdo->prepare("SELECT id, ml, price, stock_qty FROM product_variants WHERE product_id=? ORDER BY ml ASC");
    $stmt->execute([$product['id']]);
    $variants = $stmt->fetchAll();
    $product['variants'] = $variants;
    $prices = array_column($variants, 'price');
    $product['min_price'] = $prices ? min($prices) : 0;
    $product['max_price'] = $prices ? max($prices) : 0;
    $product['total_stock'] = array_sum(array_column($variants, 'stock_qty'));
    return $product;
}

if ($id) {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=? AND p.status='active'");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) json_response(['success'=>false,'message'=>'Bidhaa haipo.'], 404);
    $product['image_url'] = $product['image'] ? UPLOAD_URL . $product['image'] : null;
    attach_variants($pdo, $product);
    json_response(['success'=>true,'product'=>$product]);
}

$sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status='active'";
$params = [];
if ($featured) { $sql .= " AND p.is_featured=1"; }
if ($search) { $sql .= " AND (p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($category) { $sql .= " AND p.category_id = ?"; $params[] = $category; }
$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
foreach ($products as &$p) {
    $p['image_url'] = $p['image'] ? UPLOAD_URL . $p['image'] : null;
    attach_variants($pdo, $p);
}
unset($p);

// Ficha bidhaa ambazo hazina ukubwa/bei bado (bado hazijakamilika kuwekwa na admin)
$products = array_values(array_filter($products, fn($p) => !empty($p['variants'])));

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

json_response(['success'=>true, 'products'=>$products, 'categories'=>$categories]);
