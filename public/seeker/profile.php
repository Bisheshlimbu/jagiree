<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/seeker/profile.php';

requireRole(ROLE_SEEKER);

$authUser = currentUser();
$userId = (int) $authUser['id'];
$error = null;
$success = isset($_GET['saved']) ? 'Profile saved successfully.' : null;
$allowedTabs = ['about', 'experience', 'education', 'skills'];
$activeTab = in_array($_GET['tab'] ?? '', $allowedTabs, true) ? $_GET['tab'] : 'about';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_profile';

    if ($action === 'save_profile') {
        $result = updateSeekerProfile($userId, [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'headline' => trim($_POST['headline'] ?? ''),
            'about' => trim($_POST['about'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'skills' => trim($_POST['skills'] ?? ''),
            'open_to_work' => !empty($_POST['open_to_work']),
            'avatar_file' => $_FILES['avatar'] ?? null,
            'remove_avatar' => !empty($_POST['remove_avatar']),
            'cv_file' => $_FILES['cv'] ?? null,
            'remove_cv' => !empty($_POST['remove_cv']),
        ]);

        if ($result['success']) {
            $returnTab = in_array($_POST['return_tab'] ?? '', $allowedTabs, true) ? $_POST['return_tab'] : 'about';
            header('Location: /seeker/profile.php?tab=' . urlencode($returnTab) . '&saved=1');
            exit;
        }

        $error = $result['error'];
        $activeTab = in_array($_POST['return_tab'] ?? '', $allowedTabs, true) ? $_POST['return_tab'] : 'about';
    } elseif ($action === 'add_education') {
        $result = addSeekerEducation($userId, $_POST);
        if ($result['success']) {
            header('Location: /seeker/profile.php?tab=education&saved=1');
            exit;
        }
        $error = $result['error'];
        $activeTab = 'education';
    } elseif ($action === 'add_experience') {
        $result = addSeekerExperience($userId, $_POST);
        if ($result['success']) {
            header('Location: /seeker/profile.php?tab=experience&saved=1');
            exit;
        }
        $error = $result['error'];
        $activeTab = 'experience';
    } elseif ($action === 'delete_education') {
        $result = deleteSeekerEducation($userId, (int) ($_POST['education_id'] ?? 0));
        if ($result['success']) {
            header('Location: /seeker/profile.php?tab=education&saved=1');
            exit;
        }
        $error = $result['error'];
        $activeTab = 'education';
    } elseif ($action === 'delete_experience') {
        $result = deleteSeekerExperience($userId, (int) ($_POST['experience_id'] ?? 0));
        if ($result['success']) {
            header('Location: /seeker/profile.php?tab=experience&saved=1');
            exit;
        }
        $error = $result['error'];
        $activeTab = 'experience';
    }
}

$profile = fetchSeekerProfile($userId);
$education = fetchSeekerEducation($userId);
$experience = fetchSeekerExperience($userId);
$cvMeta = seekerCvMeta($profile ?? []);

$pageTitle = 'My Profile — Jagiree';
$activePage = 'profile';
$seekerName = $profile['full_name'] ?? displayName($authUser);
$extraScripts = ['assets/js/profile-tabs.js'];
require_once __DIR__ . '/../../includes/seeker/layout-start.php';
?>

<div class="page-layout page-layout--single">
    <?php if ($success): ?>
    <div class="seeker-alert seeker-alert--success" role="alert"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="seeker-alert seeker-alert--error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-page-card">
        <div class="profile-page-cover"></div>
        <div class="profile-page-body">
            <div class="profile-avatar-edit">
                <img src="<?= htmlspecialchars(userAvatarUrl($profile ?? $authUser)) ?>" alt="<?= htmlspecialchars($seekerName) ?>" id="profileAvatarPreview" class="<?= userHasAvatar($profile ?? $authUser) ? '' : 'user-avatar--placeholder' ?>">
                <label class="profile-avatar-camera" for="profileAvatarInput" aria-label="Change profile photo">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </label>
                <input type="file" id="profileAvatarInput" name="avatar" form="profileForm" accept="image/jpeg,image/png,image/webp" hidden>
            </div>
            <?php if (!empty($profile['avatar_path'])): ?>
            <label class="profile-avatar-remove">
                <input type="checkbox" name="remove_avatar" form="profileForm" value="1">
                Remove photo
            </label>
            <?php endif; ?>
            <div class="profile-page-intro">
                <h1><?= htmlspecialchars($seekerName) ?></h1>
                <?php if (!empty($profile['open_to_work'])): ?>
                <span class="open-to-work-badge">#OpenToWork</span>
                <?php endif; ?>
            </div>
            <p class="profile-headline"><?= htmlspecialchars(formatSeekerDisplayHeadline($profile ?? [])) ?></p>
            <?php if (!empty($profile['skill_list'])): ?>
            <div class="profile-skill-tags">
                <?php foreach ($profile['skill_list'] as $skill): ?>
                <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($profile['location'])): ?>
            <p class="profile-location">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= htmlspecialchars($profile['location']) ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-tabs" data-active-tab="<?= htmlspecialchars($activeTab) ?>">
        <div class="profile-tabs__nav" role="tablist" aria-label="Profile sections">
            <button type="button" class="profile-tabs__btn <?= $activeTab === 'about' ? 'is-active' : '' ?>" role="tab" id="tab-btn-about" aria-selected="<?= $activeTab === 'about' ? 'true' : 'false' ?>" aria-controls="tab-panel-about" data-tab="about">
                About
            </button>
            <button type="button" class="profile-tabs__btn <?= $activeTab === 'experience' ? 'is-active' : '' ?>" role="tab" id="tab-btn-experience" aria-selected="<?= $activeTab === 'experience' ? 'true' : 'false' ?>" aria-controls="tab-panel-experience" data-tab="experience">
                Experience
                <?php if (count($experience) > 0): ?>
                <span class="profile-tabs__count"><?= count($experience) ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="profile-tabs__btn <?= $activeTab === 'education' ? 'is-active' : '' ?>" role="tab" id="tab-btn-education" aria-selected="<?= $activeTab === 'education' ? 'true' : 'false' ?>" aria-controls="tab-panel-education" data-tab="education">
                Education
                <?php if (count($education) > 0): ?>
                <span class="profile-tabs__count"><?= count($education) ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="profile-tabs__btn <?= $activeTab === 'skills' ? 'is-active' : '' ?>" role="tab" id="tab-btn-skills" aria-selected="<?= $activeTab === 'skills' ? 'true' : 'false' ?>" aria-controls="tab-panel-skills" data-tab="skills">
                Skills &amp; CV
            </button>
        </div>

        <form method="post" enctype="multipart/form-data" class="profile-form" id="profileForm" novalidate>
            <input type="hidden" name="action" value="save_profile">
            <input type="hidden" name="return_tab" id="profileReturnTab" value="<?= htmlspecialchars($activeTab) ?>">

            <section class="form-section profile-tab-panel <?= $activeTab === 'about' ? 'is-active' : '' ?>" role="tabpanel" id="tab-panel-about" aria-labelledby="tab-btn-about" data-tab-panel="about" <?= $activeTab !== 'about' ? 'hidden' : '' ?>>
                <h2>About you</h2>

                <div class="open-to-work-toggle">
                    <label class="toggle-field">
                        <input type="checkbox" name="open_to_work" value="1" <?= !empty($profile['open_to_work']) ? 'checked' : '' ?>>
                        <span class="toggle-switch" aria-hidden="true"></span>
                        <span class="toggle-field__text">
                            <strong>Open to work</strong>
                            <span>Show recruiters that you're open to new opportunities</span>
                        </span>
                    </label>
                </div>

                <label class="form-field">
                    <span>Full name</span>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" required>
                </label>
                <label class="form-field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
                </label>
                <label class="form-field">
                    <span>Phone</span>
                    <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="+977 9800000000">
                </label>
                <label class="form-field">
                    <span>Headline</span>
                    <input type="text" name="headline" value="<?= htmlspecialchars($profile['headline'] ?? '') ?>" placeholder="e.g. UX Designer · Product design">
                </label>
                <label class="form-field">
                    <span>Location</span>
                    <input type="text" name="location" value="<?= htmlspecialchars($profile['location'] ?? '') ?>" placeholder="Kathmandu, Nepal">
                </label>
                <label class="form-field">
                    <span>About</span>
                    <textarea name="about" placeholder="Tell employers about your background and goals..."><?= htmlspecialchars($profile['about'] ?? '') ?></textarea>
                </label>

                <div class="profile-tab-actions" data-save-tab="about">
                    <button type="submit" class="btn-primary">Save changes</button>
                </div>
            </section>

            <section class="form-section profile-tab-panel <?= $activeTab === 'skills' ? 'is-active' : '' ?>" role="tabpanel" id="tab-panel-skills" aria-labelledby="tab-btn-skills" data-tab-panel="skills" <?= $activeTab !== 'skills' ? 'hidden' : '' ?>>
                <h2>Skills &amp; CV</h2>

                <label class="form-field">
                    <span>Skills (comma separated)</span>
                    <input type="text" name="skills" value="<?= htmlspecialchars($profile['skills'] ?? '') ?>" placeholder="Figma, React, UX Research">
                    <small class="field-hint">These appear as tags on your profile and help with job matching.</small>
                </label>

                <?php if (!empty($profile['skill_list'])): ?>
                <div class="profile-skill-preview">
                    <span class="profile-skill-preview__label">Preview</span>
                    <div class="profile-skill-tags">
                        <?php foreach ($profile['skill_list'] as $skill): ?>
                        <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-field">
                    <span>CV / Resume</span>
                    <p class="form-hint">One CV for your profile, Easy Apply, and AI job recommendations.</p>
                    <?php if ($cvMeta['has_cv']): ?>
                    <p class="cv-current">
                        Current file:
                        <a href="<?= htmlspecialchars($cvMeta['path']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($cvMeta['filename']) ?></a>
                        <?php if ($cvMeta['updated_label']): ?>
                        <span class="cv-updated">· Updated <?= htmlspecialchars($cvMeta['updated_label']) ?></span>
                        <?php endif; ?>
                    </p>
                    <label class="form-check">
                        <input type="checkbox" name="remove_cv" value="1">
                        Remove current CV
                    </label>
                    <?php endif; ?>
                    <label class="upload-zone upload-zone--input">
                        <input type="file" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" hidden>
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <strong>Upload your CV or Resume</strong>
                        <span>PDF or DOCX — used by our AI to match you with jobs</span>
                    </label>
                </div>

                <div class="profile-tab-actions" data-save-tab="skills">
                    <button type="submit" class="btn-primary">Save changes</button>
                </div>
            </section>
        </form>

        <section class="form-section profile-tab-panel <?= $activeTab === 'experience' ? 'is-active' : '' ?>" role="tabpanel" id="tab-panel-experience" aria-labelledby="tab-btn-experience" data-tab-panel="experience" <?= $activeTab !== 'experience' ? 'hidden' : '' ?>>
            <div class="section-heading section-heading--flush">
                <h2>Experience</h2>
                <span class="section-count"><?= count($experience) ?> added</span>
            </div>

            <?php if ($experience === []): ?>
            <p class="section-empty">Add your work experience so employers can see your career path.</p>
            <?php else: ?>
            <ul class="profile-entry-list">
                <?php foreach ($experience as $entry): ?>
                <li class="profile-entry">
                    <div class="profile-entry__icon profile-entry__icon--experience">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                    </div>
                    <div class="profile-entry__body">
                        <strong><?= htmlspecialchars($entry['title']) ?></strong>
                        <?php if (!empty($entry['company'])): ?>
                        <span class="profile-entry__sub"><?= htmlspecialchars($entry['company']) ?></span>
                        <?php endif; ?>
                        <?php $years = formatExperienceYears($entry); ?>
                        <?php if ($years !== '' || !empty($entry['location'])): ?>
                        <span class="profile-entry__meta">
                            <?= htmlspecialchars($years) ?>
                            <?php if ($years !== '' && !empty($entry['location'])): ?> · <?php endif; ?>
                            <?= htmlspecialchars($entry['location'] ?? '') ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($entry['description'])): ?>
                        <p class="profile-entry__desc"><?= htmlspecialchars($entry['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <form method="post" class="profile-entry__actions">
                        <input type="hidden" name="action" value="delete_experience">
                        <input type="hidden" name="experience_id" value="<?= (int) $entry['id'] ?>">
                        <button type="submit" class="btn-text-danger" aria-label="Remove experience">Remove</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <form method="post" class="profile-add-form" novalidate>
                <input type="hidden" name="action" value="add_experience">
                <h3 class="profile-add-title">Add experience</h3>
                <div class="form-grid form-grid--2">
                    <label class="form-field">
                        <span>Title *</span>
                        <input type="text" name="title" placeholder="e.g. Senior UX Designer" required>
                    </label>
                    <label class="form-field">
                        <span>Company</span>
                        <input type="text" name="company" placeholder="Company name">
                    </label>
                </div>
                <label class="form-field">
                    <span>Location</span>
                    <input type="text" name="location" placeholder="City or Remote">
                </label>
                <div class="form-grid form-grid--2">
                    <label class="form-field">
                        <span>Start year</span>
                        <input type="text" name="start_year" placeholder="2020" maxlength="4" pattern="[0-9]{4}">
                    </label>
                    <label class="form-field" id="experienceEndYearField">
                        <span>End year</span>
                        <input type="text" name="end_year" id="experienceEndYear" placeholder="2024" maxlength="4" pattern="[0-9]{4}">
                    </label>
                </div>
                <label class="form-check form-check--inline">
                    <input type="checkbox" name="is_current" value="1" id="experienceIsCurrent">
                    I currently work here
                </label>
                <label class="form-field">
                    <span>Description</span>
                    <textarea name="description" placeholder="Key responsibilities and achievements..."></textarea>
                </label>
                <button type="submit" class="btn-secondary">Add experience</button>
            </form>
        </section>

        <section class="form-section profile-tab-panel <?= $activeTab === 'education' ? 'is-active' : '' ?>" role="tabpanel" id="tab-panel-education" aria-labelledby="tab-btn-education" data-tab-panel="education" <?= $activeTab !== 'education' ? 'hidden' : '' ?>>
            <div class="section-heading section-heading--flush">
                <h2>Education</h2>
                <span class="section-count"><?= count($education) ?> added</span>
            </div>

            <?php if ($education === []): ?>
            <p class="section-empty">Add your education to strengthen your profile.</p>
            <?php else: ?>
            <ul class="profile-entry-list">
                <?php foreach ($education as $entry): ?>
                <li class="profile-entry">
                    <div class="profile-entry__icon profile-entry__icon--education">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.1 2.7 2 6 2s6-.9 6-2v-5"/></svg>
                    </div>
                    <div class="profile-entry__body">
                        <strong><?= htmlspecialchars($entry['school']) ?></strong>
                        <?php
                        $degreeLine = trim(($entry['degree'] ?? '') . (!empty($entry['field_of_study']) ? ', ' . $entry['field_of_study'] : ''));
                        ?>
                        <?php if ($degreeLine !== ''): ?>
                        <span class="profile-entry__sub"><?= htmlspecialchars($degreeLine) ?></span>
                        <?php endif; ?>
                        <?php $years = formatEducationYears($entry['start_year'], $entry['end_year']); ?>
                        <?php if ($years !== ''): ?>
                        <span class="profile-entry__meta"><?= htmlspecialchars($years) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($entry['description'])): ?>
                        <p class="profile-entry__desc"><?= htmlspecialchars($entry['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <form method="post" class="profile-entry__actions">
                        <input type="hidden" name="action" value="delete_education">
                        <input type="hidden" name="education_id" value="<?= (int) $entry['id'] ?>">
                        <button type="submit" class="btn-text-danger" aria-label="Remove education">Remove</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <form method="post" class="profile-add-form" novalidate>
                <input type="hidden" name="action" value="add_education">
                <h3 class="profile-add-title">Add education</h3>
                <label class="form-field">
                    <span>School / University *</span>
                    <input type="text" name="school" placeholder="e.g. Tribhuvan University" required>
                </label>
                <div class="form-grid form-grid--2">
                    <label class="form-field">
                        <span>Degree</span>
                        <input type="text" name="degree" placeholder="e.g. Bachelor's">
                    </label>
                    <label class="form-field">
                        <span>Field of study</span>
                        <input type="text" name="field_of_study" placeholder="e.g. Computer Science">
                    </label>
                </div>
                <div class="form-grid form-grid--2">
                    <label class="form-field">
                        <span>Start year</span>
                        <input type="text" name="start_year" placeholder="2018" maxlength="4" pattern="[0-9]{4}">
                    </label>
                    <label class="form-field">
                        <span>End year</span>
                        <input type="text" name="end_year" placeholder="2022" maxlength="4" pattern="[0-9]{4}">
                    </label>
                </div>
                <label class="form-field">
                    <span>Description</span>
                    <textarea name="description" placeholder="Activities, honors, or relevant coursework..."></textarea>
                </label>
                <button type="submit" class="btn-secondary">Add education</button>
            </form>
        </section>
    </div>
</div>

<script>
document.getElementById('experienceIsCurrent')?.addEventListener('change', function () {
  const endField = document.getElementById('experienceEndYearField');
  const endInput = document.getElementById('experienceEndYear');
  if (!endField || !endInput) return;
  endField.hidden = this.checked;
  if (this.checked) endInput.value = '';
});

document.getElementById('profileAvatarInput')?.addEventListener('change', function () {
  const preview = document.getElementById('profileAvatarPreview');
  const file = this.files?.[0];
  if (!preview || !file) return;
  preview.src = URL.createObjectURL(file);
  preview.classList.remove('user-avatar--placeholder');
});
</script>

<?php require_once __DIR__ . '/../../includes/seeker/layout-end.php'; ?>
