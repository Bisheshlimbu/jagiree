document.addEventListener('DOMContentLoaded', () => {
  const menu = document.getElementById('notifMenu');
  if (!menu) {
    return;
  }

  const apiUrl = menu.dataset.notifApi;
  const btn = document.getElementById('notifMenuBtn');
  const panel = document.getElementById('notifMenuPanel');
  const list = document.getElementById('notifMenuList');
  const badge = document.getElementById('notifMenuBadge');
  const markAllBtn = document.getElementById('notifMarkAllBtn');

  if (!apiUrl || !btn || !panel || !list || !badge) {
    return;
  }

  let isOpen = false;
  let notifications = [];

  btn.addEventListener('click', async (event) => {
    event.stopPropagation();
    isOpen = !isOpen;
    panel.hidden = !isOpen;
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

    if (isOpen) {
      await loadNotifications();
    }
  });

  markAllBtn?.addEventListener('click', async (event) => {
    event.preventDefault();
    await markAllRead();
  });

  document.addEventListener('click', (event) => {
    if (!menu.contains(event.target)) {
      closePanel();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closePanel();
    }
  });

  async function loadNotifications() {
    try {
      const response = await fetch(apiUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await response.json();

      if (!data.success) {
        return;
      }

      notifications = data.notifications || [];
      updateBadge(data.unread ?? 0);
      renderList(notifications);
    } catch (error) {
      list.innerHTML = '<p class="notif-menu__empty">Could not load notifications.</p>';
    }
  }

  function renderList(items) {
    if (!items.length) {
      list.innerHTML = '<p class="notif-menu__empty">No notifications yet.</p>';
      markAllBtn.hidden = true;
      return;
    }

    const hasUnread = items.some((item) => !item.is_read);
    markAllBtn.hidden = !hasUnread;

    list.innerHTML = items.map((item) => {
      const unreadClass = item.is_read ? '' : ' is-unread';
      const content = `
        <div class="notif-item__content">
          <strong>${escapeHtml(item.title)}</strong>
          <p>${escapeHtml(item.message)}</p>
          <span>${escapeHtml(item.time || '')}</span>
        </div>
      `;

      if (item.link) {
        return `
          <a href="${escapeHtml(item.link)}" class="notif-item${unreadClass}" data-notification-id="${item.id}">
            ${content}
          </a>
        `;
      }

      return `
        <button type="button" class="notif-item${unreadClass}" data-notification-id="${item.id}">
          ${content}
        </button>
      `;
    }).join('');

    list.querySelectorAll('[data-notification-id]').forEach((el) => {
      el.addEventListener('click', () => {
        const id = el.dataset.notificationId;
        if (id && !el.classList.contains('is-read')) {
          markRead(id);
        }
      });
    });
  }

  async function markRead(notificationId) {
    const formData = new FormData();
    formData.set('action', 'mark_read');
    formData.set('notification_id', notificationId);

    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });
      const data = await response.json();

      if (data.success) {
        updateBadge(data.unread ?? 0);
        renderList(data.notifications || []);
      }
    } catch (error) {
      // Ignore — navigation can continue
    }
  }

  async function markAllRead() {
    const formData = new FormData();
    formData.set('action', 'mark_all_read');

    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });
      const data = await response.json();

      if (data.success) {
        updateBadge(0);
        renderList(data.notifications || []);
      }
    } catch (error) {
      // Ignore
    }
  }

  function updateBadge(count) {
    if (count <= 0) {
      badge.hidden = true;
      badge.textContent = '0';
      return;
    }

    badge.hidden = false;
    badge.textContent = count > 9 ? '9+' : String(count);
  }

  function closePanel() {
    isOpen = false;
    panel.hidden = true;
    btn.setAttribute('aria-expanded', 'false');
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
});
