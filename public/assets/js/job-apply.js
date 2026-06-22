document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('applyModal');
  if (!modal) {
    return;
  }

  const form = document.getElementById('applyModalForm');
  const eyebrowEl = document.getElementById('applyModalEyebrow');
  const jobIdInput = document.getElementById('applyModalJobId');
  const applicationIdInput = document.getElementById('applyModalApplicationId');
  const titleEl = document.getElementById('applyModalTitle');
  const subtitleEl = document.getElementById('applyModalSubtitle');
  const coverLetterEl = document.getElementById('applyCoverLetter');
  const cvInput = document.getElementById('applyCvInput');
  const cvEmpty = document.getElementById('applyCvEmpty');
  const cvCurrent = document.getElementById('applyCvCurrent');
  const cvFilename = document.getElementById('applyCvFilename');
  const cvUpdated = document.getElementById('applyCvUpdated');
  const cvViewLink = document.getElementById('applyCvViewLink');
  const cvPending = document.getElementById('applyCvPending');
  const cvUploadLabel = document.getElementById('applyCvUploadLabel');
  const errorEl = document.getElementById('applyModalError');
  const submitBtn = document.getElementById('applyModalSubmit');

  let activeJobId = null;
  let activeApplicationId = null;
  let modalMode = 'apply';
  let activeButtons = [];

  document.addEventListener('click', async (event) => {
    const editButton = event.target.closest('[data-edit-application]');
    if (editButton && !editButton.disabled) {
      event.preventDefault();
      await openEditModal(editButton.dataset.editApplication);
      return;
    }

    const deleteButton = event.target.closest('[data-delete-application]');
    if (deleteButton && !deleteButton.disabled) {
      event.preventDefault();
      await withdrawApplication(deleteButton);
      return;
    }

    const button = event.target.closest('[data-apply-job]');
    if (!button || button.disabled || button.classList.contains('is-applied')) {
      return;
    }

    event.preventDefault();
    await openApplyModal(button);
  });

  modal.querySelectorAll('[data-close-apply-modal]').forEach((el) => {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });

  cvInput?.addEventListener('change', () => {
    const file = cvInput.files?.[0];
    if (!file) {
      cvPending.hidden = true;
      cvPending.textContent = '';
      return;
    }

    cvPending.hidden = false;
    cvPending.textContent = `New file selected: ${file.name}`;
    cvUploadLabel.textContent = 'Replace CV file';
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (modalMode === 'edit') {
      if (!activeApplicationId) {
        return;
      }
      await submitEdit();
      return;
    }

    if (!activeJobId) {
      return;
    }

    await submitApply();
  });

  async function openApplyModal(button) {
    const jobId = button.dataset.applyJob;
    if (!jobId) {
      return;
    }

    setMode('apply');
    activeJobId = jobId;
    activeApplicationId = null;
    activeButtons = Array.from(document.querySelectorAll(`[data-apply-job="${jobId}"]`));

    const title = button.dataset.jobTitle || 'Apply for job';
    const company = button.dataset.jobCompany || '';

    openModal();
    resetForm();
    titleEl.textContent = title;
    subtitleEl.textContent = company;
    jobIdInput.value = jobId;
    setSubmitting(false);

    try {
      const response = await fetch(`/seeker/api/apply-context.php?job_id=${encodeURIComponent(jobId)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await response.json();

      if (!data.success) {
        closeModal();
        showApplyToast(data.error || 'Could not load apply form.', true);
        return;
      }

      if (data.job?.applied) {
        closeModal();
        markApplied(jobId);
        showApplyToast('You have already applied to this job.');
        return;
      }

      if (data.job?.title) {
        titleEl.textContent = data.job.title;
      }
      if (data.job?.company) {
        subtitleEl.textContent = [data.job.company, data.job.location].filter(Boolean).join(' · ');
      }

      updateCvDisplay(data.cv || {});
    } catch (error) {
      closeModal();
      showApplyToast('Could not load apply form. Please try again.', true);
    }
  }

  async function openEditModal(applicationId) {
    if (!applicationId) {
      return;
    }

    setMode('edit');
    activeApplicationId = applicationId;
    activeJobId = null;
    activeButtons = [];

    openModal();
    resetForm();
    applicationIdInput.value = applicationId;
    setSubmitting(false);

    try {
      const response = await fetch(`/seeker/api/application-context.php?application_id=${encodeURIComponent(applicationId)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await response.json();

      if (!data.success) {
        closeModal();
        showApplyToast(data.error || 'Could not load application.', true);
        return;
      }

      if (!data.application?.can_edit) {
        closeModal();
        showApplyToast('This application can no longer be edited.', true);
        return;
      }

      activeJobId = String(data.job?.id || data.application?.job_id || '');
      jobIdInput.value = activeJobId;
      titleEl.textContent = data.job?.title || 'Edit application';
      subtitleEl.textContent = [data.job?.company, data.job?.location].filter(Boolean).join(' · ');
      coverLetterEl.value = data.application?.cover_letter || '';

      const applicationCv = data.application?.cv || {};
      const profileCv = data.profile_cv || {};
      const displayCv = applicationCv.has_cv ? applicationCv : profileCv;
      updateCvDisplay(displayCv, applicationCv.has_cv ? 'CV sent with this application' : null);
    } catch (error) {
      closeModal();
      showApplyToast('Could not load application. Please try again.', true);
    }
  }

  async function submitApply() {
    hideError();
    setSubmitting(true);

    const formData = new FormData(form);

    try {
      const response = await fetch('/seeker/apply.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        markApplied(activeJobId);
        closeModal();
        showApplyToast(data.message || 'Application submitted!');
        return;
      }

      if (data.already_applied) {
        markApplied(activeJobId);
        closeModal();
        showApplyToast(data.error || 'You have already applied to this job.');
        return;
      }

      showError(data.error || 'Could not submit your application.');
    } catch (error) {
      showError('Could not submit your application. Please try again.');
    } finally {
      setSubmitting(false);
    }
  }

  async function submitEdit() {
    hideError();
    setSubmitting(true);

    const formData = new FormData(form);
    formData.set('action', 'update');
    formData.set('application_id', activeApplicationId);

    try {
      const response = await fetch('/seeker/application.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        closeModal();
        showApplyToast(data.message || 'Application updated.');
        window.setTimeout(() => window.location.reload(), 600);
        return;
      }

      showError(data.error || 'Could not update your application.');
    } catch (error) {
      showError('Could not update your application. Please try again.');
    } finally {
      setSubmitting(false);
    }
  }

  async function withdrawApplication(button) {
    const applicationId = button.dataset.deleteApplication;
    const jobTitle = button.dataset.jobTitle || 'this job';

    if (!applicationId) {
      return;
    }

    if (!window.confirm(`Withdraw your application for "${jobTitle}"? This cannot be undone.`)) {
      return;
    }

    button.disabled = true;

    const formData = new FormData();
    formData.set('action', 'delete');
    formData.set('application_id', applicationId);

    try {
      const response = await fetch('/seeker/application.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        const card = document.querySelector(`[data-application-id="${applicationId}"]`);
        card?.remove();

        if (!document.querySelector('[data-application-id]')) {
          window.location.reload();
        }

        showApplyToast(data.message || 'Application withdrawn.');
        return;
      }

      button.disabled = false;
      showApplyToast(data.error || 'Could not withdraw application.', true);
    } catch (error) {
      button.disabled = false;
      showApplyToast('Could not withdraw application. Please try again.', true);
    }
  }

  function setMode(mode) {
    modalMode = mode;
    if (eyebrowEl) {
      eyebrowEl.textContent = mode === 'edit' ? 'Edit application' : 'Easy Apply';
    }
    if (submitBtn) {
      submitBtn.textContent = mode === 'edit' ? 'Save changes' : 'Submit application';
    }
  }

  function openModal() {
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('apply-modal-open');
    window.setTimeout(() => coverLetterEl?.focus(), 50);
  }

  function closeModal() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('apply-modal-open');
    activeJobId = null;
    activeApplicationId = null;
    modalMode = 'apply';
    activeButtons = [];
    hideError();
    setSubmitting(false);
    setMode('apply');
  }

  function resetForm() {
    form?.reset();
    jobIdInput.value = activeJobId || '';
    applicationIdInput.value = activeApplicationId || '';
    cvPending.hidden = true;
    cvPending.textContent = '';
    cvUploadLabel.textContent = 'Choose CV file';
    hideError();
    updateCvDisplay({ has_cv: false });
  }

  function updateCvDisplay(cv, subtitle = null) {
    const hasCv = Boolean(cv.has_cv && cv.path);

    if (cvEmpty) {
      cvEmpty.hidden = hasCv;
      cvEmpty.textContent = modalMode === 'edit'
        ? 'No CV on file for this application. Upload one below.'
        : 'No CV attached yet. Upload one below to apply.';
    }
    if (cvCurrent) {
      cvCurrent.hidden = !hasCv;
    }
    if (hasCv) {
      if (cvFilename) {
        cvFilename.textContent = cv.filename || 'Your CV';
      }
      if (cvUpdated) {
        cvUpdated.textContent = subtitle || (cv.updated_label ? `Updated ${cv.updated_label}` : '');
      }
      if (cvViewLink) {
        cvViewLink.href = cv.path;
      }
      if (cvUploadLabel) {
        cvUploadLabel.textContent = 'Replace CV file';
      }
    } else if (cvUploadLabel) {
      cvUploadLabel.textContent = 'Choose CV file';
    }
  }

  function markApplied(jobId) {
    document.querySelectorAll(`[data-apply-job="${jobId}"]`).forEach((btn) => {
      btn.textContent = 'Applied';
      btn.classList.add('is-applied');
      btn.disabled = true;
    });
  }

  function showError(message) {
    if (!errorEl) {
      showApplyToast(message, true);
      return;
    }
    errorEl.textContent = message;
    errorEl.hidden = false;
  }

  function hideError() {
    if (errorEl) {
      errorEl.hidden = true;
      errorEl.textContent = '';
    }
  }

  function setSubmitting(isSubmitting) {
    if (!submitBtn) {
      return;
    }

    submitBtn.disabled = isSubmitting;
    if (modalMode === 'edit') {
      submitBtn.textContent = isSubmitting ? 'Saving…' : 'Save changes';
      return;
    }

    submitBtn.textContent = isSubmitting ? 'Submitting…' : 'Submit application';
  }
});

function showApplyToast(message, isError = false) {
  let toast = document.getElementById('applyToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'applyToast';
    toast.className = 'apply-toast';
    document.body.appendChild(toast);
  }

  toast.textContent = message;
  toast.classList.toggle('apply-toast--error', isError);
  toast.classList.add('is-visible');

  window.clearTimeout(showApplyToast.hideTimer);
  showApplyToast.hideTimer = window.setTimeout(() => {
    toast.classList.remove('is-visible');
  }, 3200);
}
