/**
 * Jagiree Admin Dashboard
 */

document.addEventListener('DOMContentLoaded', () => {
  initActivityChart();
  initChartToggle();
});

function initActivityChart() {
  const canvas = document.getElementById('activityChart');
  if (!canvas || typeof Chart === 'undefined') return;

  const ctx = canvas.getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 280);
  gradient.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
  gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
      datasets: [{
        label: 'Engagement',
        data: [1200, 1900, 1500, 2200, 2800, 2400, 3200, 3800],
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
          grid: { color: '#f1f5f9' },
          ticks: { color: '#94a3b8', font: { size: 12 }, maxTicksLimit: 5 },
          border: { display: false },
        },
      },
    },
  });
}

function initChartToggle() {
  const buttons = document.querySelectorAll('.chart-toggle-btn');
  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      buttons.forEach((b) => b.classList.remove('is-active'));
      btn.classList.add('is-active');
    });
  });
}
