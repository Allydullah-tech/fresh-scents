<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Kama tayari kuna admin, usiruhusu install tena
$stmt = $pdo->query("SELECT COUNT(*) c FROM admins");
$adminCount = (int) $stmt->fetch()['c'];

$error = '';
$success = false;

if ($adminCount > 0) {
    $installed = get_setting($pdo, 'installed', '0');
    if ($installed === '1') {
        // Tayari imesakinishwa
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adminCount === 0) {
    $full_name = clean($_POST['full_name'] ?? '');
    $username  = clean($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $sec_q     = clean($_POST['security_question'] ?? '');
    $sec_a     = clean($_POST['security_answer'] ?? '');
    $shop_name = clean($_POST['shop_name'] ?? 'FRESH SCENTS');
    $shop_phone = clean($_POST['shop_phone'] ?? '');
    $payment_name = clean($_POST['payment_name'] ?? '');

    if (!$full_name || !$username || !$password || !$sec_q || !$sec_a) {
        $error = 'Tafadhali jaza sehemu zote muhimu.';
    } elseif (strlen($password) < 6) {
        $error = 'Password lazima iwe na herufi/namba angalau 6.';
    } elseif ($password !== $confirm) {
        $error = 'Password hazifanani.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO admins (full_name, username, password_hash, security_question, security_answer_hash, role)
            VALUES (?,?,?,?,?, 'super_admin')");
        $stmt->execute([
            $full_name,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $sec_q,
            password_hash(strtolower(trim($sec_a)), PASSWORD_DEFAULT),
        ]);

        set_setting($pdo, 'shop_name', $shop_name ?: 'FRESH SCENTS');
        if ($shop_phone) {
            set_setting($pdo, 'shop_phone', $shop_phone);
            set_setting($pdo, 'payment_phone', $shop_phone);
        }
        if ($payment_name) set_setting($pdo, 'payment_name', $payment_name);
        set_setting($pdo, 'installed', '1');

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<title>Usakinishaji - FRESH SCENTS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="admin/css/admin.css">
<style>
 body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#1a1408,#2b2118);}
 .install-box{max-width:520px;width:92%;background:#fffdf8;border-radius:16px;padding:36px;box-shadow:0 20px 60px rgba(0,0,0,.4);}
 .install-box h1{font-size:22px;color:#8a6d1f;margin-bottom:4px;text-align:center}
 .install-box p.sub{text-align:center;color:#8a7d5f;margin-bottom:24px;font-size:14px}
 .logo-wrap{text-align:center;margin-bottom:14px}
 .logo-wrap img{width:80px}
</style>
</head>
<body>
<div class="install-box">
  <div class="logo-wrap"><img src="admin/images/logo.jpeg" alt="Fresh Scents"></div>
  <h1>Usakinishaji wa Mfumo</h1>
  <p class="sub">Karibu FRESH SCENTS &mdash; tengeneza akaunti ya kwanza ya Msimamizi Mkuu (Super Admin)</p>

  <?php if ($adminCount > 0 && !$success): ?>
    <div class="alert alert-info">Mfumo tayari umeshasakinishwa. <a href="admin/login.php">Bofya hapa kuingia</a>.</div>
  <?php elseif ($success): ?>
    <div class="alert alert-success">Akaunti ya msimamizi imetengenezwa kikamilifu! <a href="admin/login.php">Bofya hapa kuingia sasa</a>.</div>
  <?php else: ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <label>Jina la Duka</label>
      <input type="text" name="shop_name" value="FRESH SCENTS" required>

      <label>Namba ya Simu ya Duka / Malipo</label>
      <input type="text" name="shop_phone" placeholder="0621002091">

      <label>Jina Litakalotumika Kulipia (Mobile Money)</label>
      <input type="text" name="payment_name" placeholder="YAHYA JUMA IS-HAKA">

      <hr style="margin:18px 0;border-color:#eee2c5">

      <label>Jina Kamili la Msimamizi</label>
      <input type="text" name="full_name" required>

      <label>Jina la Kuingilia (Username)</label>
      <input type="text" name="username" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Rudia Password</label>
      <input type="password" name="confirm_password" required>

      <label>Swali la Usalama (kwa kusahau password)</label>
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

      <button type="submit" class="btn-primary" style="width:100%;margin-top:16px">Tengeneza Akaunti</button>
    </form>
    <script>
      function onSecQChange() {
        const sel = document.getElementById('secQSelect');
        document.getElementById('secQCustom').classList.toggle('hidden', sel.value !== '__custom__');
      }
      document.querySelector('form').addEventListener('submit', function (e) {
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
  <?php endif; ?>
</div>
</body>
</html>
