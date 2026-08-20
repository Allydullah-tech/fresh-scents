<?php
$pageTitle = 'Malipo ya Wafanyakazi'; $activePage = 'payroll';
require_once __DIR__ . '/includes/header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = (int)($_POST['employee_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $pay_month = clean($_POST['pay_month'] ?? '');
    $pay_date = clean($_POST['pay_date'] ?? date('Y-m-d'));
    $notes = clean($_POST['notes'] ?? '');

    if ($employee_id && $amount > 0 && $pay_month) {
        $pdo->prepare("INSERT INTO salary_payments (employee_id, amount, pay_month, pay_date, notes, paid_by) VALUES (?,?,?,?,?,?)")
            ->execute([$employee_id, $amount, $pay_month, $pay_date, $notes, $_SESSION['admin_id']]);
        $msg = 'Malipo ya mshahara yamerekodiwa kikamilifu.';
    }
}

$employees = $pdo->query("SELECT * FROM employees WHERE status='active' ORDER BY full_name")->fetchAll();
$history = $pdo->query("SELECT sp.*, e.full_name, e.position FROM salary_payments sp JOIN employees e ON sp.employee_id=e.id ORDER BY sp.pay_date DESC LIMIT 100")->fetchAll();
$totalPaid = $pdo->query("SELECT COALESCE(SUM(amount),0) t FROM salary_payments")->fetch()['t'];
?>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="grid grid-3">
  <div class="stat-card success"><div class="label">Jumla ya Mishahara Iliyolipwa</div><div class="value"><?= money($totalPaid) ?></div></div>
  <div class="stat-card"><div class="label">Wafanyakazi Wanaofanya Kazi</div><div class="value"><?= count($employees) ?></div></div>
  <div class="stat-card info"><div class="label">Jumla ya Mishahara kwa Mwezi</div><div class="value"><?= money(array_sum(array_column($employees,'salary'))) ?></div></div>
</div>

<br>
<div class="card">
  <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-receipt"></i> Rekodi Malipo ya Mshahara</h3>
  <form method="POST">
    <div class="form-row">
      <div><label>Chagua Mfanyakazi</label>
        <select name="employee_id" id="empSelect" onchange="fillSalary()" required>
          <option value="">-- Chagua --</option>
          <?php foreach ($employees as $e): ?>
          <option value="<?= $e['id'] ?>" data-salary="<?= $e['salary'] ?>"><?= htmlspecialchars($e['full_name']) ?> (<?= htmlspecialchars($e['position']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Kiasi Kinacholipwa (TZS)</label><input type="number" step="0.01" name="amount" id="amountField" required></div>
    </div>
    <div class="form-row">
      <div><label>Mwezi Unaolipiwa</label><input type="text" name="pay_month" placeholder="Mfano: Agosti 2026" required></div>
      <div><label>Tarehe ya Malipo</label><input type="date" name="pay_date" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <label>Maelezo</label>
    <textarea name="notes"></textarea>
    <button class="btn-primary" style="width:100%;margin-top:14px" type="submit">Rekodi Malipo</button>
  </form>
</div>

<script>
function fillSalary(){
  const sel = document.getElementById('empSelect');
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('amountField').value = opt.getAttribute('data-salary') || '';
}
</script>

<br>
<div class="page-header"><h2>Historia ya Malipo</h2></div>
<div class="table-wrap">
<table>
<tr><th>Mfanyakazi</th><th>Nafasi</th><th>Kiasi</th><th>Mwezi</th><th>Tarehe</th><th>Maelezo</th></tr>
<?php foreach ($history as $h): ?>
<tr>
  <td><?= htmlspecialchars($h['full_name']) ?></td>
  <td><?= htmlspecialchars($h['position']) ?></td>
  <td><?= money($h['amount']) ?></td>
  <td><?= htmlspecialchars($h['pay_month']) ?></td>
  <td><?= date('d/m/Y', strtotime($h['pay_date'])) ?></td>
  <td><?= htmlspecialchars($h['notes'] ?: '-') ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$history): ?><tr><td colspan="6" class="muted">Hakuna malipo yaliyorekodiwa bado.</td></tr><?php endif; ?>
</table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
