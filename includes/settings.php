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
    'apify_enabled' => '0',
    'apify_api_token' => '',
    'apify_actor_id' => '',
    'apify_job_keywords' => 'UI designer, software engineer',
    'apify_job_location' => 'Nepal',
    'apify_linkedin_search_url' => '',
    'apify_job_limit' => '50',
    'apify_show_external_jobs' => '1',
    'apify_last_sync_at' => '',
    'apify_last_sync_count' => '0',
    'apify_last_test_at' => '',
    'apify_last_test_status' => '',
    'apify_last_test_message' => '',
    'apify_last_test_response' => '',
    'apify_last_sync_response' => '',
    'nlp_service_url' => 'http://127.0.0.1:8001',
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

function maskSecretSetting(string $value, int $visibleTail = 4): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (strlen($value) <= $visibleTail) {
        return str_repeat('•', strlen($value));
    }

    return str_repeat('•', max(8, strlen($value) - $visibleTail)) . substr($value, -$visibleTail);
}

function apifyIntegrationConfigured(): bool
{
    return trim(getApifyApiToken()) !== '' && trim(getSiteSetting('apify_actor_id')) !== '';
}

function apifyConnectionVerified(): bool
{
    return getSiteSetting('apify_last_test_status') === 'success';
}

function recordApifyConnectionTest(bool $success, string $message, ?array $response = null): void
{
    saveSiteSetting('apify_last_test_at', date('Y-m-d H:i:s'));
    saveSiteSetting('apify_last_test_status', $success ? 'success' : 'error');
    saveSiteSetting('apify_last_test_message', $message);
    if ($response !== null) {
        saveApifyResponseLog('test', $response);
    }
}

function saveApifyResponseLog(string $context, array $payload): void
{
    $key = $context === 'test' ? 'apify_last_test_response' : 'apify_last_sync_response';
    saveSiteSetting(
        $key,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}'
    );
}

function getApifyResponseLog(string $context): ?array
{
    $key = $context === 'test' ? 'apify_last_test_response' : 'apify_last_sync_response';
    $raw = trim(getSiteSetting($key));
    if ($raw === '') {
        return null;
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : null;
}

function formatApifyResponseForDisplay(?array $payload): string
{
    if ($payload === null || $payload === []) {
        return '';
    }

    return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
}

function getApifyApiToken(): string
{
    $stored = trim(getSiteSetting('apify_api_token'));
    if ($stored !== '') {
        return $stored;
    }

    $envToken = trim((string) getenv('APIFY_API_TOKEN'));
    return $envToken !== '' ? $envToken : '';
}

function getApifyActorId(): string
{
    $stored = trim(getSiteSetting('apify_actor_id'));
    if ($stored !== '') {
        return $stored;
    }

    $envActor = trim((string) getenv('APIFY_LINKEDIN_ACTOR_ID'));
    return $envActor !== '' ? $envActor : '';
}

function updateApifyIntegrationSettings(array $data): array
{
    $enabled = !empty($data['apify_enabled']);
    $showExternalJobs = !empty($data['apify_show_external_jobs']);
    $actorId = trim($data['apify_actor_id'] ?? '');
    $keywords = trim($data['apify_job_keywords'] ?? '');
    $location = trim($data['apify_job_location'] ?? '');
    $searchUrl = trim($data['apify_linkedin_search_url'] ?? '');
    $limit = (int) ($data['apify_job_limit'] ?? 50);
    $newToken = trim($data['apify_api_token'] ?? '');
    $clearToken = !empty($data['clear_apify_api_token']);
    $currentToken = trim(getSiteSetting('apify_api_token'));

    if ($enabled) {
        if ($actorId === '') {
            return ['success' => false, 'error' => 'Apify actor ID is required when integration is enabled.'];
        }

        if (strlen($actorId) > 120) {
            return ['success' => false, 'error' => 'Apify actor ID must be 120 characters or fewer.'];
        }

        if ($keywords === '') {
            return ['success' => false, 'error' => 'Job keywords are required when integration is enabled.'];
        }

        if ($limit < 1 || $limit > 500) {
            return ['success' => false, 'error' => 'Job limit must be between 1 and 500.'];
        }

        $tokenToUse = $clearToken ? '' : ($newToken !== '' ? $newToken : $currentToken);
        if ($tokenToUse === '') {
            return ['success' => false, 'error' => 'Apify API token is required when integration is enabled.'];
        }
    }

    if ($clearToken) {
        saveSiteSetting('apify_api_token', '');
    } elseif ($newToken !== '') {
        if (!str_starts_with($newToken, 'apify_api_')) {
            return ['success' => false, 'error' => 'Apify API token should start with apify_api_.'];
        }
        saveSiteSetting('apify_api_token', $newToken);
    }

    saveSiteSetting('apify_enabled', $enabled ? '1' : '0');
    saveSiteSetting('apify_show_external_jobs', $showExternalJobs ? '1' : '0');
    saveSiteSetting('apify_actor_id', $actorId);
    saveSiteSetting('apify_job_keywords', $keywords);
    saveSiteSetting('apify_job_location', $location);
    saveSiteSetting('apify_linkedin_search_url', $searchUrl);
    saveSiteSetting('apify_job_limit', (string) max(1, min(500, $limit)));

    return ['success' => true, 'message' => 'Integration settings saved.'];
}

function testApifyConnection(): array
{
    if (!apifyIntegrationConfigured()) {
        return ['success' => false, 'error' => 'Save a valid Apify token and actor ID first.'];
    }

    $token = getApifyApiToken();
    $url = 'https://api.apify.com/v2/users/me?token=' . rawurlencode($token);

    $response = apifyHttpGet($url);
    if ($response === null) {
        return ['success' => false, 'error' => 'Could not reach Apify. Check your internet connection and PHP curl extension.'];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['success' => false, 'error' => 'Apify returned an unexpected response.'];
    }

    if (!empty($data['error'])) {
        $message = $data['error']['message'] ?? 'Apify rejected the API token.';

        return [
            'success' => false,
            'error' => $message,
            'response' => $data,
        ];
    }

    $username = $data['data']['username'] ?? $data['username'] ?? '';
    if ($username === '') {
        return ['success' => false, 'error' => 'Apify accepted the token but no username was returned.', 'response' => $data];
    }

    return [
        'success' => true,
        'message' => 'Connected to Apify as @' . $username . '.',
        'response' => $data,
    ];
}

function apifyHttpGet(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $response === '') {
            return null;
        }

        return (string) $response;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 20,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    return (string) $response;
}
