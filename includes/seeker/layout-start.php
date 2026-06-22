<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../users.php';
require_once __DIR__ . '/../site-brand.php';
require_once __DIR__ . '/../notifications.php';
requireRole(ROLE_SEEKER);

$authUser = currentUser();
$siteName = siteName();
$pageTitle = $pageTitle ?? $siteName . ' — Job Seeker';
$activePage = $activePage ?? 'home';
$seekerName = $seekerName ?? displayName($authUser);
$seekerTitle = $seekerTitle ?? 'Open to work';
$bodyClass = $bodyClass ?? 'seeker-body';
$extraScripts = $extraScripts ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/seeker.css') ?>">
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">

<header class="seeker-header">
    <div class="header-inner">
        <?php renderSiteBrand('seeker'); ?>

        <div class="header-search">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search" placeholder="Search jobs, companies, skills..." aria-label="Search jobs">
        </div>

        <nav class="header-nav" aria-label="Main navigation">
            <a href="/seeker/" class="nav-item <?= $activePage === 'home' ? 'is-active' : '' ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Home</span>
            </a>
            <a href="/seeker/jobs.php" class="nav-item <?= $activePage === 'jobs' ? 'is-active' : '' ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                <span>Jobs</span>
            </a>
            <a href="/seeker/applications.php" class="nav-item <?= $activePage === 'applications' ? 'is-active' : '' ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
                <span>Applications</span>
            </a>
            <a href="/seeker/chat.php" class="nav-item <?= $activePage === 'chat' ? 'is-active' : '' ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>AI Chat</span>
            </a>
        </nav>

        <div class="header-actions">
            <?php
            $notifApiUrl = '/seeker/api/notifications.php';
            $notifUserId = (int) $authUser['id'];
            require __DIR__ . '/../notification-bell.php';
            ?>
            <div class="header-user-menu" id="headerUserMenu">
                <button
                    type="button"
                    class="header-avatar-btn <?= $activePage === 'profile' ? 'is-active' : '' ?>"
                    id="headerUserMenuBtn"
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-controls="headerUserMenuPanel"
                    aria-label="Account menu"
                >
                    <img src="<?= htmlspecialchars(userAvatarUrl($authUser)) ?>" alt="" class="header-avatar<?= userHasAvatar($authUser) ? '' : ' user-avatar--placeholder' ?>">
                </button>
                <div class="header-user-menu__panel" id="headerUserMenuPanel" hidden>
                    <div class="header-user-menu__user">
                        <strong><?= htmlspecialchars(displayName($authUser)) ?></strong>
                        <span>Job seeker</span>
                    </div>
                    <a href="/seeker/profile.php" class="header-user-menu__item">View profile</a>
                    <a href="/seeker/settings.php" class="header-user-menu__item">Settings</a>
                    <a href="/logout.php" class="header-user-menu__item header-user-menu__item--danger">Log out</a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="seeker-page">
