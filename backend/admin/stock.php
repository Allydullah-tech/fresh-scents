<?php
$pageTitle = 'Stock Zinazoingia'; $activePage = 'stock';
require_once __DIR__ . '/includes/header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $variant_id = (int)($_POST['variant_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $cost_price = (float)($_POST['cost_price'] ?? 0);
    $supplier = clean($_POST['supplier'] ?? '');
    $stock_date = clean($_POST['stock_date'] ?? date('Y-m-d'));
    $notes = clean($_POST['notes'] ?? '');

    if ($variant_id && $quantity > 0) {
        $pdo->prepare("INSERT INTO stock_ins (variant_id, quantity, cost_price, supplier, stock_date, notes, added_by) VALUES (?,?,?,?,?,?,?)")
            ->execute([$variant_id, $quantity, $cost_price, $supplier, $stock_date, $notes, $_SESSION['admin_id']]);
        $pdo->prepare("UPDATE product_variants SET stock_qty = stock_qty + ? WHERE id=?")->execute([$quantity, $variant_id]);
        if ($cost_price > 0) {
            $pdo->prepare("UPDATE product_variants SET cost_price = ? WHERE id=?")->execute([$cost_price, $variant_id]);
        }
        $msg = 'Stock imeongezwa kikamilifu kwenye ukubwa (ML) uliochagua.';
    }
}

$variants = $pdo->query("SELECT pv.id, pv.ml, p.name FROM product_variants pv JOIN products p ON pv.product_id=p.id ORDER BY p.name, pv.ml")->fetchAll();
$history = $pdo->query("SELECT s.*, pv.ml, p.name AS product_name FROM stock_ins s
    JOIN product_variants pv ON s.variant_id=pv.id
    JOIN products p ON pv.product_id=p.id
    ORDER BY s.created_at DESC LIMIT 100")->fetchAll();
?>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="grid grid-2">
  <div class="card">
    <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-box-arrow-in-down"></i> Ongeza Stock Mpya</h3>
    <form method="POST">
      <label>Chagua Bidhaa na Ukubwa (ML)</label>
      <select name="variant_id" required>
        <option value="">-- Chagua Bidhaa na ML --</option>
        <?php foreach ($variants as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?> — <?= (int)$v['ml'] ?>ml</option><?php endforeach; ?>
      </select>
      <?php if (!$variants): ?><p class="muted" style="font-size:12.5px">Hakuna bidhaa/ukubwa bado. Nenda kwenye "Bidhaa" kuongeza bidhaa na ML zake kwanza.</p><?php endif; ?>
      <div class="form-row">
        <div><label>Idadi Inayoingia</label><input type="number" name="quantity" required></div>
        <div><label>Bei ya Ununuzi (kwa kimoja)</label><input type="number" step="0.01" name="cost_price"></div>
      </div>
      <div class="form-row">
        <div><label>Muuzaji/Supplier</label><input type="text" name="supplier"></div>
        <div><label>Tarehe</label><input type="date" name="stock_date" value="<?= date('Y-m-d') ?>"></div>
      </div>
      <label>Maelezo</label>
      <textarea name="notes"></textarea>
      <button class="btn-primary" style="width:100%;margin-top:14px" type="submit">Hifadhi Stock</button>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-bar-chart"></i> Muhtasari</h3>
    <p class="muted">Kila bidhaa inaweza kuwa na ukubwa (ML) tofauti — mfano 30ml, 50ml, 100ml — na kila ukubwa una stock yake mwenyewe. Chagua ukubwa sahihi unapoongeza stock ili kiasi kiongezeke kwenye ML sahihi.</p>
  </div>
</div>

<br>
<div class="page-header"><h2>Historia ya Stock Zilizoingia</h2></div>
<div class="table-wrap">
<table>
<tr><th>Bidhaa</th><th>ML</th><th>Idadi</th><th>Bei ya Ununuzi</th><th>Muuzaji</th><th>Tarehe</th><th>Maelezo</th></tr>
<?php foreach ($history as $h): ?>
<tr>
  <td><?= htmlspecialchars($h['product_name']) ?></td>
  <td><?= (int)$h['ml'] ?>ml</td>
  <td><?= (int)$h['quantity'] ?></td>
  <td><?= money($h['cost_price']) ?></td>
  <td><?= htmlspecialchars($h['supplier'] ?: '-') ?></td>
  <td><?= date('d/m/Y', strtotime($h['stock_date'])) ?></td>
  <td><?= htmlspecialchars($h['notes'] ?: '-') ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$history): ?><tr><td colspan="7" class="muted">Hakuna historia bado.</td></tr><?php endif; ?>
</table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
