<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Box Office') ?> | Harbourlight Theatre</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="wrap header-row">
        <a class="brand" href="<?= url(isLoggedIn() ? 'dashboard.php' : 'index.php') ?>">
            <span class="brand-mark" aria-hidden="true">&#9670;</span> Harbourlight <span class="brand-sub">Theatre</span>
        </a>
        <nav class="main-nav" aria-label="Main">
            <a href="<?= url('performances.php') ?>">What's On</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?= url('my_bookings.php') ?>">My Bookings</a>
                <?php if (hasRole('staff', 'manager')): ?>
                    <a href="<?= url('staff/checkin.php') ?>">Door Check-in</a>
                <?php endif; ?>
                <?php if (hasRole('manager')): ?>
                    <a href="<?= url('manager/shows.php') ?>">Shows</a>
                    <a href="<?= url('manager/performances.php') ?>">Performances</a>
                    <a href="<?= url('manager/reports.php') ?>">Sales</a>
                    <a href="<?= url('manager/users.php') ?>">Users</a>
                <?php endif; ?>
                <span class="who"><?= e($_SESSION['full_name']) ?> &middot; <?= e(ucfirst($_SESSION['role'])) ?></span>
                <form method="post" action="<?= url('logout.php') ?>" class="inline">
                    <?= csrfField() ?>
                    <button type="submit" class="link-button">Log out</button>
                </form>
            <?php else: ?>
                <a href="<?= url('login.php') ?>">Log in</a>
                <a class="nav-cta" href="<?= url('register.php') ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="wrap page">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash flash-<?= e($_SESSION['flash']['type']) ?>" role="status"><?= e($_SESSION['flash']['message']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
