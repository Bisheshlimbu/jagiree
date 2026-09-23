<?php
/**
 * Sync external job listings (LinkedIn via Apify) into the jobs table.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/jobs.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/helpers.php';

function apifyHttpRequest(string $method, string $url, ?array $body = null): ?array
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_PROXY => '',
        CURLOPT_NOPROXY => '*',
    ];

    if ($body !== null) {
        $payload = json_encode($body, JSON_UNESCAPED_SLASHES);
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_HTTPHEADER] = $headers;
        $options[CURLOPT_POSTFIELDS] = $payload;
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $response === '') {
        return null;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['_http_status' => $status, '_raw' => $response];
    }

    $decoded['_http_status'] = $status;

    return $decoded;
}

function apifyActorApiId(string $actorId): string
{
    $actorId = trim($actorId);
    if (str_contains($actorId, '/')) {
        return str_replace('/', '~', $actorId);
    }

    return $actorId;
}

function buildLinkedInJobsSearchUrl(string $keywords, string $location = ''): string
{
    $params = [];
    $keywords = trim($keywords);
    $location = trim($location);

    if ($keywords !== '') {
        $params['keywords'] = $keywords;
    }
    if ($location !== '') {
        $params['location'] = $location;
    }

    if ($params === []) {
        return '';
    }

    return 'https://www.linkedin.com/jobs/search/?' . http_build_query($params);
}

/**
 * Build actor input for the configured LinkedIn jobs scraper.
 *
 * Supports common Store actors:
 * - curious_coder/linkedin-jobs-scraper (urls + limitPerSource)
 * - crawlworks/linkedin-jobs-scraper (searchUrls + jobsToFetch)
 *
 * @return array{0: ?string, 1: array<string, mixed>}
 */
function buildApifyLinkedInActorInput(): array
{
    $limit = max(10, min(500, (int) getSiteSetting('apify_job_limit', '50')));
    $customUrl = trim(getSiteSetting('apify_linkedin_search_url'));
    $keywords = trim(getSiteSetting('apify_job_keywords'));
    $location = trim(getSiteSetting('apify_job_location'));
    $actorId = mb_strtolower(trim(getApifyActorId()));

    $searchUrl = '';
    if ($customUrl !== '' && str_starts_with($customUrl, 'https://www.linkedin.com/jobs/search')) {
        $searchUrl = $customUrl;
    } else {
        $searchUrl = buildLinkedInJobsSearchUrl($keywords, $location);
    }

    if ($searchUrl === '' && $keywords === '') {
        return [null, []];
    }

    // CrawlWorks actor: required field is jobsToFetch (Apify may report it as input.jobs.jobsToFetch).
    if (str_contains($actorId, 'crawlworks')) {
        $input = [
            'jobsToFetch' => $limit,
            'enrichCompanyDetails' => false,
        ];
        if ($searchUrl !== '') {
            $input['searchUrls'] = [$searchUrl];
        }
        if ($keywords !== '') {
            $input['query'] = $keywords;
        }
        if ($location !== '') {
            $input['location'] = $location;
        }

        return [$searchUrl !== '' ? $searchUrl : $keywords, $input];
    }

    // Default: curious_coder/linkedin-jobs-scraper (+ compatible aliases).
    if ($searchUrl === '') {
        return [null, []];
    }

    $input = [
        'urls' => [$searchUrl],
        'searchUrls' => [$searchUrl],
        'scrapeCompany' => true,
        // Newer curious_coder builds
        'limitPerSource' => $limit,
        // Older curious_coder / search scrapers
        'count' => $limit,
        // CrawlWorks-compatible field (safe extra; ignored by curious_coder)
        'jobsToFetch' => $limit,
        'autoConvertToAiSearch' => true,
        'splitByLocation' => false,
    ];

    if ($keywords !== '') {
        $input['keywords'] = $keywords;
        $input['query'] = $keywords;
    }
    if ($location !== '') {
        $input['location'] = $location;
    }

    return [$searchUrl, $input];
}

function resolveLinkedInSearchUrl(): string
{
    [$searchUrl] = buildApifyLinkedInActorInput();

    return $searchUrl ?? '';
}

