/**
 * Jagiree landing page interactions
 */

document.addEventListener('DOMContentLoaded', () => {
  const chatFab = document.querySelector('.chatbot-fab');

  if (chatFab && !chatFab.getAttribute('onclick')) {
    chatFab.addEventListener('click', () => {
      window.location.href = '/seeker/chat.php';
    });
  }

  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', (e) => {
      const targetId = anchor.getAttribute('href');
      if (targetId === '#') return;

      const target = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
});
