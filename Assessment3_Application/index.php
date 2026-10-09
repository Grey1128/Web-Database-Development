<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (isLoggedIn()) {
    redirect('dashboard.php');
}
$pageTitle = 'Welcome';
$upcoming = array_slice((new Performance())->upcoming(), 0, 3);
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <p class="eyebrow">Harbourlight Theatre &middot; Box Office</p>
    <h1>Pick your seat. Hold it while you pay. No double bookings.</h1>
    <p class="lede">Browse what's on, choose exact seats on a live seat map, and we'll hold them for
       <?= HOLD_MINUTES ?> minutes while you check out.</p>
    <p>
        <a class="btn" href="performances.php">See what's on</a>
        <a class="btn btn-ghost" href="register.php">Create an account</a>
    </p>
</section>

<h2>Coming up</h2>
<div class="cards">
    <?php foreach ($upcoming as $p): ?>
        <article class="card">
            <p class="eyebrow"><?= e($p['genre']) ?> &middot; <?= e($p['age_rating']) ?></p>
            <h3><?= e($p['title']) ?></h3>
            <p class="muted"><?= date('D j M Y, g:i a', strtotime($p['starts_at'])) ?></p>
            <p>From <strong><?= money($p['from_price']) ?></strong> &middot; <?= (int)$p['seats_left'] ?> seats left</p>
            <a class="btn btn-small" href="performance.php?id=<?= (int)$p['performance_id'] ?>">Choose seats</a>
        </article>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
