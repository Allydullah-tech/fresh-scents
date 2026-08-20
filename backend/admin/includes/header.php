<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_admin_login();

$shop_name = get_setting($pdo, 'shop_name', 'FRESH SCENTS');
$pageTitle = $pageTitle ?? 'Dashibodi';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> - Admin | <?= htmlspecialchars($shop_name) ?></title>
<link rel="stylesheet" href="css/admin.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="icon" href="images/logo.jpeg">
<script>
  // Zuia browser kurudisha (restore) scroll ya ukurasa uliopita kiotomatiki.
  // (Haisogezi ukurasa popote — inazuia tu tabia ya "auto restore" ya browser.)
  if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
  }
</script>
</head>
<body>
<div class="admin-wrap">
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <img src="images/logo.jpeg" alt="logo">
      <span><?= htmlspecialchars($shop_name) ?></span>
      <button class="sidebar-close" onclick="closeSidebar()" aria-label="Funga menyu"><i class="bi bi-x-lg"></i></button>
    </div>
    <nav>
      <a href="dashboard.php" class="<?= $activePage==='dashboard'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashibodi</a>
      <a href="products.php" class="<?= $activePage==='products'?'active':'' ?>"><i class="bi bi-droplet-half"></i> Bidhaa (Manukato)</a>
      <a href="requests.php" class="<?= $activePage==='requests'?'active':'' ?>"><i class="bi bi-box-seam"></i> Maombi/Oda za Wateja</a>
      <a href="pos.php" class="<?= $activePage==='pos'?'active':'' ?>"><i class="bi bi-bag-check"></i> Uuzaji Dukani</a>
      <a href="stock.php" class="<?= $activePage==='stock'?'active':'' ?>"><i class="bi bi-box-arrow-in-down"></i> Stock Zinazoingia</a>

      <div class="section-label">Fedha</div>
      <a href="sales.php" class="<?= $activePage==='sales'?'active':'' ?>"><i class="bi bi-cash-coin"></i> Mauzo</a>
      <a href="finance.php" class="<?= $activePage==='finance'?'active':'' ?>"><i class="bi bi-graph-up-arrow"></i> Mapato na Matumizi</a>
      <a href="payroll.php" class="<?= $activePage==='payroll'?'active':'' ?>"><i class="bi bi-receipt"></i> Malipo ya Wafanyakazi</a>

      <div class="section-label">Wafanyakazi</div>
      <a href="employees.php" class="<?= $activePage==='employees'?'active':'' ?>"><i class="bi bi-people"></i> Wafanyakazi</a>

      <?php if (is_super_admin()): ?>
      <div class="section-label">Usimamizi</div>
      <a href="admins.php" class="<?= $activePage==='admins'?'active':'' ?>"><i class="bi bi-shield-lock"></i> Wasimamizi (Admins)</a>
      <a href="settings.php" class="<?= $activePage==='settings'?'active':'' ?>"><i class="bi bi-gear"></i> Mipangilio ya Duka</a>
      <?php endif; ?>

      <div class="section-label">Akaunti</div>
      <a href="profile.php" class="<?= $activePage==='profile'?'active':'' ?>"><i class="bi bi-person-circle"></i> Wasifu Wangu</a>
      <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Toka</a>
    </nav>
  </aside>

  <div class="main">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="hamburger" onclick="toggleSidebar()" aria-label="Fungua menyu"><i class="bi bi-list"></i></button>
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
      </div>
      <div class="user-chip">
        Karibu, <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? '') ?></strong>
        &nbsp;(<?= $_SESSION['admin_role'] === 'super_admin' ? 'Msimamizi Mkuu' : 'Msimamizi' ?>)
      </div>
    </div>
    <div class="content">
