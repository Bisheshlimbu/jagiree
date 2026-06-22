<?php
/**
 * Shared admin user table row partial.
 *
 * Expects $user array from formatAdminUserRow().
 */
?>
<tr>
    <td>
        <div class="table-user">
            <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="" class="<?= !empty($user['has_avatar']) ? '' : 'user-avatar--placeholder' ?>">
            <div>
                <strong><?= htmlspecialchars($user['name']) ?></strong>
                <span><?= htmlspecialchars($user['email']) ?></span>
            </div>
        </div>
    </td>
    <td><?= htmlspecialchars($user['role']) ?></td>
    <td><?= htmlspecialchars($user['company_name']) ?></td>
    <td>
        <span class="status-badge status-badge--<?= htmlspecialchars($user['status']) ?>">
            <?= htmlspecialchars(userStatusLabel($user['status'])) ?>
        </span>
    </td>
    <td>
        <div class="table-actions">
            <a href="/admin/user-edit.php?id=<?= (int) $user['id'] ?>" class="table-action-btn" aria-label="Edit user">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
            <?php if (($user['role_key'] ?? '') !== 'admin'): ?>
            <form method="post" action="/admin/user-delete.php" class="table-action-form" onsubmit="return confirm('Delete <?= htmlspecialchars($user['name'], ENT_QUOTES) ?>? This cannot be undone.');">
                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                <button type="submit" class="table-action-btn table-action-btn--danger" aria-label="Delete user">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </td>
</tr>
