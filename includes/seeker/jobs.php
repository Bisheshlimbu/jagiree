<?php
/**
 * Seeker job browse queries and formatting.
 */

require_once __DIR__ . '/../jobs.php';
require_once __DIR__ . '/../users.php';
require_once __DIR__ . '/../applications.php';
require_once __DIR__ . '/profile.php';

function seekerJobFilters(): array
{
    return [
        'all' => 'All',
        'remote' => 'Remote',
        'full-time' => 'Full-time',
        'fresher' => 'Fresher',
        'it-tech' => 'IT & Tech',
        'design' => 'Design',
    ];
}

function parseJobSkillsList(?string $skills): array
{
    if ($skills === null || trim($skills) === '') {
        return [];
    }

    $parts = preg_split('/\s*,\s*/', trim($skills)) ?: [];

    return array_values(array_filter(array_map('trim', $parts), fn ($skill) => $skill !== ''));
}

function formatSeekerJobType(?string $jobType, ?string $location): string
{
    $labels = [
        'full-time' => 'Full-time',
        'part-time' => 'Part-time',
        'contract' => 'Contract',
        'remote' => 'Remote',
    ];

    $type = $labels[$jobType ?? ''] ?? 'Full-time';
    $locationText = strtolower($location ?? '');

    if (($jobType ?? '') !== 'remote' && str_contains($locationText, 'hybrid')) {
        return $type . ' · Hybrid';
    }

    if (($jobType ?? '') !== 'remote' && str_contains($locationText, 'remote')) {
        return $type . ' · Remote';
    }

    return $type;
}

function calculateSeekerJobMatch(array $seekerSkills, ?string $jobSkills): int
{
    $jobSkillList = parseJobSkillsList($jobSkills);
    if ($seekerSkills === [] || $jobSkillList === []) {
        return 0;
    }

    $seekerLower = array_map('strtolower', $seekerSkills);
    $jobLower = array_map('strtolower', $jobSkillList);
    $matched = 0;

    foreach ($jobLower as $jobSkill) {
        foreach ($seekerLower as $seekerSkill) {
            if ($jobSkill === $seekerSkill || str_contains($jobSkill, $seekerSkill) || str_contains($seekerSkill, $jobSkill)) {
                $matched++;
                break;
            }
        }
    }

    return (int) min(99, max(0, round(($matched / count($jobLower)) * 100)));
}

function jobMatchesSeekerFilter(array $job, string $filter): bool
{
    if ($filter === 'all') {
        return true;
    }

    $title = strtolower($job['title'] ?? '');
    $description = strtolower($job['description'] ?? '');
    $skills = strtolower($job['skills'] ?? '');
    $location = strtolower($job['location'] ?? '');
    $jobType = $job['job_type'] ?? '';

    return match ($filter) {
        'remote' => $jobType === 'remote'
            || str_contains($location, 'remote')
            || str_contains($title, 'remote'),
        'full-time' => $jobType === 'full-time',
        'fresher' => str_contains($title, 'fresher')
            || str_contains($title, 'junior')
            || str_contains($title, 'intern')
            || str_contains($description, 'fresher')
            || str_contains($description, 'entry level'),
        'it-tech' => preg_match('/\b(php|python|react|javascript|java|mysql|sql|node|laravel|developer|engineer|devops|backend|frontend|full stack|software)\b/i', $title . ' ' . $skills . ' ' . $description) === 1,
        'design' => preg_match('/\b(design|figma|ui|ux|product designer|graphic)\b/i', $title . ' ' . $skills . ' ' . $description) === 1,
        default => true,
    };
}

function formatSeekerJobCard(array $job, array $seekerSkills = [], array $appliedJobIds = []): array
{
    $company = trim($job['company_name'] ?? '');
    $tags = parseJobSkillsList($job['skills'] ?? null);
    $match = calculateSeekerJobMatch($seekerSkills, $job['skills'] ?? null);
    $employerUser = ['avatar_path' => $job['employer_avatar_path'] ?? null];
    $hasLogo = userHasAvatar($employerUser);

    $employerId = (int) ($job['employer_id'] ?? 0);

    return [
        'id' => (int) $job['id'],
        'title' => $job['title'],
        'company' => $company,
        'employer_id' => $employerId,
        'employer_url' => $employerId > 0 ? '/seeker/employer.php?id=' . $employerId : null,
        'location' => trim($job['location'] ?? '') ?: 'Location not set',
        'salary' => trim($job['salary'] ?? '') ?: 'Negotiable',
        'type' => formatSeekerJobType($job['job_type'] ?? null, $job['location'] ?? null),
        'posted' => formatJobSubmittedAt($job['created_at'] ?? null),
        'posted_at' => $job['created_at'] ?? '',
        'match' => $match,
        'tags' => $tags,
        'logo' => strtoupper(mb_substr($company !== '' ? $company : 'J', 0, 1)),
        'logo_url' => $hasLogo ? userAvatarUrl($employerUser) : null,
        'has_logo' => $hasLogo,
        'saved' => false,
        'applied' => in_array((int) $job['id'], $appliedJobIds, true),
    ];
}

