/**
 * Jagiree Admin Analytics
 */

const chartInstances = {};

document.addEventListener('DOMContentLoaded', () => {
  const data = window.adminAnalyticsData;
  if (!data || typeof Chart === 'undefined') return;

  initLineChart(
    'registrationsChart',
    'registrations',
    data.charts.registrations.monthly,
    '#2563eb',
    'New registrations'
  );

  initLineChart(
    'jobsChart',
    'jobs',
    data.charts.jobs.monthly,
    '#14b8a6',
    'Jobs posted',
    true
  );

  initDoughnutChart(
    'usersRoleChart',
    data.breakdowns.usersByRole,
    ['#2563eb', '#8b5cf6']
  );

  initDoughnutChart(
    'usersStatusChart',
    data.breakdowns.usersByStatus,
    ['#10b981', '#f59e0b']
  );

  initDoughnutChart(
    'jobsStatusChart',
    data.breakdowns.jobsByStatus,
    ['#10b981', '#8b5cf6', '#ef4444']
  );

  initAnalyticsChartToggles(data);
});

function initLineChart(canvasId, key, series, color, label, useBar = false) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const fillColor = useBar
    ? color
    : createLineGradient(ctx, color);

  chartInstances[key] = new Chart(ctx, {
    type: useBar ? 'bar' : 'line',
    data: {
      labels: series.labels,
      datasets: [{
        label,
        data: series.data,
        borderColor: color,
        backgroundColor: fillColor,
        borderWidth: useBar ? 0 : 2.5,
        fill: !useBar,
        tension: 0.42,
        pointRadius: 0,
        pointHoverRadius: 5,
        borderRadius: useBar ? 8 : 0,
      }],
    },
    options: baseChartOptions(),
  });
}

function createLineGradient(ctx, color) {
  const gradient = ctx.createLinearGradient(0, 0, 0, 280);
  const start = color === '#14b8a6' ? 'rgba(20, 184, 166, 0.25)' : 'rgba(37, 99, 235, 0.25)';
  gradient.addColorStop(0, start);
  gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');
  return gradient;
}

function initDoughnutChart(canvasId, series, colors) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  new Chart(canvas.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: series.labels,
      datasets: [{
        data: series.data,
        backgroundColor: colors,
        borderWidth: 0,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 12,
            boxHeight: 12,
            padding: 16,
            color: '#64748b',
            font: { size: 12 },
          },
        },
      },
    },
  });
}

function baseChartOptions() {
  return {
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
  };
}

function initAnalyticsChartToggles(data) {
  document.querySelectorAll('.chart-toggle-btn[data-chart]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const chartKey = btn.dataset.chart;
      const period = btn.dataset.period === 'weekly' ? 'weekly' : 'monthly';
      const group = btn.closest('.chart-toggle');

      group.querySelectorAll('.chart-toggle-btn').forEach((item) => {
        item.classList.remove('is-active');
      });
      btn.classList.add('is-active');

      const chart = chartInstances[chartKey];
      const series = data.charts[chartKey][period];
      if (!chart || !series) return;

      chart.data.labels = series.labels;
      chart.data.datasets[0].data = series.data;
      chart.update();
    });
  });
}