function externalJobPick(array $item, array $keys, string $default = ''): string
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $item) || $item[$key] === null || $item[$key] === '') {
            continue;
        }

        if (is_string($item[$key])) {
            $value = trim($item[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        if (is_array($item[$key])) {
            $parts = array_values(array_filter(array_map(static function ($part) {
                return is_scalar($part) ? trim((string) $part) : '';
            }, $item[$key])));
            if ($parts !== []) {
                return implode(' - ', $parts);
            }
        }
    }

    return $default;
}

function normalizeLinkedInJobItem(array $item): ?array
{
    $title = externalJobPick($item, ['title', 'jobTitle', 'position', 'job_title']);
    $company = externalJobPick($item, ['companyName', 'company', 'company_name']);
    $location = externalJobPick($item, ['location', 'jobLocation', 'place', 'formattedLocation']);
    $description = externalJobPick($item, ['descriptionText', 'description', 'jobDescription', 'job_description']);
    $externalUrl = externalJobPick($item, ['link', 'jobUrl', 'url', 'applyUrl', 'apply_url', 'jobLink', 'jobUrl']);
    $salary = externalJobPick($item, ['salary', 'salaryInfo', 'salaryRange']);
    $employmentType = strtolower(externalJobPick($item, ['employmentType', 'jobType', 'workplaceType']));

    if ($title === '' || $company === '') {
        return null;
    }

    if ($description === '') {
        $description = externalJobPick($item, ['companyDescription', 'company_description']);
    }

    if ($description === '') {
        $description = $title . ' at ' . $company . '.';
    }

    $description = cleanJobDescriptionText($description);

    $externalId = externalJobPick($item, ['id', 'jobId', 'job_id', 'linkedinJobId']);
    if ($externalId === '' && $externalUrl !== '') {
        if (preg_match('/currentJobId=(\d+)/', $externalUrl, $matches)) {
            $externalId = $matches[1];
        } elseif (preg_match('/\/jobs\/view\/(\d+)/', $externalUrl, $matches)) {
            $externalId = $matches[1];
        } else {
            $externalId = substr(sha1($externalUrl), 0, 16);
        }
    }

    if ($externalId === '') {
        $externalId = substr(sha1($title . '|' . $company . '|' . $location), 0, 16);
    }

    $jobType = 'full-time';
    if (str_contains($employmentType, 'part')) {
        $jobType = 'part-time';
    } elseif (str_contains($employmentType, 'contract')) {
        $jobType = 'contract';
    } elseif (str_contains($employmentType, 'remote') || str_contains(strtolower($location), 'remote')) {
        $jobType = 'remote';
    }

    $skills = externalJobPick($item, ['skills', 'skillsList']);
    if ($skills === '' && preg_match_all('/\b(figma|ui|ux|react|php|python|javascript|java|design|sql|node)\b/i', $title . ' ' . $description, $matches)) {
        $skills = implode(', ', array_unique(array_map('strtolower', $matches[0])));
    }

    return [
        'source' => 'linkedin',
        'external_id' => $externalId,
        'external_url' => $externalUrl !== '' ? $externalUrl : null,
        'company_name' => $company,
        'title' => $title,
        'location' => $location !== '' ? $location : null,
        'job_type' => $jobType,
        'salary' => $salary !== '' ? $salary : null,
        'skills' => $skills !== '' ? $skills : null,
        'description' => $description,
        'status' => 'approved',
        'created_by' => 'admin',
    ];
}

function upsertLinkedInJob(array $job): string
{
    ensureJobsSchema();

    $pdo = db();
    $now = date('Y-m-d H:i:s');

    $existing = $pdo->prepare(
        'SELECT id FROM jobs WHERE source = :source AND external_id = :external_id LIMIT 1'
    );
    $existing->execute([
        'source' => $job['source'],
        'external_id' => $job['external_id'],
    ]);
    $row = $existing->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $stmt = $pdo->prepare(
            'UPDATE jobs SET company_name = :company_name, title = :title, location = :location,
             job_type = :job_type, salary = :salary, skills = :skills, description = :description,
             external_url = :external_url, status = :status, synced_at = :synced_at
             WHERE id = :id'
        );
        $stmt->execute([
            'company_name' => $job['company_name'],
            'title' => $job['title'],
            'location' => $job['location'],
            'job_type' => $job['job_type'],
            'salary' => $job['salary'],
            'skills' => $job['skills'],
            'description' => $job['description'],
            'external_url' => $job['external_url'],
            'status' => 'approved',
            'synced_at' => $now,
            'id' => (int) $row['id'],
        ]);

        return 'updated';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO jobs (
            employer_id, company_name, title, location, job_type, salary, skills, description,
            status, created_by, source, external_id, external_url, synced_at
        ) VALUES (
            NULL, :company_name, :title, :location, :job_type, :salary, :skills, :description,
            :status, :created_by, :source, :external_id, :external_url, :synced_at
        )'
    );
    $stmt->execute([
        'company_name' => $job['company_name'],
        'title' => $job['title'],
        'location' => $job['location'],
        'job_type' => $job['job_type'],
        'salary' => $job['salary'],
        'skills' => $job['skills'],
        'description' => $job['description'],
        'status' => 'approved',
        'created_by' => 'admin',
        'source' => 'linkedin',
        'external_id' => $job['external_id'],
        'external_url' => $job['external_url'],
        'synced_at' => $now,
    ]);

    return 'inserted';
}

