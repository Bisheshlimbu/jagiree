<?php
$pageTitle = 'Users — Jagiree Admin';
$activePage = 'users';
$pageHeading = 'Users';
require_once __DIR__ . '/../../includes/admin/layout-start.php';

$users = [
    ['name' => 'Sarah Jenkins', 'email' => 'sarah.j@email.com', 'role' => 'Seeker', 'location' => 'Kathmandu, Nepal', 'status' => 'verified', 'avatar' => 32],
    ['name' => 'Marcus Thorne', 'email' => 'm.thorne@corp.io', 'role' => 'Employer', 'location' => 'Lalitpur, Nepal', 'status' => 'verified', 'avatar' => 15],
    ['name' => 'Elena Rodriguez', 'email' => 'elena.r@design.net', 'role' => 'Seeker', 'location' => 'Pokhara, Nepal', 'status' => 'pending', 'avatar' => 45],
    ['name' => 'David Chen', 'email' => 'd.chen@tech.dev', 'role' => 'Employer', 'location' => 'Remote', 'status' => 'verified', 'avatar' => 68],
    ['name' => 'Amira Gurung', 'email' => 'amira@email.com', 'role' => 'Seeker', 'location' => 'Bhaktapur, Nepal', 'status' => 'verified', 'avatar' => 25],
];
?>

<section class="panel panel--table">
    <div class="panel-header">
        <h2>All Users</h2>
        <button type="button" class="btn-sm btn-sm--primary">Add User</button>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div class="table-user">
                            <img src="https://i.pravatar.cc/80?img=<?= $user['avatar'] ?>" alt="">
                            <div>
                                <strong><?= htmlspecialchars($user['name']) ?></strong>
                                <span><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= htmlspecialchars($user['location']) ?></td>
                    <td>
                        <span class="status-badge status-badge--<?= $user['status'] ?>">
                            <?= $user['status'] === 'verified' ? 'Verified' : 'Pending' ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button type="button" class="table-action-btn" aria-label="Edit user">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button type="button" class="table-action-btn table-action-btn--danger" aria-label="Delete user">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
