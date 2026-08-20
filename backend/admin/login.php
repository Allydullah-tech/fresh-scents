<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Kama hakuna admin bado, elekeza kwenye install.php
$stmt = $pdo->query("SELECT COUNT(*) c FROM admins");
if ((int)$stmt->fetch()['c'] === 0) {
    header('Location: ../install.php');
    exit;
}

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        $error = 'Username au password si sahihi.';
    } elseif ($admin['status'] !== 'active') {
        $error = 'Akaunti yako imezimwa. Wasiliana na msimamizi mkuu.';
    } else {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
        $pdo->prepare("INSERT INTO login_logs (user_type, user_id, ip_address) VALUES ('admin', ?, ?)")
            ->execute([$admin['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
        header('Location: dashboard.php');
        exit;
    }
}

$shop_name = get_setting($pdo, 'shop_name', 'FRESH SCENTS');
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - <?= htmlspecialchars($shop_name) ?></title>
<link rel="stylesheet" href="css/admin.css">
<link rel="icon" href="images/logo.jpeg">
<style>
 body{
   display:flex;align-items:center;justify-content:center;min-height:100vh;
   background:linear-gradient(135deg,#1a1408,#2b2118), url('../../frontend/images/logo.jpeg');
   background-blend-mode:overlay; background-size:cover; background-position:center;
 }
 .login-box{max-width:400px;width:92%;background:rgba(255,253,248,.97);border-radius:16px;padding:36px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
 .logo-wrap{text-align:center;margin-bottom:6px}
 .logo-wrap img{width:78px}
 .login-box h1{font-size:19px;color:var(--gold-dark);text-align:center;margin:6px 0 22px}
 .fp-link{display:block;text-align:right;font-size:13px;margin-top:8px}
</style>
</head>
<body>
<div class="login-box">
  <div class="logo-wrap"><img src="images/logo.jpeg" alt="logo"></div>
  <h1>Ingia Kama Msimamizi<br><?= htmlspecialchars($shop_name) ?></h1>
  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <label>Username</label>
    <input type="text" name="username" required autofocus>
    <label>Password</label>
    <input type="password" name="password" required>
    <a class="fp-link" href="../auth/forgot-password.php">Umesahau password?</a>
    <button type="submit" class="btn-primary" style="width:100%;margin-top:16px">Ingia</button>
  </form>
  <p style="text-align:center;margin-top:18px;font-size:13px"><a href="../../frontend/index.html">&larr; Rudi Tovuti Kuu</a></p>
</div>
</body>
</html>
