<?php
$pageTitle = 'Wafanyakazi'; $activePage = 'employees';
require_once __DIR__ . '/includes/header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='save_employee') {
    $id = (int)($_POST['id'] ?? 0);
    $full_name = clean($_POST['full_name'] ?? '');
    $position = clean($_POST['position'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $salary = (float)($_POST['salary'] ?? 0);
    $hired_date = clean($_POST['hired_date'] ?? '');
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if ($full_name && $position) {
        if ($id) {
            $pdo->prepare("UPDATE employees SET full_name=?,position=?,phone=?,salary=?,hired_date=?,status=? WHERE id=?")
                ->execute([$full_name,$position,$phone,$salary,$hired_date ?: null,$status,$id]);
            $msg = 'Taarifa za mfanyakazi zimesasishwa.';
        } else {
            $pdo->prepare("INSERT INTO employees (full_name,position,phone,salary,hired_date,status) VALUES (?,?,?,?,?,?)")
                ->execute([$full_name,$position,$phone,$salary,$hired_date ?: null,$status]);
            $msg = 'Mfanyakazi mpya ameongezwa.';
        }
    }
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM employees WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: employees.php'); exit;
}

$employees = $pdo->query("SELECT * FROM employees ORDER BY status ASC, full_name ASC")->fetchAll();
?>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="page-header">
  <h2>Wafanyakazi (<?= count($employees) ?>)</h2>
  <button class="btn-primary" onclick="openAdd()">+ Ongeza Mfanyakazi</button>
</div>

<div class="table-wrap">
<table>
<tr><th>Jina</th><th>Nafasi</th><th>Simu</th><th>Mshahara</th><th>Tarehe ya Kuajiriwa</th><th>Hali</th><th>Vitendo</th></tr>
<?php foreach ($employees as $e): ?>
<tr>
  <td><?= htmlspecialchars($e['full_name']) ?></td>
  <td><?= htmlspecialchars($e['position']) ?></td>
  <td><?= htmlspecialchars($e['phone'] ?: '-') ?></td>
  <td><?= money($e['salary']) ?></td>
  <td><?= $e['hired_date'] ? date('d/m/Y', strtotime($e['hired_date'])) : '-' ?></td>
  <td><span class="badge <?= $e['status']==='active'?'badge-completed':'badge-cancelled' ?>"><?= $e['status']==='active'?'Yupo Kazini':'Hayupo' ?></span></td>
  <td>
    <button class="btn-outline btn-sm" onclick='openEdit(<?= json_encode($e, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Hariri</button>
    <a class="btn-danger btn-sm" href="employees.php?delete=<?= $e['id'] ?>" onclick="return confirm('Futa mfanyakazi huyu?')">Futa</a>
  </td>
</tr>
<?php endforeach; ?>
<?php if (!$employees): ?><tr><td colspan="7" class="muted">Hakuna wafanyakazi bado.</td></tr><?php endif; ?>
</table>
</div>

<div class="modal-bg hidden" id="modalBg">
  <div class="modal">
    <span class="close-x" onclick="closeModal()">&times;</span>
    <h3 id="modalTitle">Ongeza Mfanyakazi</h3>
    <form method="POST">
      <input type="hidden" name="action" value="save_employee">
      <input type="hidden" name="id" id="f_id">
      <label>Jina Kamili</label>
      <input type="text" name="full_name" id="f_name" required>
      <div class="form-row">
        <div><label>Nafasi (Position)</label><input type="text" name="position" id="f_position" required placeholder="Mfano: Muuzaji, Msimamizi wa Stoo"></div>
        <div><label>Namba ya Simu</label><input type="text" name="phone" id="f_phone"></div>
      </div>
      <div class="form-row">
        <div><label>Mshahara (TZS)</label><input type="number" step="0.01" name="salary" id="f_salary" required></div>
        <div><label>Tarehe ya Kuajiriwa</label><input type="date" name="hired_date" id="f_hired_date"></div>
      </div>
      <label>Hali</label>
      <select name="status" id="f_status">
        <option value="active">Yupo Kazini</option>
        <option value="inactive">Hayupo</option>
      </select>
      <button type="submit" class="btn-primary" style="width:100%;margin-top:16px">Hifadhi</button>
    </form>
  </div>
</div>

<script>
function openAdd(){
  document.getElementById('modalTitle').innerText='Ongeza Mfanyakazi';
  document.getElementById('f_id').value='';
  document.getElementById('f_name').value='';
  document.getElementById('f_position').value='';
  document.getElementById('f_phone').value='';
  document.getElementById('f_salary').value='';
  document.getElementById('f_hired_date').value='';
  document.getElementById('f_status').value='active';
  document.getElementById('modalBg').classList.remove('hidden');
}
function openEdit(e){
  document.getElementById('modalTitle').innerText='Hariri Mfanyakazi';
  document.getElementById('f_id').value=e.id;
  document.getElementById('f_name').value=e.full_name;
  document.getElementById('f_position').value=e.position;
  document.getElementById('f_phone').value=e.phone || '';
  document.getElementById('f_salary').value=e.salary;
  document.getElementById('f_hired_date').value=e.hired_date || '';
  document.getElementById('f_status').value=e.status;
  document.getElementById('modalBg').classList.remove('hidden');
}
function closeModal(){ document.getElementById('modalBg').classList.add('hidden'); }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
