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
        (SELECT bp.filename FROM bike_photos bp WHERE bp.bike_id = b.id ORDER BY bp.created_at DESC LIMIT 1) AS photo_filename,
        GREATEST(b.updated_at, COALESCE(MAX(l.log_date), b.updated_at), COALESCE(MAX(l.created_at), b.updated_at), COALESCE(MAX(c.created_at), b.updated_at)) AS last_activity
     FROM bikes b
     LEFT JOIN people p ON p.id = b.owner_person_id
     LEFT JOIN maintenance_logs l ON l.bike_id = b.id
     LEFT JOIN components c ON c.bike_id = b.id
     GROUP BY b.id
     ORDER BY last_activity DESC"
)->fetchAll();

$soldBikes = array_values(array_filter($bikes, fn ($b) => (int) $b['is_sold'] === 1));
$bikes = array_values(array_filter($bikes, fn ($b) => (int) $b['is_sold'] === 0));

$pageTitle = 'Velos';
require __DIR__ . '/src/views/header.php';
?>
<div class="page-header">
    <h1>Velos</h1>
    <a class="button" href="/bike_edit.php">+ Neues Velo</a>
</div>

<?php
$renderBikeCard = function (array $bike): void {
    ?>
    <a class="card bike-card<?= $bike['is_active'] ? '' : ' inactive' ?>" href="/bike.php?id=<?= (int) $bike['id'] ?>">
        <?php if ($bike['photo_filename']): ?><img class="card-photo" src="/uploads/bikes/<?= htmlspecialchars($bike['photo_filename']) ?>" alt=""><?php endif; ?>
        <h2><?= htmlspecialchars($bike['name']) ?></h2>
        <p class="muted"><?= htmlspecialchars(trim($bike['brand'] . ' ' . $bike['model'])) ?></p>
        <?php if ($bike['owner_name']): ?><p class="owner">👤 <?= htmlspecialchars($bike['owner_name']) ?></p><?php endif; ?>
        <?php if ($bike['frame_number']): ?><p class="muted small">Rahmen-Nr.: <?= htmlspecialchars($bike['frame_number']) ?></p><?php endif; ?>
        <?php if ($bike['registration_number']): ?><p class="muted small">Kontrollschild-Nr.: <?= htmlspecialchars($bike['registration_number']) ?></p><?php endif; ?>
    </a>
    <?php
};
?>

<div class="card-grid">
<?php foreach ($bikes as $bike): ?>
    <?php $renderBikeCard($bike); ?>
<?php endforeach; ?>
<?php if (!$bikes): ?>
    <p class="muted">Noch keine Velos erfasst.</p>
<?php endif; ?>
</div>

<?php if ($soldBikes): ?>
<details class="panel collapsible sold-bikes">
    <summary><h2>Verkaufte Velos (<?= count($soldBikes) ?>)</h2></summary>
    <div class="card-grid">
    <?php foreach ($soldBikes as $bike): ?>
        <?php $renderBikeCard($bike); ?>
    <?php endforeach; ?>
    </div>
</details>
<?php endif; ?>
<?php
require __DIR__ . '/src/views/footer.php';
