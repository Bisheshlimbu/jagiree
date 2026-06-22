document.addEventListener('DOMContentLoaded', () => {
  const menuBtn = document.querySelector('.topnav-menu-btn');
  const sidebar = document.querySelector('.employer-sidebar');
  const backdrop = document.querySelector('.sidebar-backdrop');
  const sidebarLinks = sidebar?.querySelectorAll('a') ?? [];

  function isMobileNav() {
    return window.matchMedia('(max-width: 900px)').matches;
  }

  function setSidebarOpen(open) {
    if (!sidebar || !isMobileNav()) {
      sidebar?.classList.remove('is-open');
      backdrop?.classList.remove('is-visible');
      document.body.classList.remove('employer-nav-open');
      menuBtn?.setAttribute('aria-expanded', 'false');
      return;
    }

    sidebar.classList.toggle('is-open', open);
    backdrop?.classList.toggle('is-visible', open);
    document.body.classList.toggle('employer-nav-open', open);
    menuBtn?.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function closeSidebar() {
    setSidebarOpen(false);
  }

  function toggleSidebar() {
    setSidebarOpen(!sidebar?.classList.contains('is-open'));
  }

  menuBtn?.addEventListener('click', toggleSidebar);
  backdrop?.addEventListener('click', closeSidebar);

  sidebarLinks.forEach((link) => {
    link.addEventListener('click', closeSidebar);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeSidebar();
    }
  });

  window.addEventListener('resize', closeSidebar);
});
