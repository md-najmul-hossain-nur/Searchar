document.addEventListener('DOMContentLoaded', () => {
  const logo = document.getElementById('logo');
  if (logo) {
    logo.addEventListener('click', () => {
      window.location.href = '../Html/Index.html';
    });
  }

  const donationForm = document.getElementById('donationForm');

  const animatedNodes = document.querySelectorAll('.animate-text');
  if (animatedNodes.length) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const delay = String(el.getAttribute('data-delay') || '0').trim();
        el.style.setProperty('--delay', `${delay}s`);
        el.classList.add('in-view');
        observer.unobserve(el);
      });
    }, { threshold: 0.18 });

    animatedNodes.forEach((el) => revealObserver.observe(el));
  }

  if (!donationForm) return;

  donationForm.addEventListener('submit', (event) => {
    event.preventDefault();

    const txidInput = document.getElementById('txid');
    const txid = String(txidInput?.value || '').trim();

    if (txid.length < 6) {
      alert('Please enter a valid TXID.');
      txidInput?.focus();
      return;
    }

    alert('Thank you! Your donation request has been received for verification.');
    donationForm.reset();
  });
});