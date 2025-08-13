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

// Count-up animation for lives saved
document.addEventListener('DOMContentLoaded', function() {
  const counters = document.querySelectorAll('.count-up');
  counters.forEach(counter => {
    const target = +counter.getAttribute('data-count');
    let count = 0;
    const duration = 900;
    const step = Math.ceil(target / (duration / 20));
    function updateCounter() {
      count += step;
      if (count < target) {
        counter.textContent = count;
        requestAnimationFrame(updateCounter);
      } else {
        counter.textContent = target;
      }
    }
    setTimeout(updateCounter, 2300); // sync after animation
  });

  // Button pulse animation
  const btn = document.querySelector('.cta-btn');
  btn.addEventListener('mouseenter', () => {
    btn.style.transform = 'scale(1.08)';
    btn.style.boxShadow = '0 6px 26px rgba(44,111,181,0.17)';
  });
  btn.addEventListener('mouseleave', () => {
    btn.style.transform = '';
    btn.style.boxShadow = '';
  });
});
