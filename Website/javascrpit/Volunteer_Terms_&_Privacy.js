(function () {
  function initTermsPage() {
    var body = document.querySelector('.terms-body');
    var agreeBtn = document.querySelector('.agree-btn');
    var emailCopy = document.querySelector('.email-copy-checkbox');
    var tocLinks = Array.prototype.slice.call(document.querySelectorAll('.terms-toc a'));
    var sections = Array.prototype.slice.call(document.querySelectorAll('.terms-body section'));

    if (!body || !agreeBtn) {
      return;
    }

    function updateAgreeState() {
      var isBottom = body.scrollTop + body.clientHeight >= body.scrollHeight - 4;
      agreeBtn.disabled = !isBottom;
    }

    function updateActiveSection() {
      if (!sections.length || !tocLinks.length) {
        return;
      }

      var currentId = sections[0].id;
      var fromTop = body.scrollTop + 20;

      for (var i = 0; i < sections.length; i += 1) {
        if (sections[i].offsetTop <= fromTop) {
          currentId = sections[i].id;
        }
      }

      tocLinks.forEach(function (link) {
        var isActive = link.getAttribute('href') === '#' + currentId;
        link.classList.toggle('active', isActive);
      });
    }

    tocLinks.forEach(function (link) {
      link.addEventListener('click', function (event) {
        var targetId = link.getAttribute('href').slice(1);
        var target = document.getElementById(targetId);

        if (!target) {
          return;
        }

        event.preventDefault();
        body.scrollTo({ top: target.offsetTop - 8, behavior: 'smooth' });
      });
    });

    body.addEventListener('scroll', function () {
      updateAgreeState();
      updateActiveSection();
    });

    updateAgreeState();
    updateActiveSection();

    agreeBtn.addEventListener('click', function () {
      if (agreeBtn.disabled) {
        return;
      }

      var role = agreeBtn.getAttribute('data-role') || 'user';
      var next = agreeBtn.getAttribute('data-next') || '';
      var acceptedAt = new Date().toISOString();

      try {
        localStorage.setItem('searchar_terms_accepted_' + role, acceptedAt);
        localStorage.setItem('searchar_terms_email_copy_' + role, emailCopy && emailCopy.checked ? 'yes' : 'no');
      } catch (error) {
      }

      if (next) {
        window.location.href = next;
      } else {
        window.history.back();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTermsPage);
  } else {
    initTermsPage();
  }
})();
