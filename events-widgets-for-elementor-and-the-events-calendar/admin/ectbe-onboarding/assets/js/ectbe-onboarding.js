/* =========================================================================
   Events Widgets for Elementor — setup wizard onboarding logic.
   Extracted verbatim from the inline <script> blocks of
   wizard-events-widgets.html (page config + the pre-render localStorage
   bootstrap stay inline in the HTML — they must run before
   ectbe-wizard.js is parsed / boots).

   Load order: ectbe-wizard.js first, then this file.
   ========================================================================= */

/* =========================================================================
   CONFIG — single source of truth for every external URL, YouTube base
   and UTM constant this wizard uses. Edit here, not in the code below.

   NOTE ON VIDEO IDS: the two walkthrough video ids live in the HTML as
   data-youtube-id attributes on the Step 1 mode options:
     - Events Listing (Events Widgets for Elementor) → 2m74nSrEo0g
     - Single Event Page Template (Single Page Builder) → 50FBrcqoB-M
   ========================================================================= */
var ECTBE_ONBOARDING_CONFIG = {
  UTM: {
    // Owner canonical code for THIS plugin — "ecteb", NOT the "ectbe" CSS
    // prefix. utm_source is ALWAYS this wizard's code, even when the link
    // points at a sibling addon's landing page.
    SOURCE: 'ecteb_plugin',
    MEDIUM: 'inside',
    // utm_content = onboarding_step{N} derived from the wizard step hosting
    // the link; this value is only used for links outside any step.
    CONTENT_FALLBACK: 'onboarding',
    // Hosts that receive UTM parameters. wordpress.org links get NONE.
    HOSTS: ['eventscalendaraddons.com', 'coolplugins.net']
  },
  URLS: {
    // Event Single Page Builder Pro landing page (Step 1 sibling promo).
    SPB_PRO: 'https://eventscalendaraddons.com/plugin/event-single-page-builder-pro/',
    // Resolved at runtime from PHP (ECTBE_ONBOARDING / __ECTBE_SPB_WIZARD_URL).
    SPB_WIZARD: ''
  },
  YOUTUBE: {
    THUMB_BASE: 'https://img.youtube.com/vi/',
    EMBED_BASE: 'https://www.youtube.com/embed/'
  }
};

(function () {
  var url = (window.ECTBE_ONBOARDING && window.ECTBE_ONBOARDING.spbWizardUrl)
    || (typeof window.__ECTBE_SPB_WIZARD_URL === 'string' && window.__ECTBE_SPB_WIZARD_URL)
    || '';
  if (url) {
    ECTBE_ONBOARDING_CONFIG.URLS.SPB_WIZARD = url;
  }
})();

/* =========================================================================
   Shared helpers used by every step script. Kept minimal so the file
   doesn't accumulate boilerplate.

     __ECTBE.setDisplayMode  — flips body[data-display-mode] AND persists
                               to localStorage so a page reload doesn't
                               lose the user's "shortcode inside builder"
                               preference.
   ========================================================================= */
