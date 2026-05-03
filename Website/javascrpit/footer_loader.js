// Loads shared_footer.html into any element with id 'site-footer-placeholder'
(function () {
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
