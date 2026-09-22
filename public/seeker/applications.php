<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/applications.php';

requireRole(ROLE_SEEKER);

$userId = (int) currentUser()['id'];
$applications = fetchSeekerApplications($userId);
$applicationSummary = seekerApplicationStatusSummary($userId);

$pageTitle = 'My Applications — Jagiree';
$activePage = 'applications';
$extraScripts = ['assets/js/job-apply.js'];
require_once __DIR__ . '/../../includes/seeker/layout-start.php';
?>

<div class="page-layout page-layout--single page-layout--applications">
    <div class="page-content">
        <div class="page-title-bar">
            <h1>My Applications</h1>
            <p>Track where you've applied and your current status</p>
        </div>

        <?php if ($applications === []): ?>
        <div class="panel--empty">
            <div class="empty-state">
                <h2>No applications yet</h2>
                <p>Browse jobs and click Easy Apply to send your profile and CV to employers.</p>
                <a href="/seeker/jobs.php" class="btn-view">Browse jobs</a>
            </div>
        </div>
        <?php else: ?>
        <div class="applications-summary">
            <div class="applications-summary__item">
                <strong><?= (int) $applicationSummary['total'] ?></strong>
                <span>Total</span>
            </div>
            <?php if ($applicationSummary['applied'] > 0): ?>
            <div class="applications-summary__item applications-summary__item--applied">
                <strong><?= (int) $applicationSummary['applied'] ?></strong>
                <span>Applied</span>
            </div>
            <?php endif; ?>
            <?php if ($applicationSummary['review'] > 0): ?>
            <div class="applications-summary__item applications-summary__item--review">
                <strong><?= (int) $applicationSummary['review'] ?></strong>
                <span>In review</span>
            </div>
            <?php endif; ?>
            <?php if ($applicationSummary['rejected'] > 0): ?>
            <div class="applications-summary__item applications-summary__item--rejected">
                <strong><?= (int) $applicationSummary['rejected'] ?></strong>
                <span>Not selected</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="applications-list">
        <?php foreach ($applications as $app): ?>
        <?php
        $statusClass = applicationStatusClass($app['status']);
        ?>
        <article class="app-card" data-application-id="<?= (int) $app['id'] ?>">
            <div class="app-card__header">
                <?php if (!empty($app['has_logo']) && !empty($app['logo_url'])): ?>
                <div class="app-card__brand app-card__brand--image">
                    <img src="<?= htmlspecialchars($app['logo_url']) ?>" alt="">
                </div>
                <?php endif; ?>
                <div class="app-card__title-block">
                    <h3><a href="/seeker/jobs.php?id=<?= (int) $app['job_id'] ?>"><?= htmlspecialchars($app['title']) ?></a></h3>
                    <p><?= htmlspecialchars($app['company']) ?> &bull; <?= htmlspecialchars($app['location']) ?></p>
                </div>
                <span class="app-status-badge app-status-badge--<?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($app['statusLabel']) ?></span>
            </div>

            <div class="app-card__meta">
                <span class="app-card__meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Applied <?= htmlspecialchars($app['applied']) ?>
                </span>
                <?php if (!empty($app['cv_path'])): ?>
                <span class="app-card__meta-divider" aria-hidden="true"></span>
                <a href="<?= htmlspecialchars($app['cv_path']) ?>" class="app-card__meta-link" target="_blank" rel="noopener">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    CV sent
                </a>
                <?php endif; ?>
                <?php if (!empty($app['match'])): ?>
                <span class="match-pill match-pill--<?= ($app['match'] >= 90) ? 'high' : 'good' ?> app-card__match">
                    <?= (int) $app['match'] ?>% match
                </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($app['can_edit']) || !empty($app['can_delete'])): ?>
            <div class="app-card__footer">
                <?php if (!empty($app['can_edit'])): ?>
                <button
                    type="button"
                    class="app-card-action"
                    data-edit-application="<?= (int) $app['id'] ?>"
                    aria-label="Edit application for <?= htmlspecialchars($app['title']) ?>"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                </button>
                <?php endif; ?>
                <?php if (!empty($app['can_delete'])): ?>
                <button
                    type="button"
                    class="app-card-action app-card-action--danger"
                    data-delete-application="<?= (int) $app['id'] ?>"
                    data-job-title="<?= htmlspecialchars($app['title'], ENT_QUOTES) ?>"
                    aria-label="Withdraw application for <?= htmlspecialchars($app['title']) ?>"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Withdraw
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/seeker/layout-end.php'; ?>
