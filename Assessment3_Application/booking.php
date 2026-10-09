<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();
$pageTitle = 'Your tickets';

// orderForUser() only returns the order if it belongs to the logged-in user,
// so changing the id in the URL cannot reveal someone else's tickets.
$order = (new Booking())->orderForUser((int)($_GET['id'] ?? 0), currentUserId());
if (!$order) {
    http_response_code(404);
    flash('error', 'Booking not found.');
    redirect('my_bookings.php');
}

require __DIR__ . '/includes/header.php';
?>
<p class="eyebrow"><a href="my_bookings.php">&larr; My bookings</a></p>
<h1>Booking <?= e($order['booking_ref']) ?></h1>
<p class="muted"><?= e($order['title']) ?> &middot; <?= date('l j F Y, g:i a', strtotime($order['starts_at'])) ?>
   &middot; <?= e($order['venue_name']) ?>, <?= e($order['address']) ?></p>
<p>Status: <span class="tag tag-<?= strtolower(e($order['status'])) ?>"><?= e($order['status']) ?></span>
   &middot; Total paid <?= money($order['total']) ?></p>

<div class="tickets">
<?php foreach ($order['tickets'] as $t): ?>
    <article class="ticket <?= $t['is_active'] ? '' : 'void' ?>">
        <div class="ticket-main">
            <p class="eyebrow">Harbourlight Theatre</p>
            <h3><?= e($order['title']) ?></h3>
            <p><?= date('D j M Y, g:i a',strtotime($order['starts_at'])) ?></p>
            <p><?= e($t['section_name']) ?> &middot; Row <?= e($t['row_label']) ?> &middot; Seat <?= (int)$t['seat_number'] ?></p>
        </div>
        <div class="ticket-stub">
            <span class="label">Ticket code</span>
            <code class="code"><?= e($t['ticket_code']) ?></code>
            <span class="small">
                <?php if (!$t['is_active']): ?>VOID - refunded
                <?php elseif ($t['checked_in_at']): ?>Used <?= date('j M g:i a', strtotime($t['checked_in_at'])) ?>
                <?php else: ?>Show this code at the door<?php endif; ?>
            </span>
        </div>
    </article>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
