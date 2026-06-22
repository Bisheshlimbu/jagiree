<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(ROLE_ADMIN);

$settings = getSiteSettings();
$errorGeneral = null;
$errorNotifications = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';

    if ($section === 'general') {
        $result = updateGeneralSiteSettings([
            'site_name' => trim($_POST['site_name'] ?? ''),
            'site_logo_file' => $_FILES['site_logo'] ?? null,
            'remove_site_logo' => !empty($_POST['remove_site_logo']),
        ]);

        if ($result['success']) {
            flashSet('success', $result['message']);
            header('Location: /admin/settings.php');
            exit;
        }

        $errorGeneral = $result['error'];
        $settings['site_name'] = trim($_POST['site_name'] ?? $settings['site_name']);
    }

    if ($section === 'notifications') {
        $result = updateNotificationSiteSettings([
            'notification_email' => trim($_POST['notification_email'] ?? ''),
            'notify_new_registrations' => !empty($_POST['notify_new_registrations']),
            'notify_new_jobs' => !empty($_POST['notify_new_jobs']),
            'notify_new_applications' => !empty($_POST['notify_new_applications']),
            'notify_pending_jobs' => !empty($_POST['notify_pending_jobs']),
        ]);

        if ($result['success']) {
            flashSet('success', $result['message']);
            header('Location: /admin/settings.php');
            exit;
        }

        $errorNotifications = $result['error'];
        $settings['notification_email'] = trim($_POST['notification_email'] ?? $settings['notification_email']);
        $settings['notify_new_registrations'] = !empty($_POST['notify_new_registrations']) ? '1' : '0';
        $settings['notify_new_jobs'] = !empty($_POST['notify_new_jobs']) ? '1' : '0';
        $settings['notify_new_applications'] = !empty($_POST['notify_new_applications']) ? '1' : '0';
        $settings['notify_pending_jobs'] = !empty($_POST['notify_pending_jobs']) ? '1' : '0';
    }
}

$siteLogoUrl = siteLogoUrl();

$pageTitle = 'Settings — ' . siteName() . ' Admin';
$activePage = 'settings';
$pageHeading = 'Settings';
require_once __DIR__ . '/../../includes/admin/layout-start.php';
?>

<?php renderAdminFlash(); ?>

<div class="settings-grid">
    <section class="panel">
        <div class="panel-header panel-header--compact">
            <h2>General Settings</h2>
            <p>Set the site name and logo shown across the platform.</p>
        </div>

        <?php if ($errorGeneral): ?>
            <div class="admin-flash admin-flash--error settings-flash" role="alert"><?= htmlspecialchars($errorGeneral) ?></div>
        <?php endif; ?>

        <form class="settings-form settings-form--wide" method="post" enctype="multipart/form-data">
            <input type="hidden" name="section" value="general">

            <label class="form-field">
                <span>Site name *</span>
                <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" required maxlength="80">
            </label>

            <div class="logo-field">
                <span class="logo-field__label">Site logo</span>
                <div class="logo-field__row">
                    <div class="logo-field__preview-wrap">
                        <?php if ($siteLogoUrl): ?>
                            <img src="<?= htmlspecialchars($siteLogoUrl) ?>" alt="" class="logo-field__preview" id="siteLogoPreview">
                        <?php else: ?>
                            <div class="logo-field__placeholder" id="siteLogoPreview">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2 2 7l10 5 10-5-10-5z"/>
                                    <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="logo-field__controls">
                        <label class="btn-sm btn-sm--ghost logo-field__upload">
                            Choose logo
                            <input type="file" name="site_logo" accept="image/jpeg,image/png,image/webp" hidden id="siteLogoInput">
                        </label>
                        <?php if ($siteLogoUrl): ?>
                        <label class="logo-field__remove">
                            <input type="checkbox" name="remove_site_logo" value="1">
                            Remove current logo
                        </label>
                        <?php endif; ?>
                        <p class="logo-field__hint">JPG, PNG, or WEBP. Max 2MB. Recommended height: 40px.</p>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-sm btn-sm--primary">Save Changes</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header panel-header--compact">
            <h2>Notification Settings</h2>
            <p>Choose which admin alerts are sent and where they go.</p>
        </div>

        <?php if ($errorNotifications): ?>
            <div class="admin-flash admin-flash--error settings-flash" role="alert"><?= htmlspecialchars($errorNotifications) ?></div>
        <?php endif; ?>

        <form class="settings-form settings-form--wide" method="post">
            <input type="hidden" name="section" value="notifications">

            <label class="form-field">
                <span>Notification email *</span>
                <input type="email" name="notification_email" value="<?= htmlspecialchars($settings['notification_email']) ?>" required>
            </label>

            <div class="notification-toggles">
                <label class="toggle-field">
                    <span class="toggle-field__text">
                        <strong>New user registrations</strong>
                        <small>Alert when a seeker or employer creates an account.</small>
                    </span>
                    <input type="checkbox" name="notify_new_registrations" value="1" class="toggle-field__input" <?= $settings['notify_new_registrations'] === '1' ? 'checked' : '' ?>>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <label class="toggle-field">
                    <span class="toggle-field__text">
                        <strong>New job postings</strong>
                        <small>Alert when an employer publishes a new job.</small>
                    </span>
                    <input type="checkbox" name="notify_new_jobs" value="1" class="toggle-field__input" <?= $settings['notify_new_jobs'] === '1' ? 'checked' : '' ?>>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <label class="toggle-field">
                    <span class="toggle-field__text">
                        <strong>New job applications</strong>
                        <small>Alert when a seeker applies to a job listing.</small>
                    </span>
                    <input type="checkbox" name="notify_new_applications" value="1" class="toggle-field__input" <?= $settings['notify_new_applications'] === '1' ? 'checked' : '' ?>>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <label class="toggle-field">
                    <span class="toggle-field__text">
                        <strong>Pending job approvals</strong>
                        <small>Alert when jobs are waiting for admin review.</small>
                    </span>
                    <input type="checkbox" name="notify_pending_jobs" value="1" class="toggle-field__input" <?= $settings['notify_pending_jobs'] === '1' ? 'checked' : '' ?>>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>
            </div>

            <button type="submit" class="btn-sm btn-sm--primary">Save Changes</button>
        </form>
    </section>
</div>

<script>
document.getElementById('siteLogoInput')?.addEventListener('change', function () {
  const file = this.files?.[0];
  if (!file) {
    return;
  }

  let preview = document.getElementById('siteLogoPreview');
  if (preview?.tagName === 'DIV') {
    const img = document.createElement('img');
    img.id = 'siteLogoPreview';
    img.className = 'logo-field__preview';
    img.alt = '';
    preview.replaceWith(img);
    preview = img;
  }

  if (preview) {
    preview.src = URL.createObjectURL(file);
  }
});
</script>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
