<?php
/**
 * Session-based authentication helpers.
 */

require_once __DIR__ . '/database.php';

function authStartSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name(SESSION_NAME);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

function currentUser(): ?array
{
    authStartSession();

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    static $loadedId = null;

    if ($user !== null && $loadedId === (int) $_SESSION['user_id']) {
        return $user;
    }

    try {
        $stmt = db()->prepare(
            'SELECT id, username, email, full_name, company_name, avatar_path, role, created_at
             FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => (int) $_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        $loadedId = $user ? (int) $user['id'] : null;
    } catch (PDOException) {
        return null;
    }

    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['role']);
    }

    return $user;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function hasRole(string $role): bool
{
    $user = currentUser();
    return $user !== null && $user['role'] === $role;
}

function dashboardPathForRole(string $role): string
{
    return DASHBOARD_PATHS[$role] ?? '/';
}

function loginUser(array $user): void
{
    authStartSession();
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = $user['role'];
}

function logoutUser(): void
{
    authStartSession();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function requireGuest(): void
{
    if (!isLoggedIn()) {
        return;
    }

    $user = currentUser();
    header('Location: ' . dashboardPathForRole($user['role']));
    exit;
}

function requireRole(string $role): void
{
    authStartSession();

    if (!isLoggedIn()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?redirect=' . $redirect);
        exit;
    }

    $user = currentUser();
    if ($user['role'] !== $role) {
        header('Location: ' . dashboardPathForRole($user['role']));
        exit;
    }
}

function attemptLogin(string $login, string $password): array
{
    $login = trim($login);

    if ($login === '' || $password === '') {
        return ['success' => false, 'error' => 'Please enter your username or email and password.'];
    }

    try {
        $stmt = db()->prepare(
            'SELECT id, username, email, full_name, company_name, role, password_hash
             FROM users WHERE username = :login OR email = :login LIMIT 1'
        );
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch();
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Database unavailable. Run: php scripts/setup.php'];
    }

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid username, email, or password.'];
    }

    unset($user['password_hash']);
    loginUser($user);

    return ['success' => true, 'user' => $user];
}

function usernameExists(string $username): bool
{
    $stmt = db()->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);

    return (bool) $stmt->fetch();
}

function resolveUsername(string $username, string $email): array
{
    $username = trim($username);

    if ($username !== '') {
        if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'error' => 'Username must be at least 3 characters (letters, numbers, underscore only).'];
        }

        if (usernameExists($username)) {
            return ['success' => false, 'error' => 'That username is already taken.'];
        }

        return ['success' => true, 'username' => $username];
    }

    $local = strtolower(explode('@', $email)[0] ?? 'user');
    $base = preg_replace('/[^a-z0-9_]+/', '_', $local);
    $base = trim($base, '_');

    if (strlen($base) < 3) {
        $base = 'user_' . substr(md5($email), 0, 6);
    }

    $candidate = $base;
    $suffix = 0;

    while (usernameExists($candidate)) {
        $suffix++;
        $candidate = $base . $suffix;
    }

    return ['success' => true, 'username' => $candidate];
}

function registerUser(array $data, string $role): array
{
    if (!in_array($role, [ROLE_EMPLOYER, ROLE_SEEKER], true)) {
        return ['success' => false, 'error' => 'Invalid registration type.'];
    }

    $fullName = trim($data['full_name'] ?? '');
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirm = $data['password_confirm'] ?? '';
    $companyName = trim($data['company_name'] ?? '');

    if ($fullName === '' || $email === '' || $password === '') {
        return ['success' => false, 'error' => 'Please fill in all required fields.'];
    }

    if ($role === ROLE_EMPLOYER && $companyName === '') {
        return ['success' => false, 'error' => 'Company name is required for employers.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    if ($password !== $confirm) {
        return ['success' => false, 'error' => 'Passwords do not match.'];
    }

    try {
        require_once __DIR__ . '/users.php';
        ensureUsersSchema();

        $emailCheck = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $emailCheck->execute(['email' => $email]);
        if ($emailCheck->fetch()) {
            return ['success' => false, 'error' => 'That email is already registered.'];
        }

        $usernameResult = resolveUsername($username, $email);
        if (!$usernameResult['success']) {
            return $usernameResult;
        }

        $username = $usernameResult['username'];

        $stmt = db()->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, company_name, role, status)
             VALUES (:username, :email, :password_hash, :full_name, :company_name, :role, :status)'
        );
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'company_name' => $role === ROLE_EMPLOYER ? $companyName : null,
            'role' => $role,
            'status' => 'verified',
        ]);
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Database unavailable. Run: php scripts/setup.php'];
    }

    $userId = (int) db()->lastInsertId();
    $user = [
        'id' => $userId,
        'username' => $username,
        'email' => $email,
        'full_name' => $fullName,
        'company_name' => $role === ROLE_EMPLOYER ? $companyName : null,
        'role' => $role,
    ];

    loginUser($user);

    return ['success' => true, 'user' => $user];
}

function displayName(?array $user): string
{
    if (!$user) {
        return 'User';
    }

    return $user['full_name'] ?: $user['username'];
}
