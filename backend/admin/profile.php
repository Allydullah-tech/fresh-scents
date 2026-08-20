<?php
$pageTitle = 'Wasifu Wangu'; $activePage = 'profile';
require_once __DIR__ . '/includes/header.php';

$msg = ''; $err = '';
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id=?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $full_name = clean($_POST['full_name'] ?? '');
        $username = clean($_POST['username'] ?? '');
        $email = clean($_POST['email'] ?? '');
        if ($full_name && $username) {
            $pdo->prepare("UPDATE admins SET full_name=?, username=?, email=? WHERE id=?")
                ->execute([$full_name, $username, $email, $_SESSION['admin_id']]);
            $_SESSION['admin_name'] = $full_name;
            $msg = 'Taarifa zako zimesasishwa kikamilifu.';
        }
    }

    if ($action === 'update_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $admin['password_hash'])) {
            $err = 'Password ya sasa si sahihi.';
        } elseif (strlen($new) < 6) {
            $err = 'Password mpya lazima iwe na herufi/namba angalau 6.';
        } elseif ($new !== $confirm) {
            $err = 'Password mpya hazifanani.';
        } else {
            $pdo->prepare("UPDATE admins SET password_hash=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
            $msg = 'Password imebadilishwa kikamilifu.';
        }
    }

    if ($action === 'update_security') {
        $sec_q = clean($_POST['security_question'] ?? '');
        $sec_a = clean($_POST['security_answer'] ?? '');
        if ($sec_q && $sec_a) {
            $pdo->prepare("UPDATE admins SET security_question=?, security_answer_hash=? WHERE id=?")
                ->execute([$sec_q, password_hash(strtolower(trim($sec_a)), PASSWORD_DEFAULT), $_SESSION['admin_id']]);
            $msg = 'Swali la usalama limesasishwa kikamilifu.';
        }
    }

    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
}
?>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="grid grid-2">
  <div class="card">
    <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-person-circle"></i> Taarifa Binafsi</h3>
    <form method="POST">
      <input type="hidden" name="action" value="update_info">
      <label>Jina Kamili</label>
      <input type="text" name="full_name" value="<?= htmlspecialchars($admin['full_name']) ?>" required>
      <label>Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required>
      <label>Email (hiari)</label>
      <input type="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>">
      <button class="btn-primary" style="width:100%;margin-top:14px" type="submit">Hifadhi</button>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-lock"></i> Badilisha Password</h3>
    <form method="POST">
      <input type="hidden" name="action" value="update_password">
      <label>Password ya Sasa</label>
      <input type="password" name="current_password" required>
      <label>Password Mpya</label>
      <input type="password" name="new_password" required>
      <label>Rudia Password Mpya</label>
      <input type="password" name="confirm_password" required>
      <button class="btn-primary" style="width:100%;margin-top:14px" type="submit">Badilisha Password</button>
    </form>
  </div>
</div>

<br>
<div class="card" style="max-width:600px">
  <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-shield-check"></i> Swali la Usalama</h3>
  <p class="muted">Hili linatumika kukusaidia kurejesha password endapo utasahau.</p>
  <form method="POST" id="secQForm">
    <input type="hidden" name="action" value="update_security">
    <label>Swali la Usalama la Sasa</label>
    <input type="text" value="<?= htmlspecialchars($admin['security_question']) ?>" disabled>

    <label>Chagua Swali Jipya</label>
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

    <label>Jibu Jipya</label>
    <input type="text" name="security_answer" required placeholder="Andika jibu jipya">
    <button class="btn-primary" style="width:100%;margin-top:14px" type="submit">Sasisha Swali la Usalama</button>
  </form>
</div>

<script>
  function onSecQChange() {
    const sel = document.getElementById('secQSelect');
    document.getElementById('secQCustom').classList.toggle('hidden', sel.value !== '__custom__');
  }
  document.getElementById('secQForm').addEventListener('submit', function (e) {
    const sel = document.getElementById('secQSelect');
    const custom = document.getElementById('secQCustom');
    const finalVal = sel.value === '__custom__' ? custom.value.trim() : sel.value;
    if (!finalVal) {
      e.preventDefault();
      alert('Tafadhali chagua au andika swali jipya la usalama.');
      return;
    }
    document.getElementById('security_question_final').value = finalVal;
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
