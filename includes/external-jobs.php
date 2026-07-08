<?php
/**
 * Sync external job listings (LinkedIn via Apify) into the jobs table.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/jobs.php';
require_once __DIR__ . '/settings.php';

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
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
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
        return 'https://www.linkedin.com/jobs/search/';
    }

    return 'https://www.linkedin.com/jobs/search/?' . http_build_query($params);
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

function waitForApifyRun(string $token, string $runId, int $maxSeconds = 300): ?array
{
    $deadline = time() + $maxSeconds;

    while (time() < $deadline) {
        $url = 'https://api.apify.com/v2/actor-runs/' . rawurlencode($runId) . '?token=' . rawurlencode($token);
        $response = apifyHttpRequest('GET', $url);
        $status = strtoupper((string) ($response['data']['status'] ?? ''));

        if (in_array($status, ['SUCCEEDED'], true)) {
            return $response['data'] ?? null;
        }

        if (in_array($status, ['FAILED', 'ABORTED', 'TIMED-OUT'], true)) {
            return null;
        }

        sleep(3);
    }

    return null;
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
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
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
{
    $customUrl = trim(getSiteSetting('apify_linkedin_search_url'));
    if ($customUrl !== '' && str_starts_with($customUrl, 'https://www.linkedin.com/jobs/search')) {
        return $customUrl;
    }

    $keywords = trim(getSiteSetting('apify_job_keywords'));
    $location = trim(getSiteSetting('apify_job_location'));

    return buildLinkedInJobsSearchUrl($keywords, $location);
}

function syncLinkedInJobsFromApify(): array
{
    if (!siteSettingEnabled('apify_enabled')) {
        return ['success' => false, 'error' => 'Enable Apify integration in Settings first.'];
    }

    if (!apifyIntegrationConfigured()) {
        return ['success' => false, 'error' => 'Save a valid Apify token and actor ID first.'];
    }

    $token = getApifyApiToken();
    $actorId = apifyActorApiId(getApifyActorId());
    $limit = max(1, min(500, (int) getSiteSetting('apify_job_limit', '20')));
    $searchUrl = resolveLinkedInSearchUrl();

    $runUrl = 'https://api.apify.com/v2/acts/' . rawurlencode($actorId) . '/runs?token=' . rawurlencode($token);
    $runResponse = apifyHttpRequest('POST', $runUrl, [
        'urls' => [$searchUrl],
        'count' => $limit,
        'scrapeCompany' => true,
    ]);

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
    $runData = waitForApifyRun($token, $runId, 300);
    if ($runData === null) {
        return ['success' => false, 'error' => 'Apify run did not finish in time. Check the run in Apify Console.'];
    }

    $datasetId = (string) ($runData['defaultDatasetId'] ?? '');
    if ($datasetId === '') {
        return ['success' => false, 'error' => 'Apify run finished but no dataset was returned.'];
    }

    $items = fetchApifyDatasetItems($token, $datasetId);
    if ($items === []) {
        return [
            'success' => false,
            'error' => 'Apify returned no jobs. Try a custom LinkedIn search URL that works in Apify Console (Nepal searches often return fewer results).',
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

        return [
            'success' => false,
            'error' => 'Apify returned ' . count($items) . ' row(s) but none could be imported. Sample fields: ' . $sampleKeys,
        ];
    }
    saveSiteSetting('apify_last_sync_at', date('Y-m-d H:i:s'));
    saveSiteSetting('apify_last_sync_count', (string) $processed);

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
