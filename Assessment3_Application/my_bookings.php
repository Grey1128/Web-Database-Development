<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();
$pageTitle = 'My Bookings';
$booking = new Booking();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $ok = $booking->cancelOrder((int)$_POST['cancel_order'], currentUserId());
    flash($ok ? 'success' : 'error', $ok
        ? 'Booking cancelled. A refund has been issued and the seats released.'
        : 'That booking can no longer be cancelled.');
    redirect('my_bookings.php');
}

$orders = $booking->ordersForUser(currentUserId());
require __DIR__ . '/includes/header.php';
?>
<h1>My bookings</h1>
<table>
    <thead>
        <tr><th>Booking</th><th>Performance</th><th>Tickets</th><th class="num">Total</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php if (!$orders): ?>
        <tr><td colspan="6">No bookings yet. <a href="performances.php">See what's on</a>.</td></tr>
    <?php endif; ?>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td><a href="booking.php?id=<?= (int)$o['order_id'] ?>"><?= e($o['booking_ref']) ?></a></td>
            <td><?= e($o['title']) ?><br><span class="muted small"><?= date('D j M Y, g:i a', strtotime($o['starts_at'])) ?></span></td>
            <td><?= (int)$o['ticket_count'] ?></td>
            <td class="num"><?= money($o['total']) ?></td>
            <td>
                <?php if ($o['performance_status'] === 'Cancelled'): ?>
                    <span class="tag tag-refunded">Show cancelled &middot; refunded</span>
                <?php else: ?>
                    <span class="tag tag-<?= strtolower(e($o['status'])) ?>"><?= e($o['status']) ?></span>
                <?php endif; ?>
            </td>
            <td>
                <a class="btn btn-small btn-ghost" href="booking.php?id=<?= (int)$o['order_id'] ?>">Tickets</a>
                <?php if ($o['can_cancel']): ?>
                    <form method="post" action="my_bookings.php" class="inline" onsubmit="return confirm('Cancel booking <?= e($o['booking_ref']) ?> and get a refund?');">
                        <?= csrfField() ?>
                        <button class="btn btn-small btn-danger" name="cancel_order" value="<?= (int)$o['order_id'] ?>">Cancel</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/includes/footer.php'; ?>
