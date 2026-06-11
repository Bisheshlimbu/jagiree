<?php
/**
 * Site header — public pages (guest)
 */
$pageTitle = $pageTitle ?? 'Jagiree — Find Your Next Opportunity';
$activeNav = $activeNav ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Jagiree — AI-powered job matching platform. Upload your CV, get personalized recommendations, and connect with top employers.">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/landing.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="/" class="logo">
            <span class="logo-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                </svg>
            </span>
            Jagiree
        </a>

        <nav class="nav-center" aria-label="Main navigation">
            <a href="/" class="nav-item <?= $activeNav === 'home' ? 'is-active' : '' ?>">Home</a>
            <a href="#chat" class="nav-item <?= $activeNav === 'chat' ? 'is-active' : '' ?>">AI Chat</a>
            <a href="#employers" class="nav-item <?= $activeNav === 'employers' ? 'is-active' : '' ?>">Employers</a>
        </nav>

        <div class="nav-actions">
            <a href="/login.php" class="nav-link">Log In</a>
            <a href="/register.php" class="btn btn-primary">Join Free</a>
        </div>
    </div>
</header>
