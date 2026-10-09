<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireRole('staff', 'manager');
$pageTitle = 'Door Check-in';

$ticketModel = new Ticket();
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['ticket_code'] ?? ''));
    $result = preg_match('/^[A-Z0-9]{10}$/', $code)
        ? $ticketModel->checkIn($code)
        : ['ok' => false, 'message' => 'Ticket codes are 10 letters and numbers.', 'ticket' => null];
}

$attendance = $ticketModel->todaysAttendance();
require __DIR__ . '/../includes/header.php';
?>
<h1>Door check-in</h1>

<div class="checkout">
    <section class="panel">
        <form method="post" action="checkin.php" class="checkin-form">
            <?= csrfField() ?>
            <label for="ticket_code">Ticket code</label>
            <input id="ticket_code" name="ticket_code" autocomplete="off" autofocus maxlength="10"
                   placeholder="T7A2K9Q1MX" style="text-transform:uppercase">
            <button class="btn" type="submit">Check in</button>
        </form>

        <?php if ($result): ?>
            <div class="verdict <?= $result['ok'] ? 'admit' : 'deny' ?>" role="status">
                <strong><?= $result['ok'] ? 'ADMIT' : 'DO NOT ADMIT' ?></strong>
                <span><?= e($result['message']) ?></span>
                <?php if ($result['ticket']): $t = $result['ticket']; ?>
                    <span class="small"><?= e($t['full_name']) ?> &middot; <?= e($t['title']) ?> &middot;
                        <?= e($t['section_name']) ?> <?= e($t['row_label'] . $t['seat_number']) ?> &middot; <?= e($t['booking_ref']) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <h2>Today</h2>
        <?php if (!$attendance): ?>
            <p class="muted">No performances today.</p>
        <?php endif; ?>
        <?php foreach ($attendance as $a):
            $pct = $a['sold'] ? round(100 * $a['checked_in'] / $a['sold']) : 0; ?>
            <p><strong><?= e($a['title']) ?></strong> &middot; <?= date('g:i a', strtotime($a['starts_at'])) ?></p>
            <div class="meter" aria-label="<?= $pct ?>% checked in"><span style="width: <?= $pct ?>%"></span></div>
            <p class="muted small"><?= (int)$a['checked_in'] ?> of <?= (int)$a['sold'] ?> ticket holders checked in</p>
        <?php endforeach; ?>
    </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
