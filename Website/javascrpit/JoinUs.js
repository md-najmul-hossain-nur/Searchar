  document.getElementById('logo').onclick = function() {
    window.location.href = '../Html/Index.html';
  };
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
});
document.getElementById('showRulesBtn').addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('rulesModal').style.display = 'block';
});
document.getElementById('closeRulesModal').addEventListener('click', function() {
  document.getElementById('rulesModal').style.display = 'none';
});
window.addEventListener('click', function(event) {
  const modal = document.getElementById('rulesModal');
  if (event.target === modal) {
    modal.style.display = 'none';
  }
});
// Enable the "Install Our App" button only if the checkbox is checked
document.getElementById('rulesAgreeCheckbox').addEventListener('change', function() {
  document.getElementById('installAppBtn').disabled = !this.checked;
});

document.getElementById('installAppBtn').addEventListener('click', function() {
  alert('Redirecting you to install the app!');
  window.location.href = "index.html";
});

// Modal close logic
document.getElementById('closeRulesModal').addEventListener('click', function() {
  document.getElementById('rulesModal').style.display = 'none';
});
window.addEventListener('click', function(event) {
  const modal = document.getElementById('rulesModal');
  if (event.target === modal) {
    modal.style.display = 'none';
  }
});