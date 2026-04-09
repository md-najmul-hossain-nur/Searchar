(function () {
  function fallbackRoleFromPath(pathname) {
    if (pathname.includes('User_Terms_&_Privacy')) return 'user';
    if (pathname.includes('Volunteer_Terms_&_Privacy')) return 'volunteer';
    if (pathname.includes('Policeman_Terms_&_Privacy')) return 'police';
    if (pathname.includes('Camera_Contribution_Terms_&_Privacy')) return 'contributor';
    return '';
  }

  function resolveBackUrl() {
    const params = new URLSearchParams(window.location.search);
    const explicitReturn = params.get('returnTo');

    if (explicitReturn) {
      try {
        return new URL(explicitReturn, window.location.href).toString();
      } catch (error) {
      }
    }

    const role = params.get('role') || fallbackRoleFromPath(window.location.pathname);
    const loginUrl = new URL('login.html', window.location.href);
    loginUrl.searchParams.set('openSignup', '1');
    if (role) {
      loginUrl.searchParams.set('role', role);
    }

    return loginUrl.toString();
  }

  function handleBackClick(event) {
    event.preventDefault();

    const target = resolveBackUrl();

    if (document.referrer) {
      try {
        const ref = new URL(document.referrer);
        if (ref.pathname.endsWith('/login.html')) {
          window.location.href = target;
          return;
        }
      } catch (error) {
      }
    }

    window.location.href = target;
  }

  document.addEventListener('DOMContentLoaded', function () {
    const backButtons = document.querySelectorAll('.terms-back-btn');
    backButtons.forEach((button) => {
      button.addEventListener('click', handleBackClick);
    });
  });
})();
