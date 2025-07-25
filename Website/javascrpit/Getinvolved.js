  document.getElementById('logo').onclick = function() {
    window.location.href = '../Html/Index.html';
  };
  // Animate sections when they come into view
// Intersection Observer animation logic
const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add("animate-pop-up");
      observer.unobserve(entry.target); // trigger only once
    }
  });
});

// Elements to animate on scroll
document.querySelectorAll(
  ".volunteer-hero, .volunteer-hero h1, .volunteer-hero p, .volunteer-hero button, .reason-card, .cta-download"
).forEach((el) => {
  observer.observe(el);
});

