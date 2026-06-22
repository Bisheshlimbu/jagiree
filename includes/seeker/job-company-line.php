<?php
/**
 * Company name and location line for job cards.
 * Expects $job array with company, location, and optional employer_url.
 */
$job = $job ?? [];
$companyName = $job['company'] ?? '';
$location = $job['location'] ?? '';
?>
<p class="job-card-company">
    <?php if (!empty($job['employer_url'])): ?>
    <a href="<?= htmlspecialchars($job['employer_url']) ?>" class="job-card-company-link"><?= htmlspecialchars($companyName) ?></a>
    <?php else: ?>
    <?= htmlspecialchars($companyName) ?>
    <?php endif; ?>
    &bull; <?= htmlspecialchars($location) ?>
</p>
