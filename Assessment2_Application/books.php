<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Book.php';
requireLogin();

$pageTitle = 'Catalogue';
$bookModel = new Book();

$keyword = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);

$books = $bookModel->search($keyword, $categoryId);
$categories = $bookModel->getAllCategories();

require __DIR__ . '/includes/header.php';
?>
<h1>Book Catalogue</h1>

<form method="get" action="books.php" class="filters">
    <input type="text" name="q" placeholder="Search by title or author..." value="<?= e($keyword) ?>">
    <select name="category">
        <option value="0">All categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['category_id'] ?>" <?= $categoryId === (int)$cat['category_id'] ? 'selected' : '' ?>>
                <?= e($cat['category_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Search</button>
</form>

<div class="grid">
    <?php if (!$books): ?>
        <p>No books matched your search.</p>
    <?php endif; ?>
    <?php foreach ($books as $book): ?>
        <div class="card book-card">
            <h3><?= e($book['title']) ?></h3>
            <div class="meta">by <?= e($book['author']) ?> &middot; <?= e($book['category_name']) ?></div>
            <?php if ((int)$book['available_copies'] > 0): ?>
                <span class="badge badge-available"><?= (int)$book['available_copies'] ?> of <?= (int)$book['total_copies'] ?> available</span>
            <?php else: ?>
                <span class="badge badge-unavailable">Fully reserved</span>
            <?php endif; ?>
            <p style="margin-top:12px;">
                <?php if ((int)$book['available_copies'] > 0): ?>
                    <form class="inline" method="post" action="reserve.php">
                        <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                        <button type="submit" class="btn btn-small">Reserve</button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-small" disabled>Unavailable</button>
                <?php endif; ?>
            </p>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
