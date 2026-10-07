<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/User.php';

$pageTitle = 'Register';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '') {
        $errors[] = 'Name, email and password are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $userModel = new User();
        $result = $userModel->register($fullName, $email, $password, $phone);
        if ($result === true) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Account created! Please log in.'];
            header('Location: login.php');
            exit;
        }
        $errors[] = $result;
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:480px;margin:0 auto;">
    <h1>Create an account</h1>
    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>
    <form method="post" action="register.php" novalidate>
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">

        <label for="phone">Phone (optional)</label>
        <input type="tel" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">

        <label for="password">Password (min. 8 characters)</label>
        <input type="password" id="password" name="password" minlength="8" required>

        <label for="confirm_password">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>

        <p style="margin-top:18px;"><button type="submit" class="btn">Register</button></p>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
