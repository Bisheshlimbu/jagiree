<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/applications.php';

requireRole(ROLE_EMPLOYER);

$authUser = currentUser();
$employerId = (int) $authUser['id'];

$filterJobId = (int) ($_GET['job_id'] ?? 0);
$filterStatus = trim($_GET['status'] ?? '');
$filterSort = trim($_GET['sort'] ?? 'recent');
if (!in_array($filterSort, ['recent', 'match'], true)) {
    $filterSort = 'recent';
}

$applicants = fetchEmployerApplicants($employerId, [
    'job_id' => $filterJobId,
    'status' => $filterStatus,
    'sort' => $filterSort,
]);
$filterJobs = fetchEmployerApplicantJobs($employerId);
$statusOptions = employerApplicationFilterStatusOptions();

$pageTitle = 'Applicants — Jagiree Employer';
$activePage = 'applicants';
$activeTopNav = 'talent';
$extraScripts = ['employer-applications.js'];
require_once __DIR__ . '/../../includes/employer/layout-start.php';
?>

<div class="page-header">
    <div>
        <h1>Talent Pool</h1>
        <p>Review applicants matched to your open roles.</p>
    </div>
</div>

<form class="applicant-filters" method="get" action="/employer/applicants.php">
    <label class="applicant-filter">
        <span>Job</span>
        <select name="job_id">
            <option value="">All jobs</option>
            <?php foreach ($filterJobs as $job): ?>
            <option value="<?= (int) $job['id'] ?>" <?= $filterJobId === (int) $job['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($job['title']) ?> (<?= (int) $job['applicant_count'] ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="applicant-filter">
        <span>Status</span>
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach ($statusOptions as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= $filterStatus === $value ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="applicant-filter">
        <span>Sort by</span>
        <select name="sort">
            <option value="recent" <?= $filterSort === 'recent' ? 'selected' : '' ?>>Most recent</option>
            <option value="match" <?= $filterSort === 'match' ? 'selected' : '' ?>>Best match</option>
        </select>
    </label>
    <button type="submit" class="btn-outline-full applicant-filter-submit">Apply filters</button>
    <?php if ($filterJobId > 0 || $filterStatus !== '' || $filterSort !== 'recent'): ?>
    <a href="/employer/applicants.php" class="applicant-filter-reset">Clear</a>
    <?php endif; ?>
</form>

<section class="panel panel--table">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Match Score</th>
                    <th>Cover letter</th>
                    <th>Applied</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($applicants === []): ?>
                <tr>
                    <td colspan="7" class="table-empty">
                        <?= ($filterJobId > 0 || $filterStatus !== '')
                            ? 'No applicants match these filters.'
                            : 'No applications yet. Applicants will appear here once seekers apply to your jobs.' ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($applicants as $app): ?>
                <tr data-application-row="<?= (int) $app['id'] ?>">
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
                        <?php if (!empty($app['status_locked'])): ?>
                        <span class="app-status app-status--completed"><?= htmlspecialchars($app['status_label']) ?></span>
                        <?php else: ?>
                        <select
                            class="app-status-select"
                            data-application-id="<?= (int) $app['id'] ?>"
                            aria-label="Update status for <?= htmlspecialchars($app['name']) ?>"
                        >
                            <?php foreach (employerApplicationStatusOptions() as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= $app['status'] === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
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
                            <a href="<?= htmlspecialchars($app['cv_path']) ?>" class="btn-primary btn-primary--sm" target="_blank" rel="noopener">View CV</a>
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

<?php require_once __DIR__ . '/../../includes/employer/layout-end.php'; ?>
