<?php
/**
 * Simple session flash messages for admin actions.
 */

require_once __DIR__ . '/auth.php';

function flashSet(string $type, string $message): void
{
    authStartSession();
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function flashGet(): ?array
{
    authStartSession();

    if (empty($_SESSION['admin_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);

    return $flash;
}

function renderAdminFlash(): void
{
    $flash = flashGet();
    if (!$flash) {
        return;
    }

    $type = $flash['type'] === 'error' ? 'error' : 'success';
    ?>
    <div class="admin-flash admin-flash--<?= htmlspecialchars($type) ?>" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php
}
