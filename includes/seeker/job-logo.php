<?php
/**
 * Employer logo for job cards and detail panels.
 * Shows the image only when the employer has an uploaded avatar.
 * Expects $job with has_logo, logo_url, and company keys.
 */
$job = $job ?? [];
$logoClass = $logoClass ?? 'job-card-logo';
$hasLogo = !empty($job['has_logo']) && !empty($job['logo_url']);

if (!$hasLogo) {
    return;
}

$companyName = $job['company'] ?? $job['company_name'] ?? 'Employer';
?>
<div class="<?= htmlspecialchars($logoClass) ?> job-card-logo--image">
    <img src="<?= htmlspecialchars($job['logo_url']) ?>" alt="<?= htmlspecialchars($companyName) ?>">
</div>