function waitForApifyRun(string $token, string $runId, int $maxSeconds = 300): array
{
    $deadline = time() + $maxSeconds;

    while (time() < $deadline) {
        $url = 'https://api.apify.com/v2/actor-runs/' . rawurlencode($runId) . '?token=' . rawurlencode($token);
        $response = apifyHttpRequest('GET', $url);

        if ($response === null) {
            sleep(3);
            continue;
        }

        if (!empty($response['error'])) {
            return [
                'ok' => false,
                'error' => $response['error']['message'] ?? 'Could not read Apify run status.',
                'data' => null,
            ];
        }

        $status = strtoupper((string) ($response['data']['status'] ?? ''));
        $data = $response['data'] ?? null;

        if ($status === 'SUCCEEDED') {
            return ['ok' => true, 'error' => null, 'data' => $data];
        }

        if (in_array($status, ['FAILED', 'ABORTED', 'TIMED-OUT'], true)) {
            $statusMessage = trim((string) ($data['statusMessage'] ?? ''));

            return [
                'ok' => false,
                'error' => 'Apify run ' . strtolower($status)
                    . ($statusMessage !== '' ? ': ' . $statusMessage : '. Check the run log in Apify Console.'),
                'data' => $data,
            ];
        }

        sleep(3);
    }

    return [
        'ok' => false,
        'error' => 'Apify run did not finish in time. Open Apify Console and check the latest run.',
        'data' => null,
    ];
}

function fetchApifyDatasetItems(string $token, string $datasetId): array
{
    $url = 'https://api.apify.com/v2/datasets/' . rawurlencode($datasetId) . '/items?token=' . rawurlencode($token) . '&format=json';

    if (!function_exists('curl_init')) {
        return [];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_PROXY => '',
        CURLOPT_NOPROXY => '*',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false || $response === '') {
        return [];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return [];
    }

    if (array_is_list($decoded)) {
        return $decoded;
    }

    if (isset($decoded['data']['items']) && is_array($decoded['data']['items'])) {
        return $decoded['data']['items'];
    }

    return [];
}

function summarizeApifyJobItemForLog(array $item): array
{
    $summary = [];
    $fields = [
        'id', 'title', 'companyName', 'location', 'link', 'applyUrl',
        'employmentType', 'seniorityLevel', 'postedAt', 'applicantsCount',
    ];

    foreach ($fields as $field) {
        if (array_key_exists($field, $item) && $item[$field] !== '' && $item[$field] !== null) {
            $summary[$field] = $item[$field];
        }
    }

    $description = '';
    if (is_string($item['descriptionText'] ?? null)) {
        $description = $item['descriptionText'];
    } elseif (is_string($item['description'] ?? null)) {
        $description = $item['description'];
    }

    if ($description !== '') {
        $summary['descriptionPreview'] = mb_strlen($description) > 280
            ? mb_substr($description, 0, 280) . '…'
            : $description;
    }

    return $summary;
}

