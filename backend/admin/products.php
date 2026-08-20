<?php
$pageTitle = 'Bidhaa (Manukato)'; $activePage = 'products';
require_once __DIR__ . '/includes/header.php';

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$msg = ''; $err = '';

// ---- HANDLE ADD / EDIT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_product') {
    $id = (int)($_POST['id'] ?? 0);
    $name = clean($_POST['name'] ?? '');
    $brand = clean($_POST['brand'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Variant arrays (parallel)
    $variantIds = $_POST['variant_id'] ?? [];
    $variantMls = $_POST['variant_ml'] ?? [];
    $variantPrices = $_POST['variant_price'] ?? [];
    $variantStocks = $_POST['variant_stock'] ?? [];
    $deletedVariantIds = array_filter(explode(',', $_POST['deleted_variant_ids'] ?? ''));

    // Build a clean list of valid variant rows (must have ml + price)
    $variantRows = [];
    foreach ($variantMls as $i => $mlVal) {
        $ml = (int)$mlVal;
        $price = (float)($variantPrices[$i] ?? 0);
        $stock = (int)($variantStocks[$i] ?? 0);
        $vid = (int)($variantIds[$i] ?? 0);
        if ($ml > 0 && $price > 0) {
            $variantRows[] = ['id' => $vid, 'ml' => $ml, 'price' => $price, 'stock' => $stock];
        }
    }

    if (!$name) {
        $err = 'Tafadhali jaza jina la bidhaa.';
    } elseif (!$variantRows) {
        $err = 'Ongeza angalau ukubwa (ML) mmoja na bei yake.';
    } else {
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $imageName = 'p_' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $imageName);
            }
        }

        if ($id) {
            if ($imageName) {
                $stmt = $pdo->prepare("UPDATE products SET name=?,brand=?,description=?,category_id=?,status=?,is_featured=?,image=? WHERE id=?");
                $stmt->execute([$name,$brand,$description,$category_id,$status,$is_featured,$imageName,$id]);
            } else {
                $stmt = $pdo->prepare("UPDATE products SET name=?,brand=?,description=?,category_id=?,status=?,is_featured=? WHERE id=?");
                $stmt->execute([$name,$brand,$description,$category_id,$status,$is_featured,$id]);
            }
            $productId = $id;
            $msg = 'Bidhaa imesasishwa kikamilifu.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (name,brand,description,category_id,status,is_featured,image,created_by) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$name,$brand,$description,$category_id,$status,$is_featured,$imageName,$_SESSION['admin_id']]);
            $productId = $pdo->lastInsertId();
            $msg = 'Bidhaa mpya imeongezwa kikamilifu.';
        }

        // Delete removed variants
        if ($deletedVariantIds) {
            $in = implode(',', array_map('intval', $deletedVariantIds));
            $pdo->exec("DELETE FROM product_variants WHERE id IN ($in) AND product_id = $productId");
        }

        // Upsert variant rows
        $insertVariant = $pdo->prepare("INSERT INTO product_variants (product_id, ml, price, stock_qty) VALUES (?,?,?,?)");
        $updateVariant = $pdo->prepare("UPDATE product_variants SET ml=?, price=?, stock_qty=? WHERE id=? AND product_id=?");
        foreach ($variantRows as $v) {
            if ($v['id']) {
                $updateVariant->execute([$v['ml'], $v['price'], $v['stock'], $v['id'], $productId]);
            } else {
                $insertVariant->execute([$productId, $v['ml'], $v['price'], $v['stock']]);
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: products.php'); exit;
}

$search = clean($_GET['q'] ?? '');
$sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id";
$params = [];
if ($search) { $sql .= " WHERE p.name LIKE ? OR p.brand LIKE ?"; $params = ["%$search%","%$search%"]; }
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$products = $stmt->fetchAll();

// Load all variants, grouped by product_id
$variantsAll = $pdo->query("SELECT * FROM product_variants ORDER BY ml ASC")->fetchAll();
$variantsByProduct = [];
foreach ($variantsAll as $v) { $variantsByProduct[$v['product_id']][] = $v; }
?>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="page-header">
  <h2>Bidhaa Zote (<?= count($products) ?>)</h2>
  <button class="btn-primary" onclick="openAdd()">+ Ongeza Bidhaa Mpya</button>
</div>

<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px">
    <input type="text" name="q" placeholder="Tafuta bidhaa au brand..." value="<?= htmlspecialchars($search) ?>">
    <button class="btn-secondary" type="submit">Tafuta</button>
  </form>
</div>

<div class="table-wrap">
<table>
<tr><th>Picha</th><th>Jina</th><th>Brand</th><th>Ukubwa (ML) na Bei</th><th>Stock Jumla</th><th>Aina</th><th>Hali</th><th>Vitendo</th></tr>
<?php foreach ($products as $p): $variants = $variantsByProduct[$p['id']] ?? []; $totalStock = array_sum(array_column($variants, 'stock_qty')); ?>
<tr>
  <td><?php if ($p['image']): ?><img src="../uploads/products/<?= htmlspecialchars($p['image']) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:6px"><?php else: ?><i class="bi bi-droplet-half" style="font-size:22px;color:var(--gold)"></i><?php endif; ?></td>
  <td><?= htmlspecialchars($p['name']) ?> <?= $p['is_featured'] ? '<i class="bi bi-star-fill" style="color:var(--gold);font-size:13px"></i>' : '' ?></td>
  <td><?= htmlspecialchars($p['brand']) ?></td>
  <td>
    <?php if ($variants): ?>
      <?php foreach ($variants as $v): ?>
        <span class="badge badge-confirmed" style="margin:2px 3px 2px 0"><?= (int)$v['ml'] ?>ml — <?= money($v['price']) ?></span>
      <?php endforeach; ?>
    <?php else: ?>
      <span class="muted">Hakuna ukubwa bado</span>
    <?php endif; ?>
  </td>
  <td><?= (int)$totalStock ?></td>
  <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
  <td><span class="badge <?= $p['status']==='active'?'badge-completed':'badge-cancelled' ?>"><?= $p['status']==='active'?'Inauzwa':'Imezimwa' ?></span></td>
  <td>
    <button class="btn-outline btn-sm" onclick='openEdit(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>, <?= json_encode($variants, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Hariri</button>
    <a class="btn-danger btn-sm" href="products.php?delete=<?= $p['id'] ?>" onclick="return confirm('Una uhakika unataka kufuta bidhaa hii? Ukubwa/bei zake zote zitafutika pia.')">Futa</a>
  </td>
</tr>
<?php endforeach; ?>
<?php if (!$products): ?><tr><td colspan="8" class="muted">Hakuna bidhaa zilizopatikana.</td></tr><?php endif; ?>
</table>
</div>

<!-- MODAL -->
<div class="modal-bg hidden" id="modalBg">
  <div class="modal" style="max-width:620px">
    <span class="close-x" onclick="closeModal()">&times;</span>
    <h3 id="modalTitle">Ongeza Bidhaa Mpya</h3>
    <form method="POST" enctype="multipart/form-data" id="productForm">
      <input type="hidden" name="action" value="save_product">
      <input type="hidden" name="id" id="f_id">
      <input type="hidden" name="deleted_variant_ids" id="f_deleted_variants" value="">

      <label>Jina la Bidhaa</label>
      <input type="text" name="name" id="f_name" required>

      <div class="form-row">
        <div><label>Brand</label><input type="text" name="brand" id="f_brand" value="Fresh Scents"></div>
        <div><label>Aina (Category)</label>
          <select name="category_id" id="f_category">
            <option value="">-- Chagua --</option>
            <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>

      <label>Maelezo</label>
      <textarea name="description" id="f_description"></textarea>

      <div class="form-row">
        <div><label>Hali</label>
          <select name="status" id="f_status">
            <option value="active">Inauzwa</option>
            <option value="inactive">Imezimwa</option>
          </select>
        </div>
        <div><label>Picha ya Bidhaa</label><input type="file" name="image" accept="image/*"></div>
      </div>
      <label style="display:flex;align-items:center;gap:8px;font-weight:400">
        <input type="checkbox" name="is_featured" id="f_featured" style="width:auto"> Onyesha kwenye ukurasa mkuu (Featured)
      </label>

      <hr style="margin:18px 0;border-color:#eee2c5">

      <div style="display:flex;justify-content:space-between;align-items:center">
        <label style="margin:0">Ukubwa (ML) na Bei za Bidhaa Hii</label>
        <button type="button" class="btn-outline btn-sm" onclick="addVariantRow()">+ Ongeza Ukubwa Mwingine</button>
      </div>
      <p class="muted" style="font-size:12.5px;margin:4px 0 10px">Mfano: 30ml kwa 22,000 TZS, 50ml kwa 32,000 TZS, 100ml kwa 45,000 TZS — mteja atachagua ukubwa anaoutaka.</p>

      <div id="variantRows"></div>

      <button type="submit" class="btn-primary" style="width:100%;margin-top:16px">Hifadhi Bidhaa</button>
    </form>
  </div>
</div>

<script>
let variantRowCount = 0;

function addVariantRow(data) {
  data = data || {};
  const wrap = document.getElementById('variantRows');
  const rowId = 'vrow_' + (variantRowCount++);
  const row = document.createElement('div');
  row.className = 'form-row';
  row.id = rowId;
  row.style.alignItems = 'flex-end';
  row.style.marginBottom = '10px';
  row.innerHTML = `
    <input type="hidden" name="variant_id[]" value="${data.id || ''}">
    <div>
      <label style="margin-top:0">ML</label>
      <input type="number" name="variant_ml[]" value="${data.ml || ''}" placeholder="Mfano: 50" required>
    </div>
    <div>
      <label style="margin-top:0">Bei (TZS)</label>
      <input type="number" step="0.01" name="variant_price[]" value="${data.price || ''}" placeholder="Mfano: 30000" required>
    </div>
    <div style="display:flex;gap:8px;align-items:flex-end">
      <div style="flex:1">
        <label style="margin-top:0">Stock</label>
        <input type="number" name="variant_stock[]" value="${data.stock_qty !== undefined ? data.stock_qty : ''}" placeholder="0">
      </div>
      <button type="button" class="btn-danger btn-sm" style="margin-bottom:2px" onclick="removeVariantRow('${rowId}', '${data.id || ''}')"><i class="bi bi-trash3"></i></button>
    </div>
  `;
  wrap.appendChild(row);
}

function removeVariantRow(rowId, variantId) {
  document.getElementById(rowId).remove();
  if (variantId) {
    const field = document.getElementById('f_deleted_variants');
    field.value = field.value ? field.value + ',' + variantId : variantId;
  }
}

function resetVariantRows() {
  document.getElementById('variantRows').innerHTML = '';
  document.getElementById('f_deleted_variants').value = '';
}

function openAdd(){
  document.getElementById('modalTitle').innerText = 'Ongeza Bidhaa Mpya';
  document.getElementById('f_id').value = '';
  document.getElementById('f_name').value = '';
  document.getElementById('f_brand').value = 'Fresh Scents';
  document.getElementById('f_category').value = '';
  document.getElementById('f_description').value = '';
  document.getElementById('f_status').value = 'active';
  document.getElementById('f_featured').checked = false;
  resetVariantRows();
  addVariantRow(); // start with one empty row
  document.getElementById('modalBg').classList.remove('hidden');
}

function openEdit(p, variants){
  document.getElementById('modalTitle').innerText = 'Hariri Bidhaa';
  document.getElementById('f_id').value = p.id;
  document.getElementById('f_name').value = p.name;
  document.getElementById('f_brand').value = p.brand || '';
  document.getElementById('f_category').value = p.category_id || '';
  document.getElementById('f_description').value = p.description || '';
  document.getElementById('f_status').value = p.status;
  document.getElementById('f_featured').checked = p.is_featured == 1;
  resetVariantRows();
  if (variants && variants.length) {
    variants.forEach(v => addVariantRow(v));
  } else {
    addVariantRow();
  }
  document.getElementById('modalBg').classList.remove('hidden');
}
function closeModal(){ document.getElementById('modalBg').classList.add('hidden'); }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
