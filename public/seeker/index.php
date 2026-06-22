<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/seeker/profile.php';
require_once __DIR__ . '/../../includes/seeker/jobs.php';
require_once __DIR__ . '/../../includes/applications.php';

requireRole(ROLE_SEEKER);

$authUser = currentUser();
$userId = (int) $authUser['id'];
$profile = fetchSeekerProfile($userId);
$seekerSkills = $profile['skill_list'] ?? [];
$seekerName = $profile['full_name'] ?? displayName($authUser);
$seekerTitle = formatSeekerDisplayHeadline($profile ?? []);
$seekerLocation = trim($profile['location'] ?? '') ?: 'Add your location';
$seekerOpenToWork = !empty($profile['open_to_work']);
$seekerHasCv = seekerHasCv($userId);

$allMatches = fetchSeekerJobs('', 'all', 'match', $seekerSkills, $userId);
$matchCount = count($allMatches);
$recommendedJobs = array_slice($allMatches, 0, 4);
$recentJobs = getSeekerRecentJobs($userId, 3);
$appliedCount = countSeekerApplications($userId);
$applicationSummary = seekerApplicationStatusSummary($userId);

$pageTitle = 'Home — Jagiree';
$activePage = 'home';
$extraScripts = ['assets/js/job-apply.js'];
require_once __DIR__ . '/../../includes/seeker/layout-start.php';

$savedJobs = [
    ['title' => 'Senior UX Designer', 'company' => 'CloudBase Nepal'],
    ['title' => 'Product Manager', 'company' => 'Stripe Nepal'],
];

$filters = ['All Jobs', 'Remote', 'IT & Tech', 'Design', 'Fresher', 'Kathmandu'];
?>

<div class="feed-layout">
    <aside class="feed-left">
        <?php require __DIR__ . '/../../includes/seeker/profile-sidebar.php'; ?>
    </aside>

    <main class="feed-center">
        <div class="search-hero">
            <h1>Find your next opportunity</h1>
            <p>Discover jobs matched to your skills across Nepal</p>
            <form class="search-form" action="/seeker/jobs.php" method="get">
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="q" placeholder="Job title, keyword, or company">
                </div>
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <input type="text" name="location" placeholder="City or remote">
                </div>
                <button type="submit" class="btn-search">Search Jobs</button>
            </form>
        </div>

        <div class="filter-chips">
            <?php foreach ($filters as $i => $filter): ?>
            <a href="/seeker/jobs.php" class="filter-chip <?= $i === 0 ? 'is-active' : '' ?>"><?= htmlspecialchars($filter) ?></a>
            <?php endforeach; ?>
        </div>

        <section class="feed-section">
            <div class="feed-section-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    Recommended for you
                </h2>
                <span class="feed-badge">AI Powered</span>
            </div>
            <?php if ($recommendedJobs === []): ?>
            <div class="panel--empty panel--empty--compact">
                <p>No approved jobs yet. Check back soon or ask the AI assistant for help.</p>
            </div>
            <?php else: ?>
            <div class="job-feed">
                <?php foreach ($recommendedJobs as $job): ?>
                    <?php
                    $jobViewUrl = '/seeker/jobs.php?id=' . (int) $job['id'];
                    require __DIR__ . '/../../includes/seeker/job-card.php';
                    unset($jobViewUrl);
                    ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <section class="feed-section">
            <div class="feed-section-header">
                <h2>Recently posted</h2>
                <a href="/seeker/jobs.php?sort=recent" class="see-all">See all jobs</a>
            </div>
            <?php if ($recentJobs === []): ?>
            <div class="panel--empty panel--empty--compact">
                <p>No recent listings yet.</p>
            </div>
            <?php else: ?>
            <div class="job-feed">
                <?php foreach ($recentJobs as $job): ?>
                    <?php
                    $jobViewUrl = '/seeker/jobs.php?id=' . (int) $job['id'];
                    require __DIR__ . '/../../includes/seeker/job-card.php';
                    unset($jobViewUrl);
                    ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <aside class="feed-right">
        <div class="widget-card widget-card--ai">
            <div class="ai-widget-header">
                <span class="ai-icon">🤖</span>
                <div>
                    <h3>Jagiree AI Assistant</h3>
                    <p>Upload CV once — used for matches and Easy Apply</p>
                </div>
            </div>
            <button type="button" class="btn-ai-chat" onclick="window.location.href='/seeker/chat.php'">Start Chat</button>
        </div>

        <div class="widget-card">
            <div class="widget-header">
                <h3>Saved Jobs</h3>
                <a href="/seeker/jobs.php?saved=1">View all</a>
            </div>
            <ul class="saved-list">
                <?php foreach ($savedJobs as $saved): ?>
                <li>
                    <strong><?= htmlspecialchars($saved['title']) ?></strong>
                    <span><?= htmlspecialchars($saved['company']) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="widget-card">
            <div class="widget-header">
                <h3>Application Status</h3>
                <a href="/seeker/applications.php">View all</a>
            </div>
            <ul class="status-list">
                <?php if ($applicationSummary['total'] === 0): ?>
                <li><span class="status-dot status-dot--applied"></span> No applications yet</li>
                <?php else: ?>
                <?php if ($applicationSummary['review'] > 0): ?>
                <li><span class="status-dot status-dot--review"></span> <?= (int) $applicationSummary['review'] ?> Under review</li>
                <?php endif; ?>
                <?php if ($applicationSummary['interview'] > 0): ?>
                <li><span class="status-dot status-dot--interview"></span> <?= (int) $applicationSummary['interview'] ?> Interview scheduled</li>
                <?php endif; ?>
                <?php if ($applicationSummary['applied'] > 0): ?>
                <li><span class="status-dot status-dot--applied"></span> <?= (int) $applicationSummary['applied'] ?> Applied</li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="widget-card widget-card--promo">
            <p><?= $seekerHasCv ? 'Your CV is on file for Easy Apply' : 'Upload your CV to unlock Easy Apply' ?></p>
            <strong><a href="/seeker/profile.php?tab=skills" class="widget-link"><?= $seekerHasCv ? 'Manage CV' : 'Upload CV' ?></a></strong>
        </div>
    </aside>
</div>

<script>
window.seekerHasCv = <?= json_encode($seekerHasCv) ?>;
window.seekerProfileCvUrl = <?= json_encode('/seeker/profile.php?tab=skills') ?>;
</script>

<?php require_once __DIR__ . '/../../includes/seeker/layout-end.php'; ?>
