<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireRole('manager');
$pageTitle = 'Manage Performances';

$perfModel = new Performance();
$errors = [];
$venues = $perfModel->venues();
$venueId = (int)($venues[0]['venue_id'] ?? 0);   // prototype: single venue
$sections = $perfModel->sectionsForVenue($venueId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        $ok = $perfModel->cancel((int)$_POST['performance_id']);
        flash($ok ? 'success' : 'error', $ok
            ? 'Performance cancelled. All tickets have been refunded and seats released.'
            : 'That performance could not be cancelled.');
        redirect('manager/performances.php');
    }

    if ($action === 'create') {
        $showId = (int)($_POST['show_id'] ?? 0);
        $startsRaw = $_POST['starts_at'] ?? '';
        $start = DateTime::createFromFormat('Y-m-d\TH:i', $startsRaw);
        $prices = [];

        if (!(new Show())->find($showId)) {
            $errors[] = 'Please choose a show.';
        }
        if (!$start || $start <= new DateTime()) {
            $errors[] = 'Please choose a start date and time in the future.';
        }
        foreach ($sections as $sec) {
            $raw = $_POST['price'][$sec['section_id']] ?? '';
            if (!is_numeric($raw) || (float)$raw < 0 || (float)$raw > 1000) {
                $errors[] = 'Enter a price between $0 and $1000 for ' . $sec['section_name'] . '.';
            }
            $prices[$sec['section_id']] = round((float)$raw, 2);
        }

        if (!$errors) {
            $ok = $perfModel->create($showId, $venueId, $start->format('Y-m-d H:i:s'), $prices);
            flash($ok ? 'success' : 'error', $ok ? 'Performance scheduled and on sale.' : 'The performance could not be saved.');
            redirect('manager/performances.php');
        }
    }
}

$shows = (new Show())->all();
$performances = $perfModel->allForManager();
require __DIR__ . '/../includes/header.php';
?>
<h1>Performances</h1>
<div class="checkout">
    <section>
        <table>
            <thead><tr><th>When</th><th>Show</th><th>Sold</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($performances as $p):
                $future = strtotime($p['starts_at']) > time(); ?>
                <tr>
                    <td><?= date('D j M Y, g:i a', strtotime($p['starts_at'])) ?></td>
                    <td><?= e($p['title']) ?></td>
                    <td><?= (int)$p['sold'] ?></td>
                    <td><span class="tag tag-<?= strtolower(e($p['status'])) ?>"><?= $p['status'] === 'Scheduled' && !$future ? 'Finished' : e($p['status']) ?></span></td>
                    <td>
                        <?php if ($p['status'] === 'Scheduled' && $future): ?>
                            <form method="post" action="performances.php" class="inline"
                                  onsubmit="return confirm('Cancel this performance? All <?= (int)$p['sold'] ?> ticket(s) will be refunded.');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="cancel">
                                <button class="btn btn-small btn-danger" name="performance_id" value="<?= (int)$p['performance_id'] ?>">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <aside class="panel">
        <h2>Schedule a performance</h2>
        <?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" action="performances.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            <label for="show_id">Show</label>
            <select id="show_id" name="show_id" required>
                <?php foreach ($shows as $s): ?>
                    <option value="<?= (int)$s['show_id'] ?>" <?= (int)($_POST['show_id'] ?? 0) === (int)$s['show_id'] ? 'selected' : '' ?>><?= e($s['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="starts_at">Starts at</label>
            <input id="starts_at" name="starts_at" type="datetime-local" required value="<?= e($_POST['starts_at'] ?? '') ?>">
            <?php foreach ($sections as $sec): ?>
                <label for="price-<?= (int)$sec['section_id'] ?>"><?= e($sec['section_name']) ?> price ($)</label>
                <input id="price-<?= (int)$sec['section_id'] ?>" name="price[<?= (int)$sec['section_id'] ?>]" type="number"
                       min="0" max="1000" step="0.01" required value="<?= e($_POST['price'][$sec['section_id']] ?? '') ?>">
            <?php endforeach; ?>
            <button class="btn" type="submit">Put on sale</button>
        </form>
    </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
