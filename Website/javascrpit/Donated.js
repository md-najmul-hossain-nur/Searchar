  document.getElementById('logo').onclick = function() {
    window.location.href = '../Html/Index.html';
  };
  document.addEventListener("DOMContentLoaded", () => {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
        }
      });
    },
    {
      threshold: 0.3,
    }
  );

  document.querySelectorAll(".donation-hero, .donation-title, .donation-desc, .donation-form-container").forEach((el) => {
    observer.observe(el);
  });

  // Optional ripple effect for Donate button
  document.querySelectorAll(".donate-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      const ripple = document.createElement("span");
      ripple.classList.add("ripple");
      this.appendChild(ripple);

      const size = Math.max(this.offsetWidth, this.offsetHeight);
      ripple.style.width = ripple.style.height = `${size}px`;
      ripple.style.left = `${e.offsetX - size / 2}px`;
      ripple.style.top = `${e.offsetY - size / 2}px`;

      setTimeout(() => {
        ripple.remove();
      }, 600);
    });
  });
});
// Animate form fields on scroll
  const formCard = document.querySelector('.donation-form-card');

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        formCard.style.animation = 'popUp 0.9s ease-out forwards';
      }
    });
  });

  observer.observe(formCard);