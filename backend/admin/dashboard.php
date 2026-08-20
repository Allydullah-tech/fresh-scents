<?php
$pageTitle = 'Dashibodi'; $activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';

// ---- Takwimu za Haraka ----
$totalSales = $pdo->query("SELECT COALESCE(SUM(total_amount),0) t FROM orders WHERE status='completed'")->fetch()['t'];
$onlineSales = $pdo->query("SELECT COALESCE(SUM(total_amount),0) t FROM orders WHERE status='completed' AND source='online'")->fetch()['t'];
$dukaSales = $pdo->query("SELECT COALESCE(SUM(total_amount),0) t FROM orders WHERE status='completed' AND source='duka'")->fetch()['t'];
$pendingOrders = $pdo->query("SELECT COUNT(*) c FROM orders WHERE status IN ('pending','confirmed','processing')")->fetch()['c'];
$totalExpenses = $pdo->query("SELECT COALESCE(SUM(amount),0) t FROM expenses")->fetch()['t'];
$totalProducts = $pdo->query("SELECT COUNT(*) c FROM products WHERE status='active'")->fetch()['c'];
$totalCustomers = $pdo->query("SELECT COUNT(*) c FROM customers")->fetch()['c'];
$lowStock = $pdo->query("SELECT p.name, pv.ml, pv.price, pv.stock_qty FROM product_variants pv
    JOIN products p ON pv.product_id = p.id
    WHERE pv.stock_qty <= 5 AND p.status='active' ORDER BY pv.stock_qty ASC LIMIT 6")->fetchAll();
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8")->fetchAll();
$pendingPayments = $pdo->query("SELECT COUNT(*) c FROM orders WHERE payment_status='pending_confirmation'")->fetch()['c'];
$netIncome = $totalSales - $totalExpenses;
?>

<div class="grid grid-4">
  <div class="stat-card success">
    <div class="label">Mauzo Yote (Yaliyokamilika)</div>
    <div class="value"><?= money($totalSales) ?></div>
  </div>
  <div class="stat-card info">
    <div class="label"><i class="bi bi-globe"></i> Mauzo Kutoka Tovuti</div>
    <div class="value"><?= money($onlineSales) ?></div>
  </div>
  <div class="stat-card">
    <div class="label"><i class="bi bi-shop"></i> Mauzo ya Dukani</div>
    <div class="value"><?= money($dukaSales) ?></div>
  </div>
  <div class="stat-card danger">
    <div class="label">Matumizi Yote</div>
    <div class="value"><?= money($totalExpenses) ?></div>
  </div>
</div>

<br>
<div class="grid grid-4">
  <div class="stat-card info">
    <div class="label">Faida Halisi</div>
    <div class="value"><?= money($netIncome) ?></div>
  </div>
  <div class="stat-card">
    <div class="label">Maombi Yanayosubiri</div>
    <div class="value"><?= (int)$pendingOrders ?></div>
  </div>
  <div class="stat-card"><div class="label">Bidhaa Zilizopo</div><div class="value"><?= (int)$totalProducts ?></div></div>
  <div class="stat-card"><div class="label">Wateja Waliojisajili</div><div class="value"><?= (int)$totalCustomers ?></div></div>
</div>

<br>
<div class="grid grid-4">
  <div class="stat-card danger"><div class="label">Malipo Yanayosubiri Uthibitisho</div><div class="value"><?= (int)$pendingPayments ?></div></div>
</div>

<br><br>
<div class="grid grid-2">
  <div class="card">
    <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-clock-history"></i> Maombi ya Hivi Karibuni</h3>
    <div class="table-wrap">
    <table>
      <tr><th>Namba</th><th>Mteja</th><th>Chanzo</th><th>Kiasi</th><th>Hali</th></tr>
      <?php foreach ($recentOrders as $o): ?>
      <tr>
        <td><a href="requests.php?view=<?= $o['id'] ?>"><?= htmlspecialchars($o['order_code']) ?></a></td>
        <td><?= htmlspecialchars($o['customer_id'] ? '(Akaunti) ' : ($o['guest_name'] ?: '-')) ?></td>
        <td><?= $o['source']==='duka' ? '<i class="bi bi-shop" title="Dukani"></i> Dukani' : '<i class="bi bi-globe" title="Tovuti"></i> Tovuti' ?></td>
        <td><?= money($o['total_amount']) ?></td>
        <td><span class="badge badge-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$recentOrders): ?><tr><td colspan="5" class="muted">Hakuna maombi bado.</td></tr><?php endif; ?>
    </table>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-exclamation-triangle"></i> Bidhaa Zinazokaribia Kuisha</h3>
    <div class="table-wrap">
    <table>
      <tr><th>Bidhaa</th><th>ML</th><th>Bei</th><th>Kiasi Kilichobaki</th></tr>
      <?php foreach ($lowStock as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= (int)$p['ml'] ?>ml</td>
        <td><?= money($p['price']) ?></td>
        <td><strong style="color:var(--danger)"><?= (int)$p['stock_qty'] ?></strong></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$lowStock): ?><tr><td colspan="4" class="muted">Bidhaa zote ziko sawa.</td></tr><?php endif; ?>
    </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
