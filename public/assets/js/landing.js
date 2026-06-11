/**
 * Jagiree landing page interactions
 */

document.addEventListener('DOMContentLoaded', () => {
  const chatFab = document.querySelector('.chatbot-fab');

  if (chatFab) {
    chatFab.addEventListener('click', () => {
      // Chatbot widget will be wired to Python NLP service later
      alert('Jagiree AI Assistant coming soon! Upload your CV and get personalized job recommendations.');
    });
  }

  // Smooth scroll for anchor links
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
