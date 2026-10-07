<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Reservation.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: books.php');
    exit;
}

$bookId = (int)($_POST['book_id'] ?? 0);
$resModel = new Reservation();
$ok = $resModel->create((int)$_SESSION['user_id'], $bookId);

$_SESSION['flash'] = $ok
    ? ['type' => 'success', 'message' => 'Book reserved! Check "My Reservations" for pickup status.']
    : ['type' => 'error', 'message' => 'Sorry, that book is no longer available.'];

header('Location: books.php');
exit;
