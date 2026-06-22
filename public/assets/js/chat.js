/**
 * Jagiree AI Chat — profile-aware recommendations and unified CV upload.
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
  let cachedRecommendations = null;
  let profileSkills = Array.isArray(config.skills) ? [...config.skills] : [];
  let hasCv = Boolean(config.hasCv);

  function normalize(text) {
    return text.toLowerCase().trim().replace(/[^\w\s]/g, ' ');
  }

  function matchAny(text, patterns) {
    return patterns.some((p) => text.includes(p));
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
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
      wrap.innerHTML = `<span class="message-avatar">🤖</span><div class="message-bubble">${content.html || `<p>${formatText(content.text)}</p>`}</div>`;
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
    el.innerHTML = '<span class="message-avatar">🤖</span><div class="message-bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div>';
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

  async function fetchRecommendations(force = false) {
    if (!force && cachedRecommendations) {
      return cachedRecommendations;
    }

    const response = await fetch('/seeker/api/recommendations.php?limit=5', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await response.json();

    if (!data.success) {
      throw new Error(data.error || 'Could not load recommendations.');
    }

    cachedRecommendations = data;
    profileSkills = Array.isArray(data.skills) ? data.skills : profileSkills;
    hasCv = Boolean(data.has_cv);
    window.seekerHasCv = hasCv;
    if (data.cv) {
      updateCvSidebar(data.cv);
    }

    return data;
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
      html += `
        <a class="chat-job-item" href="${escapeHtml(job.url)}">
          <div class="chat-job-match">${job.match}% Match</div>
          <strong>${escapeHtml(job.title)}</strong>
          <span>${escapeHtml(job.company)} · ${escapeHtml(job.location)}</span>
        </a>`;
    });
    html += '</div><p>Open a job to <strong>Easy Apply</strong> — your profile CV is sent automatically.</p>';
    return html;
  }

  async function buildRecommendationsResponse() {
    const data = await fetchRecommendations(true);
    return {
      html: `${buildSkillsHtml(data.skills)}${buildJobRecommendationsHtml(data.jobs)}`,
    };
  }

  async function getBotResponse(userText) {
    const t = normalize(userText);

    if (matchAny(t, ['hi', 'hello', 'hey', 'namaste', 'good morning', 'good evening'])) {
      return {
        text: "Hello! I use your Jagiree profile and CV to recommend real jobs. Ask for recommendations, upload your CV, or learn how Easy Apply works.",
      };
    }

    if (matchAny(t, ['how does', 'how do', 'what is jagiree', 'about jagiree', 'how it work', 'platform'])) {
      return {
        text: "Jagiree connects seekers and employers with profile-based matching:\n\n1. Upload your CV once on your profile\n2. Browse jobs ranked by match score\n3. Easy Apply sends your profile + CV to employers\n4. Track applications under Applications\n\nYour CV and chat upload use the same profile file.",
      };
    }

    if (matchAny(t, ['recommend', 'suggestion', 'match me', 'find job', 'find me job', 'best job'])) {
      return buildRecommendationsResponse();
    }

    if (matchAny(t, ['upload', 'cv', 'resume', 'curriculum'])) {
      return {
        text: hasCv
          ? "Your CV is saved on your profile. Use the sidebar upload button or the attach icon to replace it. Then ask for job recommendations!"
          : "Upload your CV using the sidebar button or the attach icon. It saves to your profile and powers Easy Apply plus recommendations.",
        action: 'highlight-upload',
      };
    }

    if (matchAny(t, ['apply', 'application', 'how to apply'])) {
      return {
        text: hasCv
          ? "To apply:\n\n1. Open a job from recommendations or Jobs\n2. Click **Easy Apply**\n3. Your profile and CV are sent to the employer\n4. Track status under **Applications**"
          : "Before applying, upload your CV on your profile (Skills & CV tab). Then click **Easy Apply** on any job to send your profile package.",
      };
    }

    if (matchAny(t, ['skill', 'trending', 'demand', 'popular'])) {
      const skillsText = profileSkills.length
        ? `Your profile skills: ${profileSkills.join(', ')}.`
        : 'Add skills on your profile to improve matches.';
      return {
        text: `${skillsText}\n\nIn-demand areas on Jagiree include PHP, React, Figma, UX, and digital marketing.`,
      };
    }

    if (matchAny(t, ['thank', 'thanks', 'dhanyabad'])) {
      return { text: "You're welcome! Ask anytime for job recommendations or CV help." };
    }

    return {
      text: "Try asking for job recommendations, uploading your CV, or how Easy Apply works. I use your live profile data!",
    };
  }

  async function sendUserMessage(text) {
    const trimmed = text.trim();
    if (!trimmed) return;

    appendMessage('user', { text: trimmed });
    input.value = '';
    showTyping();

    try {
      const response = await getBotResponse(trimmed);
      hideTyping();
      appendMessage('bot', response);

      if (response.action === 'highlight-upload') {
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

      cachedRecommendations = null;
      updateCvSidebar(data.cv);
      profileSkills = Array.isArray(data.skills) ? data.skills : profileSkills;

      const recData = await fetchRecommendations(true);
      appendMessage('bot', {
        html: `
          <p>CV saved to your profile: <strong>${escapeHtml(data.cv?.filename || file.name)}</strong></p>
          <p>It will be used for Easy Apply and job matching.</p>
          ${buildSkillsHtml(recData.skills)}
          ${buildJobRecommendationsHtml(recData.jobs)}
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
      text: 'Chat cleared. Ask for recommendations or upload your CV to get started.',
    });
  });

  cvUpload?.addEventListener('change', (e) => uploadCvFile(e.target.files[0]));
  chatAttach?.addEventListener('change', (e) => uploadCvFile(e.target.files[0]));

  input.focus();
})();
