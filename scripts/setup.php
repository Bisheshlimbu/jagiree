<?php
/**
 * Initialize SQLite database and seed the default admin user.
 * Usage: php scripts/setup.php
 *
 * No MySQL/XAMPP/MAMP needed — creates database/jagiree.sqlite locally.
 */

require_once __DIR__ . '/../includes/config.php';

$adminUsername = 'bishesh';
$adminPassword = 'adminBishesh';
$adminEmail = 'admin@jagiree.local';
$adminName = 'Bishesh Admin';

echo "Jagiree database setup (SQLite)\n";
echo "===============================\n\n";

try {
    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $schema = file_get_contents(__DIR__ . '/../sql/schema.sqlite.sql');
    $pdo->exec($schema);

    echo "Database file: " . DB_PATH . "\n";
    echo "Tables created.\n";

    $stmt = $pdo->prepare('SELECT id FROM users WHERE role = :role LIMIT 1');
    $stmt->execute(['role' => ROLE_ADMIN]);
    $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingAdmin) {
        echo "Admin user already exists (id: {$existingAdmin['id']}). Skipping seed.\n";
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, company_name, role, status)
             VALUES (:username, :email, :password_hash, :full_name, NULL, :role, :status)'
        );
        $insert->execute([
            'username' => $adminUsername,
            'email' => $adminEmail,
            'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
            'full_name' => $adminName,
            'role' => ROLE_ADMIN,
            'status' => 'verified',
        ]);

        echo "Admin user created.\n";
        echo "  Username: {$adminUsername}\n";
        echo "  Password: {$adminPassword}\n";
    }

    echo "\nSetup complete. Start the server with: php -S localhost:8000 router.php\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Setup failed: " . $e->getMessage() . "\n");
    exit(1);
}
