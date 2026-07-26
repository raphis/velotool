<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$items = $pdo->query(
    'SELECT c.*, b.name AS bike_name
     FROM parts_catalog c LEFT JOIN bikes b ON b.id = c.for_bike_id
     ORDER BY c.manufacturer, c.name'
)->fetchAll();

$pageTitle = 'Ersatzteil-Katalog';
require __DIR__ . '/src/views/header.php';
?>
<div class="page-header">
    <h1>Ersatzteil-Katalog &amp; Lager</h1>
    <a class="button" href="/catalog_edit.php">+ Neues Teil</a>
</div>

<?php if ($items): ?>
<table class="data-table">
    <thead><tr><th>Teil</th><th>Für Velo</th><th>Preis</th><th>Auf Lager</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
        <tr>
            <td><?= htmlspecialchars(($i['manufacturer'] ? $i['manufacturer'] . ' – ' : '') . $i['name']) ?></td>
            <td><?= $i['bike_name'] ? htmlspecialchars($i['bike_name']) : '<span class="muted small">universell</span>' ?></td>
            <td><?= $i['price_chf'] !== null ? 'CHF ' . htmlspecialchars($i['price_chf']) : '' ?></td>
            <td>
                <?php if ((int) $i['stock_qty'] > 0): ?>
                <span class="badge status-installed"><?= (int) $i['stock_qty'] ?>x</span>
                <?php else: ?>
                <span class="muted small">–</span>
                <?php endif; ?>
                <?php if ($i['stock_note']): ?><span class="muted small"><?= htmlspecialchars($i['stock_note']) ?></span><?php endif; ?>
            </td>
            <td><a href="/catalog_edit.php?id=<?= (int) $i['id'] ?>">bearbeiten</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p class="muted">Noch keine Katalog-Teile erfasst.</p>
<?php endif; ?>
<?php require __DIR__ . '/src/views/footer.php'; ?>
