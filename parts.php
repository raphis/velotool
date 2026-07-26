<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$parts = $pdo->query(
    "SELECT p.*, b.name AS bike_name, c.name AS component_name
     FROM parts_needed p
     JOIN bikes b ON b.id = p.bike_id
     LEFT JOIN components c ON c.id = p.component_id
     ORDER BY (p.status = 'installed'), p.priority = 'high' DESC, p.created_at DESC"
)->fetchAll();

$pageTitle = 'Ersatzteile';
require __DIR__ . '/src/views/header.php';
?>
<h1>Ersatzteile – alle Velos</h1>

<?php if ($parts): ?>
<table class="data-table">
    <thead><tr><th>Velo</th><th>Teil</th><th>Grund</th><th>Status</th><th>Priorität</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($parts as $p): ?>
        <tr class="<?= $p['status'] === 'installed' ? 'inactive' : '' ?>">
            <td><a href="/bike.php?id=<?= (int) $p['bike_id'] ?>"><?= htmlspecialchars($p['bike_name']) ?></a></td>
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
    <p class="muted">Keine Ersatzteile erfasst.</p>
<?php endif; ?>
<?php require __DIR__ . '/src/views/footer.php'; ?>
