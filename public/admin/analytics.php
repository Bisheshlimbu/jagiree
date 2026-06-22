<?php
require_once __DIR__ . '/../../includes/admin/analytics.php';

$pageTitle = 'Analytics — Jagiree Admin';
$activePage = 'analytics';
$pageHeading = 'Analytics';
$pageScripts = ['/assets/js/admin-analytics.js'];
require_once __DIR__ . '/../../includes/admin/layout-start.php';

$analytics = getAdminAnalyticsData();
$stats = $analytics['stats'];
$summary = $analytics['summary'];
?>

<div class="stats-grid stats-grid--analytics">
    <?php foreach ($stats as $stat): ?>
    <article class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label"><?= htmlspecialchars($stat['label']) ?></span>
            <span class="stat-icon stat-icon--<?= htmlspecialchars($stat['icon']) ?>">
                <?php if ($stat['icon'] === 'users'): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <?php elseif ($stat['icon'] === 'growth'): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                <?php else: ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                <?php endif; ?>
            </span>
        </div>
        <div class="stat-value"><?= htmlspecialchars($stat['value']) ?></div>
        <span class="stat-trend stat-trend--<?= htmlspecialchars($stat['trendType']) ?>"><?= htmlspecialchars($stat['trend']) ?></span>
    </article>
    <?php endforeach; ?>
</div>

<div class="analytics-charts-grid">
    <section class="panel panel--chart">
        <div class="panel-header">
            <div>
                <h2>User Registrations</h2>
                <p>New seeker and employer sign-ups</p>
            </div>
            <div class="chart-toggle" role="group" aria-label="Registration chart period">
                <button type="button" class="chart-toggle-btn" data-chart="registrations" data-period="weekly">Weekly</button>
                <button type="button" class="chart-toggle-btn is-active" data-chart="registrations" data-period="monthly">Monthly</button>
            </div>
        </div>
        <div class="chart-wrap">
            <canvas id="registrationsChart" aria-label="User registrations chart"></canvas>
        </div>
    </section>

    <section class="panel panel--chart">
        <div class="panel-header">
            <div>
                <h2>Job Listings</h2>
                <p>Jobs posted over time</p>
            </div>
            <div class="chart-toggle" role="group" aria-label="Jobs chart period">
                <button type="button" class="chart-toggle-btn" data-chart="jobs" data-period="weekly">Weekly</button>
                <button type="button" class="chart-toggle-btn is-active" data-chart="jobs" data-period="monthly">Monthly</button>
            </div>
        </div>
        <div class="chart-wrap">
            <canvas id="jobsChart" aria-label="Job listings chart"></canvas>
        </div>
    </section>
</div>

<div class="analytics-breakdown-grid">
    <section class="panel">
        <div class="panel-header panel-header--compact">
            <div>
                <h2>Users by Role</h2>
                <p>Seekers vs employers</p>
            </div>
        </div>
        <div class="chart-wrap chart-wrap--donut">
            <canvas id="usersRoleChart" aria-label="Users by role chart"></canvas>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header panel-header--compact">
            <div>
                <h2>Users by Status</h2>
                <p>Verified vs pending accounts</p>
            </div>
        </div>
        <div class="chart-wrap chart-wrap--donut">
            <canvas id="usersStatusChart" aria-label="Users by status chart"></canvas>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header panel-header--compact">
            <div>
                <h2>Jobs by Status</h2>
                <p>Approved, pending, and rejected</p>
            </div>
        </div>
        <div class="chart-wrap chart-wrap--donut">
            <canvas id="jobsStatusChart" aria-label="Jobs by status chart"></canvas>
        </div>
    </section>
</div>

<section class="panel">
    <div class="panel-header panel-header--compact">
        <div>
            <h2>Platform Summary</h2>
            <p>Quick links to areas that need attention</p>
        </div>
    </div>
    <ul class="analytics-summary-list">
        <?php foreach ($summary as $row): ?>
        <li>
            <span><?= htmlspecialchars($row['label']) ?></span>
            <strong><?= number_format($row['value']) ?></strong>
            <a href="<?= htmlspecialchars($row['href']) ?>" class="panel-link">View</a>
        </li>
        <?php endforeach; ?>
    </ul>
</section>

<script>
window.adminAnalyticsData = <?= json_encode($analytics, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
