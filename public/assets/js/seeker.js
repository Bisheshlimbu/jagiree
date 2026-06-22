document.addEventListener('DOMContentLoaded', () => {
  const userMenu = document.getElementById('headerUserMenu');
  const userMenuBtn = document.getElementById('headerUserMenuBtn');
  const userMenuPanel = document.getElementById('headerUserMenuPanel');

  if (userMenu && userMenuBtn && userMenuPanel) {
    userMenuBtn.addEventListener('click', (event) => {
      event.stopPropagation();
      const isOpen = userMenu.classList.toggle('is-open');
      userMenuPanel.hidden = !isOpen;
      userMenuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', (event) => {
      if (!userMenu.contains(event.target)) {
        userMenu.classList.remove('is-open');
        userMenuPanel.hidden = true;
        userMenuBtn.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && userMenu.classList.contains('is-open')) {
        userMenu.classList.remove('is-open');
        userMenuPanel.hidden = true;
        userMenuBtn.setAttribute('aria-expanded', 'false');
        userMenuBtn.focus();
      }
    });
  }

  document.querySelectorAll('.filter-chip').forEach((chip) => {
    chip.addEventListener('click', () => {
      chip.closest('.filter-chips')?.querySelectorAll('.filter-chip').forEach((c) => c.classList.remove('is-active'));
      chip.classList.add('is-active');
    });
  });

  document.querySelectorAll('.job-save-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      btn.classList.toggle('is-saved');
      const svg = btn.querySelector('svg');
      if (svg) svg.setAttribute('fill', btn.classList.contains('is-saved') ? 'currentColor' : 'none');
    });
  });
});
