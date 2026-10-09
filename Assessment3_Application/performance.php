<?php
require_once __DIR__ . '/includes/bootstrap.php';

$performanceModel = new Performance();
$performanceId = (int)($_GET['id'] ?? 0);
$performance = $performanceModel->find($performanceId);
if (!$performance) {
    http_response_code(404);
    flash('error', 'That performance could not be found.');
    redirect('performances.php');
}

$pageTitle = $performance['title'];
$seats = $performanceModel->seatMap($performanceId, currentUserId());
$tiers = $performanceModel->priceTiers($performanceId);

// Group seats as [section][row][] for drawing the map.
$map = [];
$myHeld = 0;
foreach ($seats as $seat) {
    $map[$seat['section_name']][$seat['row_label']][] = $seat;
    $myHeld += $seat['state'] === 'mine' ? 1 : 0;
}

require __DIR__ . '/includes/header.php';
?>
<p class="eyebrow"><a href="performances.php">&larr; What's on</a></p>
<h1><?= e($performance['title']) ?></h1>
<p class="muted">
    <?= date('l j F Y, g:i a', strtotime($performance['starts_at'])) ?> &middot; <?= e($performance['venue_name']) ?>
    &middot; <?= (int)$performance['duration_mins'] ?> min &middot; <?= e($performance['age_rating']) ?>
</p>
<p><?= e($performance['description']) ?></p>

<?php if (!$performance['is_bookable']): ?>
    <div class="flash flash-error">This performance is <?= $performance['status'] === 'Cancelled' ? 'cancelled' : 'no longer on sale' ?>.</div>
<?php else: ?>

<?php if ($myHeld): ?>
    <div class="flash flash-info">You are holding <?= $myHeld ?> seat(s) for this performance.
        <a href="checkout.php?performance=<?= $performanceId ?>">Continue to checkout &rarr;</a></div>
<?php endif; ?>

<div class="legend" aria-hidden="true">
    <span><i class="seat available"></i> Available</span>
    <span><i class="seat selected"></i> Your selection</span>
    <span><i class="seat held"></i> Being held</span>
    <span><i class="seat sold"></i> Sold</span>
    <?php foreach ($tiers as $t): ?>
        <span class="price-chip"><?= e($t['section_name']) ?> <?= money($t['price']) ?></span>
    <?php endforeach; ?>
</div>

<form method="post" action="hold.php" id="seat-form">
    <?= csrfField() ?>
    <input type="hidden" name="performance_id" value="<?= $performanceId ?>">

    <div class="seatmap">
        <div class="stage">STAGE</div>
        <?php foreach ($map as $sectionName => $rows): ?>
            <fieldset class="section-block">
                <legend><?= e($sectionName) ?></legend>
                <?php foreach ($rows as $rowLabel => $rowSeats): ?>
                    <div class="seat-row">
                        <span class="row-label"><?= e($rowLabel) ?></span>
                        <?php foreach ($rowSeats as $seat):
                            $label = $seat['row_label'] . $seat['seat_number'];
                            $free = in_array($seat['state'], ['available', 'mine'], true);
                        ?>
                            <label class="seat <?= e($seat['state']) ?>" title="<?= e("$sectionName $label - " . money($seat['price'])) ?>">
                                <input type="checkbox" name="seats[]" value="<?= (int)$seat['seat_id'] ?>"
                                       data-price="<?= e($seat['price']) ?>" data-label="<?= e($label) ?>"
                                       <?= $seat['state'] === 'mine' ? 'checked' : '' ?> <?= $free ? '' : 'disabled' ?>>
                                <span><?= (int)$seat['seat_number'] ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </fieldset>
        <?php endforeach; ?>
    </div>

    <div class="basket" id="basket">
        <div><strong id="basket-count">0 seats</strong> <span class="muted" id="basket-seats"></span></div>
        <div>Total <strong id="basket-total">$0.00</strong></div>
        <?php if (isLoggedIn()): ?>
            <button type="submit" class="btn" id="hold-btn" disabled>Hold seats &amp; check out</button>
        <?php else: ?>
            <a class="btn" href="login.php">Log in to book</a>
        <?php endif; ?>
    </div>
</form>

<script>
// Live basket: count, seat list and total update as seats are clicked.
(function () {
    const MAX = <?= MAX_SEATS_PER_ORDER ?>;
    const boxes = Array.from(document.querySelectorAll('#seat-form input[name="seats[]"]'));
    const btn = document.getElementById('hold-btn');

    function refresh() {
        const chosen = boxes.filter(b => b.checked);
        boxes.forEach(b => {
            b.parentElement.classList.toggle('selected', b.checked);
            if (!b.checked && !b.parentElement.classList.contains('sold') && !b.parentElement.classList.contains('held')) {
                b.disabled = chosen.length >= MAX;   // stop selection at the limit
            }
        });
        const total = chosen.reduce((sum, b) => sum + parseFloat(b.dataset.price), 0);
        document.getElementById('basket-count').textContent = chosen.length + (chosen.length === 1 ? ' seat' : ' seats');
        document.getElementById('basket-seats').textContent = chosen.map(b => b.dataset.label).join(', ');
        document.getElementById('basket-total').textContent = '$' + total.toFixed(2);
        if (btn) btn.disabled = chosen.length === 0;
    }
    boxes.forEach(b => b.addEventListener('change', refresh));
    refresh();
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
