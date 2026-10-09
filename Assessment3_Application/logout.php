<?php
// Logout is a POST (with CSRF token) so another site cannot log users out with a link.
require_once __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
$_SESSION = [];
session_destroy();
session_start();
flash('success', 'You have been logged out.');
redirect('index.php');
