/**
 * Shoptet Custom Fonts - Admin UI
 */
(function () {
  'use strict';

  const FONTS      = window.FONTS_DATA       || [];
  const SETTINGS   = window.CURRENT_SETTINGS || {};
  const DEF_SIZES  = window.DEFAULT_H_SIZES  || {};

  const loadedFonts = new Set();

  // -- Font loading ------------------------------------------

  function loadFont(family) {
    if (!family || loadedFonts.has(family)) return;
    loadedFonts.add(family);
    const slug = family.replace(/ /g, '+');
    const link = document.createElement('link');
    link.rel  = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${slug}:wght@300;400;500;700;800&display=swap`;
    document.head.appendChild(link);
  }

  // -- FontCombobox ------------------------------------------
  //
  // Wraps a .font-combobox element:
  //   <div class="font-combobox">
  //     <div class="combobox-inner">
  //       <input class="font-input">
  //       <button class="combobox-clear">x</button>
  //     </div>
  //     <div class="font-dropdown"></div>
  //   </div>

  class FontCombobox {
    constructor(rootEl, onChange) {
      this.root      = rootEl;
      this.input     = rootEl.querySelector('.font-input');
      this.dropdown  = rootEl.querySelector('.font-dropdown');
      this.clearBtn  = rootEl.querySelector('.combobox-clear');
      this.onChange  = onChange;
      this.selected  = this.input.value.trim() || null;
      this.activeIdx = -1;

      this._bindEvents();
    }

    getValue() { return this.selected; }

    setValue(family) {
      this.selected                = family || null;
      this.input.value             = family || '';
      this.clearBtn.style.display  = family ? '' : 'none';
    }

    // -- Private ----------------------------------------------

    _bindEvents() {
      this.input.addEventListener('input',   () => this._onInput());
      this.input.addEventListener('focus',   () => this._showDropdown(this._filtered()));
      this.input.addEventListener('keydown', (e) => this._onKeydown(e));
      this.clearBtn.addEventListener('click', () => this._clear());

      // Close on outside click
      document.addEventListener('click', (e) => {
        if (!this.root.contains(e.target)) this._hide();
      });
    }

    _filtered() {
      const q = this.input.value.trim().toLowerCase();
      if (!q) return FONTS;
      return FONTS.filter(f => f.name.toLowerCase().includes(q));
    }

    _onInput() {
      this.activeIdx = -1;
      this._showDropdown(this._filtered());
    }

    _showDropdown(fonts) {
      const items = fonts.slice(0, 30);
      this.dropdown.innerHTML = '';

      if (!items.length) {
        this.dropdown.innerHTML = '<div class="fdrop-empty">No results</div>';
        this.dropdown.style.display = 'block';
        return;
      }

      items.forEach((font, i) => {
        const el = document.createElement('div');
        el.className    = 'fdrop-item' + (font.name === this.selected ? ' is-selected' : '');
        el.dataset.idx  = i;
        el.dataset.font = font.name;

        const nameEl = document.createElement('span');
        nameEl.className   = 'fdrop-name';
        nameEl.textContent = font.name;

        const catEl = document.createElement('span');
        catEl.className   = 'fdrop-cat';
        catEl.textContent = font.category;

        el.appendChild(nameEl);
        el.appendChild(catEl);

        // Apply font face after small delay (Google Fonts needs time to load)
        loadFont(font.name);
        setTimeout(() => { nameEl.style.fontFamily = `'${font.name}', sans-serif`; }, 400);

        el.addEventListener('mousedown', (e) => {
          e.preventDefault(); // prevent input blur before we can handle click
          this._select(font.name);
        });

        this.dropdown.appendChild(el);
      });

      this.dropdown.style.display = 'block';
    }

    _hide() {
      this.dropdown.style.display = 'none';
      this.activeIdx = -1;
    }

    _select(family) {
      this.selected                = family;
      this.input.value             = family;
      this.clearBtn.style.display  = '';
      this._hide();
      loadFont(family);
      this.onChange(family);
    }

    _clear() {
      this.selected                = null;
      this.input.value             = '';
      this.clearBtn.style.display  = 'none';
      this._hide();
      this.onChange(null);
    }

    _onKeydown(e) {
      const items = this.dropdown.querySelectorAll('.fdrop-item');
      if (!items.length) return;

      if (e.key === 'Escape') { this._hide(); return; }

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        this.activeIdx = Math.min(this.activeIdx + 1, items.length - 1);
        this._highlightItem(items);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        this.activeIdx = Math.max(this.activeIdx - 1, 0);
        this._highlightItem(items);
      } else if (e.key === 'Enter' && this.activeIdx >= 0) {
        e.preventDefault();
        this._select(items[this.activeIdx].dataset.font);
      }
    }

    _highlightItem(items) {
      items.forEach((el, i) => el.classList.toggle('is-active', i === this.activeIdx));
      if (this.activeIdx >= 0) items[this.activeIdx].scrollIntoView({ block: 'nearest' });
    }
  }

  // -- Preview helpers ---------------------------------------

  function applyBodyPreview(family, weight, size) {
    const box = document.getElementById('body-preview');
    if (!box) return;
    box.style.fontFamily = family ? `'${family}', sans-serif` : '';
    box.style.fontWeight = weight || '';
    box.style.fontSize   = size   || '';
  }

  function applyHeadingsPreview(family, weight) {
    const box = document.getElementById('headings-preview');
    if (!box) return;
    box.querySelectorAll('h1,h2,h3,h4,h5,h6').forEach(el => {
      el.style.fontFamily = family ? `'${family}', sans-serif` : '';
      el.style.fontWeight = weight || '';
    });
  }

  function applyHeadingSizePreview(tag, size) {
    const box = document.getElementById('headings-preview');
    if (!box) return;
    const el = box.querySelector(tag);
    if (el) el.style.fontSize = size || DEF_SIZES[tag] || '';
  }

  function applyHeadingWeightPreview(tag, weight) {
    const box = document.getElementById('headings-preview');
    if (!box) return;
    const el = box.querySelector(tag);
    if (el) el.style.fontWeight = weight || '';
  }

  function applyHeadingColorPreview(tag, color) {
    const box = document.getElementById('headings-preview');
    if (!box) return;
    const el = box.querySelector(tag);
    if (el) el.style.color = color || '';
  }

  function applyHeadingUppercasePreview(tag, uppercase) {
    const box = document.getElementById('headings-preview');
    if (!box) return;
    const el = box.querySelector(tag);
    if (el) el.style.textTransform = uppercase ? 'uppercase' : '';
  }

  // -- Collect current form values ---------------------------

  function collectSettings() {
    const sizes = {}, weights = {}, colors = {}, textTransforms = {};
    document.querySelectorAll('.heading-size-input').forEach(inp => {
      sizes[inp.dataset.tag] = inp.value.trim();
    });
    document.querySelectorAll('.heading-weight-select').forEach(sel => {
      weights[sel.dataset.tag] = sel.value;
    });
    document.querySelectorAll('.heading-color-enable').forEach(chk => {
      if (chk.checked) {
        const colorInput = document.querySelector(`.heading-color-input[data-tag="${chk.dataset.tag}"]`);
        colors[chk.dataset.tag] = colorInput ? colorInput.value : '';
      }
    });
    document.querySelectorAll('.heading-uppercase-check').forEach(chk => {
      textTransforms[chk.dataset.tag] = chk.checked;
    });

    return {
      body: {
        family:         bodyCombobox.getValue() || '',
        weight:         document.getElementById('body-weight').value,
        size:           document.getElementById('body-size').value.trim(),
        extraSelectors: document.getElementById('body-selectors').value.trim(),
      },
      headings: {
        family:         headingsCombobox.getValue() || '',
        weight:         document.getElementById('headings-weight').value,
        extraSelectors: document.getElementById('headings-selectors').value.trim(),
        sizes,
        weights,
        colors,
        textTransforms,
      },
    };
  }

  // -- Save --------------------------------------------------

  async function onSave() {
    const btn = document.getElementById('save-btn');
    btn.disabled    = true;
    btn.textContent = 'Ukládání...';

    try {
      const res = await fetch(window.API_SAVE_URL, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
          project_id: window.PROJECT_ID,
          settings:   collectSettings(),
        }),
      });

      const data = await res.json();
      if (!res.ok || data.error) throw new Error(data.error || 'Chyba serveru');

      showToast('Nastavení uloženo', 'success');
    } catch (err) {
      showToast('Chyba: ' + err.message, 'error');
    } finally {
      btn.disabled    = false;
      btn.textContent = 'Uložit nastavení';
    }
  }

  async function onClear() {
    if (!confirm('Odstranit všechny vlastní fonty a obnovit výchozí nastavení?')) return;

    bodyCombobox.setValue(null);
    headingsCombobox.setValue(null);
    applyBodyPreview(null, null, null);
    applyHeadingsPreview(null, null);

    await onSave();
  }

  // -- Toast -------------------------------------------------

  function showToast(msg, type) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className   = `toast ${type}`;
    void el.offsetWidth;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 3000);
  }

  // -- Init --------------------------------------------------

  let bodyCombobox, headingsCombobox;

  function init() {
    // Body combobox
    bodyCombobox = new FontCombobox(
      document.getElementById('body-combobox'),
      (family) => {
        loadFont(family);
        applyBodyPreview(
          family,
          document.getElementById('body-weight').value,
          document.getElementById('body-size').value.trim(),
        );
      }
    );

    // Headings combobox
    headingsCombobox = new FontCombobox(
      document.getElementById('headings-combobox'),
      (family) => {
        loadFont(family);
        applyHeadingsPreview(family, document.getElementById('headings-weight').value);
      }
    );

    // Restore current settings into the UI
    const body     = SETTINGS.body     || {};
    const headings = SETTINGS.headings || {};

    if (body.family)     { bodyCombobox.setValue(body.family);         loadFont(body.family);     }
    if (headings.family) { headingsCombobox.setValue(headings.family); loadFont(headings.family); }

    // Initial previews
    applyBodyPreview(body.family, body.weight, body.size);
    applyHeadingsPreview(headings.family, headings.weight);

    // Apply per-heading weights / colors / uppercase from saved settings
    if (headings.weights) {
      document.querySelectorAll('.heading-weight-select').forEach(sel => {
        const saved = headings.weights[sel.dataset.tag];
        if (saved) applyHeadingWeightPreview(sel.dataset.tag, saved);
      });
    }
    if (headings.colors) {
      document.querySelectorAll('.heading-color-enable').forEach(chk => {
        const color = headings.colors[chk.dataset.tag];
        if (color) applyHeadingColorPreview(chk.dataset.tag, color);
      });
    }
    if (headings.textTransforms) {
      document.querySelectorAll('.heading-uppercase-check').forEach(chk => {
        if (headings.textTransforms[chk.dataset.tag]) {
          applyHeadingUppercasePreview(chk.dataset.tag, true);
        }
      });
    }

    // Live preview - body weight / size
    document.getElementById('body-weight').addEventListener('change', () =>
      applyBodyPreview(
        bodyCombobox.getValue(),
        document.getElementById('body-weight').value,
        document.getElementById('body-size').value.trim(),
      ));

    document.getElementById('body-size').addEventListener('input', () =>
      applyBodyPreview(
        bodyCombobox.getValue(),
        document.getElementById('body-weight').value,
        document.getElementById('body-size').value.trim(),
      ));

    // Live preview - headings weight
    document.getElementById('headings-weight').addEventListener('change', () =>
      applyHeadingsPreview(
        headingsCombobox.getValue(),
        document.getElementById('headings-weight').value,
      ));

    // Live preview - per-heading sizes
    document.querySelectorAll('.heading-size-input').forEach(inp => {
      inp.addEventListener('input', () =>
        applyHeadingSizePreview(inp.dataset.tag, inp.value.trim()));
    });

    // Live preview - per-heading weights
    document.querySelectorAll('.heading-weight-select').forEach(sel => {
      sel.addEventListener('change', () =>
        applyHeadingWeightPreview(sel.dataset.tag, sel.value));
    });

    // When global headings weight changes, update preview for headings without per-H override
    document.getElementById('headings-weight').addEventListener('change', () => {
      const globalW = document.getElementById('headings-weight').value;
      applyHeadingsPreview(headingsCombobox.getValue(), globalW);
      // Per-H weight selects override their own element
      document.querySelectorAll('.heading-weight-select').forEach(sel => {
        applyHeadingWeightPreview(sel.dataset.tag, sel.value || globalW);
      });
    });

    // Live preview - per-heading colors
    document.querySelectorAll('.heading-color-enable').forEach(chk => {
      const colorInput = document.querySelector(`.heading-color-input[data-tag="${chk.dataset.tag}"]`);
      chk.addEventListener('change', () => {
        colorInput.disabled = !chk.checked;
        applyHeadingColorPreview(chk.dataset.tag, chk.checked ? colorInput.value : '');
      });
      if (colorInput) {
        colorInput.addEventListener('input', () =>
          applyHeadingColorPreview(chk.dataset.tag, colorInput.value));
      }
    });

    // Live preview - per-heading uppercase
    document.querySelectorAll('.heading-uppercase-check').forEach(chk => {
      chk.addEventListener('change', () =>
        applyHeadingUppercasePreview(chk.dataset.tag, chk.checked));
    });

    // Action buttons
    document.getElementById('save-btn').addEventListener('click', onSave);
    document.getElementById('clear-btn').addEventListener('click', onClear);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
