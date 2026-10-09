<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireRole('manager');
$pageTitle = 'Sales Report';

$report = new Report();
$byPerformance = $report->byPerformance();
$byShow = $report->byShow();

require __DIR__ . '/../includes/header.php';
?>
<h1>Sales report</h1>
<p class="muted">Live tickets only - refunded tickets are excluded from every figure.</p>

<h2>By show</h2>
<table>
    <thead><tr><th>Show</th><th class="num">Performances</th><th class="num">Tickets</th><th class="num">Revenue</th></tr></thead>
    <tbody>
    <?php foreach ($byShow as $s): ?>
        <tr><td><?= e($s['title']) ?></td><td class="num"><?= (int)$s['performances'] ?></td>
            <td class="num"><?= (int)$s['sold'] ?></td><td class="num"><?= money($s['revenue']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>By performance</h2>
<table>
    <thead><tr><th>When</th><th>Show</th><th>Status</th><th class="num">Sold / Capacity</th><th>Occupancy</th><th class="num">Attended</th><th class="num">Revenue</th></tr></thead>
    <tbody>
    <?php foreach ($byPerformance as $p): ?>
        <tr>
            <td><?= date('D j M, g:i a', strtotime($p['starts_at'])) ?></td>
            <td><?= e($p['title']) ?></td>
            <td><?= $p['status'] === 'Scheduled' && strtotime($p['starts_at']) < time() ? 'Finished' : e($p['status']) ?></td>
            <td class="num"><?= (int)$p['sold'] ?> / <?= (int)$p['capacity'] ?></td>
            <td><div class="meter small-meter" aria-label="<?= e($p['occupancy_pct']) ?>%"><span style="width: <?= (float)$p['occupancy_pct'] ?>%"></span></div>
                <span class="small"><?= e($p['occupancy_pct']) ?>%</span></td>
            <td class="num"><?= (int)$p['attended'] ?></td>
            <td class="num"><?= money($p['revenue']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
