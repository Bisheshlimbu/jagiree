document.addEventListener('DOMContentLoaded', () => {
  const tabsRoot = document.querySelector('.profile-tabs');
  if (!tabsRoot) {
    return;
  }

  const tabButtons = tabsRoot.querySelectorAll('.profile-tabs__btn');
  const tabPanels = tabsRoot.querySelectorAll('[data-tab-panel]');
  const returnTabInput = document.getElementById('profileReturnTab');
  const allowedTabs = ['about', 'experience', 'education', 'skills'];

  function setActiveTab(tabId, updateUrl = true) {
    if (!allowedTabs.includes(tabId)) {
      tabId = 'about';
    }

    tabButtons.forEach((btn) => {
      const isActive = btn.dataset.tab === tabId;
      btn.classList.toggle('is-active', isActive);
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    tabPanels.forEach((panel) => {
      const isActive = panel.dataset.tabPanel === tabId;
      panel.classList.toggle('is-active', isActive);
      panel.hidden = !isActive;
    });

    if (returnTabInput && (tabId === 'about' || tabId === 'skills')) {
      returnTabInput.value = tabId;
    }

    tabsRoot.dataset.activeTab = tabId;

    if (updateUrl) {
      const url = new URL(window.location.href);
      url.searchParams.set('tab', tabId);
      if (!url.searchParams.has('saved')) {
        url.searchParams.delete('saved');
      }
      window.history.replaceState({}, '', url);
    }
  }

  tabButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      setActiveTab(btn.dataset.tab || 'about');
    });
  });

  const initialTab = tabsRoot.dataset.activeTab || 'about';
  setActiveTab(initialTab, false);
});
