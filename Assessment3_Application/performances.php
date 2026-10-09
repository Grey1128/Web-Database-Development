<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = "What's On";

$showId = (int)($_GET['show'] ?? 0);
$date = $_GET['date'] ?? '';
if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = '';
}

$performances = (new Performance())->upcoming($showId, $date);
$shows = (new Show())->all();

require __DIR__ . '/includes/header.php';
?>
<h1>What's on</h1>

<form method="get" action="performances.php" class="filters">
    <label class="sr-only" for="show">Show</label>
    <select id="show" name="show">
        <option value="0">All shows</option>
        <?php foreach ($shows as $s): ?>
            <option value="<?= (int)$s['show_id'] ?>" <?= $showId === (int)$s['show_id'] ? 'selected' : '' ?>><?= e($s['title']) ?></option>
        <?php endforeach; ?>
    </select>
    <label class="sr-only" for="date">Date</label>
    <input id="date" type="date" name="date" value="<?= e($date) ?>">
    <button class="btn" type="submit">Filter</button>
    <?php if ($showId || $date): ?><a class="btn btn-ghost" href="performances.php">Clear</a><?php endif; ?>
</form>

<?php if (!$performances): ?>
    <p class="panel">No upcoming performances match that search.</p>
<?php endif; ?>

<div class="perf-list">
<?php foreach ($performances as $p): ?>
    <article class="perf">
        <div class="perf-date">
            <span class="day"><?= date('j', strtotime($p['starts_at'])) ?></span>
            <span class="mon"><?= date('M', strtotime($p['starts_at'])) ?></span>
        </div>
        <div class="perf-body">
            <p class="eyebrow"><?= e($p['genre']) ?> &middot; <?= e($p['age_rating']) ?> &middot; <?= (int)$p['duration_mins'] ?> min</p>
            <h3><?= e($p['title']) ?></h3>
            <p class="muted"><?= date('l, g:i a', strtotime($p['starts_at'])) ?> &middot; <?= e($p['venue_name']) ?></p>
        </div>
        <div class="perf-cta">
            <p>From <strong><?= money($p['from_price']) ?></strong></p>
            <?php if ((int)$p['seats_left'] > 0): ?>
                <p class="muted"><?= (int)$p['seats_left'] ?> seats left</p>
                <a class="btn btn-small" href="performance.php?id=<?= (int)$p['performance_id'] ?>">Choose seats</a>
            <?php else: ?>
                <span class="tag tag-sold">Sold out</span>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
