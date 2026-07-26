<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bikeId = (int) ($_GET['bike_id'] ?? $_POST['bike_id'] ?? 0);

$part = ['part_name' => '', 'reason' => '', 'component_id' => '', 'catalog_item_id' => '', 'status' => 'needed',
    'priority' => 'normal', 'price_chf' => '', 'ordered_date' => ''];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM parts_needed WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $part = $found;
        $bikeId = (int) $found['bike_id'];
    }
}

if (!$bikeId) {
    http_response_code(400);
    exit('bike_id fehlt.');
}

$components = $pdo->prepare('SELECT id, name, category FROM components WHERE bike_id = ? ORDER BY category, name');
$components->execute([$bikeId]);
$components = $components->fetchAll();

$catalog = $pdo->query('SELECT id, name, manufacturer, price_chf, stock_qty, stock_note FROM parts_catalog ORDER BY manufacturer, name')->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data = [
        'part_name' => trim($_POST['part_name'] ?? ''),
        'reason' => trim($_POST['reason'] ?? '') ?: null,
        'component_id' => $_POST['component_id'] !== '' ? (int) $_POST['component_id'] : null,
        'catalog_item_id' => $_POST['catalog_item_id'] !== '' ? (int) $_POST['catalog_item_id'] : null,
        'status' => in_array($_POST['status'] ?? '', ['needed', 'ordered', 'installed'], true) ? $_POST['status'] : 'needed',
        'priority' => in_array($_POST['priority'] ?? '', ['low', 'normal', 'high'], true) ? $_POST['priority'] : 'normal',
        'price_chf' => $_POST['price_chf'] !== '' ? $_POST['price_chf'] : null,
        'ordered_date' => $_POST['ordered_date'] ?: null,
    ];

    if ($data['part_name'] === '') {
        $errors[] = 'Teil-Bezeichnung ist erforderlich.';
    }

    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE parts_needed SET part_name=?, reason=?, component_id=?, catalog_item_id=?, status=?, priority=?, price_chf=?, ordered_date=? WHERE id=?')
                ->execute([...array_values($data), $id]);
        } else {
            $pdo->prepare('INSERT INTO parts_needed (bike_id, part_name, reason, component_id, catalog_item_id, status, priority, price_chf, ordered_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$bikeId, ...array_values($data)]);
        }
        header('Location: /bike.php?id=' . $bikeId);
        exit;
    }
    $part = array_merge($part, $data);
}

$pageTitle = $id ? 'Ersatzteil bearbeiten' : 'Neues Ersatzteil';
require __DIR__ . '/src/views/header.php';
?>
<h1><?= htmlspecialchars($pageTitle) ?></h1>
<?php foreach ($errors as $e): ?><p class="error"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <input type="hidden" name="bike_id" value="<?= (int) $bikeId ?>">
    <input type="hidden" name="catalog_item_id" value="<?= htmlspecialchars((string) ($part['catalog_item_id'] ?? '')) ?>">

    <?php if ($catalog): ?>
    <label class="full">Aus Katalog übernehmen<select id="catalogPick" onchange="pickCatalogItem(this)">
        <option value="">– manuell eingeben –</option>
        <?php foreach ($catalog as $c): ?>
        <option value="<?= (int) $c['id'] ?>"
            data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>"
            data-price="<?= htmlspecialchars((string) $c['price_chf'], ENT_QUOTES) ?>">
            <?= htmlspecialchars(($c['manufacturer'] ? $c['manufacturer'] . ' – ' : '') . $c['name']) ?> (<?= number_format((float) $c['price_chf'], 2) ?> CHF)<?= (int) $c['stock_qty'] > 0 ? ' — auf Lager: ' . (int) $c['stock_qty'] . 'x' : '' ?>
        </option>
        <?php endforeach; ?>
    </select></label>
    <p class="muted small"><a href="/catalog.php">Katalog &amp; Lagerbestand verwalten</a></p>
    <?php endif; ?>

    <label>Teil*<input type="text" name="part_name" id="partNameField" value="<?= htmlspecialchars($part['part_name']) ?>" required></label>
    <label>Preis (CHF)<input type="number" step="0.01" name="price_chf" id="priceField" value="<?= htmlspecialchars((string) ($part['price_chf'] ?? '')) ?>"></label>
    <label>Komponente<select name="component_id">
        <option value="">–</option>
        <?php foreach ($components as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= (int) ($part['component_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category'] . ' – ' . $c['name']) ?></option>
        <?php endforeach; ?>
    </select></label>
    <label class="full">Grund<textarea name="reason" rows="3"><?= htmlspecialchars($part['reason'] ?? '') ?></textarea></label>
    <label>Status<select name="status">
        <?php foreach (['needed' => 'benötigt', 'ordered' => 'bestellt', 'installed' => 'eingebaut'] as $val => $label): ?>
        <option value="<?= $val ?>" <?= $part['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select></label>
    <label>Priorität<select name="priority">
        <?php foreach (['low' => 'niedrig', 'normal' => 'normal', 'high' => 'hoch'] as $val => $label): ?>
        <option value="<?= $val ?>" <?= $part['priority'] === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select></label>
    <label>Bestellt am<input type="date" name="ordered_date" value="<?= htmlspecialchars($part['ordered_date'] ?? '') ?>"></label>

    <div class="form-actions">
        <button type="submit" class="button">Speichern</button>
        <a class="button secondary" href="/bike.php?id=<?= (int) $bikeId ?>">Abbrechen</a>
    </div>
</form>

<?php if ($catalog): ?>
<script>
function pickCatalogItem(select) {
    var opt = select.options[select.selectedIndex];
    document.querySelector('input[name="catalog_item_id"]').value = opt.value;
    if (opt.value) {
        document.getElementById('partNameField').value = opt.dataset.name;
        document.getElementById('priceField').value = opt.dataset.price;
    }
}
</script>
<?php endif; ?>
<?php require __DIR__ . '/src/views/footer.php'; ?>
