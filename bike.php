<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$bikeId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT b.*, u.name AS owner_name
     FROM bikes b LEFT JOIN users u ON u.id = b.owner_user_id
     WHERE b.id = ?'
);
$stmt->execute([$bikeId]);
$bike = $stmt->fetch();

if (!$bike) {
    http_response_code(404);
    $pageTitle = 'Nicht gefunden';
    require __DIR__ . '/src/views/header.php';
    echo '<p>Velo nicht gefunden.</p>';
    require __DIR__ . '/src/views/footer.php';
    exit;
}

$components = $pdo->prepare(
    "SELECT * FROM components WHERE bike_id = ? ORDER BY is_current DESC, category"
);
$components->execute([$bikeId]);
$components = $components->fetchAll();

$logs = $pdo->prepare(
    'SELECT l.*, c.name AS component_name
     FROM maintenance_logs l LEFT JOIN components c ON c.id = l.component_id
     WHERE l.bike_id = ? ORDER BY l.log_date DESC, l.id DESC'
);
$logs->execute([$bikeId]);
$logs = $logs->fetchAll();

$parts = $pdo->prepare(
    "SELECT p.*, c.name AS component_name FROM parts_needed p
     LEFT JOIN components c ON c.id = p.component_id
     WHERE p.bike_id = ? ORDER BY (p.status = 'installed'), p.priority = 'high' DESC, p.created_at DESC"
);
$parts->execute([$bikeId]);
$parts = $parts->fetchAll();

$pageTitle = $bike['name'];
require __DIR__ . '/src/views/header.php';
?>
<div class="page-header">
    <div>
        <h1><?= htmlspecialchars($bike['name']) ?></h1>
        <p class="muted"><?= htmlspecialchars(trim($bike['brand'] . ' ' . $bike['model'])) ?><?= $bike['model_year'] ? ' · ' . (int) $bike['model_year'] : '' ?></p>
    </div>
    <div class="actions">
        <a class="button secondary" href="/bike_edit.php?id=<?= (int) $bike['id'] ?>">Bearbeiten</a>
        <a class="button secondary" href="/component_edit.php?bike_id=<?= (int) $bike['id'] ?>">+ Komponente</a>
        <a class="button secondary" href="/maintenance_edit.php?bike_id=<?= (int) $bike['id'] ?>">+ Wartungseintrag</a>
        <a class="button secondary" href="/part_edit.php?bike_id=<?= (int) $bike['id'] ?>">+ Ersatzteil</a>
    </div>
</div>

<section class="panel">
    <h2>Details</h2>
    <dl class="detail-grid">
        <?php if ($bike['owner_name']): ?><dt>Gehört</dt><dd><?= htmlspecialchars($bike['owner_name']) ?></dd><?php endif; ?>
        <?php if ($bike['serial_number']): ?><dt>Seriennummer</dt><dd><?= htmlspecialchars($bike['serial_number']) ?></dd><?php endif; ?>
        <?php if ($bike['frame_size']): ?><dt>Rahmengrösse</dt><dd><?= htmlspecialchars($bike['frame_size']) ?></dd><?php endif; ?>
        <?php if ($bike['color']): ?><dt>Farbe</dt><dd><?= htmlspecialchars($bike['color']) ?></dd><?php endif; ?>
        <?php if ($bike['purchase_date']): ?><dt>Kaufdatum</dt><dd><?= htmlspecialchars($bike['purchase_date']) ?></dd><?php endif; ?>
        <?php if ($bike['purchase_price']): ?><dt>Kaufpreis</dt><dd>CHF <?= htmlspecialchars($bike['purchase_price']) ?></dd><?php endif; ?>
        <?php if ($bike['weight_kg']): ?><dt>Gewicht</dt><dd><?= htmlspecialchars($bike['weight_kg']) ?> kg</dd><?php endif; ?>
    </dl>
    <?php if ($bike['notes']): ?><p class="notes"><?= nl2br(htmlspecialchars($bike['notes'])) ?></p><?php endif; ?>
