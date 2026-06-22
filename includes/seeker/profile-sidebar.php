<?php
/**
 * LinkedIn-style seeker profile sidebar.
 * Expects $authUser and $profile arrays; optional $matchCount for stats.
 */
$authUser = $authUser ?? currentUser();
$profile = $profile ?? [];
$matchCount = $matchCount ?? null;
$appliedCount = $appliedCount ?? null;

$sidebarName = $profile['full_name'] ?? displayName($authUser);
$sidebarTitle = formatSeekerDisplayHeadline($profile);
$sidebarLocation = trim($profile['location'] ?? '') ?: 'Add your location';
$sidebarOpenToWork = !empty($profile['open_to_work']);
$userId = (int) ($authUser['id'] ?? 0);

$hasEducation = false;
$hasExperience = false;
if ($userId > 0 && function_exists('fetchSeekerEducation')) {
    $hasEducation = fetchSeekerEducation($userId) !== [];
    $hasExperience = fetchSeekerExperience($userId) !== [];
}

$strengthFields = [
    !empty(trim($profile['headline'] ?? '')),
    !empty(trim($profile['about'] ?? '')),
    !empty(trim($profile['location'] ?? '')),
    !empty($profile['skill_list'] ?? []),
    !empty($profile['cv_path'] ?? ''),
    $hasEducation,
    $hasExperience,
];
$profileStrength = (int) round((count(array_filter($strengthFields)) / count($strengthFields)) * 100);
?>
<div class="profile-card">
    <div class="profile-cover"></div>
    <div class="profile-body">
            <a href="/seeker/profile.php" class="profile-avatar-wrap">
                <img src="<?= htmlspecialchars(userAvatarUrl($authUser)) ?>" alt="<?= htmlspecialchars($sidebarName) ?>" class="<?= userHasAvatar($authUser) ? '' : 'user-avatar--placeholder' ?>">
            </a>
            <h2><a href="/seeker/profile.php"><?= htmlspecialchars($sidebarName) ?></a></h2>
            <?php if ($sidebarOpenToWork): ?>
            <span class="open-to-work-badge open-to-work-badge--sm">#OpenToWork</span>
            <?php endif; ?>
            <p class="profile-headline"><?= htmlspecialchars($sidebarTitle) ?></p>
            <p class="profile-location">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= htmlspecialchars($sidebarLocation) ?>
            </p>
        </div>
        <div class="profile-stats">
            <a href="/seeker/applications.php"><strong><?= $appliedCount !== null ? (int) $appliedCount : '—' ?></strong><span>Applied</span></a>
            <a href="/seeker/jobs.php?saved=1"><strong>0</strong><span>Saved</span></a>
            <a href="/seeker/jobs.php"><strong><?= $matchCount !== null ? (int) $matchCount : '—' ?></strong><span>Matches</span></a>
        </div>
        <div class="profile-strength">
            <div class="profile-strength-header">
                <span>Profile strength</span>
                <span><?= $profileStrength ?>%</span>
            </div>
            <div class="strength-bar"><div class="strength-fill" style="width:<?= $profileStrength ?>%"></div></div>
            <a href="/seeker/profile.php" class="profile-strength-link">Complete your profile →</a>
    </div>
</div>

<div class="widget-card widget-card--tip">
        <h3>Tip</h3>
        <p>Upload your latest CV to get better AI job matches — up to 45% more accurate.</p>
    <a href="/seeker/profile.php" class="widget-link">Upload CV</a>
</div>
