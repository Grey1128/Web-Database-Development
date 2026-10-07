<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Reservation.php';
require_once __DIR__ . '/includes/Book.php';
requireLogin();

$pageTitle = 'Dashboard';
$resModel = new Reservation();
$bookModel = new Book();

$myReservations = $resModel->findByUser((int)$_SESSION['user_id']);
$activeCount = count(array_filter($myReservations, fn($r) => in_array($r['status'], ['Pending','Approved','Collected'])));

if (isLibrarian()) {
    $allReservations = $resModel->findAll();
    $pendingCount = count(array_filter($allReservations, fn($r) => $r['status'] === 'Pending'));
    $totalBooks = count($bookModel->search());
}

require __DIR__ . '/includes/header.php';
?>
<h1>Welcome back, <?= e($_SESSION['full_name']) ?></h1>

<div class="stat-row">
    <div class="stat-box"><div class="num"><?= $activeCount ?></div><div class="label">Your active reservations</div></div>
    <?php if (isLibrarian()): ?>
        <div class="stat-box"><div class="num"><?= $pendingCount ?></div><div class="label">Pending approvals</div></div>
        <div class="stat-box"><div class="num"><?= $totalBooks ?></div><div class="label">Titles in catalogue</div></div>
    <?php endif; ?>
</div>

<div class="grid">
    <div class="card">
        <h3>Browse the catalogue</h3>
        <p>Search for books and reserve an available copy.</p>
        <a class="btn" href="books.php">Go to catalogue</a>
    </div>
    <div class="card">
        <h3>My reservations</h3>
        <p>Track the status of books you've reserved.</p>
        <a class="btn" href="my_reservations.php">View my reservations</a>
    </div>
    <?php if (isLibrarian()): ?>
    <div class="card">
        <h3>Manage catalogue</h3>
        <p>Add, edit, or remove books and categories.</p>
        <a class="btn btn-accent" href="admin/books.php">Manage books</a>
    </div>
    <div class="card">
        <h3>Process reservations</h3>
        <p>Approve, mark collected, or mark returned.</p>
        <a class="btn btn-accent" href="admin/reservations.php">Manage reservations</a>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
