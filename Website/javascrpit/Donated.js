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