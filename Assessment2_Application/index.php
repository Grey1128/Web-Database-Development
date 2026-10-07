<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Home';
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
require __DIR__ . '/includes/header.php';
?>
<div class="hero">
    <h1>Reserve books from your community library, online.</h1>
    <p>Browse the Riverbend Community Library catalogue, check live availability, and reserve
       a copy for pickup &mdash; no more calling ahead or missing out.</p>
    <a class="btn" href="register.php">Create a free account</a>
    <a class="btn btn-accent" href="login.php">Login</a>
</div>

<div class="grid">
    <div class="card">
        <h3>Search the catalogue</h3>
        <p>Find books by title, author, or category, and see how many copies are available right now.</p>
    </div>
    <div class="card">
        <h3>Reserve in a click</h3>
        <p>Reserve an available book and track its status from Pending to Approved to Collected.</p>
    </div>
    <div class="card">
        <h3>Librarian tools</h3>
        <p>Library staff can manage the catalogue and process reservations from a dedicated dashboard.</p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