function buildApifySyncResponseLog(
    string $searchUrl,
    int $limit,
    array $actorInput,
    ?array $runStartResponse,
    ?array $runData,
    array $items,
    int $inserted,
    int $updated,
    int $skipped
): array {
    $preview = [];
    foreach (array_slice($items, 0, 5) as $item) {
        if (is_array($item)) {
            $preview[] = summarizeApifyJobItemForLog($item);
        }
    }

    return [
        'synced_at' => date('Y-m-d H:i:s'),
        'search_url' => $searchUrl,
        'requested_limit' => $limit,
        'actor_input' => $actorInput,
        'run' => [
            'id' => $runData['id'] ?? ($runStartResponse['data']['id'] ?? null),
            'status' => $runData['status'] ?? null,
            'dataset_id' => $runData['defaultDatasetId'] ?? null,
            'started_at' => $runStartResponse['data']['startedAt'] ?? null,
            'finished_at' => $runData['finishedAt'] ?? null,
        ],
        'summary' => [
            'items_received' => count($items),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
        ],
        'dataset_preview' => $preview,
    ];
}

function syncLinkedInJobsFromApify(): array
{
    if (!siteSettingEnabled('apify_enabled')) {
        return ['success' => false, 'error' => 'Enable Apify integration in Settings first.'];
    }

    if (!apifyIntegrationConfigured()) {
        return ['success' => false, 'error' => 'Save a valid Apify token and actor ID first.'];
    }

    [$searchUrl, $actorInput] = buildApifyLinkedInActorInput();
    if ($searchUrl === null || $actorInput === []) {
        return [
            'success' => false,
            'error' => 'Add a LinkedIn search URL, or keywords (and optionally location), then Save integration before syncing. A blank search cannot run like your Apify Console test.',
        ];
    }

    $token = getApifyApiToken();
    $actorId = apifyActorApiId(getApifyActorId());
    $limit = max(10, min(500, (int) getSiteSetting('apify_job_limit', '50')));

    $runUrl = 'https://api.apify.com/v2/acts/' . rawurlencode($actorId) . '/runs?token=' . rawurlencode($token);
    $runResponse = apifyHttpRequest('POST', $runUrl, $actorInput);

    if ($runResponse === null) {
        return ['success' => false, 'error' => 'Could not start Apify actor run. Check PHP curl and your token.'];
    }

    if (!empty($runResponse['error'])) {
        $message = $runResponse['error']['message'] ?? 'Apify could not start the actor run.';
        return ['success' => false, 'error' => $message];
    }

    $runId = (string) ($runResponse['data']['id'] ?? '');
    if ($runId === '') {
        return ['success' => false, 'error' => 'Apify did not return a run ID.'];
    }

    set_time_limit(360);
    $wait = waitForApifyRun($token, $runId, 300);
    if (empty($wait['ok'])) {
        saveApifyResponseLog('sync', [
            'synced_at' => date('Y-m-d H:i:s'),
            'search_url' => $searchUrl,
            'actor_input' => $actorInput,
            'run' => [
                'id' => $runId,
                'status' => $wait['data']['status'] ?? null,
            ],
            'error' => $wait['error'] ?? 'Apify run failed.',
        ]);

        return ['success' => false, 'error' => $wait['error'] ?? 'Apify run failed. Check the run in Apify Console.'];
    }

    $runData = $wait['data'] ?? [];
    $datasetId = (string) ($runData['defaultDatasetId'] ?? '');
    if ($datasetId === '') {
        return ['success' => false, 'error' => 'Apify run finished but no dataset was returned.'];
    }

    $items = fetchApifyDatasetItems($token, $datasetId);
    if ($items === []) {
        saveApifyResponseLog('sync', [
            'synced_at' => date('Y-m-d H:i:s'),
            'search_url' => $searchUrl,
            'actor_input' => $actorInput,
            'run' => [
                'id' => $runData['id'] ?? $runId,
                'status' => $runData['status'] ?? null,
                'dataset_id' => $datasetId,
            ],
            'summary' => [
                'items_received' => 0,
                'inserted' => 0,
                'updated' => 0,
                'skipped' => 0,
            ],
            'dataset_preview' => [],
            'error' => 'Dataset was empty.',
        ]);

        return [
            'success' => false,
            'error' => 'Apify returned no jobs for this search. Paste the same LinkedIn URL that worked in Apify Console, then Save and sync again.',
        ];
    }

    $inserted = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($items as $item) {
        if (!is_array($item)) {
            $skipped++;
            continue;
        }

        $normalized = normalizeLinkedInJobItem($item);
        if ($normalized === null) {
            $skipped++;
            continue;
        }

        $result = upsertLinkedInJob($normalized);
        if ($result === 'inserted') {
            $inserted++;
        } elseif ($result === 'updated') {
            $updated++;
        } else {
            $skipped++;
        }
    }

    $processed = $inserted + $updated;
    if ($processed === 0) {
        $sampleKeys = is_array($items[0] ?? null) ? implode(', ', array_slice(array_keys($items[0]), 0, 8)) : 'unknown';
        saveApifyResponseLog('sync', buildApifySyncResponseLog(
            $searchUrl,
            $limit,
            $actorInput,
            $runResponse,
            $runData,
            $items,
            $inserted,
            $updated,
            $skipped
        ) + ['error' => 'No rows could be imported. Sample fields: ' . $sampleKeys]);

        return [
            'success' => false,
            'error' => 'Apify returned ' . count($items) . ' row(s) but none could be imported. Sample fields: ' . $sampleKeys,
        ];
    }

    saveSiteSetting('apify_last_sync_at', date('Y-m-d H:i:s'));
    saveSiteSetting('apify_last_sync_count', (string) $processed);
    saveApifyResponseLog('sync', buildApifySyncResponseLog(
        $searchUrl,
        $limit,
        $actorInput,
        $runResponse,
        $runData,
        $items,
        $inserted,
        $updated,
        $skipped
    ));

    return [
        'success' => true,
        'message' => sprintf(
            'LinkedIn sync complete: %d new, %d updated, %d skipped.',
            $inserted,
            $updated,
            $skipped
        ),
        'inserted' => $inserted,
        'updated' => $updated,
        'skipped' => $skipped,
        'total' => $processed,
    ];
}

