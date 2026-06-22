<?php
/**
 * User queries for admin dashboard and profile features.
 */

require_once __DIR__ . '/database.php';

function ensureUsersSchema(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $pdo = db();
    $columns = array_column(
        $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC),
        'name'
    );

    if (!in_array('location', $columns, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN location TEXT NULL');
    }

    if (!in_array('status', $columns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status TEXT NOT NULL DEFAULT 'verified'");
    }

    if (!in_array('avatar_path', $columns, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN avatar_path TEXT NULL');
    }

    if (!in_array('industry', $columns, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN industry TEXT NULL');
    }

    if (!in_array('company_about', $columns, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN company_about TEXT NULL');
    }

    $checked = true;
}

function userAvatarPlaceholderUrl(): string
{
    return '/assets/images/avatar-placeholder.svg';
}

function userHasAvatar(array $user): bool
{
    $path = trim($user['avatar_path'] ?? '');

    return $path !== '' && str_starts_with($path, '/assets/uploads/avatars/');
}

function userAvatarUrl(array $user): string
{
    if (userHasAvatar($user)) {
        return trim($user['avatar_path']);
    }

    return userAvatarPlaceholderUrl();
}

function removeUserAvatarFile(?string $path): void
{
    if (!$path || !str_starts_with($path, '/assets/uploads/avatars/')) {
        return;
    }

    $fullPath = dirname(__DIR__) . '/public' . $path;
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function processUserAvatarUpdate(int $userId, ?array $file, bool $remove, ?string $currentPath): array
{
    if ($remove) {
        removeUserAvatarFile($currentPath);
        return ['success' => true, 'path' => null];
    }

    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => $currentPath];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Could not upload profile image. Please try again.'];
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Profile image must be 2MB or smaller.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        return ['success' => false, 'error' => 'Profile image must be JPG, PNG, or WEBP.'];
    }

    $uploadDir = dirname(__DIR__) . '/public/assets/uploads/avatars';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    removeUserAvatarFile($currentPath);

    $filename = sprintf('user-%d-%s.%s', $userId, bin2hex(random_bytes(8)), $allowed[$mime]);
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Could not save profile image.'];
    }

    return ['success' => true, 'path' => '/assets/uploads/avatars/' . $filename];
}

function formatAdminUserRow(array $user): array
{
    $companyName = trim($user['company_name'] ?? '') ?: '—';
    $status = $user['status'] ?? 'verified';

    return [
        'id' => (int) $user['id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'username' => $user['username'],
        'role' => ucfirst($user['role']),
        'role_key' => $user['role'],
        'company_name' => $companyName,
        'status' => $status,
        'avatar_url' => userAvatarUrl($user),
        'has_avatar' => userHasAvatar($user),
        'created_at' => $user['created_at'] ?? '',
    ];
}

function fetchAdminUsers(bool $includeAdmin = true): array
{
    ensureUsersSchema();

    $sql = 'SELECT id, username, email, full_name, company_name, avatar_path, location, role, status, created_at
            FROM users';

    if (!$includeAdmin) {
        $sql .= " WHERE role != 'admin'";
    }

    $sql .= ' ORDER BY datetime(created_at) DESC, id DESC';

    $stmt = db()->query($sql);

    return array_map('formatAdminUserRow', $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

function fetchRecentUsers(int $limit = 4): array
{
    ensureUsersSchema();

    $stmt = db()->prepare(
        "SELECT id, username, email, full_name, company_name, avatar_path, location, role, status, created_at
         FROM users
         WHERE role != 'admin'
         ORDER BY datetime(created_at) DESC, id DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return array_map('formatAdminUserRow', $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

function countAllUsers(bool $includeAdmin = true): int
{
    ensureUsersSchema();

    $sql = 'SELECT COUNT(*) FROM users';
    if (!$includeAdmin) {
        $sql .= " WHERE role != 'admin'";
    }

    return (int) db()->query($sql)->fetchColumn();
}

function countUsersByStatus(string $status): int
{
    ensureUsersSchema();

    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE status = :status');
    $stmt->execute(['status' => $status]);

    return (int) $stmt->fetchColumn();
}

function userStatusLabel(string $status): string
{
    return $status === 'pending' ? 'Pending' : 'Verified';
}

function fetchUserById(int $id): ?array
{
    ensureUsersSchema();

    $stmt = db()->prepare(
        'SELECT id, username, email, full_name, company_name, avatar_path, role, status, created_at
         FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

function updateUserByAdmin(int $id, array $data, int $adminId): array
{
    ensureUsersSchema();

    $user = fetchUserById($id);
    if (!$user) {
        return ['success' => false, 'error' => 'User not found.'];
    }

    $fullName = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $username = trim($data['username'] ?? '');
    $status = $data['status'] ?? 'verified';
    $role = $user['role'] === ROLE_ADMIN ? ROLE_ADMIN : ($data['role'] ?? $user['role']);
    $companyName = trim($data['company_name'] ?? '');
    $newPassword = $data['new_password'] ?? '';

    if ($fullName === '' || $email === '' || $username === '') {
        return ['success' => false, 'error' => 'Full name, email, and username are required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }

    if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['success' => false, 'error' => 'Username must be at least 3 characters (letters, numbers, underscore only).'];
    }

    if (!in_array($status, ['verified', 'pending'], true)) {
        return ['success' => false, 'error' => 'Invalid status selected.'];
    }

    if ($user['role'] !== ROLE_ADMIN && !in_array($role, [ROLE_SEEKER, ROLE_EMPLOYER], true)) {
        return ['success' => false, 'error' => 'Invalid role selected.'];
    }

    if ($role === ROLE_EMPLOYER && $companyName === '') {
        return ['success' => false, 'error' => 'Company name is required for employers.'];
    }

    if ($newPassword !== '' && strlen($newPassword) < 8) {
        return ['success' => false, 'error' => 'New password must be at least 8 characters.'];
    }

    $duplicate = db()->prepare(
        'SELECT id FROM users WHERE (email = :email OR username = :username) AND id != :id LIMIT 1'
    );
    $duplicate->execute(['email' => $email, 'username' => $username, 'id' => $id]);
    if ($duplicate->fetch()) {
        return ['success' => false, 'error' => 'Email or username is already used by another account.'];
    }

    $avatarFile = $data['avatar_file'] ?? null;
    $removeAvatar = !empty($data['remove_avatar']);
    $avatarResult = processUserAvatarUpdate(
        $id,
        is_array($avatarFile) ? $avatarFile : null,
        $removeAvatar,
        $user['avatar_path'] ?? null
    );

    if (!$avatarResult['success']) {
        return $avatarResult;
    }

    $avatarPath = $avatarResult['path'];

    $sql = 'UPDATE users SET full_name = :full_name, email = :email, username = :username,
            status = :status, company_name = :company_name, avatar_path = :avatar_path';
    $params = [
        'full_name' => $fullName,
        'email' => $email,
        'username' => $username,
        'status' => $status,
        'company_name' => $role === ROLE_EMPLOYER ? $companyName : null,
        'avatar_path' => $avatarPath,
        'id' => $id,
    ];

    if ($user['role'] !== ROLE_ADMIN) {
        $sql .= ', role = :role';
        $params['role'] = $role;
    }

    if ($newPassword !== '') {
        $sql .= ', password_hash = :password_hash';
        $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $sql .= ' WHERE id = :id';

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not update user. Please try again.'];
    }

    return ['success' => true, 'message' => 'User updated successfully.'];
}

function createUserByAdmin(array $data): array
{
    ensureUsersSchema();

    $fullName = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $status = $data['status'] ?? 'verified';
    $role = $data['role'] ?? ROLE_SEEKER;
    $companyName = trim($data['company_name'] ?? '');

    if ($fullName === '' || $email === '') {
        return ['success' => false, 'error' => 'Full name and email are required.'];
    }

    if ($password === '') {
        return ['success' => false, 'error' => 'Password is required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }

    if ($username !== '') {
        if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'error' => 'Username must be at least 3 characters (letters, numbers, underscore only).'];
        }
    } else {
        require_once __DIR__ . '/auth.php';
        $usernameResult = resolveUsername('', $email);
        if (!$usernameResult['success']) {
            return $usernameResult;
        }
        $username = $usernameResult['username'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    if (!in_array($status, ['verified', 'pending'], true)) {
        return ['success' => false, 'error' => 'Invalid status selected.'];
    }

    if (!in_array($role, [ROLE_SEEKER, ROLE_EMPLOYER], true)) {
        return ['success' => false, 'error' => 'Invalid role selected.'];
    }

    if ($role === ROLE_EMPLOYER && $companyName === '') {
        return ['success' => false, 'error' => 'Company name is required for employers.'];
    }

    $emailCheck = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $emailCheck->execute(['email' => $email]);
    if ($emailCheck->fetch()) {
        return ['success' => false, 'error' => 'That email is already registered.'];
    }

    $usernameCheck = db()->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $usernameCheck->execute(['username' => $username]);
    if ($usernameCheck->fetch()) {
        return ['success' => false, 'error' => 'That username is already taken.'];
    }

    $avatarFile = $data['avatar_file'] ?? null;
    $hasAvatarUpload = is_array($avatarFile)
        && ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    try {
        $stmt = db()->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, company_name, role, status, avatar_path)
             VALUES (:username, :email, :password_hash, :full_name, :company_name, :role, :status, NULL)'
        );
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'company_name' => $role === ROLE_EMPLOYER ? $companyName : null,
            'role' => $role,
            'status' => $status,
        ]);

        $userId = (int) db()->lastInsertId();

        if ($hasAvatarUpload) {
            $avatarResult = processUserAvatarUpdate(
                $userId,
                $avatarFile,
                false,
                null
            );

            if (!$avatarResult['success']) {
                db()->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $userId]);
                return $avatarResult;
            }

            if (!empty($avatarResult['path'])) {
                db()->prepare('UPDATE users SET avatar_path = :path WHERE id = :id')->execute([
                    'path' => $avatarResult['path'],
                    'id' => $userId,
                ]);
            }
        }

        return ['success' => true, 'message' => 'User created successfully.', 'user_id' => $userId];
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not create user. Please try again.'];
    }
}

function deleteUserByAdmin(int $id, int $adminId): array
{
    ensureUsersSchema();

    if ($id === $adminId) {
        return ['success' => false, 'error' => 'You cannot delete your own admin account.'];
    }

    $user = fetchUserById($id);
    if (!$user) {
        return ['success' => false, 'error' => 'User not found.'];
    }

    if ($user['role'] === ROLE_ADMIN) {
        return ['success' => false, 'error' => 'Admin accounts cannot be deleted.'];
    }

    removeUserAvatarFile($user['avatar_path'] ?? null);

    $stmt = db()->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return ['success' => true, 'message' => 'User deleted successfully.'];
}

function employerIndustries(): array
{
    return [
        'technology' => 'Technology',
        'finance' => 'Finance',
        'healthcare' => 'Healthcare',
        'education' => 'Education',
    ];
}

function fetchEmployerProfile(int $employerId): ?array
{
    ensureUsersSchema();

    $stmt = db()->prepare(
        "SELECT id, email, company_name, industry, company_about, avatar_path
         FROM users
         WHERE id = :id AND role = 'employer'
         LIMIT 1"
    );
    $stmt->execute(['id' => $employerId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    return $profile ?: null;
}

function updateEmployerProfile(int $employerId, array $data): array
{
    ensureUsersSchema();

    $profile = fetchEmployerProfile($employerId);
    if (!$profile) {
        return ['success' => false, 'error' => 'Employer profile not found.'];
    }

    $companyName = trim($data['company_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $industry = trim($data['industry'] ?? '');
    $companyAbout = trim($data['company_about'] ?? '');

    if ($companyName === '') {
        return ['success' => false, 'error' => 'Company name is required.'];
    }

    if ($email === '') {
        return ['success' => false, 'error' => 'Company email is required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }

    $allowedIndustries = employerIndustries();
    if ($industry !== '' && !isset($allowedIndustries[$industry])) {
        return ['success' => false, 'error' => 'Invalid industry selected.'];
    }

    $duplicate = db()->prepare(
        'SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1'
    );
    $duplicate->execute(['email' => $email, 'id' => $employerId]);
    if ($duplicate->fetch()) {
        return ['success' => false, 'error' => 'That email is already used by another account.'];
    }

    $avatarFile = $data['avatar_file'] ?? null;
    $removeAvatar = !empty($data['remove_avatar']);
    $avatarResult = processUserAvatarUpdate(
        $employerId,
        is_array($avatarFile) ? $avatarFile : null,
        $removeAvatar,
        $profile['avatar_path'] ?? null
    );

    if (!$avatarResult['success']) {
        return $avatarResult;
    }

    $avatarPath = $avatarResult['path'];

    try {
        $stmt = db()->prepare(
            'UPDATE users SET company_name = :company_name, email = :email,
             industry = :industry, company_about = :company_about, avatar_path = :avatar_path
             WHERE id = :id AND role = :role'
        );
        $stmt->execute([
            'company_name' => $companyName,
            'email' => $email,
            'industry' => $industry !== '' ? $industry : null,
            'company_about' => $companyAbout !== '' ? $companyAbout : null,
            'avatar_path' => $avatarPath,
            'id' => $employerId,
            'role' => ROLE_EMPLOYER,
        ]);

        if ($companyName !== trim($profile['company_name'] ?? '')) {
            db()->prepare('UPDATE jobs SET company_name = :company_name WHERE employer_id = :employer_id')
                ->execute([
                    'company_name' => $companyName,
                    'employer_id' => $employerId,
                ]);
        }
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not save settings. Please try again.'];
    }

    return ['success' => true, 'message' => 'Settings saved successfully.'];
}
