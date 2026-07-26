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
    "SELECT b.*, p.name AS owner_name,
        GREATEST(b.updated_at, COALESCE(MAX(l.log_date), b.updated_at), COALESCE(MAX(l.created_at), b.updated_at), COALESCE(MAX(c.created_at), b.updated_at)) AS last_activity
     FROM bikes b
     LEFT JOIN people p ON p.id = b.owner_person_id
     LEFT JOIN maintenance_logs l ON l.bike_id = b.id
     LEFT JOIN components c ON c.bike_id = b.id
     GROUP BY b.id
     ORDER BY last_activity DESC"
)->fetchAll();

$pageTitle = 'Velos';
require __DIR__ . '/src/views/header.php';
?>
<div class="page-header">
    <h1>Velos</h1>
    <a class="button" href="/bike_edit.php">+ Neues Velo</a>
</div>

<div class="card-grid">
<?php foreach ($bikes as $bike): ?>
    <a class="card bike-card<?= $bike['is_active'] ? '' : ' inactive' ?>" href="/bike.php?id=<?= (int) $bike['id'] ?>">
        <h2><?= htmlspecialchars($bike['name']) ?></h2>
        <p class="muted"><?= htmlspecialchars(trim($bike['brand'] . ' ' . $bike['model'])) ?></p>
        <?php if ($bike['owner_name']): ?><p class="owner">👤 <?= htmlspecialchars($bike['owner_name']) ?></p><?php endif; ?>
        <?php if ($bike['frame_number']): ?><p class="muted small">Rahmen-Nr.: <?= htmlspecialchars($bike['frame_number']) ?></p><?php endif; ?>
    </a>
<?php endforeach; ?>
<?php if (!$bikes): ?>
    <p class="muted">Noch keine Velos erfasst.</p>
<?php endif; ?>
</div>
<?php
require __DIR__ . '/src/views/footer.php';
