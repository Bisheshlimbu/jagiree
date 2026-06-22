<?php
/**
 * LinkedIn-style compact job row for browse lists.
 * Expects $job array; optional $selectedJobId, $jobViewUrl.
 */
$job = $job ?? [];
$logo = $job['logo'] ?? substr($job['company'] ?? 'J', 0, 1);
$jobId = (int) ($job['id'] ?? 0);
$isSelected = $jobId > 0 && isset($selectedJobId) && $jobId === (int) $selectedJobId;
$jobViewUrl = $jobViewUrl ?? ($jobId > 0 ? '/seeker/jobs.php?id=' . $jobId : '#');
?>
<a href="<?= htmlspecialchars($jobViewUrl) ?>" class="job-row<?= $isSelected ? ' is-selected' : '' ?>">
    <div class="job-row__logo"><?= htmlspecialchars($logo) ?></div>
    <div class="job-row__body">
        <h3><?= htmlspecialchars($job['title'] ?? '') ?></h3>
        <p class="job-row__company"><?= htmlspecialchars($job['company'] ?? '') ?> &bull; <?= htmlspecialchars($job['location'] ?? '') ?></p>
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
