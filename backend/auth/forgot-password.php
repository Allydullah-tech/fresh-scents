<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$step = $_POST['step'] ?? 'find';
$error = '';
$success = '';
$question = null;
$user_type = clean($_POST['user_type'] ?? 'customer');
$identifier = clean($_POST['identifier'] ?? '');

function find_user($pdo, $user_type, $identifier) {
    if ($user_type === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
    }
    return $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 'find') {
        $user = find_user($pdo, $user_type, $identifier);
        if (!$user) {
            $error = 'Hatujapata akaunti yenye taarifa hizo.';
        } else {
            $question = $user['security_question'];
            $step = 'answer';
        }
    } elseif ($step === 'reset') {
        $answer = strtolower(trim($_POST['security_answer'] ?? ''));
        $new_password = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $user = find_user($pdo, $user_type, $identifier);

        if (!$user) {
            $error = 'Hitilafu imetokea, jaribu tena.';
            $step = 'find';
        } elseif (!password_verify($answer, $user['security_answer_hash'])) {
            $error = 'Jibu la swali la usalama si sahihi.';
            $question = $user['security_question'];
            $step = 'answer';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password mpya lazima iwe na herufi/namba angalau 6.';
            $question = $user['security_question'];
            $step = 'answer';
        } elseif ($new_password !== $confirm) {
            $error = 'Password hazifanani.';
            $question = $user['security_question'];
            $step = 'answer';
        } else {
            $table = $user_type === 'admin' ? 'admins' : 'customers';
            $stmt = $pdo->prepare("UPDATE $table SET password_hash = ? WHERE id = ?");
            $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user['id']]);
            $success = 'Password yako imebadilishwa kikamilifu! Sasa unaweza kuingia kwa password mpya.';
            $step = 'done';
        }
    } elseif ($step === 'answer') {
        $user = find_user($pdo, $user_type, $identifier);
        $question = $user['security_question'] ?? '';
        $step = 'answer';
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Umesahau Password? - FRESH SCENTS</title>
<link rel="stylesheet" href="../admin/css/admin.css">
<style>
 body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#1a1408,#2b2118);}
 .box{max-width:460px;width:92%;background:#fffdf8;border-radius:16px;padding:36px;box-shadow:0 20px 60px rgba(0,0,0,.4);}
 .box h1{font-size:20px;color:#8a6d1f;text-align:center;margin-bottom:20px}
 .logo-wrap{text-align:center;margin-bottom:10px}
 .logo-wrap img{width:70px}
 .switch-link{text-align:center;margin-top:14px;font-size:14px}
</style>
</head>
<body>
<div class="box">
  <div class="logo-wrap"><img src="../../frontend/images/logo.jpeg" alt="Fresh Scents"></div>
  <h1>Kurejesha Password kwa Swali la Usalama</h1>

  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <?php if ($step === 'done'): ?>
    <p style="text-align:center">
      <a class="btn-primary" style="display:inline-block;text-decoration:none;text-align:center" href="<?= $user_type === 'admin' ? '../admin/login.php' : '../../frontend/login.html' ?>">Nenda Kuingia</a>
    </p>

  <?php elseif ($step === 'answer'): ?>
    <form method="POST">
      <input type="hidden" name="step" value="reset">
      <input type="hidden" name="user_type" value="<?= htmlspecialchars($user_type) ?>">
      <input type="hidden" name="identifier" value="<?= htmlspecialchars($identifier) ?>">

      <label>Swali la Usalama</label>
      <input type="text" value="<?= htmlspecialchars($question) ?>" disabled>

      <label>Jibu Lako</label>
      <input type="text" name="security_answer" required autofocus>

      <label>Password Mpya</label>
      <input type="password" name="new_password" required>

      <label>Rudia Password Mpya</label>
      <input type="password" name="confirm_password" required>

      <button type="submit" class="btn-primary" style="width:100%;margin-top:14px">Badilisha Password</button>
    </form>

  <?php else: ?>
    <form method="POST">
      <input type="hidden" name="step" value="find">
      <label>Wewe ni nani?</label>
      <select name="user_type" required>
        <option value="customer" <?= $user_type==='customer'?'selected':'' ?>>Mteja</option>
        <option value="admin" <?= $user_type==='admin'?'selected':'' ?>>Msimamizi (Admin)</option>
      </select>

      <label>Namba ya Simu / Username / Email</label>
      <input type="text" name="identifier" required autofocus>

      <button type="submit" class="btn-primary" style="width:100%;margin-top:14px">Endelea</button>
    </form>
  <?php endif; ?>

  <div class="switch-link"><a href="../../frontend/index.html">&larr; Rudi Mwanzo</a></div>
</div>
</body>
</html>
