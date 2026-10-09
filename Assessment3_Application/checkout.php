<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();
$pageTitle = 'Checkout';

$booking = new Booking();
$performanceId = (int)($_GET['performance'] ?? $_POST['performance_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'release') {
        $booking->releaseHolds(currentUserId(), $performanceId);
        flash('success', 'Your seats have been released.');
        redirect("performance.php?id=$performanceId");
    }
    if ($action === 'pay') {
        if (empty($_POST['agree'])) {
            flash('error', 'Please accept the booking conditions to continue.');
            redirect("checkout.php?performance=$performanceId");
        }
        $result = $booking->checkout(currentUserId(), $performanceId);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect($result['ok'] ? 'booking.php?id=' . $result['order_id'] : "performance.php?id=$performanceId");
    }
}

$performance = (new Performance())->find($performanceId);
$held = $booking->heldSeats(currentUserId(), $performanceId);
if (!$performance || !$held) {
    flash('error', 'You have no seats on hold for that performance - they may have expired.');
    redirect($performance ? "performance.php?id=$performanceId" : 'performances.php');
}

$total = array_sum(array_column($held, 'price'));
$secondsLeft = (int)min(array_column($held, 'seconds_left'));

require __DIR__ . '/includes/header.php';
?>
<h1>Checkout</h1>
<div class="checkout">
    <section class="panel">
        <h2><?= e($performance['title']) ?></h2>
        <p class="muted"><?= date('l j F Y, g:i a', strtotime($performance['starts_at'])) ?> &middot; <?= e($performance['venue_name']) ?></p>
        <table>
            <thead><tr><th>Seat</th><th>Section</th><th class="num">Price</th></tr></thead>
            <tbody>
            <?php foreach ($held as $seat): ?>
                <tr>
                    <td><?= e($seat['row_label'] . $seat['seat_number']) ?></td>
                    <td><?= e($seat['section_name']) ?></td>
                    <td class="num"><?= money($seat['price']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th colspan="2">Total</th><th class="num"><?= money($total) ?></th></tr></tfoot>
        </table>
    </section>

    <aside class="panel">
        <div class="timer" id="timer" data-seconds="<?= $secondsLeft ?>">
            <span class="label">Seats held for</span>
            <span class="clock" id="clock">--:--</span>
        </div>
        <form method="post" action="checkout.php">
            <?= csrfField() ?>
            <input type="hidden" name="performance_id" value="<?= $performanceId ?>">
            <p class="hint">Payment is simulated in this prototype. No card details are collected or stored.</p>
            <label class="check"><input type="checkbox" name="agree" value="1" required>
                I understand tickets can be cancelled for a refund up to <?= CANCEL_CUTOFF_HOURS ?> hours before the performance.</label>
            <button class="btn btn-wide" type="submit" name="action" value="pay" id="pay-btn">Pay <?= money($total) ?></button>
        </form>
        <form method="post" action="checkout.php">
            <?= csrfField() ?>
            <input type="hidden" name="performance_id" value="<?= $performanceId ?>">
            <button class="link-button" type="submit" name="action" value="release">Release these seats</button>
        </form>
    </aside>
</div>

<script>
// Countdown driven by the server's remaining seconds (not the user's clock).
// When it reaches zero the pay button is disabled - the server re-checks anyway.
(function () {
    const timer = document.getElementById('timer');
    const clock = document.getElementById('clock');
    let left = parseInt(timer.dataset.seconds, 10);
    function tick() {
        if (left <= 0) {
            clock.textContent = '0:00';
            timer.classList.add('expired');
            document.getElementById('pay-btn').disabled = true;
            timer.querySelector('.label').textContent = 'Hold expired - please reselect seats';
            return;
        }
        clock.textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
        timer.classList.toggle('urgent', left <= 60);
        left--;
        setTimeout(tick, 1000);
    }
    tick();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
