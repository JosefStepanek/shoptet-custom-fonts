/**
 * Shoptet Custom Fonts – Admin UI
 */
(function () {
  'use strict';

  const PREVIEW_TEXT = 'Příklad textu • Sample text 123';

  // Stav
  let allFonts      = window.FONTS_DATA || [];
  let selectedFont  = window.CURRENT_FONT || null;
  let activeFilter  = 'all';
  let loadedFonts   = new Set();

  // DOM reference
  const searchInput   = document.getElementById('font-search');
  const fontList      = document.getElementById('font-list');
  const previewBox    = document.getElementById('preview-box');
  const saveBtn       = document.getElementById('save-btn');
  const clearBtn      = document.getElementById('clear-btn');
  const toast         = document.getElementById('toast');
  const currentBanner = document.getElementById('current-font-banner');
  const currentLabel  = document.getElementById('current-font-label');

  // ── Init ──────────────────────────────────────────────────

  function init() {
    renderFilterBtns();
    renderFontList(allFonts);
    updateCurrentBanner(selectedFont);
    updatePreview(selectedFont);

    searchInput.addEventListener('input', onSearch);
    saveBtn.addEventListener('click', onSave);
    if (clearBtn) clearBtn.addEventListener('click', onClear);
  }

  // ── Render filtrovacích tlačítek ──────────────────────────

  function renderFilterBtns() {
    const categories = ['all', ...new Set(allFonts.map(f => f.category))];
    const row = document.getElementById('filter-row');
    row.innerHTML = '';

    categories.forEach(cat => {
      const btn = document.createElement('button');
      btn.className = 'filter-btn' + (cat === activeFilter ? ' active' : '');
      btn.textContent = cat === 'all' ? 'Vše' : capitalize(cat);
      btn.addEventListener('click', () => {
        activeFilter = cat;
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        applyFilters();
      });
      row.appendChild(btn);
    });
  }

  // ── Filtrování a vyhledávání ──────────────────────────────

  function onSearch() {
    applyFilters();
  }

  function applyFilters() {
    const q = searchInput.value.trim().toLowerCase();
    const filtered = allFonts.filter(f => {
      const matchCat  = activeFilter === 'all' || f.category === activeFilter;
      const matchName = !q || f.name.toLowerCase().includes(q);
      return matchCat && matchName;
    });
    renderFontList(filtered);
  }

  // ── Render seznamu fontů ──────────────────────────────────

  function renderFontList(fonts) {
    fontList.innerHTML = '';

    if (fonts.length === 0) {
      fontList.innerHTML = '<div class="font-empty">Žádné fonty neodpovídají hledání.</div>';
      return;
    }

    fonts.forEach(font => {
      const item = document.createElement('label');
      item.className = 'font-item' + (font.name === selectedFont ? ' selected' : '');
      item.dataset.font = font.name;

      item.innerHTML = `
        <input type="radio" name="font" value="${escHtml(font.name)}"
               ${font.name === selectedFont ? 'checked' : ''}>
        <div class="font-meta">
          <div class="font-name">${escHtml(font.name)}</div>
          <div class="font-category">${escHtml(font.category)}</div>
        </div>
        <div class="font-preview" id="prev-${slugify(font.name)}"
             data-font="${escHtml(font.name)}">
          ${escHtml(PREVIEW_TEXT)}
        </div>
      `;

      item.addEventListener('change', () => onSelectFont(font.name, item));

      fontList.appendChild(item);

      // Lazy-load preview font přes IntersectionObserver
      observePreview(item, font.name);
    });
  }

  // ── Výběr fontu ───────────────────────────────────────────

  function onSelectFont(fontName, item) {
    selectedFont = fontName;

    document.querySelectorAll('.font-item').forEach(el => el.classList.remove('selected'));
    item.classList.add('selected');

    updatePreview(fontName);
  }

  // ── Preview ───────────────────────────────────────────────

  function updatePreview(fontName) {
    if (!fontName) {
      previewBox.style.fontFamily = '';
      previewBox.querySelector('h3').textContent = 'Žádný font nevybrán';
      previewBox.querySelector('p').textContent  = 'Eshop bude používat výchozí písmo šablony.';
      return;
    }

    loadGoogleFont(fontName);
    previewBox.style.fontFamily = `'${fontName}', sans-serif`;
    previewBox.querySelector('h3').textContent = fontName;
    previewBox.querySelector('p').textContent  = PREVIEW_TEXT;
  }

  // ── Načtení Google Fontu do stránky (lazy) ────────────────

  function loadGoogleFont(fontName) {
    if (loadedFonts.has(fontName)) return;
    loadedFonts.add(fontName);

    const slug = fontName.replace(/ /g, '+');
    const link = document.createElement('link');
    link.rel  = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${slug}:wght@400;700&display=swap`;
    link.onload = () => markPreviewLoaded(fontName);
    document.head.appendChild(link);
  }

  function markPreviewLoaded(fontName) {
    const el = document.getElementById('prev-' + slugify(fontName));
    if (el) {
      el.style.fontFamily = `'${fontName}', sans-serif`;
      el.classList.add('loaded');
    }
  }

  // ── IntersectionObserver pro lazy načítání preview fontů ──

  function observePreview(item, fontName) {
    if (!('IntersectionObserver' in window)) {
      loadGoogleFont(fontName);
      return;
    }
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          loadGoogleFont(fontName);
          observer.disconnect();
        }
      });
    }, { rootMargin: '100px' });
    observer.observe(item);
  }

  // ── Uložení ───────────────────────────────────────────────

  async function onSave() {
    saveBtn.disabled = true;
    saveBtn.textContent = 'Ukládám…';

    try {
      const res = await fetch(window.API_SAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          project_id:  window.PROJECT_ID,
          font_family: selectedFont || '',
        }),
      });

      const data = await res.json();

      if (!res.ok || data.error) {
        throw new Error(data.error || 'Chyba serveru');
      }

      updateCurrentBanner(data.font);
      showToast('Nastavení uloženo ✓', 'success');
    } catch (err) {
      showToast('Chyba: ' + err.message, 'error');
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Uložit nastavení';
    }
  }

  async function onClear() {
    if (!confirm('Opravdu odebrat vlastní písmo a vrátit výchozí?')) return;
    selectedFont = null;

    document.querySelectorAll('.font-item input').forEach(r => r.checked = false);
    document.querySelectorAll('.font-item').forEach(el => el.classList.remove('selected'));
    updatePreview(null);

    await onSave();
  }

  // ── Banner aktuálního fontu ───────────────────────────────

  function updateCurrentBanner(fontName) {
    if (!fontName) {
      currentBanner.style.display = 'none';
    } else {
      currentBanner.style.display = 'flex';
      currentLabel.textContent = `Aktivní písmo: ${fontName}`;
    }
  }

  // ── Toast ─────────────────────────────────────────────────

  function showToast(msg, type = 'success') {
    toast.textContent = msg;
    toast.className = `toast ${type}`;
    // force reflow
    void toast.offsetWidth;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
  }

  // ── Pomocné funkce ────────────────────────────────────────

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function slugify(str) {
    return str.toLowerCase().replace(/[^a-z0-9]+/g, '-');
  }

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  // ── Start ─────────────────────────────────────────────────

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
