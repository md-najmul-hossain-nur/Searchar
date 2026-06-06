
// --- SUB-ADMIN MANAGEMENT ---
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const res = await fetch('../Php/admin_fetch_profile.php');
    const data = await res.json();
    if (data.success && data.admin_role === 'main_admin') {
      const el = document.getElementById('sidebar-sub-admins');
      if (el) el.style.display = 'block';
    }
  } catch (err) {
    console.error('Failed to fetch admin profile', err);
  }
});

const subAdminForm = document.getElementById('add-sub-admin-form');
if (subAdminForm) {
  subAdminForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('full_name', document.getElementById('sub-admin-name').value);
    fd.append('email', document.getElementById('sub-admin-email').value);
    fd.append('mobile', document.getElementById('sub-admin-mobile').value);
    fd.append('password', document.getElementById('sub-admin-password').value);

    try {
      const res = await fetch('../Php/admin_add_sub_admin.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        alert('Sub-admin added successfully!');
        subAdminForm.reset();
        loadSubAdmins();
        loadAdminLogs();
      } else {
        alert('Error: ' + data.error);
      }
    } catch (err) {
      alert('Network error adding sub-admin');
    }
  });
}

async function loadSubAdmins() {
  const tbody = document.getElementById('sub-admins-table-body');
  if (!tbody) return;
  try {
    const res = await fetch('../Php/admin_fetch_sub_admins.php');
    const data = await res.json();
    if (data.success) {
      if (data.admins.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4">No sub-admins found</td></tr>';
        return;
      }
      tbody.innerHTML = data.admins.map(a => `
        <tr>
          <td>${a.full_name}</td>
          <td>${a.email}</td>
          <td>${a.mobile}</td>
          <td>${new Date(a.created_at).toLocaleString()}</td>
        </tr>
      `).join('');
    }
  } catch (err) {
    console.error(err);
  }
}

async function loadAdminLogs() {
  const tbody = document.getElementById('admin-logs-table-body');
  if (!tbody) return;
  try {
    const res = await fetch('../Php/admin_fetch_logs.php');
    const data = await res.json();
    if (data.success) {
      if (data.logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4">No logs found</td></tr>';
        return;
      }
      tbody.innerHTML = data.logs.map(l => `
        <tr>
          <td>${new Date(l.created_at).toLocaleString()}</td>
          <td>${l.admin_name}<br><small>${l.admin_email}</small></td>
          <td>${l.action_type}</td>
          <td>${l.details}</td>
        </tr>
      `).join('');
    }
  } catch (err) {
    console.error(err);
  }
}

document.addEventListener('admin:refresh-section', (e) => {
  if (e.detail && e.detail.sectionId === 'sub-admins') {
    loadSubAdmins();
    loadAdminLogs();
  }
});
