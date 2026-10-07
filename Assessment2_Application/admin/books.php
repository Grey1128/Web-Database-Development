<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Book.php';
requireLibrarian();

$pageTitle = 'Manage Books';
$navBase = '../';
$assetBase = '../';
$homeLink = '../dashboard.php';
$bookModel = new Book();
$categories = $bookModel->getAllCategories();
$editBook = null;

// Handle create / update / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $ok = $bookModel->create(
            trim($_POST['title']), trim($_POST['author']), trim($_POST['isbn']),
            (int)$_POST['category_id'], max(1, (int)$_POST['copies'])
        );
        
        $_SESSION['flash'] = $ok ? ['type' => 'success', 'message' => 'Book added to catalogue.'] :   ['type' => 'error', 'message' => 'Could not add book (is the ISBN already in use?).'];
    } elseif ($action === 'update') {
        $bookModel->update(
            (int)$_POST['book_id'], trim($_POST['title']), trim($_POST['author']), trim($_POST['isbn']),
            (int)$_POST['category_id'], max(1, (int)$_POST['copies'])
        );
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Book updated.'];
    } elseif ($action === 'delete') {
        $bookModel->delete((int)$_POST['book_id']);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Book removed.'];
    }
    header('Location: books.php');
    exit;
}

if (isset($_GET['edit'])) {
    $editBook = $bookModel->findById((int)$_GET['edit']);
}

$books = $bookModel->search();
require __DIR__ . '/../includes/header.php';
?>
<h1>Manage Books</h1>

<div class="card">
    <h3><?= $editBook ? 'Edit Book' : 'Add New Book' ?></h3>
    <form method="post" action="books.php">
        <input type="hidden" name="action" value="<?= $editBook ? 'update' : 'create' ?>">
        <?php if ($editBook): ?><input type="hidden" name="book_id" value="<?= $editBook['book_id'] ?>"><?php endif; ?>

        <label for="title">Title</label>
        <input type="text" id="title" name="title" required value="<?= e($editBook['title'] ?? '') ?>">

        <label for="author">Author</label>
        <input type="text" id="author" name="author" required value="<?= e($editBook['author'] ?? '') ?>">

        <label for="isbn">ISBN</label>
        <input type="text" id="isbn" name="isbn" value="<?= e($editBook['isbn'] ?? '') ?>">

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>" <?= (($editBook['category_id'] ?? 0) == $cat['category_id']) ? 'selected' : '' ?>>
                    <?= e($cat['category_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="copies">Total copies</label>
        <input type="number" id="copies" name="copies" min="1" required value="<?= e((string)($editBook['total_copies'] ?? 1)) ?>">

        <p style="margin-top:16px;">
            <button type="submit" class="btn"><?= $editBook ? 'Save Changes' : 'Add Book' ?></button>
            <?php if ($editBook): ?><a class="btn btn-small" href="books.php" style="background:#647178;">Cancel</a><?php endif; ?>
        </p>
    </form>
</div>

<table>
    <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Copies (avail/total)</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($books as $b): ?>
        <tr>
            <td><?= e($b['title']) ?></td>
            <td><?= e($b['author']) ?></td>
            <td><?= e($b['category_name']) ?></td>
            <td><?= (int)$b['available_copies'] ?> / <?= (int)$b['total_copies'] ?></td>
            <td>
                <a class="btn btn-small" href="books.php?edit=<?= $b['book_id'] ?>">Edit</a>
                <form class="inline" method="post" action="books.php" onsubmit="return confirm('Delete this book?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
