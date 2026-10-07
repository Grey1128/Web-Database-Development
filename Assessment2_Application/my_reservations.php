<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Reservation.php';
requireLogin();

$pageTitle = 'My Reservations';
$resModel = new Reservation();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $resModel->cancel((int)$_POST['cancel_id'], (int)$_SESSION['user_id']);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Reservation cancelled.'];
    header('Location: my_reservations.php');
    exit;
}

$reservations = $resModel->findByUser((int)$_SESSION['user_id']);
require __DIR__ . '/includes/header.php';
?>
<h1>My Reservations</h1>

<table>
    <thead>
        <tr><th>Book</th><th>Reserved on</th><th>Due date</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (!$reservations): ?>
            <tr><td colspan="5">You have no reservations yet. <a href="books.php">Browse the catalogue</a>.</td></tr>
        <?php endif; ?>
        <?php foreach ($reservations as $r): ?>
            <tr>
                <td><?= e($r['title']) ?><br><span style="color:#647178;font-size:0.8rem;">by <?= e($r['author']) ?></span></td>
                <td><?= date('d M Y', strtotime($r['reservation_date'])) ?></td>
                <td><?= $r['due_date'] ? date('d M Y', strtotime($r['due_date'])) : '&mdash;' ?></td>
                <td><span class="badge badge-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                <td>
                    <?php if (in_array($r['status'], ['Pending', 'Approved'])): ?>
                        <form class="inline" method="post" action="my_reservations.php" onsubmit="return confirm('Cancel this reservation?');">
                            <input type="hidden" name="cancel_id" value="<?= $r['reservation_id'] ?>">
                            <button type="submit" class="btn btn-small btn-danger">Cancel</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/includes/footer.php'; ?>
