(() => {
  const dialog = document.querySelector('#review-dialog');
  const title = document.querySelector('#modal-title');
  const context = document.querySelector('#modal-context');
  const closeButton = document.querySelector('#close-dialog');
  const cards = [...document.querySelectorAll('.project-card')];
  let returnFocus = null;

  if (!dialog || !title || !context || !closeButton) return;

  const closeReview = () => {
    if (dialog.open) dialog.close();
  };

  cards.forEach((card) => {
    card.addEventListener('click', () => {
      returnFocus = card;
      title.textContent = card.dataset.label || 'Working label';
      context.textContent = `LAYOUT REVIEW · PLACEHOLDER PAIR ${card.dataset.pair || '—'}`;
      dialog.showModal();
      closeButton.focus();
    });
  });

  closeButton.addEventListener('click', closeReview);
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) closeReview();
  });
  dialog.addEventListener('close', () => {
    if (returnFocus) returnFocus.focus();
  });
})();
