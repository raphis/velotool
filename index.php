<?php
require __DIR__ . '/src/bootstrap.php';

if (!Auth::isLoggedIn()) {
    $pageTitle = 'Willkommen';
    require __DIR__ . '/src/views/header.php';
    ?>
    <div class="auth-page">
        <img src="/assets/logo.svg" alt="Velotool" class="auth-logo">
        <h1>Velotool</h1>
        <p>Wartung &amp; Ausstattung eurer Velos an einem Ort.</p>
        <a class="button" href="/login.php">Mit Google anmelden</a>
    </div>
    <?php
    require __DIR__ . '/src/views/footer.php';
    exit;
}

$pdo = Database::get();
$bikes = $pdo->query(
    'SELECT b.*, u.name AS owner_name
     FROM bikes b
     LEFT JOIN users u ON u.id = b.owner_user_id
     ORDER BY b.is_active DESC, b.name'
)->fetchAll();

$openPartsCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM parts_needed WHERE status != 'installed'"
)->fetchColumn();

$pageTitle = 'Velos';
require __DIR__ . '/src/views/header.php';
?>
<div class="page-header">
    <h1>Velos</h1>
    <a class="button" href="/bike_edit.php">+ Neues Velo</a>
</div>

<?php if ($openPartsCount > 0): ?>
<p class="notice"><a href="/parts.php">📦 <?= $openPartsCount ?> offene Ersatzteil-Position<?= $openPartsCount === 1 ? '' : 'en' ?></a></p>
<?php endif; ?>

<div class="card-grid">
<?php foreach ($bikes as $bike): ?>
    <a class="card bike-card<?= $bike['is_active'] ? '' : ' inactive' ?>" href="/bike.php?id=<?= (int) $bike['id'] ?>">
        <h2><?= htmlspecialchars($bike['name']) ?></h2>
        <p class="muted"><?= htmlspecialchars(trim($bike['brand'] . ' ' . $bike['model'])) ?></p>
        <?php if ($bike['owner_name']): ?><p class="owner">👤 <?= htmlspecialchars($bike['owner_name']) ?></p><?php endif; ?>
        <?php if ($bike['serial_number']): ?><p class="muted small">SN: <?= htmlspecialchars($bike['serial_number']) ?></p><?php endif; ?>
    </a>
<?php endforeach; ?>
<?php if (!$bikes): ?>
    <p class="muted">Noch keine Velos erfasst.</p>
<?php endif; ?>
</div>
<?php
require __DIR__ . '/src/views/footer.php';
