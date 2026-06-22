<?php
/**
 * Site header — public pages (guest)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/site-brand.php';

$siteName = siteName();
$pageTitle = $pageTitle ?? $siteName . ' — Find Your Next Opportunity';
$activeNav = $activeNav ?? 'home';
$extraStyles = $extraStyles ?? [];
$authUser = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($siteName) ?> — AI-powered job matching platform. Upload your CV, get personalized recommendations, and connect with top employers.">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/landing.css">
    <?php foreach ($extraStyles as $style): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>">
    <?php endforeach; ?>
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <?php renderSiteBrand('landing'); ?>

        <nav class="nav-center" aria-label="Main navigation">
            <a href="/" class="nav-item <?= $activeNav === 'home' ? 'is-active' : '' ?>">Home</a>
            <a href="/seeker/chat.php" class="nav-item <?= $activeNav === 'chat' ? 'is-active' : '' ?>">AI Chat</a>
            <a href="#employers" class="nav-item <?= $activeNav === 'employers' ? 'is-active' : '' ?>">Employers</a>
        </nav>

        <div class="nav-actions">
            <?php if ($authUser): ?>
                <a href="<?= htmlspecialchars(dashboardPathForRole($authUser['role'])) ?>" class="nav-link">Dashboard</a>
                <a href="/logout.php" class="btn btn-outline">Log Out</a>
            <?php else: ?>
                <a href="/login.php" class="nav-link">Log In</a>
                <a href="/register.php" class="btn btn-primary">Join Free</a>
            <?php endif; ?>
        </div>
    </div>
</header>
