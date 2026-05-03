// Loads shared_footer.html into any element with id 'site-footer-placeholder'
(function () {
  function renderFooterCalendar() {
    const calendarHeader = document.getElementById('footerCalendarHeader');
    const calendarBody = document.querySelector('#footerCalendar tbody');
    if (!calendarHeader || !calendarBody) return;
    if (calendarBody.children.length) return;

    const monthNames = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];

    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth();

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

          if (date === today.getDate()) {
            cell.classList.add('is-today');
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

  async function loadFooter() {
    try {
      const resp = await fetch('shared_footer.html', { cache: 'no-cache' });
      if (!resp.ok) return;
      const html = await resp.text();
      const placeholder = document.getElementById('site-footer-placeholder');
      if (placeholder) {
        placeholder.innerHTML = html;
        const yearEl = document.getElementById('footerYear');
        if (yearEl) yearEl.textContent = new Date().getFullYear();
        renderFooterCalendar();
        // notify other scripts that shared footer has been injected
        try {
          document.dispatchEvent(new CustomEvent('sharedFooter:loaded'));
        } catch (e) {
          // ignore
        }
      }
    } catch (err) {
      // fail silently
      console.warn('Footer loader:', err && err.message ? err.message : err);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadFooter);
  } else {
    loadFooter();
  }
})();
