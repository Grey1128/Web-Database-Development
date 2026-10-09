<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Register';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Server-side validation (the browser checks are only a convenience).
    if ($fullName === '' || mb_strlen($fullName) > 100) {
        $errors[] = 'Please enter your full name (up to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone !== '' && !preg_match('/^[0-9 +()-]{8,20}$/', $phone)) {
        $errors[] = 'Phone numbers may only contain digits, spaces and + ( ) -.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Password must be at least 8 characters and include a letter and a number.';
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if (!$errors) {
        $result = (new User())->register($fullName, $email, $password, $phone);
        if ($result === true) {
            flash('success', 'Account created. Please log in.');
            redirect('login.php');
        }
        $errors[] = $result;
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="panel narrow">
    <h1>Create an account</h1>
    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>
    <form method="post" action="register.php" id="register-form" novalidate>
        <?= csrfField() ?>
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" required maxlength="100" value="<?= e($_POST['full_name'] ?? '') ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>">

        <label for="phone">Mobile (optional)</label>
        <input id="phone" name="phone" type="tel" value="<?= e($_POST['phone'] ?? '') ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required minlength="8">
        <p class="hint" id="pw-hint">At least 8 characters, with a letter and a number.</p>

        <label for="confirm_password">Confirm password</label>
        <input id="confirm_password" name="confirm_password" type="password" required minlength="8">

        <button type="submit" class="btn">Register</button>
    </form>
    <p class="muted">Already registered? <a href="login.php">Log in</a>.</p>
</div>
<script>
// Instant feedback while typing; the server still re-validates everything.
(function () {
    const pw = document.getElementById('password');
    const confirmPw = document.getElementById('confirm_password');
    const hint = document.getElementById('pw-hint');
    function check() {
        const strong = pw.value.length >= 8 && /[A-Za-z]/.test(pw.value) && /\d/.test(pw.value);
        hint.className = 'hint ' + (pw.value === '' ? '' : strong ? 'ok' : 'bad');
        confirmPw.setCustomValidity(confirmPw.value && confirmPw.value !== pw.value ? 'Passwords do not match' : '');
    }
    pw.addEventListener('input', check);
    confirmPw.addEventListener('input', check);
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
