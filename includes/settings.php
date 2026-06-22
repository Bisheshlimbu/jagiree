<?php
/**
 * Site-wide settings stored in SQLite.
 */

require_once __DIR__ . '/database.php';

const SITE_SETTING_DEFAULTS = [
    'site_name' => 'Jagiree',
    'site_logo_path' => '',
    'notification_email' => 'admin@jagiree.com',
    'notify_new_registrations' => '1',
    'notify_new_jobs' => '1',
    'notify_new_applications' => '1',
    'notify_pending_jobs' => '1',
];

function ensureSiteSettingsSchema(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS site_settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT NOT NULL DEFAULT \'\'
        )'
    );

    $insert = db()->prepare(
        'INSERT OR IGNORE INTO site_settings (setting_key, setting_value) VALUES (:key, :value)'
    );

    foreach (SITE_SETTING_DEFAULTS as $key => $value) {
        $insert->execute(['key' => $key, 'value' => $value]);
    }

    $checked = true;
}

function clearSiteSettingsCache(): void
{
    $GLOBALS['_site_settings_cache'] = null;
}

function getSiteSettings(): array
{
    if (isset($GLOBALS['_site_settings_cache']) && is_array($GLOBALS['_site_settings_cache'])) {
        return $GLOBALS['_site_settings_cache'];
    }

    ensureSiteSettingsSchema();

    $rows = db()->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
    $settings = SITE_SETTING_DEFAULTS;

    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $GLOBALS['_site_settings_cache'] = $settings;

    return $settings;
}

function getSiteSetting(string $key, ?string $default = null): string
{
    $settings = getSiteSettings();
    if (array_key_exists($key, $settings)) {
        return (string) $settings[$key];
    }

    return $default ?? (SITE_SETTING_DEFAULTS[$key] ?? '');
}

function siteName(): string
{
    $name = trim(getSiteSetting('site_name', APP_NAME));

    return $name !== '' ? $name : APP_NAME;
}

function siteLogoUrl(): ?string
{
    $path = trim(getSiteSetting('site_logo_path'));
    if ($path !== '' && str_starts_with($path, '/assets/uploads/site/')) {
        return $path;
    }

    return null;
}

function saveSiteSetting(string $key, string $value): void
{
    ensureSiteSettingsSchema();

    $stmt = db()->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
         ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value'
    );
    $stmt->execute(['key' => $key, 'value' => $value]);
    clearSiteSettingsCache();
}

function removeSiteLogoFile(?string $path): void
{
    if (!$path || !str_starts_with($path, '/assets/uploads/site/')) {
        return;
    }

    $fullPath = dirname(__DIR__) . '/public' . $path;
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function processSiteLogoUpdate(?array $file, bool $remove, ?string $currentPath): array
{
    if ($remove) {
        removeSiteLogoFile($currentPath);
        return ['success' => true, 'path' => ''];
    }

    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => $currentPath ?? ''];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Could not upload site logo. Please try again.'];
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Site logo must be 2MB or smaller.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        return ['success' => false, 'error' => 'Site logo must be JPG, PNG, or WEBP.'];
    }

    $uploadDir = dirname(__DIR__) . '/public/assets/uploads/site';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    removeSiteLogoFile($currentPath);

    $filename = 'logo-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Could not save site logo.'];
    }

    return ['success' => true, 'path' => '/assets/uploads/site/' . $filename];
}

function updateGeneralSiteSettings(array $data): array
{
    $siteName = trim($data['site_name'] ?? '');
    $logoFile = $data['site_logo_file'] ?? null;
    $removeLogo = !empty($data['remove_site_logo']);
    $currentLogo = getSiteSetting('site_logo_path');

    if ($siteName === '') {
        return ['success' => false, 'error' => 'Site name is required.'];
    }

    if (strlen($siteName) > 80) {
        return ['success' => false, 'error' => 'Site name must be 80 characters or fewer.'];
    }

    $logoResult = processSiteLogoUpdate(
        is_array($logoFile) ? $logoFile : null,
        $removeLogo,
        $currentLogo !== '' ? $currentLogo : null
    );

    if (!$logoResult['success']) {
        return $logoResult;
    }

    saveSiteSetting('site_name', $siteName);
    saveSiteSetting('site_logo_path', $logoResult['path'] ?? '');

    return ['success' => true, 'message' => 'General settings saved.'];
}

function updateNotificationSiteSettings(array $data): array
{
    $email = trim($data['notification_email'] ?? '');

    if ($email === '') {
        return ['success' => false, 'error' => 'Notification email is required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid notification email.'];
    }

    saveSiteSetting('notification_email', $email);
    saveSiteSetting('notify_new_registrations', !empty($data['notify_new_registrations']) ? '1' : '0');
    saveSiteSetting('notify_new_jobs', !empty($data['notify_new_jobs']) ? '1' : '0');
    saveSiteSetting('notify_new_applications', !empty($data['notify_new_applications']) ? '1' : '0');
    saveSiteSetting('notify_pending_jobs', !empty($data['notify_pending_jobs']) ? '1' : '0');

    return ['success' => true, 'message' => 'Notification settings saved.'];
}

function siteSettingEnabled(string $key): bool
{
    return getSiteSetting($key) === '1';
}
