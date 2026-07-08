<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(ROLE_ADMIN);

$settings = getSiteSettings();
$errorGeneral = null;
$errorNotifications = null;
$errorIntegrations = null;

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

    if ($section === 'integrations') {
        $result = updateApifyIntegrationSettings([
            'apify_enabled' => !empty($_POST['apify_enabled']),
            'apify_show_external_jobs' => !empty($_POST['apify_show_external_jobs']),
            'apify_api_token' => trim($_POST['apify_api_token'] ?? ''),
            'clear_apify_api_token' => !empty($_POST['clear_apify_api_token']),
            'apify_actor_id' => trim($_POST['apify_actor_id'] ?? ''),
            'apify_job_keywords' => trim($_POST['apify_job_keywords'] ?? ''),
            'apify_job_location' => trim($_POST['apify_job_location'] ?? ''),
            'apify_linkedin_search_url' => trim($_POST['apify_linkedin_search_url'] ?? ''),
            'apify_job_limit' => (int) ($_POST['apify_job_limit'] ?? 50),
        ]);

        if ($result['success']) {
            flashSet('success', $result['message']);
            header('Location: /admin/settings.php');
            exit;
        }

        $errorIntegrations = $result['error'];
        $settings['apify_enabled'] = !empty($_POST['apify_enabled']) ? '1' : '0';
        $settings['apify_show_external_jobs'] = !empty($_POST['apify_show_external_jobs']) ? '1' : '0';
        $settings['apify_actor_id'] = trim($_POST['apify_actor_id'] ?? $settings['apify_actor_id']);
        $settings['apify_job_keywords'] = trim($_POST['apify_job_keywords'] ?? $settings['apify_job_keywords']);
        $settings['apify_job_location'] = trim($_POST['apify_job_location'] ?? $settings['apify_job_location']);
        $settings['apify_linkedin_search_url'] = trim($_POST['apify_linkedin_search_url'] ?? $settings['apify_linkedin_search_url']);
        $settings['apify_job_limit'] = (string) max(1, min(500, (int) ($_POST['apify_job_limit'] ?? 50)));
    }

    if ($section === 'integrations_test') {
        $result = testApifyConnection();
        recordApifyConnectionTest(
            $result['success'],
            $result['success'] ? $result['message'] : $result['error'],
            $result['response'] ?? null
        );
        if ($result['success']) {
            flashSet('success', $result['message']);
        } else {
            flashSet('error', $result['error']);
        }
        header('Location: /admin/settings.php#integrations');
        exit;
    }
}

