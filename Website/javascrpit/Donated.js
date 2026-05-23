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

  const heroVideo = document.getElementById('hero-video');
  if (heroVideo) {
    heroVideo.muted = false;
    heroVideo.volume = 1;
    const playAttempt = heroVideo.play();
    if (playAttempt && typeof playAttempt.catch === 'function') {
      playAttempt.catch(() => {
        heroVideo.controls = true;
      });
    }
  }


  const donationForm = document.getElementById('donationForm');
  const donationAlert = document.getElementById('donation-alert');

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

  function setDonationAlert(type, message) {
    if (!donationAlert) return;
    donationAlert.textContent = message;
    donationAlert.className = `form-alert show ${type}`;
  }

  donationForm.addEventListener('submit', (event) => {
    event.preventDefault();

    const nameInput = document.getElementById('donor-name');
    const emailInput = document.getElementById('donor-email');
    const mobileInput = document.getElementById('donor-mobile');
    const amountInput = document.getElementById('donation-amount');
    const txidInput = document.getElementById('txid');

    const donorName = String(nameInput?.value || '').trim();
    const donorEmail = String(emailInput?.value || '').trim();
    const donorMobile = String(mobileInput?.value || '').trim();
    const amountValue = String(amountInput?.value || '').trim();
    const txid = String(txidInput?.value || '').trim();

    if (donorName.length < 2) {
      setDonationAlert('error', 'Please enter your full name.');
      nameInput?.focus();
      return;
    }

    if (!donorEmail.includes('@')) {
      setDonationAlert('error', 'Please enter a valid email address.');
      emailInput?.focus();
      return;
    }

    if (donorMobile.length < 10) {
      setDonationAlert('error', 'Please enter a valid mobile number.');
      mobileInput?.focus();
      return;
    }

    if (!amountValue || Number(amountValue) <= 0) {
      setDonationAlert('error', 'Please enter a valid donation amount.');
      amountInput?.focus();
      return;
    }

    if (txid.length < 6) {
      setDonationAlert('error', 'Please enter a valid TXID.');
      txidInput?.focus();
      return;
    }

    const submitButton = donationForm.querySelector('button[type="submit"]');
    if (submitButton) submitButton.disabled = true;
    setDonationAlert('success', 'Submitting your donation details...');

    const formData = new FormData(donationForm);

    fetch('../Php/submit_donation.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
      .then(async (res) => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data?.success) {
          throw new Error(data?.error || 'Submission failed.');
        }
        return data;
      })
      .then(() => {
        setDonationAlert('success', 'Thank you! Your donation was received for verification.');
        donationForm.reset();
      })
      .catch((error) => {
        setDonationAlert('error', error?.message || 'Submission failed. Please try again.');
      })
      .finally(() => {
        if (submitButton) submitButton.disabled = false;
      });
  });
});