/**
 * Run LinkedIn sync when the admin schedule says it is due.
 *
 * @return array{ran: bool, skipped?: bool, reason?: string, success?: bool, message?: string, error?: string}
 */
function runScheduledLinkedInSyncIfDue(bool $force = false): array
{
    if (!apifyScheduleEnabled() && !$force) {
        return ['ran' => false, 'skipped' => true, 'reason' => 'Schedule is disabled.'];
    }

    if (!$force && !apifyScheduledSyncIsDue()) {
        $next = apifyNextScheduledSyncAt();
        $when = $next ? date('Y-m-d H:i:s', $next) : 'unknown';

        return ['ran' => false, 'skipped' => true, 'reason' => 'Not due yet. Next sync around ' . $when . '.'];
    }

    $lockPath = dirname(__DIR__) . '/database/apify-sync.lock';
    $lockDir = dirname($lockPath);
    if (!is_dir($lockDir)) {
        mkdir($lockDir, 0755, true);
    }

    $lockHandle = fopen($lockPath, 'c+');
    if ($lockHandle === false) {
        return ['ran' => false, 'skipped' => true, 'reason' => 'Could not open sync lock file.'];
    }

    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        fclose($lockHandle);

        return ['ran' => false, 'skipped' => true, 'reason' => 'Another LinkedIn sync is already running.'];
    }

    saveSiteSetting('apify_schedule_last_attempt_at', date('Y-m-d H:i:s'));

    try {
        $result = syncLinkedInJobsFromApify();
        $summary = !empty($result['success'])
            ? ($result['message'] ?? 'Sync succeeded.')
            : ($result['error'] ?? 'Sync failed.');
        saveSiteSetting('apify_schedule_last_result', $summary);

        return array_merge(['ran' => true], $result);
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}
