<?php
$pageTitle = 'Wasimamizi (Admins)'; $activePage = 'admins';
require_once __DIR__ . '/includes/header.php';

if (!is_super_admin()) {
    echo '<div class="alert alert-error">Huna ruhusa ya kuona ukurasa huu. Kurasa hii ni kwa Msimamizi Mkuu tu.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='add_admin') {
    $full_name = clean($_POST['full_name'] ?? '');
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] === 'super_admin' ? 'super_admin' : 'admin';
    $sec_q = clean($_POST['security_question'] ?? '');
    $sec_a = clean($_POST['security_answer'] ?? '');

    $chk = $pdo->prepare("SELECT id FROM admins WHERE username=?");
    $chk->execute([$username]);

    if (!$full_name || !$username || !$password || !$sec_q || !$sec_a) {
        $err = 'Tafadhali jaza sehemu zote.';
    } elseif ($chk->fetch()) {
        $err = 'Username hii tayari inatumika.';
    } elseif (strlen($password) < 6) {
        $err = 'Password lazima iwe na herufi/namba angalau 6.';
    } else {
        $pdo->prepare("INSERT INTO admins (full_name, username, password_hash, security_question, security_answer_hash, role) VALUES (?,?,?,?,?,?)")
            ->execute([$full_name, $username, password_hash($password, PASSWORD_DEFAULT), $sec_q, password_hash(strtolower(trim($sec_a)), PASSWORD_DEFAULT), $role]);
        $msg = 'Msimamizi mpya ameongezwa kikamilifu.';
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    if ($id != $_SESSION['admin_id']) {
        $pdo->prepare("UPDATE admins SET status = IF(status='active','disabled','active') WHERE id=?")->execute([$id]);
    }
    header('Location: admins.php'); exit;
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['admin_id']) {
        $pdo->prepare("DELETE FROM admins WHERE id=?")->execute([$id]);
    }
    header('Location: admins.php'); exit;
}

$admins = $pdo->query("SELECT * FROM admins ORDER BY created_at ASC")->fetchAll();
?>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="page-header">
  <h2>Wasimamizi wa Mfumo (<?= count($admins) ?>)</h2>
  <button class="btn-primary" onclick="document.getElementById('modalBg').classList.remove('hidden')">+ Ongeza Msimamizi</button>
</div>

<div class="table-wrap">
<table>
<tr><th>Jina</th><th>Username</th><th>Cheo</th><th>Hali</th><th>Aliingia Mwisho</th><th>Vitendo</th></tr>
<?php foreach ($admins as $a): ?>
<tr>
  <td><?= htmlspecialchars($a['full_name']) ?></td>
  <td><?= htmlspecialchars($a['username']) ?></td>
  <td><?= $a['role']==='super_admin' ? 'Msimamizi Mkuu' : 'Msimamizi' ?></td>
  <td><span class="badge <?= $a['status']==='active'?'badge-completed':'badge-cancelled' ?>"><?= $a['status']==='active'?'Hai':'Amezimwa' ?></span></td>
  <td><?= fdate($a['last_login']) ?></td>
  <td>
    <?php if ($a['id'] != $_SESSION['admin_id']): ?>
    <a class="btn-outline btn-sm" href="admins.php?toggle=<?= $a['id'] ?>"><?= $a['status']==='active'?'Zima':'Washa' ?></a>
    <a class="btn-danger btn-sm" href="admins.php?delete=<?= $a['id'] ?>" onclick="return confirm('Futa msimamizi huyu?')">Futa</a>
    <?php else: ?><span class="muted">(Wewe)</span><?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="modal-bg hidden" id="modalBg">
  <div class="modal">
    <span class="close-x" onclick="document.getElementById('modalBg').classList.add('hidden')">&times;</span>
    <h3>Ongeza Msimamizi Mpya</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add_admin">
      <label>Jina Kamili</label>
      <input type="text" name="full_name" required>
      <label>Username</label>
      <input type="text" name="username" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <label>Cheo</label>
      <select name="role">
        <option value="admin">Msimamizi</option>
        <option value="super_admin">Msimamizi Mkuu</option>
      </select>
      <label>Swali la Usalama</label>
      <select id="secQSelect" onchange="onSecQChange()" required>
        <option value="">-- Chagua Swali --</option>
        <option value="Jina la mnyama wako wa kwanza ni nani?">Jina la mnyama wako wa kwanza ni nani?</option>
        <option value="Ulizaliwa mji/kijiji gani?">Ulizaliwa mji/kijiji gani?</option>
        <option value="Jina la shule yako ya msingi ni lipi?">Jina la shule yako ya msingi ni lipi?</option>
        <option value="Chakula unachokipenda zaidi ni kipi?">Chakula unachokipenda zaidi ni kipi?</option>
        <option value="Jina la rafiki yako wa karibu utotoni ni nani?">Jina la rafiki yako wa karibu utotoni ni nani?</option>
        <option value="Jina la kati la mzazi wako ni lipi?">Jina la kati la mzazi wako ni lipi?</option>
        <option value="__custom__">Swali Langu Mwenyewe (Andika)</option>
      </select>
      <input type="text" id="secQCustom" class="hidden" placeholder="Andika swali lako hapa" style="margin-top:10px">
      <input type="hidden" name="security_question" id="security_question_final">
      <label>Jibu la Swali la Usalama</label>
      <input type="text" name="security_answer" required>
      <button type="submit" class="btn-primary" style="width:100%;margin-top:16px">Ongeza Msimamizi</button>
    </form>
  </div>
</div>

<script>
  function onSecQChange() {
    const sel = document.getElementById('secQSelect');
    document.getElementById('secQCustom').classList.toggle('hidden', sel.value !== '__custom__');
  }
  document.querySelector('#modalBg form').addEventListener('submit', function (e) {
    const sel = document.getElementById('secQSelect');
    const custom = document.getElementById('secQCustom');
    const finalVal = sel.value === '__custom__' ? custom.value.trim() : sel.value;
    if (!finalVal) {
      e.preventDefault();
      alert('Tafadhali chagua au andika swali la usalama.');
      return;
    }
    document.getElementById('security_question_final').value = finalVal;
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
