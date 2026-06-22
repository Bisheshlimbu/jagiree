/**
 * Jagiree Admin Dashboard
 */

let activityChartInstance = null;

document.addEventListener('DOMContentLoaded', () => {
  initMobileNav();
  initActivityChart();
  initChartToggle();
});

function initMobileNav() {
  const menuBtn = document.querySelector('.topbar-menu-btn');
  const sidebar = document.querySelector('.admin-sidebar');
  const backdrop = document.querySelector('.sidebar-backdrop');
  const sidebarLinks = sidebar?.querySelectorAll('a') ?? [];

  function isMobileNav() {
    return window.matchMedia('(max-width: 900px)').matches;
  }

  function setSidebarOpen(open) {
    if (!sidebar || !isMobileNav()) {
      sidebar?.classList.remove('is-open');
      backdrop?.classList.remove('is-visible');
      document.body.classList.remove('admin-nav-open');
      menuBtn?.setAttribute('aria-expanded', 'false');
      return;
    }

    sidebar.classList.toggle('is-open', open);
    backdrop?.classList.toggle('is-visible', open);
    document.body.classList.toggle('admin-nav-open', open);
    menuBtn?.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function closeSidebar() {
    setSidebarOpen(false);
  }

  menuBtn?.addEventListener('click', () => {
    setSidebarOpen(!sidebar?.classList.contains('is-open'));
  });

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
}

function initActivityChart() {
  const canvas = document.getElementById('activityChart');
  const chartData = window.adminDashboardChart;
  if (!canvas || typeof Chart === 'undefined' || !chartData) return;

  const ctx = canvas.getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 280);
  gradient.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
  gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

  const initial = chartData.monthly || { labels: [], data: [] };

  activityChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: initial.labels,
      datasets: [{
        label: 'New registrations',
        data: initial.data,
        borderColor: '#2563eb',
        backgroundColor: gradient,
        borderWidth: 2.5,
        fill: true,
        tension: 0.42,
        pointRadius: 0,
        pointHoverRadius: 5,
        pointHoverBackgroundColor: '#2563eb',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0f172a',
          padding: 12,
          cornerRadius: 8,
          displayColors: false,
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#94a3b8', font: { size: 12 } },
          border: { display: false },
        },
        y: {
          beginAtZero: true,
          grid: { color: '#f1f5f9' },
          ticks: {
            color: '#94a3b8',
            font: { size: 12 },
            maxTicksLimit: 5,
            precision: 0,
          },
          border: { display: false },
        },
      },
    },
  });
}

function initChartToggle() {
  const buttons = document.querySelectorAll('.chart-toggle-btn');
  const chartData = window.adminDashboardChart;
  if (!buttons.length || !chartData || !activityChartInstance) return;

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      buttons.forEach((b) => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      const period = btn.dataset.period === 'weekly' ? 'weekly' : 'monthly';
      const series = chartData[period] || { labels: [], data: [] };

      activityChartInstance.data.labels = series.labels;
      activityChartInstance.data.datasets[0].data = series.data;
      activityChartInstance.update();
    });
  });
}
