<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();
$pageTitle = 'Dashboard';

$orders = (new Booking())->ordersForUser(currentUserId());
$upcomingOrders = array_filter($orders, fn($o) => $o['status'] === 'Paid' && strtotime($o['starts_at']) > time());
$attendance = hasRole('staff', 'manager') ? (new Ticket())->todaysAttendance() : [];

if (hasRole('manager')) {
    $sales = (new Report())->byPerformance();
    $revenue = array_sum(array_column($sales, 'revenue'));
    $ticketsSold = array_sum(array_column($sales, 'sold'));
}

require __DIR__ . '/includes/header.php';
?>
<h1>Hello, <?= e(strtok($_SESSION['full_name'], ' ')) ?></h1>

<div class="stats">
    <div class="stat"><span class="num"><?= count($upcomingOrders) ?></span><span class="label">Your upcoming bookings</span></div>
    <?php if (hasRole('manager')): ?>
        <div class="stat"><span class="num"><?= $ticketsSold ?></span><span class="label">Live tickets sold (all time)</span></div>
        <div class="stat"><span class="num"><?= money($revenue) ?></span><span class="label">Ticket revenue</span></div>
    <?php endif; ?>
</div>

<?php if ($attendance): ?>
    <h2>Tonight at the door</h2>
    <table>
        <thead><tr><th>Performance</th><th>Starts</th><th>Checked in</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($attendance as $a): ?>
            <tr>
                <td><?= e($a['title']) ?></td>
                <td><?= date('g:i a', strtotime($a['starts_at'])) ?></td>
                <td><?= (int)$a['checked_in'] ?> / <?= (int)$a['sold'] ?></td>
                <td><a class="btn btn-small" href="staff/checkin.php">Open check-in</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="cards">
    <article class="card">
        <h3>Book seats</h3>
        <p>Browse upcoming performances and pick seats on the live seat map.</p>
        <a class="btn btn-small" href="performances.php">What's on</a>
    </article>
    <article class="card">
        <h3>My bookings</h3>
        <p>View your tickets and codes, or cancel up to <?= CANCEL_CUTOFF_HOURS ?> hours before the show.</p>
        <a class="btn btn-small" href="my_bookings.php">View bookings</a>
    </article>
    <?php if (hasRole('manager')): ?>
        <article class="card">
            <h3>Run the season</h3>
            <p>Add shows, schedule performances with section prices, and track sales.</p>
            <a class="btn btn-small" href="manager/performances.php">Performances</a>
            <a class="btn btn-small btn-ghost" href="manager/reports.php">Sales report</a>
        </article>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
