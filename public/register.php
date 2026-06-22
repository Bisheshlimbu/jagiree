<?php
require_once __DIR__ . '/../includes/auth.php';

requireGuest();

$pageTitle = 'Join Jagiree — Create Account';
$activeNav = '';
$extraStyles = ['/assets/css/auth.css'];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="auth-page auth-page--register">
    <div class="auth-split-card auth-split-card--register">
        <section class="auth-split-form">
            <h1 class="auth-split-form__title">Create Account</h1>
            <p class="auth-split-form__subtitle">Choose how you want to use the platform</p>

            <div class="auth-role-grid auth-role-grid--split">
                <a href="/register-seeker.php" class="auth-role-card">
                    <span class="auth-role-card__icon auth-role-card__icon--seeker" aria-hidden="true">🔍</span>
                    <div>
                        <h2>I'm looking for a job</h2>
                        <p>Upload your CV, browse openings, and get AI-powered job recommendations.</p>
                        <span class="btn btn-primary">Register as Job Seeker</span>
                    </div>
                </a>

                <a href="/register-employer.php" class="auth-role-card">
                    <span class="auth-role-card__icon auth-role-card__icon--employer" aria-hidden="true">🏢</span>
                    <div>
                        <h2>I'm hiring talent</h2>
                        <p>Post job openings, review applicants, and manage your hiring pipeline.</p>
                        <span class="btn btn-outline">Register as Employer</span>
                    </div>
                </a>
            </div>

            <div class="auth-footer auth-footer--split">
                <p>Already have an account? <a href="/login.php">Log in</a></p>
            </div>
        </section>

        <aside class="auth-split-visual auth-split-visual--register">
            <div class="auth-split-visual__inner">
                <?php renderSiteBrand('auth'); ?>
                <div class="auth-split-visual__body">
                    <h2>Hello, Friend!</h2>
                    <p>Enter your personal details and start your journey with us</p>
                    <a href="/login.php" class="auth-split-ghost-btn">Sign In</a>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
