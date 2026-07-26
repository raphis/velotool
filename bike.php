<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$bikeId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT b.*, p.name AS owner_name
     FROM bikes b LEFT JOIN people p ON p.id = b.owner_person_id
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

$photoError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['delete_photo_id'])) {
        $photoId = (int) $_POST['delete_photo_id'];
        $stmt = $pdo->prepare('SELECT filename FROM bike_photos WHERE id = ? AND bike_id = ?');
        $stmt->execute([$photoId, $bikeId]);
        $filename = $stmt->fetchColumn();
        if ($filename) {
            $pdo->prepare('DELETE FROM bike_photos WHERE id = ?')->execute([$photoId]);
            @unlink(__DIR__ . '/uploads/bikes/' . $filename);
        }
        header('Location: /bike.php?id=' . $bikeId);
        exit;
    }

    if (isset($_POST['upload_photo']) && !empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $maxBytes = 8 * 1024 * 1024;

        if ($_FILES['photo']['size'] > $maxBytes) {
            $photoError = 'Datei zu gross (max. 8 MB).';
        } else {
            $info = @getimagesize($_FILES['photo']['tmp_name']);
            if ($info === false || !isset($allowedMimes[$info['mime']])) {
                $photoError = 'Nur JPG, PNG oder WebP-Bilder erlaubt.';
            } else {
                $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimes[$info['mime']];
                $dest = __DIR__ . '/uploads/bikes/' . $filename;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                    $pdo->prepare('INSERT INTO bike_photos (bike_id, filename, original_name) VALUES (?, ?, ?)')
                        ->execute([$bikeId, $filename, $_FILES['photo']['name'] ?? null]);
                    header('Location: /bike.php?id=' . $bikeId);
                    exit;
                }
                $photoError = 'Upload fehlgeschlagen.';
            }
        }
    }
}

$photos = $pdo->prepare('SELECT * FROM bike_photos WHERE bike_id = ? ORDER BY created_at DESC');
$photos->execute([$bikeId]);
$photos = $photos->fetchAll();

$components = $pdo->prepare(
    "SELECT * FROM components WHERE bike_id = ? ORDER BY is_current DESC, category"
);
$components->execute([$bikeId]);
$components = $components->fetchAll();

$logs = $pdo->prepare(
    "SELECT l.*,
        GROUP_CONCAT(DISTINCT c.category, ' – ', c.name ORDER BY c.category SEPARATOR ', ') AS component_names,
        GROUP_CONCAT(DISTINCT pc.name, ' (', mlp.quantity, 'x)' ORDER BY pc.name SEPARATOR ', ') AS parts_used_names
     FROM maintenance_logs l
     LEFT JOIN maintenance_log_components mlc ON mlc.log_id = l.id
     LEFT JOIN components c ON c.id = mlc.component_id
     LEFT JOIN maintenance_log_parts mlp ON mlp.log_id = l.id
     LEFT JOIN parts_catalog pc ON pc.id = mlp.catalog_item_id
     WHERE l.bike_id = ?
     GROUP BY l.id
     ORDER BY l.log_date DESC, l.id DESC"
);
$logs->execute([$bikeId]);
$logs = $logs->fetchAll();

$catalogStock = $pdo->prepare(
    "SELECT c.* FROM parts_catalog c
     WHERE NOT EXISTS (SELECT 1 FROM parts_catalog_bikes pcb WHERE pcb.catalog_item_id = c.id)
        OR EXISTS (SELECT 1 FROM parts_catalog_bikes pcb WHERE pcb.catalog_item_id = c.id AND pcb.bike_id = ?)
     ORDER BY (stock_qty = 0), manufacturer, name"
);
$catalogStock->execute([$bikeId]);
$catalogStock = $catalogStock->fetchAll();

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
    </div>
</div>

