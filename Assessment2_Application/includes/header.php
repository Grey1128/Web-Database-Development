<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>Riverbend Community Library</title>
<link rel="stylesheet" href="<?= $assetBase ?? '' ?>assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= $homeLink ?? 'index.php' ?>">📚 Riverbend Community Library</a>
        <nav>
            <?php if (isLoggedIn()): ?>
                <span class="welcome">Hi, <?= e($_SESSION['full_name']) ?> (<?= e($_SESSION['role']) ?>)</span>
                <a href="<?= $navBase ?? '' ?>dashboard.php">Dashboard</a>
                <a href="<?= $navBase ?? '' ?>books.php">Catalogue</a>
                <a href="<?= $navBase ?? '' ?>my_reservations.php">My Reservations</a>
                <?php if (isLibrarian()): ?>
                    <a href="<?= $navBase ?? '' ?>admin/books.php">Manage Books</a>
                    <a href="<?= $navBase ?? '' ?>admin/reservations.php">Manage Reservations</a>
                <?php endif; ?>
                <a href="<?= $navBase ?? '' ?>logout.php">Logout</a>
            <?php else: ?>
                <a href="<?= $navBase ?? '' ?>login.php">Login</a>
                <a href="<?= $navBase ?? '' ?>register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash flash-<?= e($_SESSION['flash']['type']) ?>"><?= e($_SESSION['flash']['message']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
