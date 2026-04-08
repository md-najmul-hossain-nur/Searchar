(function () {
  const messengerFab = document.getElementById('messengerFab');
  const messengerDrawer = document.getElementById('messengerDrawer');
  const messengerBackdrop = document.getElementById('messengerBackdrop');
  const messengerClose = document.getElementById('messengerClose');
  const messengerInput = document.getElementById('messengerInput');

  if (!messengerFab || !messengerDrawer || !messengerBackdrop || !messengerClose) {
    return;
  }

  function openMessengerDrawer() {
    messengerDrawer.classList.add('open');
    messengerBackdrop.classList.add('open');
    messengerDrawer.setAttribute('aria-hidden', 'false');
    if (messengerInput && typeof messengerInput.focus === 'function') {
      messengerInput.focus();
    }
  }

  function closeMessengerDrawer() {
    const active = document.activeElement;
    if (active && messengerDrawer.contains(active) && typeof active.blur === 'function') {
      active.blur();
    }

    messengerDrawer.classList.remove('open');
    messengerBackdrop.classList.remove('open');
    messengerDrawer.setAttribute('aria-hidden', 'true');

    if (messengerFab && typeof messengerFab.focus === 'function') {
      messengerFab.focus();
    }
  }

  messengerFab.addEventListener('click', openMessengerDrawer);
  messengerClose.addEventListener('click', closeMessengerDrawer);
  messengerBackdrop.addEventListener('click', closeMessengerDrawer);

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeMessengerDrawer();
    }
  });
})();
