<?php
$pageTitle = 'Mapato na Matumizi'; $activePage = 'finance';
require_once __DIR__ . '/includes/header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='add_expense') {
    $title = clean($_POST['title'] ?? '');
    $category = clean($_POST['category'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $expense_date = clean($_POST['expense_date'] ?? date('Y-m-d'));
    $notes = clean($_POST['notes'] ?? '');
    if ($title && $amount > 0) {
        $pdo->prepare("INSERT INTO expenses (title, category, amount, expense_date, notes, created_by) VALUES (?,?,?,?,?,?)")
            ->execute([$title, $category, $amount, $expense_date, $notes, $_SESSION['admin_id']]);
        $msg = 'Tumizi limeongezwa kikamilifu.';
    }
}
if (isset($_GET['delete_expense'])) {
    $pdo->prepare("DELETE FROM expenses WHERE id=?")->execute([(int)$_GET['delete_expense']]);
    header('Location: finance.php'); exit;
}

$from = clean($_GET['from'] ?? date('Y-m-01'));
$to = clean($_GET['to'] ?? date('Y-m-d'));

$income = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) t FROM orders WHERE status='completed' AND DATE(created_at) BETWEEN ? AND ?");
$income->execute([$from,$to]);
$income = $income->fetch()['t'];

$expStmt = $pdo->prepare("SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date DESC");
$expStmt->execute([$from,$to]);
$expenses = $expStmt->fetchAll();
$totalExpenses = array_sum(array_column($expenses, 'amount'));
$profit = $income - $totalExpenses;
?>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="page-header"><h2>Mapato na Matumizi</h2></div>

<form method="GET" class="toolbar">
  <label style="margin:0">Kuanzia</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
  <label style="margin:0">Hadi</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
  <button class="btn-secondary" style="margin-top:0" type="submit">Chuja</button>
</form>

<div class="grid grid-3">
  <div class="stat-card success"><div class="label">Mapato (Mauzo)</div><div class="value"><?= money($income) ?></div></div>
  <div class="stat-card danger"><div class="label">Matumizi</div><div class="value"><?= money($totalExpenses) ?></div></div>
  <div class="stat-card info"><div class="label">Faida Halisi</div><div class="value"><?= money($profit) ?></div></div>
</div>

<br>
<div class="grid grid-2">
  <div class="card">
    <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-plus-circle"></i> Ongeza Tumizi</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add_expense">
      <label>Jina la Tumizi</label>
      <input type="text" name="title" required placeholder="Mfano: Umeme, Kodi ya Duka, Usafiri...">
      <div class="form-row">
        <div><label>Aina</label><input type="text" name="category" placeholder="Mfano: Uendeshaji"></div>
        <div><label>Kiasi (TZS)</label><input type="number" step="0.01" name="amount" required></div>
      </div>
      <label>Tarehe</label>
      <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>">
      <label>Maelezo</label>
      <textarea name="notes"></textarea>
      <button class="btn-primary" style="width:100%;margin-top:14px" type="submit">Hifadhi Tumizi</button>
    </form>
  </div>
  <div class="card">
    <h3 style="margin-top:0;color:var(--gold-dark)">Maelezo</h3>
    <p class="muted">Mapato yanakokotolewa moja kwa moja kutoka maagizo yaliyokamilika. Matumizi unayaongeza wewe mwenyewe hapa (mfano kodi, umeme, usafiri, matangazo).</p>
  </div>
</div>

<br>
<div class="table-wrap">
<table>
<tr><th>Jina</th><th>Aina</th><th>Kiasi</th><th>Tarehe</th><th>Maelezo</th><th>Vitendo</th></tr>
<?php foreach ($expenses as $e): ?>
<tr>
  <td><?= htmlspecialchars($e['title']) ?></td>
  <td><?= htmlspecialchars($e['category'] ?: '-') ?></td>
  <td><?= money($e['amount']) ?></td>
  <td><?= date('d/m/Y', strtotime($e['expense_date'])) ?></td>
  <td><?= htmlspecialchars($e['notes'] ?: '-') ?></td>
  <td><a class="btn-danger btn-sm" href="finance.php?delete_expense=<?= $e['id'] ?>" onclick="return confirm('Futa tumizi hili?')">Futa</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$expenses): ?><tr><td colspan="6" class="muted">Hakuna matumizi katika kipindi hiki.</td></tr><?php endif; ?>
</table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
