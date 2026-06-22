<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/seeker/jobs.php';
require_once __DIR__ . '/../../includes/seeker/profile.php';
require_once __DIR__ . '/../../includes/applications.php';

requireRole(ROLE_SEEKER);

$authUser = currentUser();
$userId = (int) $authUser['id'];
$profile = fetchSeekerProfile($userId);
$seekerSkills = $profile['skill_list'] ?? [];

$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'match';
$filters = seekerJobFilters();
$selectedJobId = (int) ($_GET['id'] ?? 0);

if (!isset($filters[$filter])) {
    $filter = 'all';
}

if (!in_array($sort, ['match', 'recent', 'salary'], true)) {
    $sort = 'match';
}

$jobs = fetchSeekerJobs($search, $filter, $sort, $seekerSkills, $userId);
$jobCount = count($jobs);
$appliedCount = countSeekerApplications($userId);

$selectedJob = null;
$selectedJobRow = null;
$appliedJobIds = seekerAppliedJobIds($userId);
if ($selectedJobId > 0) {
    $selectedJobRow = fetchApprovedJobForSeeker($selectedJobId);
    if ($selectedJobRow) {
        $selectedJob = formatSeekerJobCard($selectedJobRow, $seekerSkills, $appliedJobIds);
    } else {
        $selectedJobId = 0;
    }
}

$hasDetail = $selectedJob !== null;
$matchCount = $jobCount;
$seekerHasCv = seekerHasCv($userId);

$queryBase = static function (array $overrides = []) use ($search, $filter, $sort, $selectedJobId): string {
    $nextId = array_key_exists('id', $overrides)
        ? $overrides['id']
        : ($selectedJobId > 0 ? $selectedJobId : null);

    if (!empty($overrides['clear_id'])) {
        $nextId = null;
    }

    $params = array_filter([
        'q' => $search !== '' ? $search : null,
        'filter' => ($overrides['filter'] ?? $filter) !== 'all' ? ($overrides['filter'] ?? $filter) : null,
        'sort' => ($overrides['sort'] ?? $sort) !== 'match' ? ($overrides['sort'] ?? $sort) : null,
        'id' => $nextId,
    ], fn ($value) => $value !== null && $value !== '');

    return $params === [] ? '/seeker/jobs.php' : '/seeker/jobs.php?' . http_build_query($params);
};

$pageTitle = 'Find Jobs — Jagiree';
$activePage = 'jobs';
$extraScripts = array_values(array_unique(array_merge(
    ['assets/js/job-apply.js'],
    $hasDetail ? ['assets/js/jobs-browse.js'] : []
)));
require_once __DIR__ . '/../../includes/seeker/layout-start.php';
?>

<div class="jobs-browse-layout<?= $hasDetail ? ' has-detail' : ' has-profile' ?>">
    <?php if (!$hasDetail): ?>
    <aside class="feed-left jobs-profile-sidebar">
        <?php
        $matchCount = $jobCount;
        $appliedCount = $appliedCount;
        require __DIR__ . '/../../includes/seeker/profile-sidebar.php';
        ?>
    </aside>
    <?php endif; ?>

    <main class="jobs-browse-main">
        <div class="page-title-bar<?= $hasDetail ? ' page-title-bar--compact' : '' ?>">
            <div>
                <?php if ($hasDetail): ?>
                <a href="<?= htmlspecialchars($queryBase(['clear_id' => true])) ?>" class="back-link">← Back to jobs</a>
                <?php endif; ?>
                <h1><?= $hasDetail ? htmlspecialchars($selectedJob['title'] ?? 'Job details') : 'Browse Jobs' ?></h1>
                <p><?= $jobCount ?> job<?= $jobCount === 1 ? '' : 's' ?> found · Sorted by <?= htmlspecialchars(strtolower(seekerSortLabel($sort))) ?></p>
            </div>
        </div>

        <form method="get" class="jobs-toolbar" action="/seeker/jobs.php">
            <?php if ($filter !== 'all'): ?>
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <?php endif; ?>
            <?php if ($selectedJobId > 0): ?>
            <input type="hidden" name="id" value="<?= $selectedJobId ?>">
            <?php endif; ?>
            <div class="search-field search-field--inline">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" placeholder="Search jobs..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select class="sort-select" name="sort" aria-label="Sort jobs" onchange="this.form.submit()">
                <option value="match" <?= $sort === 'match' ? 'selected' : '' ?>>Best Match</option>
                <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Most Recent</option>
                <option value="salary" <?= $sort === 'salary' ? 'selected' : '' ?>>Salary: High to Low</option>
            </select>
        </form>

        <div class="filter-chips filter-chips--wrap">
            <?php foreach ($filters as $key => $label): ?>
            <a href="<?= htmlspecialchars($queryBase(['filter' => $key])) ?>" class="filter-chip <?= $filter === $key ? 'is-active' : '' ?>"><?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($jobs === []): ?>
        <div class="panel--empty">
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                <h2>No jobs found</h2>
                <p><?= $search !== '' || $filter !== 'all' ? 'Try adjusting your search or filters.' : 'Approved job listings will appear here once employers post openings.' ?></p>
            </div>
        </div>
        <?php elseif ($hasDetail): ?>
        <div class="jobs-browse-split">
            <div class="jobs-browse-list">
                <div class="job-feed job-feed--full">
                    <?php foreach ($jobs as $job): ?>
                        <?php
                        $jobViewUrl = $queryBase(['id' => (int) $job['id']]);
                        $jobCardCompact = true;
                        require __DIR__ . '/../../includes/seeker/job-card.php';
                        unset($jobViewUrl, $jobCardCompact);
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="jobs-browse-detail" id="jobDetailPanel">
                <?php require __DIR__ . '/../../includes/seeker/job-detail-panel.php'; ?>
            </aside>
        </div>
        <?php else: ?>
        <div class="job-feed">
            <?php foreach ($jobs as $job): ?>
                <?php
                $jobViewUrl = $queryBase(['id' => (int) $job['id']]);
                require __DIR__ . '/../../includes/seeker/job-card.php';
                unset($jobViewUrl);
                ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php if ($hasDetail): ?>
<script>
window.jobsBrowseCloseUrl = <?= json_encode($queryBase(['clear_id' => true])) ?>;
</script>
<?php endif; ?>
<script>
window.seekerHasCv = <?= json_encode($seekerHasCv) ?>;
window.seekerProfileCvUrl = <?= json_encode('/seeker/profile.php?tab=skills') ?>;
</script>

<?php require_once __DIR__ . '/../../includes/seeker/layout-end.php'; ?>
