<?php
$pageTitle = 'Maombi/Oda za Wateja'; $activePage = 'requests';
require_once __DIR__ . '/includes/header.php';

$msg = '';

// ---- Update order status / payment ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);

    if ($_POST['action'] === 'update_status') {
        $status = clean($_POST['status'] ?? '');
        $adminNotes = clean($_POST['admin_notes'] ?? '');
        $valid = ['pending','confirmed','processing','completed','cancelled'];
        if (in_array($status, $valid)) {
            if ($status === 'completed') {
                $pdo->prepare("UPDATE orders SET status=?, admin_notes=?, completed_at=NOW() WHERE id=?")
                    ->execute([$status, $adminNotes, $orderId]);

                // Punguza stock kwa bidhaa zilizonunuliwa (mara ya kwanza tu inapokamilika)
                $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
                $items->execute([$orderId]);
                foreach ($items->fetchAll() as $it) {
                    if ($it['variant_id']) {
                        $pdo->prepare("UPDATE product_variants SET stock_qty = GREATEST(stock_qty - ?, 0) WHERE id=?")
                            ->execute([$it['qty'], $it['variant_id']]);
                    }
                }
            } else {
                $pdo->prepare("UPDATE orders SET status=?, admin_notes=? WHERE id=?")->execute([$status, $adminNotes, $orderId]);
            }
            $msg = 'Hali ya ombi imesasishwa.';
        }
    }

    if ($_POST['action'] === 'confirm_payment') {
        $pdo->prepare("UPDATE orders SET payment_status='paid' WHERE id=?")->execute([$orderId]);
        $msg = 'Malipo yamethibitishwa.';
    }

    if ($_POST['action'] === 'reject_payment') {
        $pdo->prepare("UPDATE orders SET payment_status='unpaid', payment_transaction_id=NULL, payment_name=NULL WHERE id=?")->execute([$orderId]);
        $msg = 'Malipo yamekataliwa, mteja anahitaji kutuma taarifa sahihi za malipo.';
    }
}

$statusFilter = clean($_GET['status'] ?? '');
$deliveryFilter = clean($_GET['delivery'] ?? '');
$sourceFilter = clean($_GET['source'] ?? '');
$sql = "SELECT o.*, c.full_name AS cust_name, c.phone AS cust_phone FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE 1=1";
$params = [];
if ($statusFilter) { $sql .= " AND o.status = ?"; $params[] = $statusFilter; }
if ($deliveryFilter) { $sql .= " AND o.delivery_type = ?"; $params[] = $deliveryFilter; }
if ($sourceFilter) { $sql .= " AND o.source = ?"; $params[] = $sourceFilter; }
$sql .= " ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$orders = $stmt->fetchAll();

$viewId = (int)($_GET['view'] ?? 0);
$viewOrder = null; $viewItems = [];
if ($viewId) {
    $s = $pdo->prepare("SELECT o.*, c.full_name AS cust_name, c.phone AS cust_phone, c.email AS cust_email FROM orders o LEFT JOIN customers c ON o.customer_id=c.id WHERE o.id=?");
    $s->execute([$viewId]);
    $viewOrder = $s->fetch();
    if ($viewOrder) {
        $si = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
        $si->execute([$viewId]);
        $viewItems = $si->fetchAll();
    }
}
?>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="page-header"><h2>Maombi Yote ya Wateja (<?= count($orders) ?>)</h2></div>

