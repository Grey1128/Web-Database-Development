<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireRole('manager');
$pageTitle = 'Users';

$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = (int)($_POST['user_id'] ?? 0);
    if ($targetId === currentUserId()) {
        // Stops a manager from accidentally locking themselves out.
        flash('error', 'You cannot change your own role.');
    } else {
        $ok = $userModel->setRole($targetId, $_POST['role'] ?? '');
        flash($ok ? 'success' : 'error', $ok
            ? 'Role updated. It takes effect the next time that user logs in.'
            : 'Role was not changed.');
    }
    redirect('manager/users.php');
}

$users = $userModel->all();
require __DIR__ . '/../includes/header.php';
?>
<h1>Users</h1>
<p class="muted">Promote a registered customer to box-office staff or manager.</p>
<table>
    <thead><tr><th>Name</th><th>Email</th><th>Joined</th><th>Role</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= date('j M Y', strtotime($u['created_at'])) ?></td>
            <td>
                <?php if ((int)$u['user_id'] === currentUserId()): ?>
                    <?= e(ucfirst($u['role'])) ?> (you)
                <?php else: ?>
                    <form method="post" action="users.php" class="inline role-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                        <select name="role" aria-label="Role for <?= e($u['full_name']) ?>">
                            <?php foreach (User::ROLES as $r): ?>
                                <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-small" type="submit">Save</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
