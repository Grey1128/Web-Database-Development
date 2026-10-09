<?php
// POST handler: turn the seats ticked on the seat map into a timed hold.
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('performances.php');
}

$performanceId = (int)($_POST['performance_id'] ?? 0);
$seatIds = isset($_POST['seats']) && is_array($_POST['seats']) ? $_POST['seats'] : [];

$result = (new Booking())->holdSeats(currentUserId(), $performanceId, $seatIds);
flash($result['ok'] ? 'success' : 'error', $result['message']);

redirect($result['ok'] ? "checkout.php?performance=$performanceId" : "performance.php?id=$performanceId");
