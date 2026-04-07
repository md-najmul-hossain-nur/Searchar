(function () {
  const STORAGE_KEY = 'searchar_work_tracker_items_v1';

  const HTML_FILES = [
    'Admin.html',
    'BroadCast.html',
    'Camera_Contribution_Edit_profile.php',
    'Camera_Contribution_Feed.php',
    'Camera_Contribution_Home.php',
    'Camera_Contribution_Passchanged.php',
    'Camera_Contribution_profile.php',
    'Camera_Contribution_Terms_&_Privacy.html',
    'Donated.Html',
    'Getinvoled.html',
    'Index.html',
    'JoinUs.Html',
    'login.html',
    'News_Details.html',
    'Policeman_Edit_profile.php',
    'Policeman_Home.php',
    'Policeman_Passchanged.php',
    'Policeman_profile.php',
    'Policeman_Terms_&_Privacy.html',
    'RedZone.html',
    'User_Edit_profile.php',
    'User_Home.php',
    'User_Passchagned.php',
    'User_profile.php',
    'User_Terms_&_Privacy.html',
    'Volunteer_Edit_profile.php',
    'Volunteer_Home.php',
    'Volunteer_Passchanged.php',
    'Volunteer_profile.php',
    'Volunteer_Terms_&_Privacy.html',
    'Work_Tracker.html'
  ];

  function inferScopeByPage(page) {
    const p = String(page || '').toLowerCase();
    if (p === 'user_home.php') return 'user_home';
    if (p === 'admin.html') return 'admin';
    if (p.startsWith('policeman_')) return 'policeman';
    if (p.startsWith('volunteer_')) return 'volunteer';
    if (p.startsWith('camera_contribution_')) return 'cameraman';
    return 'shared';
  }

  function buildSeedItems() {
    const focused = [
      {
        id: 'seed-focus-1',
        title: 'User_Home: mission media full preview consistency',
        scope: 'user_home',
        type: 'task',
        status: 'pending',
        priority: 'high',
        page: 'User_Home.php',
        details: 'Ensure all mission evidence opens with same preview behavior across assigned/history modal.'
      },
      {
        id: 'seed-focus-2',
        title: 'User_Home: Volunteer Plus XP rule reconfirm',
        scope: 'user_home',
        type: 'bug',
        status: 'pending',
        priority: 'medium',
        page: 'User_Home.php',
        details: 'Re-verify +400 bonus and rank text after all recent merges.'
      },
      {
        id: 'seed-focus-3',
        title: 'Admin: crime media end-to-end check',
        scope: 'admin',
        type: 'task',
        status: 'in_progress',
        priority: 'high',
        page: 'Admin.html',
        details: 'Validate old + new reports show media in View details for all source types.'
      },
      {
        id: 'seed-focus-4',
        title: 'Admin: combo and user delete behavior QA',
        scope: 'admin',
        type: 'task',
        status: 'pending',
        priority: 'high',
        page: 'Admin.html',
        details: 'Confirm user delete cascades to combo and UI refresh consistency.'
      }
    ];

    const perFileChecklist = HTML_FILES.map((fileName, index) => ({
      id: `seed-page-${index + 1}`,
      title: `${fileName}: pending QA + bug review`,
      scope: inferScopeByPage(fileName),
      type: 'task',
      status: 'pending',
      priority: fileName === 'Admin.html' || fileName === 'User_Home.php' ? 'high' : 'medium',
      page: fileName,
      details: 'Check page flow, media/notification behavior, form validation, and UI responsiveness.'
    }));

    return [...focused, ...perFileChecklist];
  }

  const el = {
    list: document.getElementById('task-list'),
    summary: document.getElementById('summary-text'),
    filterScope: document.getElementById('filter-scope'),
    filterType: document.getElementById('filter-type'),
    filterStatus: document.getElementById('filter-status'),
    filterPage: document.getElementById('filter-page'),
    filterSearch: document.getElementById('filter-search'),
    form: document.getElementById('task-form'),
    resetSeed: document.getElementById('reset-seed'),
    newTitle: document.getElementById('new-title'),
    newScope: document.getElementById('new-scope'),
    newType: document.getElementById('new-type'),
    newStatus: document.getElementById('new-status'),
    newPriority: document.getElementById('new-priority'),
    newDetails: document.getElementById('new-details'),
    newPage: document.getElementById('new-page')
  };

  function loadItems() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return buildSeedItems();
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : buildSeedItems();
    } catch (_e) {
      return buildSeedItems();
    }
  }

  function saveItems(items) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items || []));
  }

  let items = loadItems();

  function labelScope(scope) {
    if (scope === 'admin') return 'Admin';
    if (scope === 'user_home') return 'User Home';
    if (scope === 'policeman') return 'Policeman';
    if (scope === 'volunteer') return 'Volunteer';
    if (scope === 'cameraman') return 'Cameraman';
    return 'All Html Pages';
  }

  function populatePageOptions() {
    const allKnown = new Set(HTML_FILES);
    items.forEach((it) => {
      const p = String(it.page || '').trim();
      if (p) allKnown.add(p);
    });

    const sortedPages = Array.from(allKnown).sort((a, b) => a.localeCompare(b));

    if (el.filterPage) {
      el.filterPage.innerHTML = '<option value="all">All Html Files</option>' + sortedPages.map((p) => `<option value="${escapeHtml(p)}">${escapeHtml(p)}</option>`).join('');
    }

    if (el.newPage) {
      const current = el.newPage.value;
      el.newPage.innerHTML = '<option value="">Select Html file</option>' + sortedPages.map((p) => `<option value="${escapeHtml(p)}">${escapeHtml(p)}</option>`).join('');
      if (current && sortedPages.includes(current)) {
        el.newPage.value = current;
      }
    }
  }

  function applyQueryPreset() {
    const params = new URLSearchParams(window.location.search);
    const scope = String(params.get('scope') || '').trim();
    const page = String(params.get('page') || '').trim();

    if (scope && el.filterScope.querySelector(`option[value="${scope}"]`)) {
      el.filterScope.value = scope;
      if (el.newScope && el.newScope.querySelector(`option[value="${scope}"]`)) {
        el.newScope.value = scope;
      }
    }

    if (page) {
      if (el.filterPage && el.filterPage.querySelector(`option[value="${page}"]`)) {
        el.filterPage.value = page;
      }
      if (el.newPage && el.newPage.querySelector(`option[value="${page}"]`)) {
        el.newPage.value = page;
      }
    }
  }

  function labelStatus(status) {
    if (status === 'in_progress') return 'In Progress';
    if (status === 'blocked') return 'Blocked';
    if (status === 'done') return 'Done';
    return 'Pending';
  }

  function getFiltered() {
    const scope = el.filterScope.value;
    const type = el.filterType.value;
    const status = el.filterStatus.value;
    const page = el.filterPage ? el.filterPage.value : 'all';
    const search = String(el.filterSearch.value || '').trim().toLowerCase();

    return items.filter((it) => {
      if (scope !== 'all' && it.scope !== scope) return false;
      if (type !== 'all' && it.type !== type) return false;
      if (status !== 'all' && it.status !== status) return false;
      if (page !== 'all' && String(it.page || '') !== page) return false;

      if (search) {
        const hay = `${it.title} ${it.details} ${it.page} ${it.scope} ${it.type} ${it.status}`.toLowerCase();
        if (!hay.includes(search)) return false;
      }

      return true;
    });
  }

  function render() {
    const rows = getFiltered();
    const pendingCount = rows.filter((it) => String(it.status || '') !== 'done').length;
    el.summary.textContent = `${rows.length} items (pending ${pendingCount})`;

    if (!rows.length) {
      el.list.innerHTML = '<div class="task-item"><p class="task-desc">No matching pending items.</p></div>';
      return;
    }

    el.list.innerHTML = rows.map((it) => {
      return `
        <article class="task-item priority-${it.priority}">
          <div class="task-top">
            <h3 class="task-title">${escapeHtml(it.title)}</h3>
            <div class="badges">
              <span class="badge">${escapeHtml(labelScope(it.scope))}</span>
              <span class="badge type-${escapeHtml(it.type)}">${escapeHtml(it.type.toUpperCase())}</span>
              <span class="badge status-${escapeHtml(it.status)}">${escapeHtml(labelStatus(it.status))}</span>
              <span class="badge">${escapeHtml(it.priority.toUpperCase())}</span>
            </div>
          </div>
          <div class="task-meta">Page: ${escapeHtml(it.page || 'N/A')}</div>
          <p class="task-desc">${escapeHtml(it.details || '')}</p>
          <div class="task-actions">
            <button class="small-btn" data-action="toggle" data-id="${escapeHtml(it.id)}">Toggle Done</button>
            <button class="small-btn" data-action="delete" data-id="${escapeHtml(it.id)}">Delete</button>
          </div>
        </article>
      `;
    }).join('');
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function addItem(payload) {
    const row = {
      id: `it-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
      title: payload.title,
      scope: payload.scope,
      type: payload.type,
      status: payload.status,
      priority: payload.priority,
      page: payload.page,
      details: payload.details
    };
    items.unshift(row);
    saveItems(items);
    populatePageOptions();
    render();
  }

  function bindEvents() {
    [el.filterScope, el.filterType, el.filterStatus, el.filterPage, el.filterSearch].forEach((node) => {
      if (!node) return;
      node.addEventListener('input', render);
      node.addEventListener('change', render);
    });

    el.form.addEventListener('submit', (event) => {
      event.preventDefault();
      const title = String(el.newTitle.value || '').trim();
      if (!title) {
        alert('Title লাগবে.');
        return;
      }

      addItem({
        title,
        scope: el.newScope.value,
        type: el.newType.value,
        status: el.newStatus.value,
        priority: el.newPriority.value,
        page: String(el.newPage.value || '').trim(),
        details: String(el.newDetails.value || '').trim()
      });

      el.form.reset();
      el.newScope.value = 'user_home';
      el.newType.value = 'task';
      el.newStatus.value = 'pending';
      el.newPriority.value = 'high';
      if (el.newPage) el.newPage.value = '';
    });

    el.list.addEventListener('click', (event) => {
      const btn = event.target.closest('[data-action]');
      if (!btn) return;
      const id = btn.getAttribute('data-id');
      const action = btn.getAttribute('data-action');
      if (!id || !action) return;

      if (action === 'delete') {
        items = items.filter((it) => it.id !== id);
      } else if (action === 'toggle') {
        items = items.map((it) => {
          if (it.id !== id) return it;
          return { ...it, status: it.status === 'done' ? 'pending' : 'done' };
        });
      }

      saveItems(items);
      populatePageOptions();
      render();
    });

    el.resetSeed.addEventListener('click', () => {
      const ok = window.confirm('Reset করে default list এ ফিরতে চাও?');
      if (!ok) return;
      items = buildSeedItems();
      saveItems(items);
      populatePageOptions();
      applyQueryPreset();
      render();
    });
  }

  populatePageOptions();
  applyQueryPreset();
  bindEvents();
  render();
})();