<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <select name="status" onchange="this.form.submit()">
      <option value="">Hali Zote</option>
      <?php foreach (['pending'=>'Inasubiri','confirmed'=>'Imethibitishwa','processing'=>'Inaandaliwa','completed'=>'Imekamilika','cancelled'=>'Imesitishwa'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $statusFilter===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <select name="delivery" onchange="this.form.submit()">
      <option value="">Aina Zote</option>
      <option value="pickup" <?= $deliveryFilter==='pickup'?'selected':'' ?>>Kuchukua Dukani</option>
      <option value="delivery" <?= $deliveryFilter==='delivery'?'selected':'' ?>>Delivery</option>
    </select>
    <select name="source" onchange="this.form.submit()">
      <option value="">Chanzo Chote</option>
      <option value="online" <?= $sourceFilter==='online'?'selected':'' ?>>Kutoka Tovuti</option>
      <option value="duka" <?= $sourceFilter==='duka'?'selected':'' ?>>Mauzo ya Dukani</option>
    </select>
  </form>
</div>

<div class="table-wrap">
<table>
<tr><th>Namba</th><th>Mteja</th><th>Simu</th><th>Chanzo</th><th>Aina</th><th>Kiasi</th><th>Malipo</th><th>Hali</th><th>Tarehe</th><th>Vitendo</th></tr>
<?php foreach ($orders as $o): ?>
<tr>
  <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
  <td><?= htmlspecialchars($o['cust_name'] ?: $o['guest_name'] ?: '-') ?></td>
  <td><?= htmlspecialchars($o['cust_phone'] ?: $o['guest_phone'] ?: '-') ?></td>
  <td><?= $o['source']==='duka' ? '<span class="badge badge-confirmed"><i class="bi bi-shop"></i> Dukani</span>' : '<span class="badge badge-processing"><i class="bi bi-globe"></i> Tovuti</span>' ?></td>
  <td><?= $o['delivery_type']==='delivery' ? 'Delivery' : 'Dukani' ?></td>
  <td><?= money($o['total_amount']) ?></td>
  <td><span class="badge badge-<?= $o['payment_status'] ?>"><?= $o['payment_status'] ?></span></td>
  <td><span class="badge badge-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
  <td><?= fdate($o['created_at']) ?></td>
  <td><a class="btn-outline btn-sm" href="requests.php?view=<?= $o['id'] ?>">Angalia</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$orders): ?><tr><td colspan="10" class="muted">Hakuna maombi.</td></tr><?php endif; ?>
</table>
</div>

<?php if ($viewOrder): ?>
<div class="modal-bg" id="modalBg">
  <div class="modal" style="max-width:640px">
    <a href="requests.php" class="close-x" style="text-decoration:none">&times;</a>
    <h3>Maelezo ya Ombi: <?= htmlspecialchars($viewOrder['order_code']) ?>
      <?= $viewOrder['source']==='duka' ? '<span class="badge badge-confirmed" style="font-size:11px;vertical-align:middle"><i class="bi bi-shop"></i> Mauzo ya Dukani</span>' : '<span class="badge badge-processing" style="font-size:11px;vertical-align:middle"><i class="bi bi-globe"></i> Kutoka Tovuti</span>' ?>
    </h3>

    <p><strong>Mteja:</strong> <?= htmlspecialchars($viewOrder['cust_name'] ?: $viewOrder['guest_name']) ?> &nbsp;
       <strong>Simu:</strong> <?= htmlspecialchars($viewOrder['cust_phone'] ?: $viewOrder['guest_phone']) ?></p>
    <p><strong>Aina ya Utoaji:</strong> <?= $viewOrder['delivery_type']==='delivery' ? 'Delivery — ' . htmlspecialchars($viewOrder['guest_address'] ?: '') : 'Kuchukua Dukani' ?></p>
    <p><strong>Maelezo ya Mteja:</strong> <?= htmlspecialchars($viewOrder['notes'] ?: '-') ?></p>

    <div class="table-wrap">
    <table>
      <tr><th>Bidhaa</th><th>ML</th><th>Bei</th><th>Idadi</th><th>Jumla</th></tr>
      <?php foreach ($viewItems as $it): ?>
      <tr><td><?= htmlspecialchars($it['product_name']) ?></td><td><?= $it['ml'] ?>ml</td><td><?= money($it['unit_price']) ?></td><td><?= $it['qty'] ?></td><td><?= money($it['subtotal']) ?></td></tr>
      <?php endforeach; ?>
    </table>
    </div>
    <p style="text-align:right;font-size:17px;margin-top:10px"><strong>Jumla Kuu: <?= money($viewOrder['total_amount']) ?></strong></p>

    <?php if ($viewOrder['delivery_type'] === 'delivery'): ?>
    <div class="card" style="background:#fbf6e8">
      <h4 style="margin-top:0">Taarifa za Malipo</h4>
      <p>Hali: <span class="badge badge-<?= $viewOrder['payment_status'] ?>"><?= $viewOrder['payment_status'] ?></span></p>
      <p>Jina lililotumika: <strong><?= htmlspecialchars($viewOrder['payment_name'] ?: '-') ?></strong></p>
      <p>Namba ya Muamala: <strong><?= htmlspecialchars($viewOrder['payment_transaction_id'] ?: '-') ?></strong></p>
      <?php if ($viewOrder['payment_status'] === 'pending_confirmation'): ?>
      <div style="display:flex;gap:10px">
        <form method="POST"><input type="hidden" name="action" value="confirm_payment"><input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
          <button class="btn-primary btn-sm" type="submit"><i class="bi bi-check-circle"></i> Thibitisha Malipo</button></form>
        <form method="POST"><input type="hidden" name="action" value="reject_payment"><input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
          <button class="btn-danger btn-sm" type="submit"><i class="bi bi-x-circle"></i> Kataa Malipo</button></form>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <form method="POST" style="margin-top:16px">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
      <label>Sasisha Hali ya Ombi</label>
      <select name="status">
        <?php foreach (['pending'=>'Inasubiri','confirmed'=>'Imethibitishwa','processing'=>'Inaandaliwa','completed'=>'Imekamilika (imekamilika)','cancelled'=>'Imesitishwa'] as $k=>$v): ?>
        <option value="<?= $k ?>" <?= $viewOrder['status']===$k?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
      <label>Maelezo ya Ndani (Admin Notes)</label>
      <textarea name="admin_notes"><?= htmlspecialchars($viewOrder['admin_notes'] ?? '') ?></textarea>
      <button class="btn-primary" style="width:100%;margin-top:12px" type="submit">Hifadhi Mabadiliko</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
