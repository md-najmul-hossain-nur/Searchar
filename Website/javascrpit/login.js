// Logo click redirects to home
document.getElementById('logo').onclick = function() {
  window.location.href = '../Html/Index.html';
};

// Animate sections on scroll (one-time pop-up)
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
  
  // Modal logic
  const showRulesBtn = document.getElementById('showRulesBtn');
  const rulesModal = document.getElementById('rulesModal');
  const closeRulesModal = document.getElementById('closeRulesModal');
  const rulesAgreeCheckbox = document.getElementById('rulesAgreeCheckbox');
  const joinNowBtn = document.getElementById('joinNowBtn'); // Use joinNowBtn, not installAppBtn

  // Show modal on "Join Now"
  if (showRulesBtn && rulesModal) {
    showRulesBtn.addEventListener('click', function(e) {
      e.preventDefault();
      rulesModal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    });
  }

  // Close modal on close button
  if (closeRulesModal && rulesModal) {
    closeRulesModal.addEventListener('click', function() {
      rulesModal.style.display = 'none';
      document.body.style.overflow = '';
      if (rulesAgreeCheckbox) rulesAgreeCheckbox.checked = false;
      if (joinNowBtn) joinNowBtn.disabled = true;
    });
  }

  // Close modal when clicking outside modal content
  if (rulesModal) {
    window.addEventListener('click', function(event) {
      if (event.target === rulesModal) {
        rulesModal.style.display = 'none';
        document.body.style.overflow = '';
        if (rulesAgreeCheckbox) rulesAgreeCheckbox.checked = false;
        if (joinNowBtn) joinNowBtn.disabled = true;
      }
    });
  }

  // Enable the "Join Now" button only if the checkbox is checked
  if (rulesAgreeCheckbox && joinNowBtn) {
    rulesAgreeCheckbox.addEventListener('change', function() {
      joinNowBtn.disabled = !this.checked;
    });
  }

  // On "Join Now" modal button click: redirect to login
  if (joinNowBtn) {
    joinNowBtn.addEventListener('click', function() {
      if (!joinNowBtn.disabled) {
        window.location.href = '../Html/login.html';
      }
    });
  }
});
window.fbAsyncInit = function() {
  FB.init({
    appId      : '1043070284329508', // <-- Tumaar App ID ekhane
    cookie     : true,
    xfbml      : true,
    version    : 'v19.0'
  });
};

document.addEventListener('click', function(e) {
  const fbBtn = e.target.closest('.fb-btn');
  if (fbBtn) {
    let selectedRole = document.getElementById('role')?.value || '';
    if (!selectedRole) {
      alert('Please select your role first!');
      return;
    }
    FB.login(function(response) {
      if (response.authResponse) {
        FB.api('/me', {fields: 'name,email,picture'}, function(profile) {
          fetch('../Php/facebook-signup.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              fb_id: profile.id,
              name: profile.name,
              email: profile.email,
              picture: profile.picture?.data?.url,
              role: selectedRole
            })
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              const roleToPage = {
                user: '../Html/User_Home.html',
                volunteer: '../Html/Volunteer_Home.html',
                police: '../Html/Policeman_Home.html',
                contributor: '../Html/Camera_Contribution_Home.html'
              };
              const goTo = roleToPage[data.role || selectedRole] || '../Html/User_Home.html';
              alert('Sign up successful as ' + (data.role || selectedRole) + '!');
              window.location.href = goTo;
            } else {
              alert(data.error || 'Facebook sign up failed!');
            }
          });
        });
      } else {
        alert('Facebook login cancelled or failed.');
      }
    }, {scope: 'email,public_profile'});
  }
});