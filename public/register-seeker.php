<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth-form.php';

requireGuest();

$error = null;
$form = [
    'full_name' => '',
    'username' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
    ];

    $result = registerUser($form, ROLE_SEEKER);

    if ($result['success']) {
        header('Location: /seeker/');
        exit;
    }

    $error = $result['error'];
}

$pageTitle = 'Register as Job Seeker — Jagiree';
$activeNav = '';
$extraStyles = ['/assets/css/auth.css'];
$extraScripts = ['/assets/js/auth.js'];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="auth-page auth-page--register-seeker">
    <div class="auth-split-card auth-split-card--register-seeker">
        <section class="auth-split-form">
            <span class="auth-card__badge auth-card__badge--seeker">Job Seeker</span>
            <h1 class="auth-split-form__title">Create your account</h1>
            <p class="auth-split-form__subtitle">Find jobs matched to your skills with AI recommendations</p>

            <?php authAlert($error); ?>

            <form class="auth-form" method="post" novalidate>
                <?php authField('full_name', 'Full name', 'text', [
                    'value' => $form['full_name'],
                    'required' => 'required',
                    'autocomplete' => 'name',
                    'autofocus' => 'autofocus',
                ]); ?>

                <?php authField('email', 'Email address', 'email', [
                    'value' => $form['email'],
                    'required' => 'required',
                    'autocomplete' => 'email',
                ]); ?>

                <?php authField('username', 'Username (optional)', 'text', [
                    'value' => $form['username'],
                    'autocomplete' => 'username',
                    'minlength' => '3',
                    'pattern' => '[a-zA-Z0-9_]+',
                ]); ?>
                <p class="auth-hint">Optional. If left blank, one is created from your email. Letters, numbers, and underscore only.</p>

                <?php authField('password', 'Password', 'password', [
                    'required' => 'required',
                    'autocomplete' => 'new-password',
                    'minlength' => '8',
                ]); ?>

                <?php authField('password_confirm', 'Confirm password', 'password', [
                    'required' => 'required',
                    'autocomplete' => 'new-password',
                    'minlength' => '8',
                ]); ?>

                <button type="submit" class="btn btn-primary auth-submit">Create Seeker Account</button>
            </form>

            <div class="auth-footer auth-footer--split">
                <p>Looking to hire? <a href="/register-employer.php">Register as employer</a></p>
                <p style="margin-top:0.5rem">Already have an account? <a href="/login.php">Log in</a></p>
            </div>
        </section>

        <aside class="auth-split-visual auth-split-visual--seeker">
            <div class="auth-split-visual__inner">
                <?php renderSiteBrand('auth'); ?>
                <div class="auth-split-visual__body">
                    <h2>Find Your Dream Job!</h2>
                    <p>Upload your CV and let our AI match you with the best opportunities</p>
                    <a href="/login.php" class="auth-split-ghost-btn">Sign In</a>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