</section>

<section class="panel">
    <h2>Komponenten</h2>
    <?php if ($components): ?>
    <table class="data-table">
        <thead><tr><th>Kategorie</th><th>Bezeichnung</th><th>Hersteller</th><th>Details</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($components as $c): ?>
            <tr class="<?= $c['is_current'] ? '' : 'inactive' ?>">
                <td><?= htmlspecialchars($c['category']) ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?= htmlspecialchars($c['manufacturer'] ?? '') ?></td>
                <td class="muted small"><?= htmlspecialchars($c['details'] ?? '') ?></td>
                <td><a href="/component_edit.php?id=<?= (int) $c['id'] ?>">bearbeiten</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="muted">Noch keine Komponenten erfasst.</p>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Wartungshistorie</h2>
    <?php if ($logs): ?>
    <table class="data-table">
        <thead><tr><th>Datum</th><th>Kategorie</th><th>Beschreibung</th><th>km</th><th>Kosten</th><th>Messwerte</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <?php
            $measurements = [];
            if ($l['chain_wear_percent'] !== null) $measurements[] = 'Kette ' . htmlspecialchars($l['chain_wear_percent']) . '%';
            if ($l['disc_thickness_front_mm'] !== null) $measurements[] = 'Scheibe v. ' . htmlspecialchars($l['disc_thickness_front_mm']) . 'mm';
            if ($l['disc_thickness_rear_mm'] !== null) $measurements[] = 'Scheibe h. ' . htmlspecialchars($l['disc_thickness_rear_mm']) . 'mm';
            if ($l['pad_condition_front_percent'] !== null) $measurements[] = 'Klötze v. ' . (int) $l['pad_condition_front_percent'] . '%';
            if ($l['pad_condition_rear_percent'] !== null) $measurements[] = 'Klötze h. ' . (int) $l['pad_condition_rear_percent'] . '%';
            if ($l['other_measurements']) $measurements[] = htmlspecialchars($l['other_measurements']);
            ?>
            <tr>
                <td><?= htmlspecialchars($l['log_date']) ?></td>
                <td><?= htmlspecialchars($l['category']) ?><?= $l['component_name'] ? ' (' . htmlspecialchars($l['component_name']) . ')' : '' ?></td>
                <td><?= nl2br(htmlspecialchars($l['description'])) ?></td>
                <td><?= $l['mileage_km'] !== null ? (int) $l['mileage_km'] : '' ?></td>
                <td><?= $l['cost'] !== null ? 'CHF ' . htmlspecialchars($l['cost']) : '' ?></td>
                <td class="muted small"><?= $measurements ? implode('<br>', $measurements) : '' ?></td>
                <td><a href="/maintenance_edit.php?id=<?= (int) $l['id'] ?>">bearbeiten</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="muted">Noch keine Wartungseinträge.</p>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Ersatzteile</h2>
    <?php if ($parts): ?>
    <table class="data-table">
        <thead><tr><th>Teil</th><th>Grund</th><th>Status</th><th>Priorität</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($parts as $p): ?>
            <tr class="<?= $p['status'] === 'installed' ? 'inactive' : '' ?>">
                <td><?= htmlspecialchars($p['part_name']) ?><?= $p['component_name'] ? ' (' . htmlspecialchars($p['component_name']) . ')' : '' ?></td>
                <td class="muted small"><?= htmlspecialchars($p['reason'] ?? '') ?></td>
                <td><span class="badge status-<?= htmlspecialchars($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                <td><span class="badge prio-<?= htmlspecialchars($p['priority']) ?>"><?= htmlspecialchars($p['priority']) ?></span></td>
                <td><a href="/part_edit.php?id=<?= (int) $p['id'] ?>">bearbeiten</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="muted">Keine offenen Ersatzteile.</p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/src/views/footer.php'; ?>
