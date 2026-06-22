<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth-form.php';

requireGuest();

$error = null;
$form = [
    'company_name' => '',
    'full_name' => '',
    'username' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'company_name' => trim($_POST['company_name'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
    ];

    $result = registerUser($form, ROLE_EMPLOYER);

    if ($result['success']) {
        header('Location: /employer/');
        exit;
    }

    $error = $result['error'];
}

$pageTitle = 'Register as Employer — Jagiree';
$activeNav = '';
$extraStyles = ['/assets/css/auth.css'];
$extraScripts = ['/assets/js/auth.js'];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="auth-page auth-page--register-employer">
    <div class="auth-split-card auth-split-card--register-employer">
        <section class="auth-split-form">
            <span class="auth-card__badge auth-card__badge--employer">Employer</span>
            <h1 class="auth-split-form__title">Register your company</h1>
            <p class="auth-split-form__subtitle">Post jobs and manage applicants on Jagiree</p>

            <?php authAlert($error); ?>

            <form class="auth-form" method="post" novalidate>
                <?php authField('company_name', 'Company name', 'text', [
                    'value' => $form['company_name'],
                    'required' => 'required',
                    'autocomplete' => 'organization',
                    'autofocus' => 'autofocus',
                ]); ?>

                <?php authField('full_name', 'Your full name', 'text', [
                    'value' => $form['full_name'],
                    'required' => 'required',
                    'autocomplete' => 'name',
                ]); ?>

                <?php authField('email', 'Work email', 'email', [
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

                <button type="submit" class="btn btn-primary auth-submit">Create Employer Account</button>
            </form>

            <div class="auth-footer auth-footer--split">
                <p>Looking for a job? <a href="/register-seeker.php">Register as job seeker</a></p>
                <p style="margin-top:0.5rem">Already have an account? <a href="/login.php">Log in</a></p>
            </div>
        </section>

        <aside class="auth-split-visual auth-split-visual--employer">
            <div class="auth-split-visual__inner">
                <?php renderSiteBrand('auth'); ?>
                <div class="auth-split-visual__body">
                    <h2>Hire Top Talent!</h2>
                    <p>Post jobs, review applicants, and build your team with Jagiree</p>
                    <a href="/login.php" class="auth-split-ghost-btn">Sign In</a>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
