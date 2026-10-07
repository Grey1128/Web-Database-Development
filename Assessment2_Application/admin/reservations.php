<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Reservation.php';
requireLibrarian();

$pageTitle = 'Manage Reservations';
$navBase = '../';
$assetBase = '../';
$homeLink = '../dashboard.php';
$resModel = new Reservation();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    match ($action) {
        'approve' => $resModel->approve($id),
        'collected' => $resModel->markCollected($id),
        'returned' => $resModel->markReturned($id),
        'cancel' => $resModel->cancel($id, (int)$_SESSION['user_id'], true),
        default => null,
    };
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Reservation updated.'];
    header('Location: reservations.php');
    exit;
}

$reservations = $resModel->findAll();
require __DIR__ . '/../includes/header.php';
?>
<h1>Manage Reservations</h1>

<table>
    <thead>
        <tr><th>Member</th><th>Book</th><th>Reserved</th><th>Due</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($reservations as $r): ?>
        <tr>
            <td><?= e($r['full_name']) ?><br><span style="color:#647178;font-size:0.8rem;"><?= e($r['email']) ?></span></td>
            <td><?= e($r['title']) ?></td>
            <td><?= date('d M Y', strtotime($r['reservation_date'])) ?></td>
            <td><?= $r['due_date'] ? date('d M Y', strtotime($r['due_date'])) : '&mdash;' ?></td>
            <td><span class="badge badge-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
            <td>
                <?php if ($r['status'] === 'Pending'): ?>
                    <form class="inline" method="post" action="reservations.php">
                        <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-small">Approve</button>
                    </form>
                    <form class="inline" method="post" action="reservations.php">
                        <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn btn-small btn-danger">Cancel</button>
                    </form>
                <?php elseif ($r['status'] === 'Approved'): ?>
                    <form class="inline" method="post" action="reservations.php">
                        <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                        <input type="hidden" name="action" value="collected">
                        <button type="submit" class="btn btn-small">Mark Collected</button>
                    </form>
                <?php elseif ($r['status'] === 'Collected'): ?>
                    <form class="inline" method="post" action="reservations.php">
                        <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                        <input type="hidden" name="action" value="returned">
                        <button type="submit" class="btn btn-small">Mark Returned</button>
                    </form>
                <?php else: ?>
                    &mdash;
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
