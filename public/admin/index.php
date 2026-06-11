<?php
$pageTitle = 'Overview — Jagiree Admin';
$activePage = 'dashboard';
$pageHeading = 'Overview';
require_once __DIR__ . '/../../includes/admin/layout-start.php';

$stats = [
    ['label' => 'Total Users', 'value' => '24,512', 'trend' => '+12.5%', 'trendType' => 'up', 'icon' => 'users'],
    ['label' => 'Monthly Growth', 'value' => '3,204', 'trend' => '+4.2%', 'trendType' => 'up', 'icon' => 'growth'],
    ['label' => 'Active Jobs', 'value' => '1,842', 'trend' => '-0.8%', 'trendType' => 'down', 'icon' => 'jobs'],
    ['label' => 'Pending Requests', 'value' => '56', 'trend' => 'Stable', 'trendType' => 'neutral', 'icon' => 'reports'],
];

$registrations = [
    ['name' => 'Sarah Jenkins', 'email' => 'sarah.j@email.com', 'role' => 'Seeker', 'location' => 'Kathmandu, Nepal', 'status' => 'verified', 'avatar' => 32],
    ['name' => 'Marcus Thorne', 'email' => 'm.thorne@corp.io', 'role' => 'Employer', 'location' => 'Lalitpur, Nepal', 'status' => 'verified', 'avatar' => 15],
    ['name' => 'Elena Rodriguez', 'email' => 'elena.r@design.net', 'role' => 'Seeker', 'location' => 'Pokhara, Nepal', 'status' => 'pending', 'avatar' => 45],
    ['name' => 'David Chen', 'email' => 'd.chen@tech.dev', 'role' => 'Employer', 'location' => 'Remote', 'status' => 'verified', 'avatar' => 68],
];
?>

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
        <span class="stat-trend stat-trend--<?= $stat['trendType'] ?>"><?= htmlspecialchars($stat['trend']) ?></span>
    </article>
    <?php endforeach; ?>
</div>

<div class="dashboard-grid">
    <section class="panel panel--chart">
        <div class="panel-header">
            <div>
                <h2>Platform Activity</h2>
                <p>Real-time user engagement metrics</p>
            </div>
            <div class="chart-toggle" role="group" aria-label="Chart period">
                <button type="button" class="chart-toggle-btn">Weekly</button>
                <button type="button" class="chart-toggle-btn is-active">Monthly</button>
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

        <div class="insight-card insight-card--purple">
            <h3>Growth Opportunity</h3>
            <p>Tech sector listings are up 18% this week. Consider targeted outreach to IT employers in Kathmandu.</p>
        </div>

        <div class="insight-card insight-card--teal">
            <h3>Retention Alert</h3>
            <p>Weekend engagement is 12% lower than average. Suggest automated nudges for inactive seekers.</p>
        </div>

        <button type="button" class="btn-audit">Generate Full Audit &gt;</button>
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
                    <th>Location</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $user): ?>
                <tr>
                    <td>
                        <div class="table-user">
                            <img src="https://i.pravatar.cc/80?img=<?= $user['avatar'] ?>" alt="">
                            <div>
                                <strong><?= htmlspecialchars($user['name']) ?></strong>
                                <span><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= htmlspecialchars($user['location']) ?></td>
                    <td>
                        <span class="status-badge status-badge--<?= $user['status'] ?>">
                            <?= $user['status'] === 'verified' ? 'Verified' : 'Pending' ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button type="button" class="table-action-btn" aria-label="Edit user">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button type="button" class="table-action-btn table-action-btn--danger" aria-label="Delete user">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<button type="button" class="admin-fab" aria-label="Add new">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
</button>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