window.__ECTBE = {
  setDisplayMode: function (mode) {
    var slug = window.ECTBE_WIZARD.slug;
    var key = 'ectbe:wizard:' + slug + ':displayMode';
    if (mode === 'shortcode') {
      document.body.setAttribute('data-display-mode', 'shortcode');
      try { localStorage.setItem(key, 'shortcode'); } catch (_) {}
    } else {
      document.body.removeAttribute('data-display-mode');
      try { localStorage.removeItem(key); } catch (_) {}
    }
  },

  // Track whether the draft page has been created. Flips the Step 4 button
  // set from "Create Draft Page" to "Preview / Edit / Change styling" and
  // marks the progress-strip Done step in success green. Persisted so a
  // reload on Step 4 keeps the post-create state.
  setPageCreated: function (created) {
    var slug = window.ECTBE_WIZARD.slug;
    var key = 'ectbe:wizard:' + slug + ':pageCreated';
    if (created) {
      document.body.setAttribute('data-page-created', 'true');
      try { localStorage.setItem(key, '1'); } catch (_) {}
    } else {
      document.body.removeAttribute('data-page-created');
      try { localStorage.removeItem(key); } catch (_) {}
    }
  },

  // ---------------- UTM appender ---------------------------------------
  // Walks every outgoing link to the hosts in CONFIG.UTM.HOSTS and appends
  //   utm_source={CONFIG.UTM.SOURCE}&utm_medium=inside
  //   &utm_campaign={destination}&utm_content=onboarding_step{N}
  // Campaign per owner scheme (demo | get_pro | docs | get_bundle):
  //   - /demos/... and /shortcode/... demo pages → demo
  //   - /docs/...                                → docs
  //   - /pricing                                 → get_bundle
  //   - /plugin/event(s)-... and get-pro pages   → get_pro
  //   - coolplugins.net usage-tracking policy    → policy
  // utm_content is derived from the wizard step (.ectbe-wizard-step) that
  // hosts the link: onboarding_step1..N in ECTBE_WIZARD.steps order.
  // Explicit override via data-utm="{campaign}" on the link.
  applyUtm: function () {
    var UTM = ECTBE_ONBOARDING_CONFIG.UTM;

    function isTarget(url) {
      return UTM.HOSTS.some(function (h) { return url.indexOf(h) !== -1; });
    }
    function detectCampaign(url, el) {
      if (el && el.dataset.utm) return el.dataset.utm;
      // /demos/{slug}/ and /shortcode/{slug}/ (legacy per-layout demo
      // URLs) are both demo destinations → campaign "demo".
      if (/eventscalendaraddons\.com\/(demos|shortcode)\//.test(url)) return 'demo';
      if (/coolplugins\.net\/terms\/usage-tracking/.test(url)) return 'policy';
      if (/eventscalendaraddons\.com\/pricing/.test(url)) return 'get_bundle';
      if (/eventscalendaraddons\.com\/docs\//.test(url)) return 'docs';
      // Any /plugin/event-* or /plugin/events-* landing page counts as a
      // Pro CTA. Note: Event Single Page Builder uses singular "event-",
      // the rest use plural "events-".
      if (/eventscalendaraddons\.com\/(get-?pro|plugin\/events?-)/.test(url)) return 'get_pro';
      return null;
    }
    function detectContent(el) {
      var stepEl = el && el.closest ? el.closest('.ectbe-wizard-step[data-step]') : null;
      var steps = (window.ECTBE_WIZARD && window.ECTBE_WIZARD.steps) || [];
      if (stepEl) {
        var stepId = stepEl.getAttribute('data-step');
        for (var i = 0; i < steps.length; i++) {
          if (steps[i].id === stepId) return 'onboarding_step' + (i + 1);
        }
      }
      return UTM.CONTENT_FALLBACK;
    }
    function append(url, campaign, content) {
      if (!url || !campaign || url.indexOf('utm_source=') !== -1) return url;
      var params = { utm_source: UTM.SOURCE, utm_medium: UTM.MEDIUM,
                     utm_campaign: campaign, utm_content: content };
      var qs = Object.keys(params).map(function (k) {
        return k + '=' + encodeURIComponent(params[k]);
      }).join('&');
      var sep = url.indexOf('?') === -1 ? '?' : '&';
      var hashIdx = url.indexOf('#');
      if (hashIdx === -1) return url + sep + qs;
      return url.slice(0, hashIdx) + sep + qs + url.slice(hashIdx);
    }

    document.querySelectorAll('a[href]').forEach(function (a) {
      var href = a.getAttribute('href');
      if (!href || !isTarget(href)) return;
      var campaign = detectCampaign(href, a);
      if (campaign) a.setAttribute('href', append(href, campaign, detectContent(a)));
    });
    document.querySelectorAll('[data-demo-url]').forEach(function (el) {
      var url = el.getAttribute('data-demo-url');
      if (!url || !isTarget(url)) return;
      var campaign = detectCampaign(url, el);
      if (campaign) el.setAttribute('data-demo-url', append(url, campaign, detectContent(el)));
    });
  }
};

// Run UTM appender once, after the DOM is parsed. It's idempotent
// (skips URLs that already have utm_source), so late-added links stay safe.
if (document.readyState !== 'loading') window.__ECTBE.applyUtm();
else document.addEventListener('DOMContentLoaded', window.__ECTBE.applyUtm);

/* =========================================================================
   Combined Step 2 (Layout & Query) behaviour: Select2-style multiselect
   for the category picker, Pro-selection tracking → notice + nav swap,
   auto-select defaults on step-enter, and pageCreated=true when the
   primary CTA is clicked (Widgets always creates an Elementor page).
   ========================================================================= */
(function () {
  var step = document.querySelector('.ectbe-wizard-step[data-step="layout-query"]');
  if (!step) return;

  // ----- Multiselect widget (Select2-style) ---------------------------
  // Stores selected values in a hidden input as CSV so wizard.js can
  // persist / restore state with no extra plumbing. "all" is a mutually-
  // exclusive value: picking All clears specific selections; picking a
  // specific category removes All. Chip removes fall back to All if the
  // list becomes empty.
  function initMultiselect(root) {
    var control    = root.querySelector('[data-multiselect-control]');
    var dropdown   = root.querySelector('[data-multiselect-dropdown]');
    var chips      = root.querySelector('[data-multiselect-chips]');
    var placeholder= root.querySelector('[data-multiselect-placeholder]');
    var input      = root.querySelector('input[type="hidden"]');
    var options    = root.querySelectorAll('[data-multiselect-option]');

    function getValues() { return (input.value || '').split(',').filter(Boolean); }
    function setValues(vals) {
      input.value = vals.join(',');
      render();
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function render() {
      var vals = getValues();
      chips.innerHTML = '';
      vals.forEach(function (v) {
        var opt = root.querySelector('[data-multiselect-option][data-value="' + v + '"]');
        if (!opt) return;
        var label = opt.querySelector('.ectbe-multiselect__label').textContent;
        var chip = document.createElement('span');
        chip.className = 'ectbe-multiselect__chip';
        chip.innerHTML =
          '<span>' + label + '</span>' +
          '<span class="ectbe-multiselect__chip-remove" role="button" tabindex="0" ' +
            'data-multiselect-remove="' + v + '" aria-label="Remove ' + label + '">' +
            '<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>' +
          '</span>';
        chips.appendChild(chip);
      });
      if (placeholder) placeholder.hidden = vals.length > 0;
      options.forEach(function (opt) {
        opt.classList.toggle('is-selected', vals.indexOf(opt.dataset.value) >= 0);
      });
    }

    function toggleValue(value) {
      var vals = getValues();
      if (value === 'all') { setValues(['all']); return; }
      var idx = vals.indexOf(value);
      if (idx >= 0) vals.splice(idx, 1);
      else { vals = vals.filter(function (v) { return v !== 'all'; }); vals.push(value); }
      if (vals.length === 0) vals = ['all'];
      setValues(vals);
    }

    function removeValue(value) {
      var vals = getValues().filter(function (v) { return v !== value; });
      if (vals.length === 0) vals = ['all'];
      setValues(vals);
    }

    function openDropdown()  { dropdown.hidden = false; root.classList.add('is-open');    control.setAttribute('aria-expanded', 'true'); }
    function closeDropdown() { dropdown.hidden = true;  root.classList.remove('is-open'); control.setAttribute('aria-expanded', 'false'); }

    control.addEventListener('click', function (e) {
      if (e.target.closest('[data-multiselect-remove]')) return;
      e.preventDefault();
      dropdown.hidden ? openDropdown() : closeDropdown();
    });
    control.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        dropdown.hidden ? openDropdown() : closeDropdown();
      } else if (e.key === 'Escape') closeDropdown();
    });
    dropdown.addEventListener('click', function (e) {
      var opt = e.target.closest('[data-multiselect-option]');
      if (!opt) return;
      e.preventDefault();
      toggleValue(opt.dataset.value);
    });
    chips.addEventListener('click', function (e) {
      var rm = e.target.closest('[data-multiselect-remove]');
      if (!rm) return;
      e.preventDefault();
      e.stopPropagation();
      removeValue(rm.dataset.multiselectRemove);
    });
    chips.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      var rm = e.target.closest('[data-multiselect-remove]');
      if (!rm) return;
      e.preventDefault();
      e.stopPropagation();
      removeValue(rm.dataset.multiselectRemove);
    });
    document.addEventListener('click', function (e) {
      if (!root.contains(e.target)) closeDropdown();
    });

    // Expose render so refreshStep3 can re-sync the widget after wizard.js
    // restores the hidden input value on Back navigation.
    root._ecaMultiselectRender = render;
    render();
  }

  step.querySelectorAll('[data-multiselect]').forEach(initMultiselect);

  function rerenderMultiselects() {
    step.querySelectorAll('[data-multiselect]').forEach(function (el) {
      if (el._ecaMultiselectRender) el._ecaMultiselectRender();
    });
  }

  // (No editor-conditional visibility on Widgets — every row is always shown.)

  // ----- Auto-select first non-Pro option in each required group -----
  function autoSelectDefaults() {
    step.querySelectorAll('[data-required-selection]').forEach(function (group) {
      if (group.hidden || group.closest('[hidden]')) return;
      if (group.querySelector('.is-selected')) return;
      var first = group.querySelector(
        '.ectbe-layout-card:not([data-pro]), .ectbe-style-card:not([data-pro]), .ectbe-toggle-option:not([data-pro])'
      );
      if (first) first.click();
    });
  }

  // ----- Pro detection → notice + nav swap (visible selections only) --
  function updateProState() {
    var count = 0;
    step.querySelectorAll('.is-selected[data-pro="true"]').forEach(function (el) {
      if (el.offsetParent !== null) count++;
    });
    if (document.body.getAttribute('data-tier') === 'pro') count = 0;   // Pro tier: unlocked, no upsell
    var notice = step.querySelector('[data-pro-notice]');
    var countEl = step.querySelector('[data-pro-count]');
    var plural = step.querySelector('[data-pro-plural]');
    var navRight = step.querySelector('[data-step2-nav-right]');
    if (notice) notice.hidden = count === 0;
    if (countEl) countEl.textContent = count;
    if (plural) plural.textContent = count === 1 ? '' : 's';
    if (navRight) navRight.setAttribute('data-nav-mode', count > 0 ? 'upgrade' : 'default');
  }

  // Click on the primary CTA — Widgets always creates a draft Elementor
  // page, so we flip the pageCreated flag before wizard.js advances.
  step.addEventListener('click', function (e) {
    var cta = e.target.closest('[data-step-primary-cta]');
    if (!cta) return;
    window.__ECTBE.setPageCreated(true);
  });

  // ----- Boot on step-enter -------------------------------------------
  function refresh() {
    rerenderMultiselects();
    autoSelectDefaults();
    updateProState();
  }

  document.addEventListener('ectbe:wizard-step', function (e) {
    if (e.detail.stepId === 'layout-query') requestAnimationFrame(refresh);
  });

  // Refresh Pro state on any selection change inside this step
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.ectbe-layout-card, .ectbe-style-card, .ectbe-toggle-option')) return;
    requestAnimationFrame(updateProState);
  });
})();

