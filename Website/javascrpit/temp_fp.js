
// --- FORGOT PASSWORD LOGIC ---
let resetEmail = '';

function openForgotPasswordModal() {
  document.getElementById('forgot-password-modal').style.display = 'block';
  document.getElementById('fp-step-1').style.display = 'block';
  document.getElementById('fp-step-2').style.display = 'none';
  document.getElementById('fp-step-3').style.display = 'none';
  document.getElementById('fp-email').value = '';
  document.getElementById('fp-code').value = '';
  document.getElementById('fp-new-password').value = '';
}

function closeForgotPasswordModal() {
  document.getElementById('forgot-password-modal').style.display = 'none';
}

async function requestPasswordReset() {
  const email = document.getElementById('fp-email').value.trim();
  if (!email) {
    alert("Please enter your email");
    return;
  }
  
  const fd = new FormData();
  fd.append('email', email);
  
  try {
    const res = await fetch('../Php/forgot_password.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      resetEmail = email;
      document.getElementById('fp-step-1').style.display = 'none';
      document.getElementById('fp-step-2').style.display = 'block';
    } else {
      alert("Error: " + data.error);
    }
  } catch (err) {
    alert("Network error");
  }
}

async function verifyPasswordCode() {
  const code = document.getElementById('fp-code').value.trim();
  if (!code) {
    alert("Please enter the code");
    return;
  }
  
  const fd = new FormData();
  fd.append('email', resetEmail);
  fd.append('code', code);
  
  try {
    const res = await fetch('../Php/verify_code.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      document.getElementById('fp-step-2').style.display = 'none';
      document.getElementById('fp-step-3').style.display = 'block';
    } else {
      alert("Error: " + data.error);
    }
  } catch (err) {
    alert("Network error");
  }
}

async function resetPassword() {
  const newPassword = document.getElementById('fp-new-password').value;
  if (!newPassword) {
    alert("Please enter a new password");
    return;
  }
  
  const fd = new FormData();
  fd.append('email', resetEmail);
  fd.append('new_password', newPassword);
  
  try {
    const res = await fetch('../Php/reset_password.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      alert("Password updated successfully! You can now log in.");
      closeForgotPasswordModal();
    } else {
      alert("Error: " + data.error);
    }
  } catch (err) {
    alert("Network error");
  }
}
