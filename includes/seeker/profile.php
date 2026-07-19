<?php
/**
 * Seeker profile, education, experience, and CV storage.
 */

require_once __DIR__ . '/../users.php';
require_once __DIR__ . '/../helpers.php';

function ensureSeekerProfileSchema(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    ensureUsersSchema();
    $pdo = db();

    $columns = array_column(
        $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC),
        'name'
    );

    $userColumns = [
        'phone' => 'ALTER TABLE users ADD COLUMN phone TEXT NULL',
        'headline' => 'ALTER TABLE users ADD COLUMN headline TEXT NULL',
        'about' => 'ALTER TABLE users ADD COLUMN about TEXT NULL',
        'skills' => 'ALTER TABLE users ADD COLUMN skills TEXT NULL',
        'open_to_work' => 'ALTER TABLE users ADD COLUMN open_to_work INTEGER NOT NULL DEFAULT 0',
        'cv_path' => 'ALTER TABLE users ADD COLUMN cv_path TEXT NULL',
        'cv_updated_at' => 'ALTER TABLE users ADD COLUMN cv_updated_at TEXT NULL',
        'cv_parsed_text' => 'ALTER TABLE users ADD COLUMN cv_parsed_text TEXT NULL',
        'cv_parsed_at' => 'ALTER TABLE users ADD COLUMN cv_parsed_at TEXT NULL',
        'cv_titles' => 'ALTER TABLE users ADD COLUMN cv_titles TEXT NULL',
    ];

    foreach ($userColumns as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec($sql);
        }
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS seeker_education (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            school TEXT NOT NULL,
            degree TEXT NULL,
            field_of_study TEXT NULL,
            start_year TEXT NULL,
            end_year TEXT NULL,
            description TEXT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS seeker_experience (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            company TEXT NULL,
            location TEXT NULL,
            start_year TEXT NULL,
            end_year TEXT NULL,
            is_current INTEGER NOT NULL DEFAULT 0,
            description TEXT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_seeker_education_user ON seeker_education (user_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_seeker_experience_user ON seeker_experience (user_id)');

    migrateMisplacedSeekerCvFiles();

    $checked = true;
}

function migrateMisplacedSeekerCvFiles(): void
{
    $legacyDir = dirname(__DIR__) . '/public/assets/uploads/cvs';
    $targetDir = appPublicPath('assets/uploads/cvs');

    if (!is_dir($legacyDir)) {
        return;
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    foreach (glob($legacyDir . '/*') ?: [] as $legacyFile) {
        if (!is_file($legacyFile)) {
            continue;
        }

        $targetFile = $targetDir . '/' . basename($legacyFile);
        if (!is_file($targetFile)) {
            rename($legacyFile, $targetFile);
        }
    }
}

function parseSkillsList(?string $skills): array
{
    if ($skills === null || trim($skills) === '') {
        return [];
    }

    $parts = preg_split('/\s*,\s*/', trim($skills)) ?: [];

    return array_values(array_filter(array_map('trim', $parts), fn ($skill) => $skill !== ''));
}

function formatSeekerDisplayHeadline(array $profile): string
{
    return trim($profile['headline'] ?? '') ?: 'Add a headline on your profile';
}

function formatEducationYears(?string $startYear, ?string $endYear): string
{
    $start = trim($startYear ?? '');
    $end = trim($endYear ?? '');

    if ($start === '' && $end === '') {
        return '';
    }

    if ($start !== '' && $end !== '') {
        return $start . ' – ' . $end;
    }

    return $start !== '' ? $start : $end;
}

function formatExperienceYears(array $entry): string
{
    $start = trim($entry['start_year'] ?? '');
    $end = trim($entry['end_year'] ?? '');
    $isCurrent = !empty($entry['is_current']);

    if ($start === '' && $end === '' && !$isCurrent) {
        return '';
    }

    $range = $start;
    if ($start !== '' && ($end !== '' || $isCurrent)) {
        $range .= ' – ';
    }
    $range .= $isCurrent ? 'Present' : $end;

    return $range;
}

function fetchSeekerProfile(int $userId): ?array
{
    ensureSeekerProfileSchema();

    $stmt = db()->prepare(
        "SELECT id, username, email, full_name, phone, headline, about, skills, location,
                open_to_work, avatar_path, cv_path, cv_updated_at, cv_parsed_text, cv_parsed_at, cv_titles, created_at
         FROM users
         WHERE id = :id AND role = 'seeker'
         LIMIT 1"
    );
    $stmt->execute(['id' => $userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        return null;
    }

    $profile['open_to_work'] = (int) ($profile['open_to_work'] ?? 0);
    $profile['skill_list'] = parseSkillsList($profile['skills'] ?? null);
    $profile['title_list'] = parseSkillsList($profile['cv_titles'] ?? null);

    return $profile;
}

function fetchSeekerEducation(int $userId): array
{
    ensureSeekerProfileSchema();

    $stmt = db()->prepare(
        'SELECT id, school, degree, field_of_study, start_year, end_year, description
         FROM seeker_education
         WHERE user_id = :user_id
         ORDER BY datetime(created_at) DESC, id DESC'
    );
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetchSeekerExperience(int $userId): array
{
    ensureSeekerProfileSchema();

    $stmt = db()->prepare(
        'SELECT id, title, company, location, start_year, end_year, is_current, description
         FROM seeker_experience
         WHERE user_id = :user_id
         ORDER BY is_current DESC, datetime(created_at) DESC, id DESC'
    );
    $stmt->execute(['user_id' => $userId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$row) {
        $row['is_current'] = (int) ($row['is_current'] ?? 0);
    }

    return $rows;
}

function removeSeekerCvFile(?string $path): void
{
    if (!$path || !str_starts_with($path, '/assets/uploads/cvs/')) {
        return;
    }

    $fullPath = appPublicPath(ltrim($path, '/'));
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

/**
 * Clear NLP fields that belong to the uploaded CV (not profile skills).
 */
function clearSeekerCvParseData(int $userId): void
{
    ensureSeekerProfileSchema();

    try {
        db()->prepare(
            "UPDATE users SET cv_parsed_text = NULL, cv_parsed_at = NULL, cv_titles = NULL
             WHERE id = :id AND role = 'seeker'"
        )->execute(['id' => $userId]);
    } catch (PDOException) {
        // Best-effort cleanup; profile save may still succeed.
    }
}

function processSeekerCvUpdate(int $userId, ?array $file, bool $remove, ?string $currentPath): array
{
    if ($remove) {
        removeSeekerCvFile($currentPath);
        return ['success' => true, 'path' => null];
    }

    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => $currentPath];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Could not upload CV. Please try again.'];
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'CV must be 5MB or smaller.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    if (!isset($allowed[$mime])) {
        return ['success' => false, 'error' => 'CV must be PDF or DOCX.'];
    }

    $uploadDir = appPublicPath('assets/uploads/cvs');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    removeSeekerCvFile($currentPath);

    $filename = sprintf('seeker-%d-%s.%s', $userId, bin2hex(random_bytes(8)), $allowed[$mime]);
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Could not save CV file.'];
    }

    return ['success' => true, 'path' => '/assets/uploads/cvs/' . $filename];
}

function seekerCvMeta(array $profile): array
{
    $path = trim($profile['cv_path'] ?? '');

    if ($path === '' || !str_starts_with($path, '/assets/uploads/cvs/')) {
        return [
            'has_cv' => false,
            'path' => null,
            'filename' => null,
            'updated_at' => null,
            'updated_label' => null,
        ];
    }

    $filename = basename($path);
    $updatedAt = trim($profile['cv_updated_at'] ?? '');
    $updatedLabel = formatSeekerCvUpdatedLabel($updatedAt !== '' ? $updatedAt : null);

    if ($updatedLabel === null) {
        $fullPath = appPublicPath(ltrim($path, '/'));
        if (is_file($fullPath)) {
            $updatedLabel = formatSeekerCvUpdatedLabel(date('c', (int) filemtime($fullPath)));
        }
    }

    return [
        'has_cv' => true,
        'path' => $path,
        'filename' => $filename,
        'updated_at' => $updatedAt !== '' ? $updatedAt : null,
        'updated_label' => $updatedLabel,
    ];
}

function formatSeekerCvUpdatedLabel(?string $timestamp): ?string
{
    if ($timestamp === null || trim($timestamp) === '') {
        return null;
    }

    $time = strtotime($timestamp);
    if ($time === false) {
        return null;
    }

    $diff = time() - $time;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return (int) floor($diff / 60) . ' min ago';
    }
    if ($diff < 86400) {
        return (int) floor($diff / 3600) . ' hours ago';
    }
    if ($diff < 604800) {
        return (int) floor($diff / 86400) . ' days ago';
    }

    return date('M j, Y', $time);
}

function uploadSeekerCv(int $userId, array $file): array
{
    ensureSeekerProfileSchema();

    $profile = fetchSeekerProfile($userId);
    if (!$profile) {
        return ['success' => false, 'error' => 'Seeker profile not found.'];
    }

    $cvResult = processSeekerCvUpdate(
        $userId,
        $file,
        false,
        $profile['cv_path'] ?? null
    );

    if (!$cvResult['success']) {
        return $cvResult;
    }

    if (($cvResult['path'] ?? null) === ($profile['cv_path'] ?? null)) {
        return ['success' => false, 'error' => 'No CV file was uploaded.'];
    }

    $updatedAt = date('c');

    try {
        db()->prepare(
            'UPDATE users SET cv_path = :cv_path, cv_updated_at = :cv_updated_at
             WHERE id = :id AND role = :role'
        )->execute([
            'cv_path' => $cvResult['path'],
            'cv_updated_at' => $updatedAt,
            'id' => $userId,
            'role' => ROLE_SEEKER,
        ]);
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not save CV. Please try again.'];
    }

    $nlpMeta = applyNlpParseToSeekerCv($userId, (string) $cvResult['path']);

    $freshProfile = fetchSeekerProfile($userId) ?? $profile;
    $cvMeta = seekerCvMeta($freshProfile);

    $message = 'CV saved to your profile. It will be used for Easy Apply and AI recommendations.';
    if (!empty($nlpMeta['success']) && !empty($nlpMeta['skills'])) {
        $message = 'CV saved and analyzed with NLP. Extracted skills: ' . implode(', ', array_slice($nlpMeta['skills'], 0, 8)) . '.';
    } elseif (!empty($nlpMeta['offline'])) {
        $message = 'CV saved. NLP service is offline — start it to extract skills from the PDF.';
    } elseif (!empty($nlpMeta['error'])) {
        $message = 'CV saved, but NLP could not parse it: ' . $nlpMeta['error'];
    }

    return [
        'success' => true,
        'message' => $message,
        'cv' => $cvMeta,
        'skills' => $freshProfile['skill_list'] ?? [],
        'titles' => $freshProfile['title_list'] ?? [],
        'nlp' => $nlpMeta,
    ];
}

/**
 * Call Python NLP to parse CV text/skills and store on the seeker profile.
 */
function applyNlpParseToSeekerCv(int $userId, string $publicCvPath): array
{
    require_once __DIR__ . '/../nlp-client.php';

    $absolute = publicPathToAbsolute($publicCvPath);
    if ($absolute === null) {
        return ['success' => false, 'error' => 'CV path is invalid.'];
    }

    $parsed = nlpParseCvFile($absolute);
    if (empty($parsed['success'])) {
        return $parsed;
    }

    $skills = $parsed['skills'] ?? [];
    $titles = $parsed['titles'] ?? [];
    $skillsCsv = implode(', ', $skills);
    $titlesCsv = implode(', ', $titles);
    $text = (string) ($parsed['text'] ?? '');
    $parsedAt = date('c');

    try {
        // Merge NLP skills with existing profile skills (NLP first).
        $existing = fetchSeekerProfile($userId);
        $existingSkills = $existing['skill_list'] ?? [];
        $merged = [];
        foreach (array_merge($skills, $existingSkills) as $skill) {
            $key = mb_strtolower(trim((string) $skill));
            if ($key === '' || isset($merged[$key])) {
                continue;
            }
            $merged[$key] = trim((string) $skill);
        }
        $mergedList = array_values($merged);
        $skillsCsv = implode(', ', $mergedList);

        db()->prepare(
            'UPDATE users SET skills = :skills, cv_parsed_text = :cv_parsed_text,
             cv_parsed_at = :cv_parsed_at, cv_titles = :cv_titles
             WHERE id = :id AND role = :role'
        )->execute([
            'skills' => $skillsCsv !== '' ? $skillsCsv : null,
            'cv_parsed_text' => $text !== '' ? $text : null,
            'cv_parsed_at' => $parsedAt,
            'cv_titles' => $titlesCsv !== '' ? $titlesCsv : null,
            'id' => $userId,
            'role' => ROLE_SEEKER,
        ]);
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Parsed CV but could not save NLP results.'];
    }

    return [
        'success' => true,
        'skills' => $mergedList ?? $skills,
        'titles' => $titles,
        'engine' => $parsed['engine'] ?? 'nlp',
        'char_count' => $parsed['char_count'] ?? 0,
        'text_preview' => $parsed['text_preview'] ?? '',
    ];
}

function updateSeekerProfile(int $userId, array $data): array
{
    ensureSeekerProfileSchema();

    $profile = fetchSeekerProfile($userId);
    if (!$profile) {
        return ['success' => false, 'error' => 'Seeker profile not found.'];
    }

    $fullName = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $headline = trim($data['headline'] ?? '');
    $about = trim($data['about'] ?? '');
    $location = trim($data['location'] ?? '');
    $skills = trim($data['skills'] ?? '');
    $openToWork = !empty($data['open_to_work']);

    if ($fullName === '') {
        return ['success' => false, 'error' => 'Full name is required.'];
    }

    if ($email === '') {
        return ['success' => false, 'error' => 'Email is required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }

    $duplicate = db()->prepare(
        'SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1'
    );
    $duplicate->execute(['email' => $email, 'id' => $userId]);
    if ($duplicate->fetch()) {
        return ['success' => false, 'error' => 'That email is already used by another account.'];
    }

    $avatarFile = $data['avatar_file'] ?? null;
    $removeAvatar = !empty($data['remove_avatar']);
    $avatarResult = processUserAvatarUpdate(
        $userId,
        is_array($avatarFile) ? $avatarFile : null,
        $removeAvatar,
        $profile['avatar_path'] ?? null
    );

    if (!$avatarResult['success']) {
        return $avatarResult;
    }

    $cvFile = $data['cv_file'] ?? null;
    $removeCv = !empty($data['remove_cv']);
    $cvResult = processSeekerCvUpdate(
        $userId,
        is_array($cvFile) ? $cvFile : null,
        $removeCv,
        $profile['cv_path'] ?? null
    );

    if (!$cvResult['success']) {
        return $cvResult;
    }

    $cvUpdatedAt = $profile['cv_updated_at'] ?? null;
    $cvRemoved = false;
    if (($cvResult['path'] ?? null) !== ($profile['cv_path'] ?? null)) {
        $cvUpdatedAt = $cvResult['path'] ? date('c') : null;
        $cvRemoved = $cvResult['path'] === null && !empty($profile['cv_path']);
    }

    try {
        $stmt = db()->prepare(
            'UPDATE users SET full_name = :full_name, email = :email, phone = :phone,
             headline = :headline, about = :about, location = :location, skills = :skills,
             open_to_work = :open_to_work, avatar_path = :avatar_path, cv_path = :cv_path,
             cv_updated_at = :cv_updated_at
             WHERE id = :id AND role = :role'
        );
        $stmt->execute([
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'headline' => $headline !== '' ? $headline : null,
            'about' => $about !== '' ? $about : null,
            'location' => $location !== '' ? $location : null,
            'skills' => $skills !== '' ? $skills : null,
            'open_to_work' => $openToWork ? 1 : 0,
            'avatar_path' => $avatarResult['path'],
            'cv_path' => $cvResult['path'],
            'cv_updated_at' => $cvUpdatedAt,
            'id' => $userId,
            'role' => ROLE_SEEKER,
        ]);
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not save profile. Please try again.'];
    }

    if ($cvRemoved) {
        clearSeekerCvParseData($userId);
    }

    return [
        'success' => true,
        'message' => $cvRemoved
            ? 'Profile saved. CV removed — AI will no longer use CV text for matching (profile skills are unchanged).'
            : 'Profile saved successfully.',
    ];
}

function addSeekerEducation(int $userId, array $data): array
{
    ensureSeekerProfileSchema();

    if (!fetchSeekerProfile($userId)) {
        return ['success' => false, 'error' => 'Seeker profile not found.'];
    }

    $school = trim($data['school'] ?? '');
    if ($school === '') {
        return ['success' => false, 'error' => 'School or university name is required.'];
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO seeker_education (user_id, school, degree, field_of_study, start_year, end_year, description)
             VALUES (:user_id, :school, :degree, :field_of_study, :start_year, :end_year, :description)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'school' => $school,
            'degree' => trim($data['degree'] ?? '') ?: null,
            'field_of_study' => trim($data['field_of_study'] ?? '') ?: null,
            'start_year' => trim($data['start_year'] ?? '') ?: null,
            'end_year' => trim($data['end_year'] ?? '') ?: null,
            'description' => trim($data['description'] ?? '') ?: null,
        ]);
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not add education. Please try again.'];
    }

    return ['success' => true, 'message' => 'Education added successfully.'];
}

function deleteSeekerEducation(int $userId, int $educationId): array
{
    ensureSeekerProfileSchema();

    $stmt = db()->prepare('DELETE FROM seeker_education WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $educationId, 'user_id' => $userId]);

    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'Education entry not found.'];
    }

    return ['success' => true, 'message' => 'Education removed.'];
}

function addSeekerExperience(int $userId, array $data): array
{
    ensureSeekerProfileSchema();

    if (!fetchSeekerProfile($userId)) {
        return ['success' => false, 'error' => 'Seeker profile not found.'];
    }

    $title = trim($data['title'] ?? '');
    if ($title === '') {
        return ['success' => false, 'error' => 'Job title is required.'];
    }

    $isCurrent = !empty($data['is_current']);

    try {
        $stmt = db()->prepare(
            'INSERT INTO seeker_experience (user_id, title, company, location, start_year, end_year, is_current, description)
             VALUES (:user_id, :title, :company, :location, :start_year, :end_year, :is_current, :description)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'company' => trim($data['company'] ?? '') ?: null,
            'location' => trim($data['location'] ?? '') ?: null,
            'start_year' => trim($data['start_year'] ?? '') ?: null,
            'end_year' => $isCurrent ? null : (trim($data['end_year'] ?? '') ?: null),
            'is_current' => $isCurrent ? 1 : 0,
            'description' => trim($data['description'] ?? '') ?: null,
        ]);
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not add experience. Please try again.'];
    }

    return ['success' => true, 'message' => 'Experience added successfully.'];
}

function deleteSeekerExperience(int $userId, int $experienceId): array
{
    ensureSeekerProfileSchema();

    $stmt = db()->prepare('DELETE FROM seeker_experience WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $experienceId, 'user_id' => $userId]);

    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'Experience entry not found.'];
    }

    return ['success' => true, 'message' => 'Experience removed.'];
}
