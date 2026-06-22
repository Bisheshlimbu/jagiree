<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth-form.php';

requireGuest();

$error = null;
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $result = attemptLogin($login, $password);

    if ($result['success']) {
        $redirect = $_GET['redirect'] ?? dashboardPathForRole($result['user']['role']);
        if (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            $redirect = dashboardPathForRole($result['user']['role']);
        }
        header('Location: ' . $redirect);
        exit;
    }

    $error = $result['error'];
}

$pageTitle = 'Log In — Jagiree';
$activeNav = '';
$extraStyles = ['/assets/css/auth.css'];
$extraScripts = ['/assets/js/auth.js'];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="auth-page auth-page--login">
    <div class="auth-split-card">
        <aside class="auth-split-visual" aria-hidden="false">
            <div class="auth-split-visual__inner">
                <?php renderSiteBrand('auth'); ?>
                <div class="auth-split-visual__body">
                    <h2>Welcome Back!</h2>
                    <p>To keep connected with us please login with your personal info</p>
                    <a href="/register.php" class="auth-split-ghost-btn">Sign Up</a>
                </div>
            </div>
        </aside>

        <section class="auth-split-form">
            <h1 class="auth-split-form__title">Sign In</h1>
            <p class="auth-split-form__subtitle">Use your username or email to access your account</p>

            <?php authAlert($error); ?>

            <form class="auth-form" method="post" action="/login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" novalidate>
                <?php authField('login', 'Username or email', 'text', [
                    'value' => $login,
                    'required' => 'required',
                    'autocomplete' => 'username',
                    'autofocus' => 'autofocus',
                ]); ?>

                <?php authField('password', 'Password', 'password', [
                    'required' => 'required',
                    'autocomplete' => 'current-password',
                ]); ?>

                <button type="submit" class="btn btn-primary auth-submit">Sign In</button>
            </form>

            <div class="auth-footer auth-footer--split">
                <p>New to Jagiree? <a href="/register.php">Create an account</a></p>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
