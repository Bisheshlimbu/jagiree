/**
 * Jagiree AI Chat — Phase 1: server-side normal replies.
 * Phase 2 will add CV text extraction; Phase 3 NLP matching.
 */

(function () {
  const messagesEl = document.getElementById('chatMessages');
  const form = document.getElementById('chatForm');
  const input = document.getElementById('chatInput');
  const clearBtn = document.getElementById('clearChat');
  const cvUpload = document.getElementById('cvUpload');
  const chatAttach = document.getElementById('chatFileAttach');
  const cvFileName = document.getElementById('cvFileName');

  if (!messagesEl || !form || !input) return;

  const config = window.chatSeekerConfig || {};
  let profileSkills = Array.isArray(config.skills) ? [...config.skills] : [];
  let hasCv = Boolean(config.hasCv);

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function botAvatarHtml(className) {
    if (config.siteLogoUrl) {
      return `<span class="${className} ${className}--logo" aria-hidden="true"><img src="${escapeHtml(config.siteLogoUrl)}" alt=""></span>`;
    }
    const letter = escapeHtml(config.siteLogoLetter || (config.siteName || 'J').charAt(0).toUpperCase());
    return `<span class="${className}" aria-hidden="true">${letter}</span>`;
  }

  function formatText(text) {
    return escapeHtml(text)
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\n/g, '<br>');
  }

  function appendMessage(type, content) {
    const wrap = document.createElement('div');
    wrap.className = `message message--${type}`;

    if (type === 'bot') {
      wrap.innerHTML = `${botAvatarHtml('message-avatar')}<div class="message-bubble">${content.html || `<p>${formatText(content.text || '')}</p>`}</div>`;
    } else {
      wrap.innerHTML = `<div class="message-bubble message-bubble--user"><p>${escapeHtml(content.text)}</p></div>`;
    }

    messagesEl.appendChild(wrap);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function showTyping() {
    const el = document.createElement('div');
    el.className = 'message message--bot message--typing';
    el.id = 'typingIndicator';
    el.innerHTML = `${botAvatarHtml('message-avatar')}<div class="message-bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div>`;
    messagesEl.appendChild(el);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function hideTyping() {
    document.getElementById('typingIndicator')?.remove();
  }

  function updateCvSidebar(cv) {
    if (!cvFileName) return;

    if (cv && cv.has_cv) {
      const updated = cv.updated_label ? ` · Updated ${cv.updated_label}` : '';
      cvFileName.textContent = `${cv.filename}${updated}`;
      hasCv = true;
      window.seekerHasCv = true;
    }
  }

  function buildSkillsHtml(skills) {
    if (!skills || skills.length === 0) {
      return '<p>Add skills on your <a href="/seeker/profile.php?tab=skills">profile</a> to improve match scores.</p>';
    }

    return `<div class="chat-tags">${skills.map((skill) => `<span>${escapeHtml(skill)}</span>`).join('')}</div>`;
  }

  function buildJobRecommendationsHtml(jobs) {
    if (!jobs || jobs.length === 0) {
      return '<p>No live job listings match your profile right now. Check back soon or browse <a href="/seeker/jobs.php">all jobs</a>.</p>';
    }

    let html = '<p>Here are your <strong>top matched jobs</strong> from live listings:</p><div class="chat-job-list">';
    jobs.forEach((job) => {
      const badge = job.is_external
        ? `<span class="chat-job-source">${escapeHtml(job.source_label || 'LinkedIn')}</span>`
        : '';
      const applyHint = job.is_external ? 'Apply on LinkedIn' : 'Easy Apply';
      html += `
        <a class="chat-job-item" href="${escapeHtml(job.url)}">
          <div class="chat-job-match">${Number(job.match) || 0}% Match</div>
          ${badge}
          <strong>${escapeHtml(job.title)}</strong>
          <span>${escapeHtml(job.company)} · ${escapeHtml(job.location)}</span>
          <span class="chat-job-apply-hint">${escapeHtml(applyHint)}</span>
        </a>`;
    });
    html += '</div><p>Jagiree jobs use <strong>Easy Apply</strong>. LinkedIn jobs open on LinkedIn.</p>';
    return html;
  }

  function renderBotReply(data) {
    const parts = [];

    if (data.text) {
      parts.push(`<p>${formatText(data.text)}</p>`);
    }

    if (Array.isArray(data.skills) && data.intent === 'recommend') {
      parts.push(buildSkillsHtml(data.skills));
    }

    if (Array.isArray(data.jobs) && data.jobs.length > 0) {
      parts.push(buildJobRecommendationsHtml(data.jobs));
    }

    return { html: parts.join('') || `<p>${formatText(data.text || 'Okay.')}</p>`, action: data.action || null };
  }

  async function askChatApi(message) {
    const response = await fetch('/seeker/api/chat.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ message }),
    });
    const data = await response.json();

    if (!data.success) {
      throw new Error(data.error || 'Could not get a reply.');
    }

    if (Array.isArray(data.skills)) {
      profileSkills = data.skills;
    }
    if (typeof data.has_cv === 'boolean') {
      hasCv = data.has_cv;
      window.seekerHasCv = hasCv;
    }

    return data;
  }

  async function sendUserMessage(text) {
    const trimmed = text.trim();
    if (!trimmed) return;

    appendMessage('user', { text: trimmed });
    input.value = '';
    showTyping();

    try {
      const data = await askChatApi(trimmed);
      hideTyping();
      const rendered = renderBotReply(data);
      appendMessage('bot', rendered);

      if (rendered.action === 'highlight-upload') {
        document.querySelector('.cv-upload-btn')?.classList.add('is-highlighted');
        window.setTimeout(() => document.querySelector('.cv-upload-btn')?.classList.remove('is-highlighted'), 2000);
      }
    } catch (error) {
      hideTyping();
      appendMessage('bot', { text: error.message || 'Something went wrong. Please try again.' });
    }
  }

  async function uploadCvFile(file) {
    if (!file) return;

    const allowed = [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    if (!allowed.includes(file.type) && !file.name.match(/\.(pdf|doc|docx)$/i)) {
      appendMessage('bot', { text: 'Please upload a PDF or DOCX file for your CV.' });
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      appendMessage('bot', { text: 'File is too large. Please upload a CV under 5MB.' });
      return;
    }

    appendMessage('user', { text: `Uploaded: ${file.name}` });
    showTyping();

    try {
      const body = new FormData();
      body.append('cv', file);

      const response = await fetch('/seeker/api/upload-cv.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body,
      });
      const data = await response.json();

      hideTyping();

      if (!data.success) {
        appendMessage('bot', { text: data.error || 'Could not save your CV.' });
        return;
      }

      updateCvSidebar(data.cv);
      profileSkills = Array.isArray(data.skills) ? data.skills : profileSkills;

      appendMessage('bot', {
        html: `
          <p>${escapeHtml(data.message || 'CV saved.')}</p>
          ${Array.isArray(data.skills) && data.skills.length ? buildSkillsHtml(data.skills) : ''}
          <p>Ask <strong>Recommend jobs for me</strong> to get NLP-ranked matches.</p>
        `,
      });
    } catch (error) {
      hideTyping();
      appendMessage('bot', { text: 'Could not upload your CV. Please try again.' });
    }
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    sendUserMessage(input.value);
  });

  document.querySelectorAll('.quick-prompt, .suggestion-chip').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (btn.dataset.action === 'upload-cv') {
        cvUpload?.click();
        return;
      }
      sendUserMessage(btn.dataset.prompt || btn.textContent);
    });
  });

  clearBtn?.addEventListener('click', () => {
    messagesEl.innerHTML = '';
    appendMessage('bot', {
      text: 'Chat cleared. Ask for recommendations, how to apply, or upload your CV.',
    });
  });

  cvUpload?.addEventListener('change', (e) => uploadCvFile(e.target.files[0]));
  chatAttach?.addEventListener('change', (e) => uploadCvFile(e.target.files[0]));

  input.focus();
})();
