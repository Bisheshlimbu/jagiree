<?php
/**
 * LinkedIn-style compact job row for browse lists.
 * Expects $job array; optional $selectedJobId, $jobViewUrl.
 */
$job = $job ?? [];
$jobId = (int) ($job['id'] ?? 0);
$isSelected = $jobId > 0 && isset($selectedJobId) && $jobId === (int) $selectedJobId;
$jobViewUrl = $jobViewUrl ?? ($jobId > 0 ? '/seeker/jobs.php?id=' . $jobId : '#');
$hasLogo = !empty($job['has_logo']) && !empty($job['logo_url']);
?>
<a href="<?= htmlspecialchars($jobViewUrl) ?>" class="job-row<?= $isSelected ? ' is-selected' : '' ?><?= $hasLogo ? '' : ' job-row--no-logo' ?>">
    <?php if ($hasLogo): ?>
    <div class="job-row__logo job-row__logo--image">
        <img src="<?= htmlspecialchars($job['logo_url']) ?>" alt="<?= htmlspecialchars($job['company'] ?? '') ?>">
    </div>
    <?php endif; ?>
    <div class="job-row__body">
        <h3><?= htmlspecialchars($job['title'] ?? '') ?></h3>
        <p class="job-row__company">
            <?= htmlspecialchars($job['company'] ?? '') ?>
            <?php if (!empty($job['is_external'])): ?>
            <span class="job-source-badge job-source-badge--inline"><?= htmlspecialchars($job['source_label'] ?? 'LinkedIn') ?></span>
            <?php endif; ?>
            &bull; <?= htmlspecialchars($job['location'] ?? '') ?>
        </p>
        <p class="job-row__meta">
            <?php if (!empty($job['type'])): ?>
            <span><?= htmlspecialchars($job['type']) ?></span>
            <?php endif; ?>
            <?php if (!empty($job['posted'])): ?>
            <span><?= htmlspecialchars($job['posted']) ?></span>
            <?php endif; ?>
        </p>
    </div>
    <?php if (!empty($job['match'])): ?>
    <span class="job-row__match match-pill match-pill--<?= ($job['match'] >= 90) ? 'high' : 'good' ?>">
        <?= (int) $job['match'] ?>%
    </span>
    <?php endif; ?>
</a>
