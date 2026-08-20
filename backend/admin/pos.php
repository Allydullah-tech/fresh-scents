<?php
$pageTitle = 'Uuzaji Dukani'; $activePage = 'pos';
require_once __DIR__ . '/includes/header.php';

$msg = ''; $err = ''; $lastOrderCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'record_sale') {
    $variantIds = $_POST['variant_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $customer_name = clean($_POST['customer_name'] ?? '');
    $customer_phone = clean($_POST['customer_phone'] ?? '');
    $notes = clean($_POST['notes'] ?? '');

    $items = [];
    foreach ($variantIds as $i => $vid) {
        $vid = (int)$vid;
        $qty = max(1, (int)($qtys[$i] ?? 1));
        if ($vid > 0) $items[] = ['variant_id' => $vid, 'qty' => $qty];
    }

    if (!$items) {
        $err = 'Chagua angalau bidhaa moja kabla ya kurekodi mauzo.';
    } else {
        $pdo->beginTransaction();
        try {
            $total = 0;
            $validated = [];
            foreach ($items as $it) {
                $stmt = $pdo->prepare("SELECT pv.*, p.name AS product_name, p.status AS product_status
                    FROM product_variants pv JOIN products p ON pv.product_id = p.id WHERE pv.id = ?");
                $stmt->execute([$it['variant_id']]);
                $v = $stmt->fetch();
                if (!$v || $v['product_status'] !== 'active') continue;
                if ($v['stock_qty'] < $it['qty']) {
                    throw new Exception('Stock haitoshi kwa ' . $v['product_name'] . ' (' . $v['ml'] . 'ml). Kilichopo: ' . $v['stock_qty']);
                }
                $subtotal = $v['price'] * $it['qty'];
                $total += $subtotal;
                $validated[] = [
                    'product_id' => $v['product_id'], 'variant_id' => $v['id'],
                    'product_name' => $v['product_name'], 'ml' => $v['ml'],
                    'unit_price' => $v['price'], 'qty' => $it['qty'], 'subtotal' => $subtotal,
                ];
            }

            if (!$validated) {
                throw new Exception('Bidhaa ulizochagua hazipatikani tena.');
            }

            $order_code = generate_order_code($pdo);
            $stmt = $pdo->prepare("INSERT INTO orders (order_code, guest_name, guest_phone, delivery_type, source, served_by, status, payment_status, total_amount, notes, completed_at)
                VALUES (?,?,?,'pickup','duka',?,'completed','paid',?,?,NOW())");
            $stmt->execute([$order_code, $customer_name ?: 'Mteja wa Dukani', $customer_phone ?: null, $_SESSION['admin_id'], $total, $notes]);
            $orderId = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, product_name, ml, unit_price, qty, subtotal) VALUES (?,?,?,?,?,?,?,?)");
            $stockStmt = $pdo->prepare("UPDATE product_variants SET stock_qty = GREATEST(stock_qty - ?, 0) WHERE id = ?");
            foreach ($validated as $v) {
                $itemStmt->execute([$orderId, $v['product_id'], $v['variant_id'], $v['product_name'], $v['ml'], $v['unit_price'], $v['qty'], $v['subtotal']]);
                $stockStmt->execute([$v['qty'], $v['variant_id']]);
            }

            $pdo->commit();
            $msg = 'Mauzo yamerekodiwa kikamilifu! Namba ya oda: ' . $order_code . ' — Jumla: ' . money($total);
            $lastOrderCode = $order_code;
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = $e->getMessage();
        }
    }
}

// Bidhaa zote zenye stock, kwa ajili ya kuchagua
$variants = $pdo->query("SELECT pv.id, pv.ml, pv.price, pv.stock_qty, p.name, p.brand, p.image
    FROM product_variants pv JOIN products p ON pv.product_id = p.id
    WHERE p.status = 'active' AND pv.stock_qty > 0
    ORDER BY p.name, pv.ml")->fetchAll();

// Mauzo ya dukani ya hivi karibuni
$recentSales = $pdo->query("SELECT o.*, a.full_name AS admin_name FROM orders o
    LEFT JOIN admins a ON o.served_by = a.id
    WHERE o.source = 'duka' ORDER BY o.created_at DESC LIMIT 15")->fetchAll();
?>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="page-header">
  <h2>Rekodi Mauzo ya Mteja wa Dukani</h2>
</div>
<p class="muted" style="margin-top:-10px;margin-bottom:20px">Tumia ukurasa huu kurekodi mauzo ya wateja wanaokuja dukani moja kwa moja (siyo kupitia tovuti). Stock hupungua moja kwa moja, na mauzo huonekana kwenye ripoti za Mauzo na Fedha.</p>

<div class="grid grid-2">
  <div class="card">
    <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-bag-check"></i> Chagua Bidhaa</h3>
    <label>Tafuta / Chagua Bidhaa na Ukubwa (ML)</label>
    <select id="productSelect">
      <option value="">-- Chagua Bidhaa --</option>
      <?php foreach ($variants as $v): ?>
      <option
        value="<?= $v['id'] ?>"
        data-name="<?= htmlspecialchars($v['name']) ?>"
        data-ml="<?= (int)$v['ml'] ?>"
        data-price="<?= $v['price'] ?>"
        data-stock="<?= (int)$v['stock_qty'] ?>">
        <?= htmlspecialchars($v['name']) ?> — <?= (int)$v['ml'] ?>ml (<?= money($v['price']) ?>, stock: <?= (int)$v['stock_qty'] ?>)
      </option>
      <?php endforeach; ?>
    </select>
    <?php if (!$variants): ?><p class="muted" style="font-size:12.5px">Hakuna bidhaa zenye stock kwa sasa.</p><?php endif; ?>
    <button type="button" class="btn-secondary" style="margin-top:12px" onclick="addItemToSale()">+ Ongeza kwenye Mauzo</button>

    <hr style="margin:18px 0;border-color:#eee2c5">

    <div id="saleItemsWrap">
      <p class="muted" id="emptyNote">Bado hujachagua bidhaa yoyote.</p>
    </div>
    <p style="text-align:right;font-size:18px;font-weight:800;margin-top:10px">Jumla: <span id="saleTotal">0 TZS</span></p>
  </div>

  <div class="card">
    <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-person-check"></i> Taarifa za Mteja (Hiari)</h3>
    <form method="POST" id="saleForm">
      <input type="hidden" name="action" value="record_sale">
      <div id="hiddenItemsWrap"></div>

      <label>Jina la Mteja (hiari)</label>
      <input type="text" name="customer_name" placeholder="Mfano: Mteja wa kawaida">

      <label>Namba ya Simu (hiari)</label>
      <input type="text" name="customer_phone" placeholder="0621002091">

      <label>Maelezo (hiari)</label>
      <textarea name="notes" placeholder="Mfano: alilipa cash, alipewa punguzo, n.k."></textarea>

      <button type="submit" class="btn-primary btn-block" style="width:100%;margin-top:16px" id="submitSaleBtn" disabled>
        <i class="bi bi-check-circle"></i> Kamilisha na Rekodi Mauzo
      </button>
      <p class="muted" style="font-size:12.5px;margin-top:8px">Mauzo haya yatarekodiwa moja kwa moja kama "Imekamilika" na "Imelipwa" (fedha taslimu dukani).</p>
    </form>
  </div>
</div>

<br>
<div class="page-header"><h2>Mauzo ya Hivi Karibuni ya Dukani</h2></div>
<div class="table-wrap">
<table>
<tr><th>Namba</th><th>Mteja</th><th>Simu</th><th>Kiasi</th><th>Aliyeuza</th><th>Tarehe</th></tr>
<?php foreach ($recentSales as $s): ?>
<tr>
  <td><strong><?= htmlspecialchars($s['order_code']) ?></strong></td>
  <td><?= htmlspecialchars($s['guest_name'] ?: '-') ?></td>
  <td><?= htmlspecialchars($s['guest_phone'] ?: '-') ?></td>
  <td><?= money($s['total_amount']) ?></td>
  <td><?= htmlspecialchars($s['admin_name'] ?: '-') ?></td>
  <td><?= fdate($s['created_at']) ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$recentSales): ?><tr><td colspan="6" class="muted">Hakuna mauzo ya dukani yaliyorekodiwa bado.</td></tr><?php endif; ?>
</table>
</div>

<script>
let saleItems = [];

function addItemToSale() {
  const sel = document.getElementById('productSelect');
  const opt = sel.options[sel.selectedIndex];
  if (!opt || !opt.value) { alert('Tafadhali chagua bidhaa kwanza.'); return; }

  const variantId = parseInt(opt.value);
  const stock = parseInt(opt.getAttribute('data-stock'));
  const existing = saleItems.find(i => i.variant_id === variantId);
  const currentQtyInCart = existing ? existing.qty : 0;

  if (currentQtyInCart + 1 > stock) { alert('Stock haitoshi kwa bidhaa hii.'); return; }

  if (existing) {
    existing.qty += 1;
  } else {
    saleItems.push({
      variant_id: variantId,
      name: opt.getAttribute('data-name'),
      ml: opt.getAttribute('data-ml'),
      price: parseFloat(opt.getAttribute('data-price')),
      stock: stock,
      qty: 1,
    });
  }
  renderSaleItems();
}

function changeItemQty(variantId, delta) {
  const item = saleItems.find(i => i.variant_id === variantId);
  if (!item) return;
  const newQty = item.qty + delta;
  if (newQty < 1) { removeItem(variantId); return; }
  if (newQty > item.stock) { alert('Stock haitoshi.'); return; }
  item.qty = newQty;
  renderSaleItems();
}

function removeItem(variantId) {
  saleItems = saleItems.filter(i => i.variant_id !== variantId);
  renderSaleItems();
}

function renderSaleItems() {
  const wrap = document.getElementById('saleItemsWrap');
  const hiddenWrap = document.getElementById('hiddenItemsWrap');
  const submitBtn = document.getElementById('submitSaleBtn');

  if (!saleItems.length) {
    wrap.innerHTML = '<p class="muted" id="emptyNote">Bado hujachagua bidhaa yoyote.</p>';
    hiddenWrap.innerHTML = '';
    document.getElementById('saleTotal').textContent = '0 TZS';
    submitBtn.disabled = true;
    return;
  }

  let total = 0;
  wrap.innerHTML = saleItems.map(i => {
    const subtotal = i.price * i.qty;
    total += subtotal;
    return `
      <div class="cart-item" style="border-bottom:1px solid var(--border);padding:10px 0;display:flex;align-items:center;gap:10px">
        <div style="flex:1">
          <div style="font-weight:700;font-size:14px">${i.name}</div>
          <div class="muted" style="font-size:12.5px">${i.ml}ml &middot; ${i.price.toLocaleString()} TZS</div>
        </div>
        <div style="display:flex;align-items:center;gap:6px">
          <button type="button" class="btn-outline btn-sm" onclick="changeItemQty(${i.variant_id}, -1)">−</button>
          <span>${i.qty}</span>
          <button type="button" class="btn-outline btn-sm" onclick="changeItemQty(${i.variant_id}, 1)">+</button>
        </div>
        <div style="min-width:90px;text-align:right;font-weight:700">${subtotal.toLocaleString()} TZS</div>
        <span style="color:var(--danger);cursor:pointer" onclick="removeItem(${i.variant_id})"><i class="bi bi-trash3"></i></span>
      </div>`;
  }).join('');

  hiddenWrap.innerHTML = saleItems.map(i => `
    <input type="hidden" name="variant_id[]" value="${i.variant_id}">
    <input type="hidden" name="qty[]" value="${i.qty}">
  `).join('');

  document.getElementById('saleTotal').textContent = total.toLocaleString() + ' TZS';
  submitBtn.disabled = false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
