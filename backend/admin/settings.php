<?php
$pageTitle = 'Mipangilio ya Duka'; $activePage = 'settings';
require_once __DIR__ . '/includes/header.php';

if (!is_super_admin()) {
    echo '<div class="alert alert-error">Huna ruhusa ya kuona ukurasa huu.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['shop_name','shop_tagline','payment_phone','payment_name','shop_phone','shop_address'] as $key) {
        set_setting($pdo, $key, clean($_POST[$key] ?? ''));
    }
    $msg = 'Mipangilio imehifadhiwa kikamilifu.';
}

$fields = [
    'shop_name' => 'Jina la Duka',
    'shop_tagline' => 'Kaulimbiu ya Duka',
    'shop_phone' => 'Namba ya Simu ya Duka',
    'shop_address' => 'Anuani ya Duka',
    'payment_name' => 'Jina la Kutumika Kulipia (Mobile Money)',
    'payment_phone' => 'Namba ya Kulipia (Mobile Money)',
];
?>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card" style="max-width:600px">
  <h3 style="margin-top:0;color:var(--gold-dark)"><i class="bi bi-gear"></i> Mipangilio ya Jumla</h3>
  <form method="POST">
    <?php foreach ($fields as $key => $label): ?>
      <label><?= $label ?></label>
      <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars(get_setting($pdo, $key)) ?>">
    <?php endforeach; ?>
    <button class="btn-primary" style="width:100%;margin-top:16px" type="submit">Hifadhi Mipangilio</button>
  </form>
  <p class="muted" style="margin-top:14px">Namba na jina la malipo hapa juu ndivyo vinavyoonekana kwenye ujumbe wa malipo ya delivery kwa wateja.</p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
