<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/employer/dashboard.php';

requireRole(ROLE_EMPLOYER);

$authUser = currentUser();
$dashboard = getEmployerDashboardData((int) $authUser['id']);
$stats = $dashboard['stats'];
$applications = $dashboard['applications'];
$activeJobs = $dashboard['activeJobs'];
$interviews = $dashboard['interviews'];
$insights = $dashboard['insights'];

$pageTitle = 'Employer Dashboard — Jagiree';
$activePage = 'dashboard';
$activeTopNav = 'dashboard';
$extraScripts = ['employer-applications.js'];
require_once __DIR__ . '/../../includes/employer/layout-start.php';
?>

<div class="page-header">
    <div>
        <h1>Employer Dashboard</h1>
        <p>Welcome back! Here's what's happening with your hiring pipeline today.</p>
    </div>
    <a href="/employer/post-job.php" class="btn-post-job">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Post a New Job
    </a>
</div>

<div class="stats-grid">
    <?php foreach ($stats as $stat): ?>
    <article class="stat-card">
        <div class="stat-card-row">
            <span class="stat-icon stat-icon--<?= $stat['icon'] ?>">
                <?php if ($stat['icon'] === 'jobs'): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                <?php elseif ($stat['icon'] === 'applicants'): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                <?php elseif ($stat['icon'] === 'interviews'): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?php else: ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?php endif; ?>
            </span>
            <span class="stat-trend stat-trend--<?= htmlspecialchars($stat['trendType']) ?>"><?= htmlspecialchars($stat['trend']) ?></span>
        </div>
        <div class="stat-label"><?= htmlspecialchars($stat['label']) ?></div>
        <div class="stat-value"><?= htmlspecialchars($stat['value']) ?></div>
    </article>
    <?php endforeach; ?>
</div>

<div class="dashboard-row dashboard-row--2-1">
    <section class="panel panel--table">
        <div class="panel-header">
            <h2>Recent Applications</h2>
            <a href="/employer/applicants.php" class="panel-link">View All</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Match Score</th>
                        <th>Cover letter</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($applications === []): ?>
                    <tr>
                        <td colspan="7" class="table-empty">No applications yet. Live job listings will receive applicants here.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td>
                            <button type="button" class="table-applicant table-applicant--button" data-view-application="<?= (int) $app['id'] ?>">
                                <?php if (!empty($app['has_avatar'])): ?>
                                <img src="<?= htmlspecialchars($app['avatar_url']) ?>" alt="" class="applicant-avatar applicant-avatar--image">
                                <?php else: ?>
                                <span class="applicant-avatar" style="background:<?= htmlspecialchars($app['color']) ?>"><?= htmlspecialchars($app['initials']) ?></span>
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($app['name']) ?></strong>
                            </button>
                        </td>
                        <td><?= htmlspecialchars($app['role']) ?></td>
                        <td>
                            <span class="app-status app-status--<?= htmlspecialchars($app['status']) ?>">
                                <?= htmlspecialchars($app['status_label']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="match-score">
                                <div class="match-bar"><span style="width:<?= (int) $app['match'] ?>%"></span></div>
                                <span><?= (int) $app['match'] ?>%</span>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($app['has_cover_letter'])): ?>
                            <button type="button" class="table-link-btn" data-view-application="<?= (int) $app['id'] ?>">Read</button>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($app['date']) ?></td>
                        <td>
                            <div class="table-actions">
                                <button type="button" class="btn-outline-sm" data-view-application="<?= (int) $app['id'] ?>">Review</button>
                                <?php if (!empty($app['cv_path'])): ?>
                                <a href="<?= htmlspecialchars($app['cv_path']) ?>" class="btn-primary btn-primary--sm" target="_blank" rel="noopener">CV</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="panel panel--ai">
        <div class="ai-header">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/></svg>
            <h2>AI Hiring Insights</h2>
        </div>
        <p class="ai-desc"><?= htmlspecialchars($insights['desc']) ?></p>
        <ul class="ai-checklist">
            <?php foreach ($insights['checklist'] as $item): ?>
            <li>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <?= htmlspecialchars($item) ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <a href="/employer/job-listings.php" class="btn-launch-engine">Manage Job Listings</a>
    </aside>
</div>

<div class="dashboard-row dashboard-row--1-1">
    <section class="panel">
        <div class="panel-header">
            <h2>Active Jobs Management</h2>
            <div class="panel-tools">
                <a href="/employer/job-listings.php" class="panel-link">View All</a>
            </div>
        </div>

        <?php if ($activeJobs === []): ?>
        <div class="empty-state">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            <h2>No active jobs yet</h2>
            <p>Approved job listings will appear here. Post a job or wait for admin approval on pending listings.</p>
            <a href="/employer/post-job.php" class="btn-primary" style="display:inline-flex;margin-top:1rem;">Post a New Job</a>
        </div>
        <?php else: ?>
        <div class="job-list">
            <?php foreach ($activeJobs as $job): ?>
            <article class="job-list-item">
                <div class="job-list-icon job-list-icon--<?= $job['icon'] ?>">
                    <?php if ($job['icon'] === 'design'): ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>
                    <?php else: ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    <?php endif; ?>
                </div>
                <div class="job-list-info">
                    <strong><?= htmlspecialchars($job['title']) ?></strong>
                    <span><?= htmlspecialchars($job['meta']) ?></span>
                </div>
                <div class="job-list-stats">
                    <div>
                        <strong>
                            <?php if ($job['applicants'] > 0): ?>
                            <a href="/employer/applicants.php?job_id=<?= (int) $job['id'] ?>"><?= (int) $job['applicants'] ?></a>
                            <?php else: ?>
                            <?= (int) $job['applicants'] ?>
                            <?php endif; ?>
                        </strong>
                        <span>Applicants</span>
                    </div>
                    <div><strong><?= $job['new'] ?></strong><span>New</span></div>
                </div>
                <div class="job-list-actions">
                    <a href="/employer/job-edit.php?id=<?= $job['id'] ?>" class="icon-tool" aria-label="Edit job">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </a>
                    <a href="/employer/job-listings.php" class="icon-tool" aria-label="View job listings">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <div class="sidebar-widgets">
        <section class="panel">
            <div class="panel-header panel-header--compact">
                <h2>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Upcoming Interviews
                </h2>
            </div>
            <?php if ($interviews === []): ?>
            <div class="empty-state" style="padding:1.5rem 1rem;">
                <p>No interviews scheduled yet. Interviews will appear here once you start reviewing applicants.</p>
            </div>
            <?php else: ?>
            <ul class="interview-list">
                <?php foreach ($interviews as $iv): ?>
                <li class="interview-item">
                    <div class="interview-date">
                        <span class="interview-month"><?= $iv['month'] ?></span>
                        <span class="interview-day"><?= $iv['day'] ?></span>
                    </div>
                    <div class="interview-info">
                        <strong><?= htmlspecialchars($iv['name']) ?></strong>
                        <span><?= htmlspecialchars($iv['time']) ?></span>
                        <a href="#"><?= htmlspecialchars($iv['role']) ?></a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <a href="/employer/applicants.php" class="btn-outline-full">View Applicants</a>
        </section>

        <section class="panel panel--help">
            <span class="help-label">Quick Help</span>
            <div class="help-grid">
                <a href="/employer/job-listings.php" class="help-card">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Job Listings
                </a>
                <a href="/employer/settings.php" class="help-card">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Settings
                </a>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/employer/layout-end.php'; ?>
