<?php
/**
 * Job detail panel for split browse layout.
 * Expects $selectedJob and $selectedJobRow arrays.
 */
$selectedJob = $selectedJob ?? [];
$selectedJobRow = $selectedJobRow ?? [];
?>
<article class="job-detail-panel">
    <div class="job-detail-panel__header">
        <div class="job-detail-header">
            <?php
            $logoClass = 'job-card-logo job-card-logo--lg';
            require __DIR__ . '/job-logo.php';
            unset($logoClass);
            ?>
            <div>
                <h2><?= htmlspecialchars($selectedJob['title'] ?? '') ?></h2>
                <?php if (!empty($selectedJob['is_external'])): ?>
                    <span class="job-source-badge job-source-badge--detail"><?= htmlspecialchars($selectedJob['source_label'] ?? 'LinkedIn') ?></span>
                <?php endif; ?>
                <p class="job-detail-company">
                    <?php if (!empty($selectedJob['employer_url'])): ?>
                    <a href="<?= htmlspecialchars($selectedJob['employer_url']) ?>" class="job-card-company-link"><?= htmlspecialchars($selectedJob['company'] ?? '') ?></a>
                    <?php else: ?>
                    <?= htmlspecialchars($selectedJob['company'] ?? '') ?>
                    <?php endif; ?>
                    &bull; <?= htmlspecialchars($selectedJob['location'] ?? '') ?>
                </p>
                <div class="job-card-meta job-card-meta--detail">
                    <span><?= htmlspecialchars($selectedJob['salary'] ?? 'Negotiable') ?></span>
                    <span><?= htmlspecialchars($selectedJob['type'] ?? '') ?></span>
                    <span class="job-posted"><?= htmlspecialchars($selectedJob['posted'] ?? '') ?></span>
                </div>
            </div>
        </div>
        <button type="button" class="job-detail-close" data-close-detail aria-label="Close job details">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <?php if (!empty($selectedJob['tags'])): ?>
    <div class="job-card-tags">
        <?php foreach ($selectedJob['tags'] as $tag): ?>
        <span class="job-tag"><?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="job-detail-actions">
        <?php if (!empty($selectedJob['match'])): ?>
        <span class="match-pill match-pill--<?= ($selectedJob['match'] >= 90) ? 'high' : 'good' ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            <?= (int) $selectedJob['match'] ?>% Match
        </span>
        <?php endif; ?>
        <?php if (!empty($selectedJob['is_external']) && !empty($selectedJob['external_url'])): ?>
            <a href="<?= htmlspecialchars($selectedJob['external_url']) ?>" class="btn-apply btn-apply--external" target="_blank" rel="noopener">Apply on LinkedIn</a>
            <p class="job-external-note">You'll complete your application on LinkedIn. Jagiree doesn't receive it.</p>
        <?php else: ?>
            <button
                type="button"
                class="btn-apply<?= !empty($selectedJob['applied']) ? ' is-applied' : '' ?>"
                data-apply-job="<?= (int) ($selectedJob['id'] ?? 0) ?>"
                data-job-title="<?= htmlspecialchars($selectedJob['title'] ?? '', ENT_QUOTES) ?>"
                data-job-company="<?= htmlspecialchars($selectedJob['company'] ?? '', ENT_QUOTES) ?>"
                <?= !empty($selectedJob['applied']) ? 'disabled' : '' ?>
            ><?= !empty($selectedJob['applied']) ? 'Applied' : 'Easy Apply' ?></button>
        <?php endif; ?>
        <button type="button" class="job-save-btn" aria-label="Save job">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        </button>
    </div>

    <section class="job-detail-section">
        <h3>Job description</h3>
        <div class="job-detail-description"><?= nl2br(htmlspecialchars($selectedJobRow['description'] ?? '')) ?></div>
    </section>
</article>
