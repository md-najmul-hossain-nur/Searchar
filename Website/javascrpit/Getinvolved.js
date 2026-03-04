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

  const counters = document.querySelectorAll('.count-up');
  counters.forEach((counter) => {
    const target = Number(counter.getAttribute('data-count') || 0);
    if (!target) {
      counter.textContent = '0';
      return;
    }

    let started = false;
    const counterObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting || started) return;
        started = true;

        let current = 0;
        const duration = 1000;
        const step = Math.max(1, Math.floor(target / (duration / 16)));

        const tick = () => {
          current += step;
          if (current >= target) {
            counter.textContent = String(target);
            observer.unobserve(counter);
            return;
          }
          counter.textContent = String(current);
          requestAnimationFrame(tick);
        };

        requestAnimationFrame(tick);
      });
    }, { threshold: 0.35 });

    counterObserver.observe(counter);
  });
});
