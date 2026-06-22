<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/users.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(ROLE_ADMIN);

$userId = (int) ($_GET['id'] ?? 0);
$user = fetchUserById($userId);

if (!$user) {
    flashSet('error', 'User not found.');
    header('Location: /admin/users.php');
    exit;
}

$error = null;
$form = [
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'username' => $user['username'],
    'role' => $user['role'],
    'company_name' => $user['company_name'] ?? '',
    'status' => $user['status'] ?? 'verified',
    'new_password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'role' => $_POST['role'] ?? $user['role'],
        'company_name' => trim($_POST['company_name'] ?? ''),
        'status' => $_POST['status'] ?? 'verified',
        'new_password' => $_POST['new_password'] ?? '',
    ];

    $admin = currentUser();
    $result = updateUserByAdmin($userId, array_merge($form, [
        'avatar_file' => $_FILES['avatar'] ?? null,
        'remove_avatar' => !empty($_POST['remove_avatar']),
    ]), (int) $admin['id']);

    if ($result['success']) {
        flashSet('success', $result['message']);
        header('Location: /admin/users.php');
        exit;
    }

    $error = $result['error'];
}

$pageTitle = 'Edit User — Jagiree Admin';
$activePage = 'users';
$pageHeading = 'Edit User';
$isAdminUser = $user['role'] === ROLE_ADMIN;
$avatarUrl = userAvatarUrl($user);
require_once __DIR__ . '/../../includes/admin/layout-start.php';
?>

<section class="panel panel--form">
    <div class="panel-header panel-header--compact">
        <div>
            <h2>Edit User</h2>
            <p>Update account details for <?= htmlspecialchars($user['full_name']) ?></p>
        </div>
        <a href="/admin/users.php" class="panel-link">Back to Users</a>
    </div>

    <?php if ($error): ?>
        <div class="admin-flash admin-flash--error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="settings-form settings-form--wide" method="post" enctype="multipart/form-data" novalidate>
        <div class="avatar-field">
            <span class="avatar-field__label">Profile image</span>
            <div class="avatar-field__row">
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="" class="avatar-field__preview<?= userHasAvatar($user) ? '' : ' user-avatar--placeholder' ?>" id="avatarPreview">
                <div class="avatar-field__controls">
                    <label class="btn-sm btn-sm--ghost avatar-field__upload">
                        Choose image
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" hidden id="avatarInput">
                    </label>
                    <?php if (!empty($user['avatar_path'])): ?>
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
            <span>Full name *</span>
            <input type="text" name="full_name" value="<?= htmlspecialchars($form['full_name']) ?>" required>
        </label>

        <label class="form-field">
            <span>Email *</span>
            <input type="email" name="email" value="<?= htmlspecialchars($form['email']) ?>" required>
        </label>

        <label class="form-field">
            <span>Username *</span>
            <input type="text" name="username" value="<?= htmlspecialchars($form['username']) ?>" required minlength="3" pattern="[a-zA-Z0-9_]+">
        </label>

        <?php if (!$isAdminUser): ?>
        <label class="form-field">
            <span>Role *</span>
            <select name="role" id="userRole">
                <option value="seeker" <?= $form['role'] === ROLE_SEEKER ? 'selected' : '' ?>>Seeker</option>
                <option value="employer" <?= $form['role'] === ROLE_EMPLOYER ? 'selected' : '' ?>>Employer</option>
            </select>
        </label>

        <label class="form-field" id="companyNameField" <?= $form['role'] !== ROLE_EMPLOYER ? 'hidden' : '' ?>>
            <span>Company name *</span>
            <input type="text" name="company_name" value="<?= htmlspecialchars($form['company_name']) ?>" <?= $form['role'] === ROLE_EMPLOYER ? 'required' : '' ?>>
        </label>
        <?php else: ?>
        <label class="form-field">
            <span>Role</span>
            <input type="text" value="Admin" disabled>
        </label>
        <?php endif; ?>

        <label class="form-field">
            <span>Status *</span>
            <select name="status">
                <option value="verified" <?= $form['status'] === 'verified' ? 'selected' : '' ?>>Verified</option>
                <option value="pending" <?= $form['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
            </select>
        </label>

        <label class="form-field">
            <span>New password</span>
            <input type="password" name="new_password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current password">
        </label>

        <div class="form-actions">
            <button type="submit" class="btn-sm btn-sm--primary">Save Changes</button>
            <a href="/admin/users.php" class="btn-sm btn-sm--ghost">Cancel</a>
        </div>
    </form>
</section>

<?php if (!$isAdminUser): ?>
<script>
function syncCompanyNameField() {
  const roleSelect = document.getElementById('userRole');
  const companyField = document.getElementById('companyNameField');
  const companyInput = companyField?.querySelector('input');
  const isEmployer = roleSelect?.value === 'employer';

  if (companyField) {
    companyField.hidden = !isEmployer;
  }
  if (companyInput) {
    companyInput.required = !!isEmployer;
    if (!isEmployer) {
      companyInput.value = '';
    }
  }
}

document.getElementById('userRole')?.addEventListener('change', syncCompanyNameField);
syncCompanyNameField();
</script>
<?php endif; ?>

<script>
document.getElementById('avatarInput')?.addEventListener('change', function () {
  const preview = document.getElementById('avatarPreview');
  const file = this.files?.[0];
  if (!preview || !file) {
    return;
  }
  preview.src = URL.createObjectURL(file);
});
</script>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