function sortSeekerJobCards(array $jobs, string $sort): array
{
    if ($sort === 'recent') {
        usort($jobs, fn ($a, $b) => strcmp($b['posted_at'] ?? '', $a['posted_at'] ?? ''));
        return $jobs;
    }

    if ($sort === 'salary') {
        usort($jobs, fn ($a, $b) => strcmp($b['salary'], $a['salary']));
        return $jobs;
    }

    usort($jobs, function ($a, $b) {
        $matchDiff = ($b['match'] ?? 0) <=> ($a['match'] ?? 0);
        if ($matchDiff !== 0) {
            return $matchDiff;
        }

        return strcmp($b['posted_at'] ?? '', $a['posted_at'] ?? '');
    });

    return $jobs;
}

function fetchSeekerJobs(string $search = '', string $filter = 'all', string $sort = 'match', array $seekerSkills = [], ?int $seekerId = null): array
{
    ensureJobsSchema();

    $appliedJobIds = $seekerId ? seekerAppliedJobIds($seekerId) : [];

    $sql = "SELECT j.id, j.employer_id, j.company_name, j.title, j.location, j.job_type, j.salary, j.skills, j.description, j.created_at,
                   u.avatar_path AS employer_avatar_path
            FROM jobs j
            LEFT JOIN users u ON u.id = j.employer_id
            WHERE j.status = 'approved'";
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (j.title LIKE :search OR j.company_name LIKE :search OR j.location LIKE :search OR j.skills LIKE :search OR j.description LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY datetime(j.created_at) DESC, j.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $allowedFilters = array_keys(seekerJobFilters());
    if (!in_array($filter, $allowedFilters, true)) {
        $filter = 'all';
    }

    $cards = [];
    foreach ($rows as $row) {
        if (!jobMatchesSeekerFilter($row, $filter)) {
            continue;
        }
        $cards[] = formatSeekerJobCard($row, $seekerSkills, $appliedJobIds);
    }

    return sortSeekerJobCards($cards, $sort);
}

function fetchApprovedJobForSeeker(int $jobId): ?array
{
    ensureJobsSchema();

    $stmt = db()->prepare(
        "SELECT j.id, j.employer_id, j.company_name, j.title, j.location, j.job_type, j.salary, j.skills, j.description, j.created_at,
                u.avatar_path AS employer_avatar_path
         FROM jobs j
         LEFT JOIN users u ON u.id = j.employer_id
         WHERE j.id = :id AND j.status = 'approved'
         LIMIT 1"
    );
    $stmt->execute(['id' => $jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    return $job ?: null;
}

function seekerSortLabel(string $sort): string
{
    return match ($sort) {
        'recent' => 'Most Recent',
        'salary' => 'Salary: High to Low',
        default => 'Best Match',
    };
}

function getSeekerRecommendations(int $seekerId, int $limit = 6, string $sort = 'match'): array
{
    $profile = fetchSeekerProfile($seekerId);
    if (!$profile) {
        return [];
    }

    $skills = $profile['skill_list'] ?? [];
    $jobs = fetchSeekerJobs('', 'all', $sort, $skills, $seekerId);

    return array_slice($jobs, 0, $limit);
}

function getSeekerRecentJobs(int $seekerId, int $limit = 4): array
{
    return getSeekerRecommendations($seekerId, $limit, 'recent');
}

function countSeekerJobMatches(int $seekerId): int
{
    $profile = fetchSeekerProfile($seekerId);
    if (!$profile) {
        return 0;
    }

    return count(fetchSeekerJobs('', 'all', 'match', $profile['skill_list'] ?? [], $seekerId));
}

function formatSeekerJobForApi(array $job): array
{
    return [
        'id' => (int) ($job['id'] ?? 0),
        'title' => $job['title'] ?? '',
        'company' => $job['company'] ?? '',
        'location' => $job['location'] ?? '',
        'salary' => $job['salary'] ?? '',
        'type' => $job['type'] ?? '',
        'posted' => $job['posted'] ?? '',
        'match' => (int) ($job['match'] ?? 0),
        'tags' => $job['tags'] ?? [],
        'applied' => !empty($job['applied']),
        'url' => '/seeker/jobs.php?id=' . (int) ($job['id'] ?? 0),
    ];
}

function fetchPublicEmployerForSeeker(int $employerId): ?array
{
    ensureUsersSchema();

    $stmt = db()->prepare(
        "SELECT id, company_name, industry, company_about, avatar_path
         FROM users
         WHERE id = :id AND role = 'employer' AND status = 'verified'
         LIMIT 1"
    );
    $stmt->execute(['id' => $employerId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    return $profile ?: null;
}

function fetchEmployerApprovedJobs(int $employerId, array $seekerSkills = [], ?int $seekerId = null): array
{
    ensureJobsSchema();

    $appliedJobIds = $seekerId ? seekerAppliedJobIds($seekerId) : [];

    $stmt = db()->prepare(
        "SELECT j.id, j.employer_id, j.company_name, j.title, j.location, j.job_type, j.salary, j.skills, j.description, j.created_at,
                u.avatar_path AS employer_avatar_path
         FROM jobs j
         LEFT JOIN users u ON u.id = j.employer_id
         WHERE j.employer_id = :employer_id AND j.status = 'approved'
         ORDER BY datetime(j.created_at) DESC, j.id DESC"
    );
    $stmt->execute(['employer_id' => $employerId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $cards = [];
    foreach ($rows as $row) {
        $cards[] = formatSeekerJobCard($row, $seekerSkills, $appliedJobIds);
    }

    return $cards;
}

function employerIndustryLabel(?string $industry): string
{
    if ($industry === null || trim($industry) === '') {
        return '';
    }

    return employerIndustries()[$industry] ?? ucfirst(str_replace('-', ' ', $industry));
}
