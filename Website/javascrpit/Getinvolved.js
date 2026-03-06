document.addEventListener('DOMContentLoaded', () => {
  function generateCalendar(year, month) {
    const calendarHeader = document.getElementById('calendarHeader');
    const calendarBody = document.querySelector('#calendar tbody');
    if (!calendarHeader || !calendarBody) return;

    const monthNames = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];
    calendarHeader.textContent = `${monthNames[month]} ${year}`;

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const startingDay = firstDay === 0 ? 6 : firstDay - 1;

    calendarBody.innerHTML = '';
    let date = 1;

    for (let i = 0; i < 6; i++) {
      const row = document.createElement('tr');

      for (let j = 0; j < 7; j++) {
        const cell = document.createElement('td');

        if (i === 0 && j < startingDay) {
          cell.textContent = '';
        } else if (date <= daysInMonth) {
          cell.textContent = date;

          const today = new Date();
          if (
            date === today.getDate() &&
            year === today.getFullYear() &&
            month === today.getMonth()
          ) {
            cell.style.backgroundColor = 'white';
            cell.style.color = 'black';
            cell.style.fontWeight = 'bold';
          }

          date++;
        } else {
          cell.textContent = '';
        }

        row.appendChild(cell);
      }

      calendarBody.appendChild(row);
      if (date > daysInMonth) break;
    }
  }

  const today = new Date();
  generateCalendar(today.getFullYear(), today.getMonth());

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
