document.querySelectorAll('.auth-password-toggle').forEach((button) => {
  const input = button.closest('.auth-password-wrap')?.querySelector('input');
  if (!input) {
    return;
  }

  button.addEventListener('click', () => {
    const showPassword = input.type === 'password';
    input.type = showPassword ? 'text' : 'password';
    button.classList.toggle('is-visible', showPassword);
    button.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
    button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
  });
});
