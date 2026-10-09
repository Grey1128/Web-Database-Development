<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireRole('manager');
$pageTitle = 'Manage Shows';

$showModel = new Show();
$errors = [];
$ratings = ['G', 'PG', 'M', 'MA15+'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $ok = $showModel->delete((int)$_POST['show_id']);
        flash($ok ? 'success' : 'error', $ok
            ? 'Show deleted.'
            : 'This show has performances scheduled, so it cannot be deleted. Cancel its performances instead.');
        redirect('manager/shows.php');
    }

    $title = trim($_POST['title'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = (int)($_POST['duration_mins'] ?? 0);
    $rating = $_POST['age_rating'] ?? '';

    if ($title === '' || $genre === '' || $description === '') {
        $errors[] = 'Title, genre and description are required.';
    }
    if ($duration < 15 || $duration > 600) {
        $errors[] = 'Duration must be between 15 and 600 minutes.';
    }
    if (!in_array($rating, $ratings, true)) {
        $errors[] = 'Please choose a valid age rating.';
    }

    if (!$errors) {
        if ($action === 'update') {
            $showModel->update((int)$_POST['show_id'], $title, $genre, $description, $duration, $rating);
            flash('success', 'Show updated.');
        } else {
            $showModel->create($title, $genre, $description, $duration, $rating);
            flash('success', 'Show added.');
        }
        redirect('manager/shows.php');
    }
}

$editing = isset($_GET['edit']) ? $showModel->find((int)$_GET['edit']) : null;
$form = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : ($editing ?? []);
$shows = $showModel->all();
require __DIR__ . '/../includes/header.php';
?>
<h1>Shows</h1>
<div class="checkout">
    <section>
        <table>
            <thead><tr><th>Title</th><th>Genre</th><th>Length</th><th>Rating</th><th>Performances</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($shows as $s): ?>
                <tr>
                    <td><?= e($s['title']) ?></td>
                    <td><?= e($s['genre']) ?></td>
                    <td><?= (int)$s['duration_mins'] ?> min</td>
                    <td><?= e($s['age_rating']) ?></td>
                    <td><?= (int)$s['performance_count'] ?></td>
                    <td>
                        <a class="btn btn-small btn-ghost" href="shows.php?edit=<?= (int)$s['show_id'] ?>">Edit</a>
                        <form method="post" action="shows.php" class="inline" onsubmit="return confirm('Delete this show?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn-small btn-danger" name="show_id" value="<?= (int)$s['show_id'] ?>">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <aside class="panel">
        <h2><?= $editing ? 'Edit show' : 'Add a show' ?></h2>
        <?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" action="shows.php<?= $editing ? '?edit=' . (int)$editing['show_id'] : '' ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
            <?php if ($editing): ?><input type="hidden" name="show_id" value="<?= (int)$editing['show_id'] ?>"><?php endif; ?>
            <label for="title">Title</label>
            <input id="title" name="title" required maxlength="150" value="<?= e($form['title'] ?? '') ?>">
            <label for="genre">Genre</label>
            <input id="genre" name="genre" required maxlength="50" value="<?= e($form['genre'] ?? '') ?>">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" required><?= e($form['description'] ?? '') ?></textarea>
            <label for="duration_mins">Duration (minutes)</label>
            <input id="duration_mins" name="duration_mins" type="number" min="15" max="600" required value="<?= e((string)($form['duration_mins'] ?? '120')) ?>">
            <label for="age_rating">Age rating</label>
            <select id="age_rating" name="age_rating">
                <?php foreach ($ratings as $r): ?>
                    <option <?= ($form['age_rating'] ?? 'G') === $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit"><?= $editing ? 'Save changes' : 'Add show' ?></button>
            <?php if ($editing): ?><a class="btn btn-ghost" href="shows.php">Cancel</a><?php endif; ?>
        </form>
    </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
