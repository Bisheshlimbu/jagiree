<?php
require_once __DIR__ . '/../../includes/users.php';
require_once __DIR__ . '/../../includes/flash.php';

$pageTitle = 'Users — Jagiree Admin';
$activePage = 'users';
$pageHeading = 'Users';
require_once __DIR__ . '/../../includes/admin/layout-start.php';

$users = fetchAdminUsers();
$userCount = count($users);
?>

<?php renderAdminFlash(); ?>

<section class="panel panel--table">
    <div class="panel-header">
        <h2>All Users <span class="panel-count">(<?= $userCount ?>)</span></h2>
        <a href="/admin/user-add.php" class="btn-sm btn-sm--primary">Add User</a>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Company Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users === []): ?>
                <tr>
                    <td colspan="5" class="table-empty">No users found. Registered seekers and employers will appear here.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php require __DIR__ . '/../../includes/admin/user-table-row.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
