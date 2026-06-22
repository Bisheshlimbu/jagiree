<?php
/**
 * Employer logo for job cards and detail panels.
 * Expects $job array with has_logo, logo_url, logo, and company keys.
 */
$job = $job ?? [];
$logoClass = $logoClass ?? 'job-card-logo';
$hasLogo = !empty($job['has_logo']) && !empty($job['logo_url']);
$companyName = $job['company'] ?? $job['company_name'] ?? 'Employer';
?>
<div class="<?= htmlspecialchars($logoClass) ?><?= $hasLogo ? ' job-card-logo--image' : '' ?>">
    <?php if ($hasLogo): ?>
    <img src="<?= htmlspecialchars($job['logo_url']) ?>" alt="<?= htmlspecialchars($companyName) ?>">
    <?php else: ?>
    <?= htmlspecialchars($job['logo'] ?? 'J') ?>
    <?php endif; ?>
</div>
