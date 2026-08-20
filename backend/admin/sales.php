<?php
$pageTitle = 'Mauzo'; $activePage = 'sales';
require_once __DIR__ . '/includes/header.php';

$from = clean($_GET['from'] ?? date('Y-m-01'));
$to = clean($_GET['to'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("SELECT o.*, c.full_name AS cust_name FROM orders o LEFT JOIN customers c ON o.customer_id=c.id
    WHERE o.status='completed' AND DATE(o.created_at) BETWEEN ? AND ? ORDER BY o.created_at DESC");
$stmt->execute([$from, $to]);
$sales = $stmt->fetchAll();

$total = array_sum(array_column($sales, 'total_amount'));

// bidhaa zinazouzwa zaidi
$topStmt = $pdo->prepare("SELECT oi.product_name, SUM(oi.qty) qty, SUM(oi.subtotal) total
    FROM order_items oi JOIN orders o ON oi.order_id=o.id
    WHERE o.status='completed' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY oi.product_name ORDER BY qty DESC LIMIT 5");
$topStmt->execute([$from, $to]);
$topProducts = $topStmt->fetchAll();
?>

<div class="page-header"><h2>Ripoti ya Mauzo</h2></div>

<form method="GET" class="toolbar">
  <label style="margin:0">Kuanzia</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
  <label style="margin:0">Hadi</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
  <button class="btn-secondary" style="margin-top:0" type="submit">Chuja</button>
</form>

<div class="grid grid-3">
  <div class="stat-card success"><div class="label">Jumla ya Mauzo</div><div class="value"><?= money($total) ?></div></div>
  <div class="stat-card"><div class="label">Idadi ya Maagizo</div><div class="value"><?= count($sales) ?></div></div>
  <div class="stat-card info"><div class="label">Wastani kwa Oda</div><div class="value"><?= money(count($sales) ? $total/count($sales) : 0) ?></div></div>
</div>

<br>
<div class="grid grid-2">
<div class="card">
  <h3 style="margin-top:0;color:var(--gold-dark)">Bidhaa Zinazouzwa Zaidi</h3>
  <div class="table-wrap">
  <table>
    <tr><th>Bidhaa</th><th>Idadi Iliyouzwa</th><th>Jumla</th></tr>
    <?php foreach ($topProducts as $t): ?>
    <tr><td><?= htmlspecialchars($t['product_name']) ?></td><td><?= (int)$t['qty'] ?></td><td><?= money($t['total']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$topProducts): ?><tr><td colspan="3" class="muted">Hakuna data.</td></tr><?php endif; ?>
  </table>
  </div>
</div>
<div class="card">
  <h3 style="margin-top:0;color:var(--gold-dark)">Maelezo</h3>
  <p class="muted">Ripoti hii inaonyesha maagizo yaliyokamilika (imekamilika) tu ndani ya kipindi ulichochagua. Badilisha tarehe hapo juu kuona kipindi kingine.</p>
</div>
</div>

<br>
<div class="table-wrap">
<table>
<tr><th>Namba</th><th>Mteja</th><th>Chanzo</th><th>Aina</th><th>Kiasi</th><th>Tarehe</th></tr>
<?php foreach ($sales as $s): ?>
<tr>
  <td><?= htmlspecialchars($s['order_code']) ?></td>
  <td><?= htmlspecialchars($s['cust_name'] ?: $s['guest_name'] ?: '-') ?></td>
  <td><?= $s['source']==='duka' ? '<i class="bi bi-shop"></i> Dukani' : '<i class="bi bi-globe"></i> Tovuti' ?></td>
  <td><?= $s['delivery_type']==='delivery'?'Delivery':'Dukani' ?></td>
  <td><?= money($s['total_amount']) ?></td>
  <td><?= fdate($s['created_at']) ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$sales): ?><tr><td colspan="6" class="muted">Hakuna mauzo katika kipindi hiki.</td></tr><?php endif; ?>
</table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
