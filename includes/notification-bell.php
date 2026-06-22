<?php
/**
 * Notification bell dropdown for authenticated portal headers.
 * Expects $notifApiUrl, $notifUserId, and optional $notifButtonClass.
 */
$notifUserId = (int) ($notifUserId ?? 0);
$notifApiUrl = $notifApiUrl ?? '';
$notifButtonClass = $notifButtonClass ?? 'header-icon-btn';
$notifUnreadCount = $notifUserId > 0 ? countUnreadNotifications($notifUserId) : 0;
$notifBadgeLabel = $notifUnreadCount > 9 ? '9+' : (string) $notifUnreadCount;
?>
<div class="notif-menu" id="notifMenu" data-notif-api="<?= htmlspecialchars($notifApiUrl) ?>">
    <button
        type="button"
        class="<?= htmlspecialchars($notifButtonClass) ?> notif-menu__btn"
        id="notifMenuBtn"
        aria-label="Notifications"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="notifMenuPanel"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span class="notif-menu__badge" id="notifMenuBadge" <?= $notifUnreadCount === 0 ? 'hidden' : '' ?>><?= htmlspecialchars($notifBadgeLabel) ?></span>
    </button>
    <div class="notif-menu__panel" id="notifMenuPanel" hidden>
        <div class="notif-menu__header">
            <strong>Notifications</strong>
            <button type="button" class="notif-menu__mark-all" id="notifMarkAllBtn" hidden>Mark all read</button>
        </div>
        <div class="notif-menu__list" id="notifMenuList">
            <p class="notif-menu__empty">No notifications yet.</p>
        </div>
    </div>
</div>
