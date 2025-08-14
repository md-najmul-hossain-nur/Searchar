// Logo click redirects to home
document.getElementById('logo').onclick = function() {
  window.location.href = '../Html/Index.html';
};

// Animate sections on scroll (one-time pop-up)
document.addEventListener('DOMContentLoaded', () => {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('pop-up');
          observer.unobserve(entry.target); // only animate once
        }
      });
    },
    { threshold: 0.25 }
  );
  document
    .querySelectorAll('.hero-section, .benefits-section, .why-help-section')
    .forEach((el) => observer.observe(el));
  
  // Modal logic
  const showRulesBtn = document.getElementById('showRulesBtn');
  const rulesModal = document.getElementById('rulesModal');
  const closeRulesModal = document.getElementById('closeRulesModal');
  const rulesAgreeCheckbox = document.getElementById('rulesAgreeCheckbox');
  const joinNowBtn = document.getElementById('joinNowBtn'); // Use joinNowBtn, not installAppBtn

  // Show modal on "Join Now"
  if (showRulesBtn && rulesModal) {
    showRulesBtn.addEventListener('click', function(e) {
      e.preventDefault();
      rulesModal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    });
  }

  // Close modal on close button
  if (closeRulesModal && rulesModal) {
    closeRulesModal.addEventListener('click', function() {
      rulesModal.style.display = 'none';
      document.body.style.overflow = '';
      if (rulesAgreeCheckbox) rulesAgreeCheckbox.checked = false;
      if (joinNowBtn) joinNowBtn.disabled = true;
    });
  }

  // Close modal when clicking outside modal content
  if (rulesModal) {
    window.addEventListener('click', function(event) {
      if (event.target === rulesModal) {
        rulesModal.style.display = 'none';
        document.body.style.overflow = '';
        if (rulesAgreeCheckbox) rulesAgreeCheckbox.checked = false;
        if (joinNowBtn) joinNowBtn.disabled = true;
      }
    });
  }

  // Enable the "Join Now" button only if the checkbox is checked
  if (rulesAgreeCheckbox && joinNowBtn) {
    rulesAgreeCheckbox.addEventListener('change', function() {
      joinNowBtn.disabled = !this.checked;
    });
  }

  // On "Join Now" modal button click: redirect to login
  if (joinNowBtn) {
    joinNowBtn.addEventListener('click', function() {
      if (!joinNowBtn.disabled) {
        window.location.href = '../Html/login.html';
      }
    });
  }
});
