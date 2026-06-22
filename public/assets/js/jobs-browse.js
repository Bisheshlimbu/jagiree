document.addEventListener('DOMContentLoaded', () => {
  const closeBtn = document.querySelector('[data-close-detail]');
  if (!closeBtn || !window.jobsBrowseCloseUrl) {
    return;
  }

  closeBtn.addEventListener('click', () => {
    window.location.href = window.jobsBrowseCloseUrl;
  });
});
