<?php
/**
 * HTTP client for the Jagiree Python NLP service.
 */

require_once __DIR__ . '/config.php';

function nlpServiceUrl(): string
{
    $fromEnv = trim((string) getenv('NLP_SERVICE_URL'));
    if ($fromEnv !== '') {
        return rtrim($fromEnv, '/');
    }

    require_once __DIR__ . '/settings.php';
    $stored = trim(getSiteSetting('nlp_service_url', 'http://127.0.0.1:8001'));

    return rtrim($stored !== '' ? $stored : 'http://127.0.0.1:8001', '/');
}

function nlpHttpJson(string $method, string $path, ?array $body = null, int $timeout = 60): ?array
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $url = nlpServiceUrl() . $path;
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
    ];

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES);
    }

    $options[CURLOPT_HTTPHEADER] = $headers;
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

function nlpServiceHealthy(): bool
{
    $result = nlpHttpJson('GET', '/health', null, 5);
    return is_array($result) && !empty($result['ok']) && ($result['_http_status'] ?? 0) === 200;
}

function nlpParseCvFile(string $absolutePath): array
{
    if (!is_file($absolutePath)) {
        return ['success' => false, 'error' => 'CV file was not found on disk.'];
    }

    $result = nlpHttpJson('POST', '/parse-cv', ['path' => $absolutePath], 90);
    if ($result === null) {
        return [
            'success' => false,
            'error' => 'NLP service is offline. Start it with: cd python && uvicorn app.main:app --port 8001',
            'offline' => true,
        ];
    }

    $status = (int) ($result['_http_status'] ?? 0);
    if ($status >= 400) {
        $detail = $result['detail'] ?? $result['error'] ?? 'CV parsing failed.';
        if (is_array($detail)) {
            $detail = json_encode($detail);
        }

        return ['success' => false, 'error' => (string) $detail];
    }

    return [
        'success' => true,
        'text' => (string) ($result['text'] ?? ''),
        'text_preview' => (string) ($result['text_preview'] ?? ''),
        'skills' => array_values(array_filter(array_map('strval', $result['skills'] ?? []))),
        'titles' => array_values(array_filter(array_map('strval', $result['titles'] ?? []))),
        'engine' => (string) ($result['engine'] ?? 'nlp'),
        'char_count' => (int) ($result['char_count'] ?? 0),
    ];
}

function nlpRecommendJobs(array $jobs, array $skills = [], array $titles = [], string $query = '', string $cvText = '', int $limit = 5): array
{
    $payloadJobs = [];
    foreach ($jobs as $job) {
        $payloadJobs[] = [
            'id' => (int) ($job['id'] ?? 0),
            'title' => (string) ($job['title'] ?? ''),
            'company' => (string) ($job['company'] ?? ($job['company_name'] ?? '')),
            'location' => (string) ($job['location'] ?? ''),
            'skills' => is_array($job['tags'] ?? null)
                ? implode(', ', $job['tags'])
                : (string) ($job['skills'] ?? ''),
            'description' => (string) ($job['description'] ?? ''),
            'is_external' => !empty($job['is_external']),
            'external_url' => $job['external_url'] ?? null,
            'source_label' => $job['source_label'] ?? null,
            'url' => $job['url'] ?? ('/seeker/jobs.php?id=' . (int) ($job['id'] ?? 0)),
        ];
    }

    $result = nlpHttpJson('POST', '/recommend', [
        'skills' => array_values($skills),
        'titles' => array_values($titles),
        'query' => $query,
        'cv_text' => $cvText,
        'jobs' => $payloadJobs,
        'limit' => $limit,
    ], 60);

    if ($result === null || empty($result['success'])) {
        return [
            'success' => false,
            'offline' => $result === null,
            'error' => 'NLP recommend failed or service offline.',
            'jobs' => [],
        ];
    }

    return [
        'success' => true,
        'engine' => (string) ($result['engine'] ?? 'tfidf-cosine'),
        'jobs' => is_array($result['jobs'] ?? null) ? $result['jobs'] : [],
    ];
}

function publicPathToAbsolute(string $publicPath): ?string
{
    $publicPath = trim($publicPath);
    if ($publicPath === '' || !str_starts_with($publicPath, '/assets/')) {
        return null;
    }

    $full = dirname(__DIR__) . '/public' . $publicPath;
    return is_file($full) ? $full : null;
}
