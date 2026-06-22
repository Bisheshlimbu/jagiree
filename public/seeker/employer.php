<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/applications.php';
require_once __DIR__ . '/../../includes/seeker/jobs.php';
require_once __DIR__ . '/../../includes/seeker/profile.php';

requireRole(ROLE_SEEKER);

$authUser = currentUser();
$userId = (int) $authUser['id'];
$profile = fetchSeekerProfile($userId);
$seekerSkills = $profile['skill_list'] ?? [];

$employerId = (int) ($_GET['id'] ?? 0);
$employer = $employerId > 0 ? fetchPublicEmployerForSeeker($employerId) : null;

if (!$employer) {
    http_response_code(404);
    $pageTitle = 'Employer Not Found — Jagiree';
    $activePage = 'jobs';
    require_once __DIR__ . '/../../includes/seeker/layout-start.php';
    ?>
    <div class="page-layout page-layout--single">
        <div class="panel--empty">
            <div class="empty-state">
                <h2>Employer not found</h2>
                <p>This company profile is unavailable or the employer is not verified.</p>
                <a href="/seeker/jobs.php" class="btn-view">Back to jobs</a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../../includes/seeker/layout-end.php';
    exit;
}

$employerJobs = fetchEmployerApprovedJobs($employerId, $seekerSkills, $userId);
$seekerHasCv = seekerHasCv($userId);
$companyName = trim($employer['company_name'] ?? '') ?: 'Company';
$industryLabel = employerIndustryLabel($employer['industry'] ?? null);
$about = trim($employer['company_about'] ?? '');
$avatarUrl = userAvatarUrl($employer);
$hasAvatar = userHasAvatar($employer);
$logoLetter = strtoupper(mb_substr($companyName, 0, 1));

$pageTitle = $companyName . ' — Jagiree';
$activePage = 'jobs';
$extraScripts = ['assets/js/job-apply.js'];
require_once __DIR__ . '/../../includes/seeker/layout-start.php';
?>

<div class="employer-public-layout">
    <a href="/seeker/jobs.php" class="back-link">← Back to jobs</a>

    <section class="employer-public-card">
        <div class="employer-public-cover"></div>
        <div class="employer-public-body">
            <div class="employer-public-avatar<?= $hasAvatar ? ' employer-public-avatar--image' : '' ?>">
                <?php if ($hasAvatar): ?>
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($companyName) ?>">
                <?php else: ?>
                <?= htmlspecialchars($logoLetter) ?>
                <?php endif; ?>
            </div>
            <div class="employer-public-info">
                <h1><?= htmlspecialchars($companyName) ?></h1>
                <?php if ($industryLabel !== ''): ?>
                <p class="employer-public-industry"><?= htmlspecialchars($industryLabel) ?></p>
                <?php endif; ?>
                <p class="employer-public-meta"><?= count($employerJobs) ?> open job<?= count($employerJobs) === 1 ? '' : 's' ?></p>
            </div>
        </div>
    </section>

    <?php if ($about !== ''): ?>
    <section class="employer-public-section">
        <h2>About</h2>
        <div class="employer-public-about"><?= nl2br(htmlspecialchars($about)) ?></div>
    </section>
    <?php endif; ?>

    <section class="employer-public-section">
        <div class="employer-public-section-header">
            <h2>Open positions</h2>
            <span><?= count($employerJobs) ?> job<?= count($employerJobs) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($employerJobs === []): ?>
        <div class="panel--empty panel--empty--compact">
            <p>No open positions from this employer right now.</p>
        </div>
        <?php else: ?>
        <div class="job-feed">
            <?php foreach ($employerJobs as $job): ?>
                <?php
                $jobViewUrl = '/seeker/jobs.php?id=' . (int) $job['id'];
                require __DIR__ . '/../../includes/seeker/job-card.php';
                unset($jobViewUrl);
                ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<script>
window.seekerHasCv = <?= json_encode($seekerHasCv) ?>;
window.seekerProfileCvUrl = <?= json_encode('/seeker/profile.php?tab=skills') ?>;
</script>

<?php require_once __DIR__ . '/../../includes/seeker/layout-end.php'; ?>