$siteLogoUrl = siteLogoUrl();
$apifyTokenStored = trim($settings['apify_api_token']) !== '';
$apifyTokenMasked = $apifyTokenStored ? maskSecretSetting($settings['apify_api_token']) : '';
$apifyLastSync = trim($settings['apify_last_sync_at'] ?? '');
$apifyLastSyncCount = (int) ($settings['apify_last_sync_count'] ?? 0);
$apifyLastTestAt = trim($settings['apify_last_test_at'] ?? '');
$apifyLastTestStatus = trim($settings['apify_last_test_status'] ?? '');
$apifyLastTestMessage = trim($settings['apify_last_test_message'] ?? '');
$apifyTestResponse = formatApifyResponseForDisplay(getApifyResponseLog('test'));
$apifySyncResponse = formatApifyResponseForDisplay(getApifyResponseLog('sync'));

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

    <section class="panel panel--integration" id="integrations">
        <div class="panel-header panel-header--compact">
            <h2>Integrations</h2>
            <p>Connect Apify to import LinkedIn job listings for seekers. Employer-posted jobs are unchanged.</p>
        </div>

        <?php if ($errorIntegrations): ?>
            <div class="admin-flash admin-flash--error settings-flash" role="alert"><?= htmlspecialchars($errorIntegrations) ?></div>
        <?php endif; ?>

        <form class="settings-form settings-form--wide" method="post" autocomplete="off">
            <input type="hidden" name="section" value="integrations">

            <div class="integration-provider">
                <div class="integration-provider__head">
                    <div>
                        <h3>Apify · LinkedIn jobs</h3>
                        <p>Store credentials here instead of <code>.env</code>. Sync jobs from the admin panel (coming next).</p>
                    </div>
                    <div class="integration-status-group">
                        <span class="integration-status <?= apifyIntegrationConfigured() ? 'is-saved' : '' ?>">
                            <?= apifyIntegrationConfigured() ? 'Credentials saved' : 'Missing credentials' ?>
                        </span>
                        <?php if ($apifyLastTestStatus === 'success'): ?>
                            <span class="integration-status is-verified">Connection verified</span>
                        <?php elseif ($apifyLastTestStatus === 'error'): ?>
                            <span class="integration-status is-failed">Connection failed</span>
                        <?php endif; ?>
                    </div>
                </div>

                <label class="toggle-field">
                    <span class="toggle-field__text">
                        <strong>Enable Apify integration</strong>
                        <small>Allow LinkedIn jobs to be synced into Jagiree.</small>
                    </span>
                    <input type="checkbox" name="apify_enabled" value="1" class="toggle-field__input" <?= $settings['apify_enabled'] === '1' ? 'checked' : '' ?>>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <label class="toggle-field">
                    <span class="toggle-field__text">
                        <strong>Show external jobs to seekers</strong>
                        <small>Display synced LinkedIn listings in browse and recommendations.</small>
                    </span>
                    <input type="checkbox" name="apify_show_external_jobs" value="1" class="toggle-field__input" <?= $settings['apify_show_external_jobs'] === '1' ? 'checked' : '' ?>>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <label class="form-field">
                    <span>Apify API token <?= $apifyTokenStored ? '(saved)' : '*' ?></span>
                    <?php if ($apifyTokenStored): ?>
                        <p class="integration-token-saved">Current token: <code><?= htmlspecialchars($apifyTokenMasked) ?></code></p>
                    <?php endif; ?>
                    <input
                        type="password"
                        name="apify_api_token"
                        value=""
                        placeholder="<?= $apifyTokenStored ? 'Leave blank to keep current token' : 'apify_api_...' ?>"
                        autocomplete="new-password"
                    >
                    <?php if ($apifyTokenStored): ?>
                        <label class="integration-clear-token">
                            <input type="checkbox" name="clear_apify_api_token" value="1">
                            Remove saved token
                        </label>
                    <?php endif; ?>
                    <p class="form-hint">Create at Apify → Settings → Integrations. Never share this token publicly.</p>
                </label>

                <label class="form-field">
                    <span>LinkedIn actor ID *</span>
                    <input
                        type="text"
                        name="apify_actor_id"
                        value="<?= htmlspecialchars($settings['apify_actor_id']) ?>"
                        placeholder="username/linkedin-jobs-scraper"
                        maxlength="120"
                    >
                    <p class="form-hint">Actor ID from Apify Store, e.g. <code>curious_coder/linkedin-jobs-scraper</code>.</p>
                </label>

                <label class="form-field">
                    <span>LinkedIn search URL (recommended)</span>
                    <input
                        type="url"
                        name="apify_linkedin_search_url"
                        value="<?= htmlspecialchars($settings['apify_linkedin_search_url'] ?? '') ?>"
                        placeholder="https://www.linkedin.com/jobs/search/?keywords=UI%20designer&location=Nepal"
                    >
                    <p class="form-hint">Paste the full URL from a successful Apify test run. Sync uses this first; keywords/location are fallback only.</p>
                </label>

                <div class="integration-sync-fields">
                    <label class="form-field">
                        <span>Default search keywords *</span>
                        <input type="text" name="apify_job_keywords" value="<?= htmlspecialchars($settings['apify_job_keywords']) ?>" maxlength="200">
                    </label>

                    <label class="form-field">
                        <span>Default location</span>
                        <input type="text" name="apify_job_location" value="<?= htmlspecialchars($settings['apify_job_location']) ?>" maxlength="120">
                    </label>

                    <label class="form-field">
                        <span>Jobs per sync</span>
                        <input type="number" name="apify_job_limit" value="<?= (int) $settings['apify_job_limit'] ?>" min="1" max="500">
                    </label>
                </div>

                <?php if ($apifyLastTestMessage !== ''): ?>
                    <p class="integration-meta integration-meta--<?= $apifyLastTestStatus === 'success' ? 'success' : 'error' ?>">
                        Last connection test<?= $apifyLastTestAt !== '' ? ' (' . htmlspecialchars($apifyLastTestAt) . ')' : '' ?>:
                        <?= htmlspecialchars($apifyLastTestMessage) ?>
                    </p>
                <?php endif; ?>

                <?php if ($apifyLastSync !== ''): ?>
                    <p class="integration-meta">Last sync: <?= htmlspecialchars($apifyLastSync) ?> · <?= $apifyLastSyncCount ?> job(s) processed</p>
                <?php else: ?>
                    <p class="integration-meta">No sync has been run yet.</p>
                <?php endif; ?>
            </div>

            <div class="integration-actions">
                <button type="submit" class="btn-sm btn-sm--primary">Save integration</button>
            </div>
        </form>

        <form class="integration-test-form" method="post" action="/admin/api/sync-linkedin-jobs.php" id="apifySyncForm">
            <button type="submit" class="btn-sm btn-sm--primary" id="apifySyncBtn" <?= apifyIntegrationConfigured() && siteSettingEnabled('apify_enabled') ? '' : 'disabled' ?>>Sync LinkedIn jobs now</button>
        </form>

        <form class="integration-test-form" method="post" id="apifyTestForm">
            <input type="hidden" name="section" value="integrations_test">
            <button type="submit" class="btn-sm btn-sm--ghost" id="apifyTestBtn" <?= apifyIntegrationConfigured() ? '' : 'disabled' ?>>Test Apify connection</button>
        </form>
        <p class="integration-meta" id="apifyTestHint">Verifies your saved token with Apify.</p>

        <?php if ($apifyTestResponse !== '' || $apifySyncResponse !== ''): ?>
        <div class="integration-response-panel">
            <h3>API response log</h3>
            <p class="form-hint">Latest responses from Apify (tokens are never stored here).</p>

            <?php if ($apifyTestResponse !== ''): ?>
            <details class="integration-response-block" <?= $apifyLastTestStatus === 'success' ? 'open' : '' ?>>
                <summary>Connection test response</summary>
                <pre class="integration-response-code"><?= htmlspecialchars($apifyTestResponse) ?></pre>
            </details>
            <?php endif; ?>

            <?php if ($apifySyncResponse !== ''): ?>
            <details class="integration-response-block" open>
                <summary>Last sync response</summary>
                <pre class="integration-response-code"><?= htmlspecialchars($apifySyncResponse) ?></pre>
            </details>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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

document.getElementById('apifyTestForm')?.addEventListener('submit', function () {
  const btn = document.getElementById('apifyTestBtn');
  const hint = document.getElementById('apifyTestHint');
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Testing connection…';
  }
  if (hint) {
    hint.textContent = 'Contacting Apify…';
  }
});

document.getElementById('apifySyncForm')?.addEventListener('submit', function () {
  const btn = document.getElementById('apifySyncBtn');
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Syncing jobs…';
  }
});

if (window.location.hash === '#integrations') {
  document.getElementById('integrations')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
