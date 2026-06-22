<?php
/**
 * Reusable job card for seeker pages
 * Expects $job array; optional $selectedJobId, $jobCardCompact, $jobViewUrl
 */
$job = $job ?? [];
$jobId = (int) ($job['id'] ?? 0);
$isSelected = $jobId > 0 && isset($selectedJobId) && $jobId === (int) $selectedJobId;
$jobViewUrl = $jobViewUrl ?? ($jobId > 0 ? '/seeker/jobs.php?id=' . $jobId : '#');
$jobCardCompact = !empty($jobCardCompact);
?>
<article class="job-card<?= $jobCardCompact ? ' job-card--compact' : '' ?><?= $isSelected ? ' is-selected' : '' ?>">
    <div class="job-card-header">
        <?php require __DIR__ . '/job-logo.php'; ?>
        <div class="job-card-title-block">
            <h3><a href="<?= htmlspecialchars($jobViewUrl) ?>"><?= htmlspecialchars($job['title'] ?? '') ?></a></h3>
            <?php require __DIR__ . '/job-company-line.php'; ?>
        </div>
        <?php if (!$jobCardCompact): ?>
        <button type="button" class="job-save-btn <?= !empty($job['saved']) ? 'is-saved' : '' ?>" aria-label="Save job">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= !empty($job['saved']) ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        </button>
        <?php endif; ?>
    </div>

    <?php if (!$jobCardCompact): ?>
    <div class="job-card-meta">
        <?php if (!empty($job['salary'])): ?>
        <span><?= htmlspecialchars($job['salary']) ?></span>
        <?php endif; ?>
        <?php if (!empty($job['type'])): ?>
        <span><?= htmlspecialchars($job['type']) ?></span>
        <?php endif; ?>
        <?php if (!empty($job['posted'])): ?>
        <span class="job-posted"><?= htmlspecialchars($job['posted']) ?></span>
        <?php endif; ?>
    </div>

    <?php if (!empty($job['tags'])): ?>
    <div class="job-card-tags">
        <?php foreach ($job['tags'] as $tag): ?>
        <span class="job-tag"><?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="job-card-meta job-card-meta--compact">
        <?php if (!empty($job['type'])): ?>
        <span><?= htmlspecialchars($job['type']) ?></span>
        <?php endif; ?>
        <?php if (!empty($job['posted'])): ?>
        <span class="job-posted"><?= htmlspecialchars($job['posted']) ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="job-card-footer">
        <?php if (!empty($job['match'])): ?>
        <span class="match-pill match-pill--<?= ($job['match'] >= 90) ? 'high' : 'good' ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            <?= (int) $job['match'] ?>% Match
        </span>
        <?php endif; ?>
        <?php if (!$jobCardCompact): ?>
        <div class="job-card-actions">
            <button
                type="button"
                class="btn-apply<?= !empty($job['applied']) ? ' is-applied' : '' ?>"
                data-apply-job="<?= $jobId ?>"
                data-job-title="<?= htmlspecialchars($job['title'] ?? '', ENT_QUOTES) ?>"
                data-job-company="<?= htmlspecialchars($job['company'] ?? '', ENT_QUOTES) ?>"
                <?= !empty($job['applied']) ? 'disabled' : '' ?>
            ><?= !empty($job['applied']) ? 'Applied' : 'Easy Apply' ?></button>
            <a href="<?= htmlspecialchars($jobViewUrl) ?>" class="btn-view">View</a>
        </div>
        <?php endif; ?>
    </div>
</article>