/* =========================================================================
   Step 4 (Done) behaviour: read wizard state from localStorage, update
   the builder recap + dynamic title, and toggle cross-sell cards based
   on which sibling addons are installed (preview state).
   Widgets always creates a draft Elementor page, so there is no
   pre-create UI and no shortcode composition.
   ========================================================================= */
(function () {
  var stepSuccess = document.querySelector('.ectbe-wizard-step[data-step="success"]');
  if (!stepSuccess) return;

  var ASSET_BASE = window.ECTBE_WIZARD.assetBase || 'assets/images/';
  // Widgets is Elementor-only, so BUILDER_INFO has one entry.
  var BUILDER_INFO = {
    elementor: { label: 'Elementor', logo: ASSET_BASE + 'elementor-icon.png' }
  };

  // ----- State collection ---------------------------------------------
  // Read persisted state from localStorage (source of truth wizard.js
  // writes to) and use the DOM to look up display labels.
  function collectState() {
    var persisted = {};
    try {
      var raw = localStorage.getItem('ectbe:wizard:' + window.ECTBE_WIZARD.slug + ':state');
      if (raw) {
        var s = JSON.parse(raw);
        if (s && s.selections) persisted = s.selections;
      }
    } catch (_) {}

    var state = {};

    // Look up the layout label from persisted state.
    var layoutValue = persisted.layout;
    if (!layoutValue) {
      var domSel = document.querySelector('.ectbe-wizard-step [data-required-selection="layout"] .is-selected');
      if (domSel) layoutValue = domSel.dataset.value;
    }
    if (layoutValue) {
      var card = document.querySelector(
        '.ectbe-wizard-step [data-required-selection="layout"] [data-value="' + layoutValue + '"]'
      );
      var labelEl = card && card.querySelector('.ectbe-layout-card__name');
      state.layout = {
        value: layoutValue,
        label: labelEl ? labelEl.textContent.trim() : layoutValue
      };
    }

    return state;
  }

  // ----- Refresh Step 4 (Done) ----------------------------------------
  // Widgets is post-create only. Update the title with the picked layout
  // name and set the Elementor builder recap card. The bundle promo below
  // is always visible (team decision — promote the Events Calendar Addons
  // bundle instead of per-sibling cross-sells), so no stack-aware
  // visibility toggling is needed here.
  function refreshStep(stepEl) {
    if (!stepEl) return;
    var state = collectState();
    var layoutName = (state.layout && state.layout.label) || 'Events';
    var bldrPanel = stepEl.querySelector('[data-review-panel="builder-recap"]');

    // Dynamic success-header copy
    var t5 = stepEl.querySelector('[data-step5-title]');
    var l5 = stepEl.querySelector('[data-step5-lede]');
    if (t5) t5.textContent = 'Your Events ' + layoutName + ' widget is ready!';
    if (l5) l5.textContent = 'Draft Elementor page created — preview or open it in Elementor to fine-tune anything.';

    // Builder recap (always Elementor).
    if (bldrPanel) {
      var info = BUILDER_INFO.elementor;
      var logo = bldrPanel.querySelector('[data-review-builder-logo]');
      var title= bldrPanel.querySelector('[data-review-builder-title]');
      var desc = bldrPanel.querySelector('[data-review-builder-desc]');
      if (logo) logo.src = info.logo;
      if (title) title.textContent = 'Events widget inserted in Elementor page';
      if (desc) desc.textContent = 'Preview or open the draft to fine-tune anything.';
    }
  }

  // ----- Step-enter refresh -------------------------------------------
  document.addEventListener('ectbe:wizard-step', function (e) {
    if (e.detail.stepId === 'success') requestAnimationFrame(function () { refreshStep(stepSuccess); });
  });

  // ----- Finish / Recreate: clear persisted state ---------------------
  // Both actions kill the wizard's state key, display-mode key, AND the
  // page-created flag so the next visit starts genuinely fresh. Finish
  // lets the anchor navigate to the hub; Recreate hard-reloads the wizard.
  function clearPersistedState() {
    var slug = window.ECTBE_WIZARD && window.ECTBE_WIZARD.slug;
    if (!slug) return;
    try {
      localStorage.removeItem('ectbe:wizard:' + slug + ':state');
      localStorage.removeItem('ectbe:wizard:' + slug + ':displayMode');
      localStorage.removeItem('ectbe:wizard:' + slug + ':pageCreated');
    } catch (_) {}
    document.body.removeAttribute('data-page-created');
  }
  // Both Finish (bottom of Step 4) and Exit Setup (top header) share the
  // same behaviour: clear ALL persisted wizard keys and let the anchor
  // navigate to the ECA dashboard.
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-wizard-finish]')) return;
    clearPersistedState();
    try { localStorage.setItem('ectbe:wizard:' + window.ECTBE_WIZARD.slug + ':completed', '1'); } catch (_) {}
  });
})();

