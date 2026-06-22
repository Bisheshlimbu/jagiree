<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/users.php';

requireRole(ROLE_EMPLOYER);

$authUser = currentUser();
$profile = fetchEmployerProfile((int) $authUser['id']);
$industries = employerIndustries();
$error = null;
$saved = isset($_GET['saved']);
$avatarUrl = userAvatarUrl($profile ?? $authUser);

$form = [
    'company_name' => $profile['company_name'] ?? '',
    'email' => $profile['email'] ?? '',
    'industry' => $profile['industry'] ?? '',
    'company_about' => $profile['company_about'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'company_name' => trim($_POST['company_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'industry' => trim($_POST['industry'] ?? ''),
        'company_about' => trim($_POST['company_about'] ?? ''),
    ];

    $result = updateEmployerProfile((int) $authUser['id'], array_merge($form, [
        'avatar_file' => $_FILES['avatar'] ?? null,
        'remove_avatar' => !empty($_POST['remove_avatar']),
    ]));

    if ($result['success']) {
        header('Location: /employer/settings.php?saved=1');
        exit;
    }

    $error = $result['error'];
}

$pageTitle = 'Settings — Jagiree Employer';
$activePage = 'settings';
$activeTopNav = 'dashboard';
require_once __DIR__ . '/../../includes/employer/layout-start.php';
?>

<div class="page-header">
    <div>
        <h1>Settings</h1>
        <p>Manage your company profile and hiring preferences.</p>
    </div>
</div>

<?php if ($saved): ?>
    <div class="employer-alert employer-alert--success" role="alert">Settings saved successfully.</div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="employer-alert employer-alert--error" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<section class="panel form-panel">
    <form method="post" enctype="multipart/form-data" novalidate>
        <div class="avatar-field">
            <span class="avatar-field__label">Profile image</span>
            <div class="avatar-field__row">
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="" class="avatar-field__preview<?= userHasAvatar($profile ?? $authUser) ? '' : ' user-avatar--placeholder' ?>" id="avatarPreview">
                <div class="avatar-field__controls">
                    <label class="btn-upload avatar-field__upload">
                        Choose image
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" hidden id="avatarInput">
                    </label>
                    <?php if (!empty($profile['avatar_path'])): ?>
                    <label class="avatar-field__remove">
                        <input type="checkbox" name="remove_avatar" value="1">
                        Remove current image
                    </label>
                    <?php endif; ?>
                    <p class="avatar-field__hint">JPG, PNG, or WEBP. Max 2MB.</p>
                </div>
            </div>
        </div>

        <label class="form-field">
            <span>Company Name</span>
            <input type="text" name="company_name" value="<?= htmlspecialchars($form['company_name']) ?>" placeholder="TechFlow Inc." required>
        </label>
        <label class="form-field">
            <span>Company Email</span>
            <input type="email" name="email" value="<?= htmlspecialchars($form['email']) ?>" placeholder="hr@techflow.com" required>
        </label>
        <label class="form-field">
            <span>Industry</span>
            <select name="industry">
                <option value="">Select industry</option>
                <?php foreach ($industries as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>" <?= $form['industry'] === $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="form-field">
            <span>About Company</span>
            <textarea name="company_about" placeholder="Leading tech company building innovative products."><?= htmlspecialchars($form['company_about']) ?></textarea>
        </label>
        <button type="submit" class="btn-primary">Save Changes</button>
    </form>
</section>

<script>
document.getElementById('avatarInput')?.addEventListener('change', function () {
  const preview = document.getElementById('avatarPreview');
  const file = this.files?.[0];
  if (!preview || !file) {
    return;
  }
  preview.src = URL.createObjectURL(file);
  preview.classList.remove('user-avatar--placeholder');
});
</script>

<?php require_once __DIR__ . '/../../includes/employer/layout-end.php'; ?>
