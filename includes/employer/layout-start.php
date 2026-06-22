<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../users.php';
require_once __DIR__ . '/../site-brand.php';
require_once __DIR__ . '/../notifications.php';
requireRole(ROLE_EMPLOYER);

$authUser = currentUser();
$siteName = siteName();
$pageTitle = $pageTitle ?? 'Employer Dashboard — ' . $siteName;
$activePage = $activePage ?? 'dashboard';
$activeTopNav = $activeTopNav ?? 'dashboard';
$companyName = $companyName ?? ($authUser['company_name'] ?: displayName($authUser));
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/employer.css">
</head>
<body class="employer-body">

<div class="employer-app">
    <header class="employer-topnav">
        <div class="topnav-left">
            <button type="button" class="topnav-menu-btn" aria-label="Toggle menu" aria-expanded="false" aria-controls="employer-sidebar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <?php renderSiteBrand('employer'); ?>
        </div>

        <nav class="topnav-links" aria-label="Employer top navigation">
            <a href="/employer/" class="topnav-link <?= $activeTopNav === 'dashboard' ? 'is-active' : '' ?>">Dashboard</a>
            <a href="/employer/applicants.php" class="topnav-link <?= $activeTopNav === 'talent' ? 'is-active' : '' ?>">Talent Pool</a>
            <a href="/employer/analytics.php" class="topnav-link <?= $activeTopNav === 'reports' ? 'is-active' : '' ?>">Reports</a>
        </nav>

        <div class="topnav-right">
            <?php
            $notifApiUrl = '/employer/api/notifications.php';
            $notifUserId = (int) $authUser['id'];
            $notifButtonClass = 'topnav-icon-btn';
            require __DIR__ . '/../notification-bell.php';
            ?>
            <a href="/employer/settings.php" class="topnav-profile" aria-label="Account settings">
                <img src="<?= htmlspecialchars(userAvatarUrl($authUser)) ?>" alt="Employer profile" class="topnav-avatar<?= userHasAvatar($authUser) ? '' : ' user-avatar--placeholder' ?>">
            </a>
        </div>
    </header>

    <div class="employer-shell">
        <button type="button" class="sidebar-backdrop" aria-label="Close navigation menu" tabindex="-1"></button>
        <aside class="employer-sidebar" id="employer-sidebar">
            <div class="sidebar-company">
                <img src="<?= htmlspecialchars(userAvatarUrl($authUser)) ?>" alt="" class="sidebar-company-avatar<?= userHasAvatar($authUser) ? '' : ' user-avatar--placeholder' ?>">
                <div class="sidebar-company-info">
                    <strong><?= htmlspecialchars($companyName) ?></strong>
                    <span>Employer Account</span>
                </div>
            </div>

            <nav class="sidebar-nav" aria-label="Employer sidebar">
                <a href="/employer/" class="sidebar-link <?= $activePage === 'dashboard' ? 'is-active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="/employer/job-listings.php" class="sidebar-link <?= $activePage === 'jobs' ? 'is-active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                    Job Listings
                </a>
                <a href="/employer/applicants.php" class="sidebar-link <?= $activePage === 'applicants' ? 'is-active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Applicants
                </a>
                <a href="/employer/analytics.php" class="sidebar-link <?= $activePage === 'analytics' ? 'is-active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    Analytics
                </a>
            </nav>

            <div class="sidebar-system">
                <span class="sidebar-system-label">System</span>
                <a href="/employer/settings.php" class="sidebar-link <?= $activePage === 'settings' ? 'is-active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                </a>
                <a href="/logout.php" class="sidebar-link sidebar-link--danger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Logout
                </a>
            </div>
        </aside>

        <main class="employer-main">