/* =========================================================================
   Step 1 specific behavior: video swap + promo (SPB Pro) + nav-mode swap
   + auto-advance on dismiss.
   (Originally loaded AFTER ectbe-wizard.js — this file keeps that order.)
   ========================================================================= */
(function () {
  var SIBLING_ICON_BASE = (window.ECTBE_WIZARD && window.ECTBE_WIZARD.siblingIconBase) || 'assets/images/';

  function spbSetupUrl() {
    var wp = window.ECTBE_ONBOARDING;
    if (wp && wp.spbWizardUrl) {
      return wp.spbWizardUrl;
    }
    if (typeof window.__ECTBE_SPB_WIZARD_URL === 'string' && window.__ECTBE_SPB_WIZARD_URL) {
      return window.__ECTBE_SPB_WIZARD_URL;
    }
    return ECTBE_ONBOARDING_CONFIG.URLS.SPB_WIZARD || '';
  }

  // Only one entry — the Single Page mode promotes SPB Pro. Elementor
  // single-page templates ship in the Pro version of Single Page Builder,
  // so `proHref` (external landing page) is used when not installed. If
  // Pro IS installed, `wizardHref` opens the sibling wizard inline.
  var ADDON_MAP = {
    'single-page': {
      addon: 'Event Single Page Builder Pro',
      addonShort: 'Single Page Builder',
      icon: SIBLING_ICON_BASE + 'event-single-page-icon.svg',
      wizardHref: ECTBE_ONBOARDING_CONFIG.URLS.SPB_WIZARD,
      proHref: ECTBE_ONBOARDING_CONFIG.URLS.SPB_PRO,
      desc: 'Design a custom Elementor template for individual event detail pages — Pro only.'
    }
  };

  // Preview state → is Single Page Builder Pro installed?
  function isSiblingInstalled(builder) {
    if (builder !== 'single-page') return false;
    var s = document.body.getAttribute('data-preview-state') || '';
    return s === 'spb-installed';
  }

  function updatePromo(modeValue) {
    var promo = document.querySelector('[data-editor-promo]');
    if (!promo) return;

    // Listing mode (or nothing picked) → no promo, plain Continue nav
    var info = ADDON_MAP[modeValue];
    if (!info) {
      promo.hidden = true;
      promo.innerHTML = '';
      promo.className = 'ectbe-editor-promo';
      setNavMode('default', null);
      return;
    }

    var installed = isSiblingInstalled(modeValue);
    var mod = installed ? 'ectbe-editor-promo--ready' : 'ectbe-editor-promo--suggest';
    var kicker = installed
      ? '<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> Already active'
      : '<span class="dashicons dashicons-star-filled" aria-hidden="true"></span> Separate Addon';
    var title = installed
      ? info.addon + ' is active on your site'
      : 'Get ' + info.addon;
    var desc = installed
      ? 'Jump into its dedicated setup to build your single event page template.'
      : info.desc;

    promo.className = 'ectbe-editor-promo ' + mod;
    promo.hidden = false;
    promo.innerHTML =
      '<span class="ectbe-editor-promo__icon">' +
        '<img src="' + info.icon + '" alt="">' +
      '</span>' +
      '<div class="ectbe-editor-promo__body">' +
        '<span class="ectbe-editor-promo__kicker">' + kicker + '</span>' +
        '<h3 class="ectbe-editor-promo__title">' + title + '</h3>' +
        '<p class="ectbe-editor-promo__desc">' + desc + '</p>' +
      '</div>';

    // Swap the nav-right area to promo mode
    setNavMode(installed ? 'promo-ready' : 'promo-suggest', info);
  }

  function setNavMode(mode, info) {
    var navRight = document.querySelector('.ectbe-wizard-step.is-active [data-nav-right]');
    if (!navRight) return;
    navRight.setAttribute('data-nav-mode', mode);

    if (info) {
      // Push addon name into all promo buttons
      navRight.querySelectorAll('[data-addon-name]').forEach(function (el) {
        el.textContent = info.addonShort;
      });
      var openBtn = navRight.querySelector('[data-promo-open]');
      var installBtn = navRight.querySelector('[data-promo-install]');
      // Open (Pro is installed) → sibling wizard.
      // "Get Pro" (Pro is NOT installed) → external Pro landing page in a
      // new tab so users can purchase without losing their wizard state.
      if (openBtn) {
        var setupUrl = spbSetupUrl() || info.wizardHref;
        openBtn.setAttribute('href', setupUrl);
        openBtn.removeAttribute('role');
      }
      if (installBtn) {
        installBtn.setAttribute('href', info.proHref || info.wizardHref);
        installBtn.setAttribute('target', '_blank');
        installBtn.setAttribute('rel', 'noopener');
      }
      // Re-tag promo links with UTM. applyUtm() only walked the DOM once at
      // DOMContentLoaded, before these hrefs existed. It is idempotent, so
      // re-running just decorates the newly set URLs.
      if (window.__ECTBE && window.__ECTBE.applyUtm) window.__ECTBE.applyUtm();
    }
  }

  function updateVideoPreview(modeValue, videoLabel) {
    var video = document.querySelector('[data-editor-video]');
    var label = document.querySelector('[data-editor-video-label]');
    var watermark = document.querySelector('[data-editor-video-watermark]');
    var frame = document.querySelector('[data-video-frame]');
    var selected = document.querySelector('.ectbe-editor-option.is-selected');

    // Reset any playing iframe when selection changes
    if (video) {
      video.classList.remove('is-playing');
      video.setAttribute('data-editor', modeValue || '');
    }
    if (frame) frame.innerHTML = '';

    if (label) label.textContent = videoLabel || 'Pick a setup to see a walkthrough';

    // Set the YouTube thumbnail as background. Preload maxresdefault (HD);
    // if it fails, fall back to hqdefault. Clear if nothing selected.
    if (video) {
      var ytId = selected && selected.dataset.youtubeId;
      if (ytId) {
        var maxres = ECTBE_ONBOARDING_CONFIG.YOUTUBE.THUMB_BASE + ytId + '/maxresdefault.jpg';
        var hq     = ECTBE_ONBOARDING_CONFIG.YOUTUBE.THUMB_BASE + ytId + '/hqdefault.jpg';
        var probe = new Image();
        probe.onload = function () {
          if (this.naturalWidth < 200) {
            video.style.backgroundImage = 'url("' + hq + '")';
          } else {
            video.style.backgroundImage = 'url("' + maxres + '")';
          }
        };
        probe.onerror = function () {
          video.style.backgroundImage = 'url("' + hq + '")';
        };
        probe.src = maxres;
        video.style.backgroundImage = 'url("' + hq + '")';
      } else {
        video.style.backgroundImage = '';
      }
    }

    // Watermark: always Elementor for Widgets wizard.
    var assetBase = (window.ECTBE_WIZARD && window.ECTBE_WIZARD.assetBase) || 'assets/images/';
    if (watermark) {
      if (modeValue) {
        watermark.src = assetBase + 'elementor-icon.png';
        watermark.hidden = false;
      } else {
        watermark.hidden = true;
      }
    }
  }

  function playVideo() {
    var video = document.querySelector('[data-editor-video]');
    var frame = document.querySelector('[data-video-frame]');
    var selected = document.querySelector('.ectbe-editor-option.is-selected');
    if (!video || !frame) return;
    var ytId = selected && selected.dataset.youtubeId;
    if (!ytId) return;
    frame.innerHTML = '<iframe src="' + ECTBE_ONBOARDING_CONFIG.YOUTUBE.EMBED_BASE + ytId
      + '?autoplay=1&rel=0&modestbranding=1" title="Walkthrough video" '
      + 'frameborder="0" allow="accelerometer; autoplay; clipboard-write; '
      + 'encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    video.classList.add('is-playing');
  }

  document.addEventListener('click', function (e) {
    // Mode option selected → swap video + update promo.
    var opt = e.target.closest('.ectbe-editor-option');
    if (opt && !opt.classList.contains('is-locked')) {
      window.__ECTBE.setDisplayMode(null);
      // Picking a different mode is a substantive change — reset the
      // pageCreated flag so Step 4 starts fresh.
      window.__ECTBE.setPageCreated(false);
      var value = opt.dataset.value;
      var videoLabel = opt.dataset.videoLabel || '';
      requestAnimationFrame(function () {
        updateVideoPreview(value, videoLabel);
        updatePromo(value);
      });
      return;
    }

    // Play the walkthrough when the user clicks the video thumbnail.
    var video = e.target.closest('[data-editor-video]');
    if (video && !video.classList.contains('is-playing') && video.getAttribute('data-editor')) {
      e.preventDefault();
      playVideo();
      return;
    }

    // "No, back to Events Listing" dismiss → switch selection to Listing
    // and auto-advance.
    var dismiss = e.target.closest('[data-editor-promo-dismiss]');
    if (dismiss) {
      e.preventDefault();
      var listingOpt = document.querySelector('.ectbe-editor-option[data-value="listing"]');
      if (listingOpt) listingOpt.click();
      requestAnimationFrame(function () {
        var nextBtn = document.querySelector('.ectbe-wizard-step.is-active [data-wizard-next]');
        if (nextBtn) nextBtn.click();
      });
    }
  });

  // Preferred pre-selection: Listing is the Widgets happy-path; fall back
  // to Single Page if for some reason Listing isn't visible.
  var PREFERRED_ORDER = ['listing', 'single-page'];

  function isVisible(el) {
    return el && el.offsetParent !== null;
  }

  function autoSelectPreferred() {
    var current = document.querySelector('.ectbe-editor-option.is-selected');
    if (isVisible(current)) return;

    // Persisted state always wins over the preferred-order fallback.
    try {
      var slug = window.ECTBE_WIZARD.slug;
      var raw = localStorage.getItem('ectbe:wizard:' + slug + ':state');
      if (raw) {
        var s = JSON.parse(raw);
        if (s && s.selections && s.selections.editor) return;
      }
    } catch (_) {}

    if (current) current.classList.remove('is-selected');
    for (var i = 0; i < PREFERRED_ORDER.length; i++) {
      var opt = document.querySelector('.ectbe-editor-option[data-value="' + PREFERRED_ORDER[i] + '"]');
      if (isVisible(opt)) { opt.click(); return; }
    }
  }

  // Preview state change → re-evaluate promo (install/ready flip).
  document.addEventListener('ectbe:preview-state', function () {
    var selected = document.querySelector('.ectbe-editor-option.is-selected');
    if (isVisible(selected)) {
      updatePromo(selected.dataset.value);
    } else {
      autoSelectPreferred();
    }
  });

  // Re-run when user navigates back to Step 1 from a later step
  document.addEventListener('ectbe:wizard-step', function (e) {
    if (e.detail.stepId !== 'mode') return;
    var selected = document.querySelector('.ectbe-editor-option.is-selected');
    if (isVisible(selected)) {
      updateVideoPreview(selected.dataset.value, selected.dataset.videoLabel || '');
      updatePromo(selected.dataset.value);
    } else {
      autoSelectPreferred();
    }
  });

  // Boot
  function bootstrap() {
    var selected = document.querySelector('.ectbe-editor-option.is-selected');
    if (isVisible(selected)) {
      updateVideoPreview(selected.dataset.value, selected.dataset.videoLabel || '');
      updatePromo(selected.dataset.value);
    } else {
      autoSelectPreferred();
    }
  }
  if (document.readyState !== 'loading') bootstrap();
  else document.addEventListener('DOMContentLoaded', bootstrap);
})();
