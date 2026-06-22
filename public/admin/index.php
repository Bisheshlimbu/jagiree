<?php
require_once __DIR__ . '/../../includes/admin/dashboard.php';
require_once __DIR__ . '/../../includes/flash.php';

$pageTitle = 'Overview — Jagiree Admin';
$activePage = 'dashboard';
$pageHeading = 'Overview';
require_once __DIR__ . '/../../includes/admin/layout-start.php';

$dashboard = getAdminDashboardData();
$stats = $dashboard['stats'];
$registrations = $dashboard['registrations'];
$insights = $dashboard['insights'];
$chartData = $dashboard['chart'];
?>

<?php renderAdminFlash(); ?>

<div class="stats-grid">
    <?php foreach ($stats as $stat): ?>
    <article class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label"><?= htmlspecialchars($stat['label']) ?></span>
            <span class="stat-icon stat-icon--<?= $stat['icon'] ?>">
                <?php if ($stat['icon'] === 'users'): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <?php elseif ($stat['icon'] === 'growth'): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                <?php elseif ($stat['icon'] === 'jobs'): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                <?php else: ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                <?php endif; ?>
            </span>
        </div>
        <div class="stat-value"><?= htmlspecialchars($stat['value']) ?></div>
        <span class="stat-trend stat-trend--<?= htmlspecialchars($stat['trendType']) ?>"><?= htmlspecialchars($stat['trend']) ?></span>
    </article>
    <?php endforeach; ?>
</div>

<div class="dashboard-grid">
    <section class="panel panel--chart">
        <div class="panel-header">
            <div>
                <h2>Platform Activity</h2>
                <p>New user registrations over time</p>
            </div>
            <div class="chart-toggle" role="group" aria-label="Chart period">
                <button type="button" class="chart-toggle-btn" data-period="weekly">Weekly</button>
                <button type="button" class="chart-toggle-btn is-active" data-period="monthly">Monthly</button>
            </div>
        </div>
        <div class="chart-wrap">
            <canvas id="activityChart" aria-label="Platform activity chart"></canvas>
        </div>
    </section>

    <aside class="panel panel--insights">
        <div class="panel-header panel-header--compact">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/></svg>
                AI Insights
            </h2>
        </div>

        <?php foreach ($insights as $insight): ?>
        <div class="insight-card insight-card--<?= htmlspecialchars($insight['type']) ?>">
            <h3><?= htmlspecialchars($insight['title']) ?></h3>
            <p><?= htmlspecialchars($insight['text']) ?></p>
        </div>
        <?php endforeach; ?>

        <a href="/admin/analytics.php" class="btn-audit">View Analytics &gt;</a>
    </aside>
</div>

<section class="panel panel--table">
    <div class="panel-header">
        <h2>Recent Registrations</h2>
        <a href="/admin/users.php" class="panel-link">View All</a>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Company Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($registrations === []): ?>
                <tr>
                    <td colspan="5" class="table-empty">No registrations yet.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($registrations as $user): ?>
                        <?php require __DIR__ . '/../../includes/admin/user-table-row.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<a href="/admin/user-add.php" class="admin-fab" aria-label="Add user">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
</a>

<script>
window.adminDashboardChart = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
