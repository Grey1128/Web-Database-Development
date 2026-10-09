<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Log in';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $user = (new User())->authenticate($email, $_POST['password'] ?? '');

    if ($user) {
        // New session ID after login prevents session-fixation attacks.
        session_regenerate_id(true);
        $_SESSION['user_id']   = (int)$user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        unset($_SESSION['csrf']);
        redirect('dashboard.php');
    }
    // Same message whether the email or the password was wrong.
    $error = 'Incorrect email or password.';
}

require __DIR__ . '/includes/header.php';
?>
<div class="panel narrow">
    <h1>Log in</h1>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="login.php">
        <?= csrfField() ?>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
        <button type="submit" class="btn">Log in</button>
    </form>
    <p class="muted">New here? <a href="register.php">Create an account</a>.</p>
    <div class="demo-box">
        <strong>Demo accounts</strong> (password <code>Password123</code>)<br>
        Customer: chris.customer@harbourlight.test<br>
        Staff: sam.staff@harbourlight.test<br>
        Manager: maya.manager@harbourlight.test
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