<section class="panel">
    <h2>Fotos</h2>
    <?php if ($photoError): ?><p class="error"><?= htmlspecialchars($photoError) ?></p><?php endif; ?>
    <?php if ($photos): ?>
    <div class="photo-grid">
        <?php foreach ($photos as $p): ?>
        <div class="photo-item">
            <a href="/uploads/bikes/<?= htmlspecialchars($p['filename']) ?>" target="_blank" rel="noopener">
                <img src="/uploads/bikes/<?= htmlspecialchars($p['filename']) ?>" alt="" loading="lazy">
            </a>
            <form method="post" onsubmit="return confirm('Foto wirklich löschen?');">
                <?= csrf_field() ?>
                <input type="hidden" name="delete_photo_id" value="<?= (int) $p['id'] ?>">
                <button type="submit" class="photo-delete" aria-label="Foto löschen">×</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p class="muted">Noch keine Fotos hochgeladen.</p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="photo-upload-form">
        <?= csrf_field() ?>
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
        <button type="submit" name="upload_photo" value="1" class="button secondary">Foto hochladen</button>
    </form>
</section>

<section class="panel">
    <h2>Details</h2>
    <dl class="detail-grid">
        <?php if ($bike['owner_name']): ?><dt>Gehört</dt><dd><?= htmlspecialchars($bike['owner_name']) ?></dd><?php endif; ?>
        <?php if ($bike['frame_number']): ?><dt>Rahmennummer</dt><dd><?= htmlspecialchars($bike['frame_number']) ?></dd><?php endif; ?>
        <?php if ($bike['registration_number']): ?><dt>Kontrollschild-Nr.</dt><dd><?= htmlspecialchars($bike['registration_number']) ?></dd><?php endif; ?>
        <?php if ($bike['frame_size']): ?><dt>Rahmengrösse</dt><dd><?= htmlspecialchars($bike['frame_size']) ?></dd><?php endif; ?>
        <?php if ($bike['color']): ?><dt>Farbe</dt><dd><?= htmlspecialchars($bike['color']) ?></dd><?php endif; ?>
        <?php if ($bike['purchase_date']): ?><dt>Kaufdatum</dt><dd><?= htmlspecialchars($bike['purchase_date']) ?></dd><?php endif; ?>
        <?php if ($bike['purchase_price']): ?><dt>Kaufpreis</dt><dd><?= htmlspecialchars($bike['purchase_price_currency']) ?> <?= htmlspecialchars($bike['purchase_price']) ?></dd><?php endif; ?>
        <?php if ($bike['dealer']): ?><dt>Händler</dt><dd><?= htmlspecialchars($bike['dealer']) ?></dd><?php endif; ?>
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
        <thead><tr><th>Datum</th><th>km</th><th>Kategorie</th><th>Beschreibung</th><th>Verwendete Teile</th><th>Messwerte</th><th></th></tr></thead>
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
                <td><?= $l['mileage_km'] !== null ? (int) $l['mileage_km'] : '' ?></td>
                <td><?= htmlspecialchars($l['category']) ?><?= $l['component_names'] ? ' (' . htmlspecialchars($l['component_names']) . ')' : '' ?></td>
                <td><?= nl2br(htmlspecialchars($l['description'])) ?></td>
                <td class="muted small"><?= $l['parts_used_names'] ? htmlspecialchars($l['parts_used_names']) : '' ?></td>
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
    <h2>Lagerbestand (passende Ersatzteile)</h2>
    <?php if ($catalogStock): ?>
    <table class="data-table">
        <thead><tr><th>Teil</th><th>Lieferant</th><th>Preis</th><th>Auf Lager</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($catalogStock as $c): ?>
            <tr class="<?= (int) $c['stock_qty'] === 0 ? 'inactive' : '' ?>">
                <td><?= htmlspecialchars(($c['manufacturer'] ? $c['manufacturer'] . ' – ' : '') . $c['name']) ?></td>
                <td><?= $c['supplier'] ? htmlspecialchars($c['supplier']) : '' ?></td>
                <td><?= $c['price_chf'] !== null ? 'CHF ' . htmlspecialchars($c['price_chf']) : '' ?></td>
                <td>
                    <?php if ((int) $c['stock_qty'] > 0): ?>
                    <span class="badge status-installed"><?= (int) $c['stock_qty'] ?>x</span>
                    <?php else: ?>
                    <span class="muted small">–</span>
                    <?php endif; ?>
                </td>
                <td><a href="/catalog_edit.php?id=<?= (int) $c['id'] ?>">bearbeiten</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="muted">Keine passenden Katalog-Teile erfasst. <a href="/catalog.php">Katalog verwalten</a></p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/src/views/footer.php'; ?>
