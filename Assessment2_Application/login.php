<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/User.php';

$pageTitle = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $userModel = new User();
    $user = $userModel->login($email, $password);

    if ($user) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid email or password.';
}

require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:420px;margin:0 auto;">
    <h1>Login</h1>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="login.php">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <p style="margin-top:18px;"><button type="submit" class="btn">Login</button></p>
    </form>
    <p>New here? <a href="register.php">Create an account</a>.</p>
    <p style="color:#647178;font-size:0.85rem;">Demo librarian: sarah.librarian@library.edu / Password123<br>
       Demo member: alex.student@library.edu / Password123</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
