document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('employerAppModal');

  if (!modal) {
    return;
  }

  const titleEl = document.getElementById('employerAppModalTitle');
  const subtitleEl = document.getElementById('employerAppModalSubtitle');
  const bodyEl = document.getElementById('employerAppModalBody');
  const footerEl = document.getElementById('employerAppModalFooter');
  const statusSelect = document.getElementById('employerAppModalStatus');
  const cvLink = document.getElementById('employerAppModalCvLink');
  const noCvEl = document.getElementById('employerAppModalNoCv');

  let activeApplicationId = null;

  document.addEventListener('click', async (event) => {
    const viewButton = event.target.closest('[data-view-application]');
    if (viewButton) {
      event.preventDefault();
      const applicationId = viewButton.dataset.viewApplication;
      if (applicationId) {
        await openApplicationModal(applicationId);
      }
      return;
    }

    if (event.target.closest('[data-close-employer-app-modal]')) {
      closeModal();
    }
  });

  document.addEventListener('focusin', (event) => {
    const select = event.target.closest('.app-status-select, .employer-app-modal__status-select');
    if (select) {
      select.dataset.previousValue = select.value;
    }
  });

  document.addEventListener('change', async (event) => {
    const select = event.target.closest('.app-status-select, .employer-app-modal__status-select');
    if (!select || select.disabled) {
      return;
    }

    const applicationId = select.dataset.applicationId;
    if (!applicationId) {
      return;
    }

    const newStatus = select.value;
    const previousStatus = select.dataset.previousValue ?? select.value;

    const success = await updateApplicationStatus(applicationId, newStatus, select);
    if (success) {
      select.dataset.previousValue = newStatus;
    } else {
      select.value = previousStatus;
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });

  async function openApplicationModal(applicationId) {
    activeApplicationId = applicationId;
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('employer-app-modal-open');
    footerEl.hidden = true;
    bodyEl.innerHTML = '<p class="employer-app-modal__loading">Loading application…</p>';
    titleEl.textContent = 'Applicant';
    subtitleEl.textContent = '';

    try {
      const response = await fetch(`/employer/api/application-context.php?application_id=${encodeURIComponent(applicationId)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await response.json();

      if (!data.success) {
        bodyEl.innerHTML = `<p class="employer-app-modal__error">${escapeHtml(data.error || 'Could not load application.')}</p>`;
        return;
      }

      renderModal(data);
    } catch (error) {
      bodyEl.innerHTML = '<p class="employer-app-modal__error">Could not load application. Please try again.</p>';
    }
  }

  function renderModal(data) {
    const app = data.application || {};
    const job = data.job || {};
    const seeker = data.seeker || {};
    const options = data.status_options || {};

    titleEl.textContent = seeker.name || 'Applicant';
    subtitleEl.textContent = [job.title, job.location].filter(Boolean).join(' · ');

    statusSelect.innerHTML = '';
    statusSelect.disabled = false;
    Object.entries(options).forEach(([value, label]) => {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = label;
      option.selected = app.status === value;
      statusSelect.appendChild(option);
    });
    statusSelect.dataset.applicationId = String(app.id || '');
    statusSelect.dataset.previousValue = app.status || 'new';

    if (app.cv_path) {
      cvLink.href = app.cv_path;
      cvLink.hidden = false;
      if (noCvEl) {
        noCvEl.hidden = true;
      }
    } else {
      cvLink.hidden = true;
      if (noCvEl) {
        noCvEl.hidden = false;
      }
    }

    const skills = (seeker.skills || []).map((skill) => `<span class="skill-pill">${escapeHtml(skill)}</span>`).join('');
    const experience = (seeker.experience || []).slice(0, 3).map((item) => `
      <li>
        <strong>${escapeHtml(item.title || '')}</strong>
        <span>${escapeHtml(item.company || '')}</span>
      </li>
    `).join('');
    const education = (seeker.education || []).slice(0, 2).map((item) => `
      <li>
        <strong>${escapeHtml(item.school || '')}</strong>
        <span>${escapeHtml(item.degree || '')}</span>
      </li>
    `).join('');

    bodyEl.innerHTML = `
      <div class="employer-app-review">
        <div class="employer-app-review__hero">
          ${seeker.has_avatar
            ? `<img src="${escapeHtml(seeker.avatar_url)}" alt="" class="employer-app-review__avatar">`
            : `<span class="employer-app-review__avatar employer-app-review__avatar--initials" style="background:${escapeHtml(seeker.color || '#0a66c2')}">${escapeHtml(seeker.initials || 'A')}</span>`
          }
          <div>
            <h3>${escapeHtml(seeker.name || 'Applicant')}</h3>
            <p>${escapeHtml(seeker.headline || '')}</p>
            <p class="employer-app-review__meta">${escapeHtml(seeker.location || '')}${seeker.open_to_work ? ' · Open to work' : ''}</p>
          </div>
          <div class="employer-app-review__match">
            <strong>${Number(app.match || 0)}%</strong>
            <span>Match score</span>
          </div>
        </div>

        <div class="employer-app-review__grid">
          <section>
            <h4>Applied for</h4>
            <p><strong>${escapeHtml(job.title || '')}</strong></p>
            <p class="text-muted">${escapeHtml(job.company || '')} · ${escapeHtml(job.location || '')}</p>
            <p class="text-muted">Applied ${escapeHtml(app.date || '')}</p>
          </section>
          <section>
            <h4>Skills</h4>
            <div class="skill-pill-list">${skills || '<span class="text-muted">No skills listed</span>'}</div>
          </section>
        </div>

        ${seeker.about ? `<section class="employer-app-review__section"><h4>About</h4><p>${escapeHtml(seeker.about)}</p></section>` : ''}

        ${app.has_cover_letter ? `
          <section class="employer-app-review__section">
            <h4>Cover letter</h4>
            <div class="employer-app-review__cover-letter">${escapeHtml(app.cover_letter).replace(/\n/g, '<br>')}</div>
          </section>
        ` : '<section class="employer-app-review__section"><h4>Cover letter</h4><p class="text-muted">No cover letter provided.</p></section>'}

        ${experience ? `<section class="employer-app-review__section"><h4>Experience</h4><ul class="employer-app-review__list">${experience}</ul></section>` : ''}
        ${education ? `<section class="employer-app-review__section"><h4>Education</h4><ul class="employer-app-review__list">${education}</ul></section>` : ''}
      </div>
    `;

    footerEl.hidden = false;
  }

  async function updateApplicationStatus(applicationId, status, sourceSelect) {
    const selects = document.querySelectorAll(`.app-status-select[data-application-id="${applicationId}"]`);
    selects.forEach((el) => {
      el.disabled = true;
    });
    if (statusSelect) {
      statusSelect.disabled = true;
    }

    const formData = new FormData();
    formData.set('action', 'update_status');
    formData.set('application_id', applicationId);
    formData.set('status', status);

    try {
      const response = await fetch('/employer/api/application.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });
      const data = await response.json();

      if (!data.success) {
        showToast(data.error || 'Could not update status.', true);
        return false;
      }

      syncStatusSelects(applicationId, status);
      if (sourceSelect) {
        sourceSelect.dataset.previousValue = status;
      }
      if (statusSelect && statusSelect.dataset.applicationId === String(applicationId)) {
        statusSelect.value = status;
        statusSelect.dataset.previousValue = status;
      }
      showToast(data.message || 'Status updated.');
      return true;
    } catch (error) {
      showToast('Could not update status. Please try again.', true);
      return false;
    } finally {
      selects.forEach((el) => {
        el.disabled = false;
      });
      if (statusSelect) {
        statusSelect.disabled = false;
      }
    }
  }

  function syncStatusSelects(applicationId, status) {
    document.querySelectorAll(`.app-status-select[data-application-id="${applicationId}"]`).forEach((el) => {
      el.value = status;
    });
  }

  function closeModal() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('employer-app-modal-open');
    activeApplicationId = null;
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function showToast(message, isError = false) {
    let toast = document.getElementById('employerAppToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'employerAppToast';
      toast.className = 'employer-app-toast';
      document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.classList.toggle('employer-app-toast--error', isError);
    toast.classList.add('is-visible');

    window.clearTimeout(showToast.hideTimer);
    showToast.hideTimer = window.setTimeout(() => {
      toast.classList.remove('is-visible');
    }, 3200);
  }
});
