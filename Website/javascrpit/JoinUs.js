document.addEventListener('DOMContentLoaded', () => {
  const logo = document.getElementById('logo');
  if (logo) {
    logo.addEventListener('click', () => {
      window.location.href = '../Html/Index.html';
    });
  }

  const revealNodes = document.querySelectorAll('.reveal-text');
  if (revealNodes.length) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const delay = String(el.getAttribute('data-delay') || '0').trim();
        el.style.setProperty('--delay', `${delay}s`);
        el.classList.add('in-view');
        observer.unobserve(el);
      });
    }, { threshold: 0.2 });

    revealNodes.forEach((el) => revealObserver.observe(el));
  }

  const showRulesBtn = document.getElementById('showRulesBtn');
  const rulesModal = document.getElementById('rulesModal');
  const closeRulesModal = document.getElementById('closeRulesModal');
  const rulesAgreeCheckbox = document.getElementById('rulesAgreeCheckbox');
  const joinNowBtn = document.getElementById('joinNowBtn');

  const closeModal = () => {
    if (!rulesModal) return;
    rulesModal.style.display = 'none';
    document.body.style.overflow = '';
    if (rulesAgreeCheckbox) rulesAgreeCheckbox.checked = false;
    if (joinNowBtn) joinNowBtn.disabled = true;
  };

  if (showRulesBtn && rulesModal) {
    showRulesBtn.addEventListener('click', () => {
      rulesModal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    });
  }

  if (closeRulesModal) {
    closeRulesModal.addEventListener('click', closeModal);
  }

  if (rulesModal) {
    rulesModal.addEventListener('click', (event) => {
      if (event.target === rulesModal) {
        closeModal();
      }
    });
  }

  if (rulesAgreeCheckbox && joinNowBtn) {
    rulesAgreeCheckbox.addEventListener('change', function () {
      joinNowBtn.disabled = !this.checked;
    });
  }

  if (joinNowBtn) {
    joinNowBtn.addEventListener('click', () => {
      if (joinNowBtn.disabled) return;
      window.location.href = '../Html/login.html';
    });
  }
});
