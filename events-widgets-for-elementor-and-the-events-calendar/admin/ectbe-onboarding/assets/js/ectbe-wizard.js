/* =========================================================================
   idea-2 — Wizard state machine.
   Pages that include this script must define window.ECTBE_WIZARD before it
   loads. The runtime renders the progress strip, handles step navigation,
   persists state to localStorage, validates before Next, and intercepts
   Pro option clicks to show the in-wizard upsell.

   window.ECTBE_WIZARD = {
     slug: 'shortcodes-blocks',
     steps: [
       { id: 'method',       label: 'Method' },
       { id: 'layout-style', label: 'Layout & Style' },
       { id: 'display',      label: 'Display' },
       { id: 'review',       label: 'Review' },
       { id: 'success',      label: 'Done' }
     ],
     summaryLabels: {
       method: 'Creation method',
       layout: 'Layout',
       ...
     }
   };
   ========================================================================= */

(function () {
  'use strict';

  if (!window.ECTBE_WIZARD) return;

  var config = window.ECTBE_WIZARD;
  var slug = config.slug || 'wizard';
  var steps = config.steps || [];
  var stateKey = 'ectbe:wizard:' + slug + ':state';

  // ---------- State ---------------------------------------------------------

  var state = {
    currentStepIdx: 0,
    selections: {},
    // Opt-in default: wizards that ship with consent pre-checked set
    // `defaultTelemetry: true` in their ECTBE_WIZARD config. localStorage
    // still wins for returning users (Object.assign below).
    telemetryAccepted: !!config.defaultTelemetry
  };

  try {
    var saved = window.localStorage.getItem(stateKey);
    if (saved) {
      var parsed = JSON.parse(saved);
      // Reset to step 0 if we previously reached success (avoid stuck loop)
      if (parsed.currentStepIdx >= steps.length) parsed.currentStepIdx = 0;
      Object.assign(state, parsed);
    }
  } catch (_) {}

  function saveState() {
    try { window.localStorage.setItem(stateKey, JSON.stringify(state)); } catch (_) {}
  }

  function clearState() {
    try { window.localStorage.removeItem(stateKey); } catch (_) {}
  }

  function getCurrentStepId() {
    return steps[state.currentStepIdx] ? steps[state.currentStepIdx].id : null;
  }

  // ---------- Progress strip rendering --------------------------------------

  function renderProgressStrip() {
    var strip = document.querySelector('[data-wizard-progress]');
    if (!strip) return;
    // Detect strip format: v2 shell (header) uses .ectbe-wizard-header-step class
    // and needs connectors between steps. Original shell uses .ectbe-wizard-progress__step.
    var isV2 = strip.classList.contains('ectbe-wizard-header__steps');
    if (isV2) {
      var html = '';
      for (var i = 0; i < steps.length; i++) {
        html += '<li class="ectbe-wizard-header-step" data-step-idx="' + i + '">';
        html +=   '<span class="ectbe-wizard-header-step__num">' + (i + 1) + '</span>';
        html +=   '<span class="ectbe-wizard-header-step__label">' + steps[i].label + '</span>';
        html += '</li>';
        if (i < steps.length - 1) {
          html += '<li class="ectbe-wizard-header__connector" data-connector-idx="' + i + '"></li>';
        }
      }
      strip.innerHTML = html;
    } else {
      strip.innerHTML = steps.map(function (step, idx) {
        return '<li class="ectbe-wizard-progress__step" data-step-idx="' + idx + '">' +
               '<span class="ectbe-wizard-progress__num"><span class="ectbe-wizard-progress__num-label">' + (idx + 1) + '</span></span>' +
               '<span class="ectbe-wizard-progress__step-label">' + step.label + '</span>' +
               '</li>';
      }).join('');
    }
  }

  // ---------- Validation ----------------------------------------------------

  function isValid(stepId) {
    var stepEl = document.querySelector('.ectbe-wizard-step[data-step="' + stepId + '"]');
    if (!stepEl) return false;
    if (stepEl.dataset.alwaysValid === 'true') return true;

    var requiredGroups = stepEl.querySelectorAll('[data-required-selection]');
    if (requiredGroups.length === 0) return true;

    for (var i = 0; i < requiredGroups.length; i++) {
      var group = requiredGroups[i];
      // Skip groups inside hidden sub-steps
      if (group.closest('[hidden]')) continue;
      var name = group.dataset.requiredSelection;
      if (!state.selections[name]) return false;
    }
    return true;
  }

  // ---------- Render --------------------------------------------------------

  function render() {
    var currentId = getCurrentStepId();
    if (!currentId) return;

    // Show only the current step
    document.querySelectorAll('.ectbe-wizard-step').forEach(function (step) {
      step.classList.toggle('is-active', step.dataset.step === currentId);
    });

    // Update progress strip states — both v1 (.ectbe-wizard-progress__step) and
    // v2 (.ectbe-wizard-header-step) work off the same data-step-idx attribute.
    document.querySelectorAll('.ectbe-wizard-progress__step, .ectbe-wizard-header-step').forEach(function (item) {
      var idx = parseInt(item.dataset.stepIdx, 10);
      item.classList.toggle('is-current', idx === state.currentStepIdx);
      item.classList.toggle('is-done', idx < state.currentStepIdx);
    });
    document.querySelectorAll('.ectbe-wizard-header__connector').forEach(function (conn) {
      var idx = parseInt(conn.dataset.connectorIdx, 10);
      conn.classList.toggle('is-done', idx < state.currentStepIdx);
    });

    // Update Back button visibility (all instances — v1 sticky footer + v2 inline)
    document.querySelectorAll('[data-wizard-back]').forEach(function (btn) {
      btn.classList.toggle('is-visible', state.currentStepIdx > 0);
    });

    // Footer meta / inline nav meta (Step X of Y)
    document.querySelectorAll('[data-wizard-meta]').forEach(function (meta) {
      meta.textContent = currentId === 'success'
        ? 'Setup complete'
        : 'Step ' + (state.currentStepIdx + 1) + ' of ' + steps.length;
    });

    // v1 sticky footer: hide on success
    var footer = document.querySelector('.ectbe-wizard__footer');
    if (footer) footer.style.display = currentId === 'success' ? 'none' : 'flex';

    // v2 inline nav: hide on success (any [data-wizard-card-nav] inside active step)
    document.querySelectorAll('.ectbe-wizard-step .ectbe-wizard-card__nav').forEach(function (nav) {
      var step = nav.closest('.ectbe-wizard-step');
      if (step && step.dataset.step === 'success') nav.style.display = 'none';
    });

    updateNextButton();
    restoreSelectionsForCurrentStep();

    // Restore telemetry checkbox (page script may override from PHP opt-in).
    var telemetryBox = document.querySelector('[data-wizard-telemetry]');
    if (telemetryBox && !telemetryBox.closest('.ectbe-telemetry[hidden]')) {
      telemetryBox.checked = !!state.telemetryAccepted;
    }

    // Render review summary if we're there
    if (currentId === 'review') renderSummary();

    // Scroll up for new step
    if (window.scrollTo) {
      try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (_) { window.scrollTo(0, 0); }
    }

    // Emit step-change event so per-page scripts can react (e.g. re-render
    // video preview / promo state on returning to step 1).
    try {
      document.dispatchEvent(new CustomEvent('ectbe:wizard-step', {
        detail: { stepId: currentId, stepIdx: state.currentStepIdx }
      }));
    } catch (_) {}
  }

  function updateNextButton() {
    var nextBtn = document.querySelector('[data-wizard-next]');
    if (!nextBtn) return;
    var currentId = getCurrentStepId();
    var label = nextBtn.querySelector('[data-wizard-next-label]');
    if (label) {
      if (currentId === 'review') label.textContent = 'Create draft page';
      else if (steps[state.currentStepIdx + 1] && steps[state.currentStepIdx + 1].id === 'success') label.textContent = 'Create draft page';
      else label.textContent = 'Next';
    }

    var valid = isValid(currentId);
    if (valid) {
      nextBtn.classList.remove('ectbe-btn-disabled');
      nextBtn.classList.add('ectbe-btn-primary');
      nextBtn.removeAttribute('disabled');
      nextBtn.removeAttribute('aria-disabled');
    } else {
      nextBtn.classList.remove('ectbe-btn-primary');
      nextBtn.classList.add('ectbe-btn-disabled');
      nextBtn.setAttribute('disabled', 'disabled');
      nextBtn.setAttribute('aria-disabled', 'true');
    }
  }

  function restoreSelectionsForCurrentStep() {
    var currentId = getCurrentStepId();
    var stepEl = document.querySelector('.ectbe-wizard-step[data-step="' + currentId + '"]');
    if (!stepEl) return;

    // Mark selected options (all v1/v2 card variants)
    stepEl.querySelectorAll('.ectbe-wizard-option, .ectbe-wizard-swatch, .ectbe-editor-option, .ectbe-layout-card, .ectbe-style-card, .ectbe-toggle-option').forEach(function (opt) {
      var group = opt.closest('[data-required-selection]') || opt.closest('[data-selection-group]');
      var name = group ? (group.dataset.requiredSelection || group.dataset.selectionGroup) : null;
      var value = opt.dataset.value;
      if (name && value) {
        opt.classList.toggle('is-selected', state.selections[name] === value);
      }
    });

    // Reveal sub-steps + apply branching in one pass
    // (data-reveal-on and data-show-on can coexist on the same element)
    applyConditionalSubSteps(stepEl);

    // Restore form input values (text, email, etc.)
    stepEl.querySelectorAll('[data-wizard-input]').forEach(function (input) {
      var name = input.dataset.wizardInput;
      if (state.selections[name] != null) input.value = state.selections[name];
    });

    // Hide any open upsell screens within this step
    stepEl.querySelectorAll('[data-wizard-upsell]').forEach(function (el) { el.hidden = true; });
    stepEl.querySelectorAll('[data-wizard-step-content]').forEach(function (el) { el.hidden = false; });
  }

  function applyConditionalSubSteps(scope) {
    var root = scope || document;
    var elements = root.querySelectorAll('[data-show-on], [data-reveal-on]');
    elements.forEach(function (el) {
      var hide = false;
      if (el.hasAttribute('data-show-on')) {
        var cond = el.dataset.showOn || '';
        var parts = cond.split(':');
        if (parts.length === 2 && state.selections[parts[0]] !== parts[1]) hide = true;
      }
      if (!hide && el.hasAttribute('data-reveal-on')) {
        var trigger = el.dataset.revealOn;
        if (!state.selections[trigger]) hide = true;
      }
      el.hidden = hide;
    });
  }

  function renderSummary() {
    var summaryEl = document.querySelector('[data-wizard-summary]');
    if (!summaryEl) return;
    var labels = config.summaryLabels || {};
    var items = Object.keys(state.selections).map(function (key) {
      var label = labels[key] || humanize(key);
      var value = humanize(state.selections[key]);
      return '<li class="ectbe-wizard-summary__item">' +
             '<span class="ectbe-wizard-summary__label">' + label + '</span>' +
             '<span class="ectbe-wizard-summary__value">' + value + '</span>' +
             '</li>';
    });
    summaryEl.innerHTML = items.join('');
  }

  function humanize(slug) {
    if (typeof slug !== 'string') return String(slug);
    return slug.split('-').map(function (w) {
      return w.charAt(0).toUpperCase() + w.slice(1);
    }).join(' ');
  }

  // ---------- Navigation ----------------------------------------------------

  // Allow page scripts (ectbe-wizard-wp.js) to intercept Next, e.g. save
  // telemetry prefs on the layout-query step before advancing.
  window.ECTBE_WIZARD_RUNTIME = {
    beforeNext: null,
    getState: function () { return state; },
    setTelemetryAccepted: function (val) {
      state.telemetryAccepted = !!val;
      saveState();
      var box = document.querySelector('[data-wizard-telemetry]');
      if (box) box.checked = !!val;
    }
  };

  function next() {
    if (!isValid(getCurrentStepId())) return;
    if (state.currentStepIdx < steps.length - 1) {
      state.currentStepIdx++;
      saveState();
      render();
    }
  }

  function requestNext() {
    if (!isValid(getCurrentStepId())) return;
    var stepId = getCurrentStepId();
    var proceed = function () { next(); };
    if (typeof window.ECTBE_WIZARD_RUNTIME.beforeNext === 'function') {
      var handled = window.ECTBE_WIZARD_RUNTIME.beforeNext(stepId, proceed);
      if (handled === false) return;
    }
    proceed();
  }

  function back() {
    if (state.currentStepIdx > 0) {
      state.currentStepIdx--;
      saveState();
      render();
    }
  }

  function reset() {
    state = { currentStepIdx: 0, selections: {}, telemetryAccepted: false };
    clearState();
    render();
  }

  // Expose navigation API for WordPress integration (ectbe-wizard-wp.js).
  window.__ECTBE_WIZARD_API = {
    next: next,
    back: back,
    reset: reset,
    getState: function () { return state; }
  };

  function showUpsell(stepEl, layoutName) {
    if (!stepEl) return;
    var content = stepEl.querySelector('[data-wizard-step-content]');
    var upsell = stepEl.querySelector('[data-wizard-upsell]');
    if (content) content.hidden = true;
    if (upsell) {
      upsell.hidden = false;
      // Inject the layout name into the upsell title if a slot exists
      var slot = upsell.querySelector('[data-wizard-upsell-layout]');
      if (slot && layoutName) slot.textContent = layoutName;
    }
  }

  function hideUpsell(stepEl) {
    if (!stepEl) return;
    var content = stepEl.querySelector('[data-wizard-step-content]');
    var upsell = stepEl.querySelector('[data-wizard-upsell]');
    if (content) content.hidden = false;
    if (upsell) upsell.hidden = true;
  }

  // ---------- Click delegation ---------------------------------------------

  document.addEventListener('click', function (e) {
    // Option / swatch / v2 editor / v2 step-2 cards
    var opt = e.target.closest('.ectbe-wizard-option, .ectbe-wizard-swatch, .ectbe-editor-option, .ectbe-layout-card, .ectbe-style-card, .ectbe-toggle-option');
    if (opt) {
      // If the option is an anchor, prevent default navigation
      if (opt.tagName === 'A') e.preventDefault();
      var stepEl = opt.closest('.ectbe-wizard-step');

      // Pro lock → upsell screen, do not select
      if (opt.classList.contains('is-locked')) {
        e.preventDefault();
        var name = (opt.querySelector('.ectbe-wizard-option__name') || {}).textContent || 'this option';
        showUpsell(stepEl, name);
        return;
      }

      var group = opt.closest('[data-required-selection]') || opt.closest('[data-selection-group]');
      if (!group) return;
      var gName = group.dataset.requiredSelection || group.dataset.selectionGroup;
      var value = opt.dataset.value;
      if (!gName || !value) return;

      state.selections[gName] = value;
      saveState();

      // Update selection visuals in this group (all option-card variants)
      group.querySelectorAll('.ectbe-wizard-option, .ectbe-wizard-swatch, .ectbe-editor-option, .ectbe-layout-card, .ectbe-style-card, .ectbe-toggle-option').forEach(function (o) {
        o.classList.toggle('is-selected', o === opt);
      });

      // Re-apply branching + reveal conditions across the whole page so future
      // steps update too. This also handles the just-selected option's reveal.
      applyConditionalSubSteps();

      updateNextButton();
      return;
    }

    // Next
    var nextBtn = e.target.closest('[data-wizard-next]');
    if (nextBtn && !nextBtn.hasAttribute('disabled')) {
      e.preventDefault();
      requestNext();
      return;
    }

    // Back
    var backBtn = e.target.closest('[data-wizard-back]');
    if (backBtn) {
      e.preventDefault();
      back();
      return;
    }

    // Skip — currently equivalent to "no selection, just advance"
    var skipBtn = e.target.closest('[data-wizard-skip]');
    if (skipBtn) {
      e.preventDefault();
      if (state.currentStepIdx < steps.length - 1) {
        state.currentStepIdx++;
        saveState();
        render();
      }
      return;
    }

    // Reset
    var resetBtn = e.target.closest('[data-wizard-reset]');
    if (resetBtn) {
      e.preventDefault();
      if (window.confirm('Reset wizard? Your progress will be lost.')) reset();
      return;
    }

    // Close upsell
    var upsellClose = e.target.closest('[data-wizard-upsell-close]');
    if (upsellClose) {
      e.preventDefault();
      var stepEl2 = upsellClose.closest('.ectbe-wizard-step');
      hideUpsell(stepEl2);
      return;
    }
  });

  // Telemetry checkbox + form inputs
  document.addEventListener('change', function (e) {
    if (e.target.matches('[data-wizard-telemetry]')) {
      state.telemetryAccepted = !!e.target.checked;
      saveState();
      return;
    }
    if (e.target.matches('[data-wizard-input]')) {
      var name = e.target.dataset.wizardInput;
      state.selections[name] = e.target.value;
      saveState();
      updateNextButton();
    }
  });

  document.addEventListener('input', function (e) {
    if (e.target.matches('[data-wizard-input]')) {
      var name = e.target.dataset.wizardInput;
      state.selections[name] = e.target.value;
      saveState();
      updateNextButton();
    }
  });

  // ---------- Preview toggle (extracted from ectbe-dashboard.js so wizard pages
  // don't need to load the Dashboard runtime — which auto-injects the Layout
  // Picker modal into the DOM and pollutes the page footer). ----------------

  function getQueryParam(name) {
    var match = window.location.search.match(new RegExp('[?&]' + name + '=([^&]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  function initPreviewToggle() {
    var toggle = document.querySelector('.ectbe-preview-toggle');

    // Preview config comes from the legacy `.ectbe-preview-toggle` markup
    // when present, else from window.ECTBE_PREVIEW_CONFIG (set synchronously
    // by the prototype-only stack simulator, assets/ectbe-sim.js, which
    // replaced the fixed-state dropdown). Neither source → quiet no-op,
    // exactly like the old missing-toggle early-return.
    var simConfig = window.ECTBE_PREVIEW_CONFIG || null;
    if (!toggle && !simConfig) return;

    // `?clean=1` hides the toggle UI (for stakeholder demos / screen
    // recordings), but a persisted preview state should still apply so a
    // pre-configured state can be shown without the toggle chrome.
    if (getQueryParam('clean') === '1') {
      document.body.setAttribute('data-preview-clean', 'true');
    }

    var pageKey = (toggle && toggle.dataset.previewKey)
      || (simConfig && simConfig.previewKey)
      || 'wizard';
    var storageKey = 'ectbe-preview-state:' + pageKey;
    var options = toggle ? toggle.querySelectorAll('.ectbe-preview-toggle__option') : [];

    function applyStateVisibility(pState) {
      document.querySelectorAll('[data-states]').forEach(function (el) {
        var states = el.dataset.states.split(/\s+/).filter(Boolean);
        el.hidden = states.indexOf(pState) === -1;
      });
    }

    function applyPreviewState(pState) {
      document.body.setAttribute('data-preview-state', pState);
      options.forEach(function (opt) {
        opt.setAttribute('aria-pressed', opt.dataset.state === pState ? 'true' : 'false');
      });
      applyStateVisibility(pState);
      try { window.localStorage.setItem(storageKey, pState); } catch (_) {}
      try {
        document.dispatchEvent(new CustomEvent('ectbe:preview-state', {
          detail: { state: pState, key: pageKey }
        }));
      } catch (_) {}
    }

    // Expose the exact apply path (body attribute + [data-states] visibility
    // + localStorage persistence + `ectbe:preview-state` event) so the
    // prototype-only simulator drives states through the SAME state machine
    // the old dropdown options used.
    window.ECTBE_PREVIEW = {
      key: pageKey,
      storageKey: storageKey,
      apply: applyPreviewState
    };

    var saved = null;
    try { saved = window.localStorage.getItem(storageKey); } catch (_) {}
    var defaultState = (toggle && toggle.dataset.previewDefault)
      || (simConfig && simConfig.previewDefault)
      || (options[0] && options[0].dataset.state)
      || 'default';
    applyPreviewState(saved || defaultState);

    options.forEach(function (opt) {
      opt.addEventListener('click', function () {
        applyPreviewState(opt.dataset.state);
      });
    });
  }

  // ---------- Boot ----------------------------------------------------------

  function boot() {
    renderProgressStrip();
    initPreviewToggle();
    render();
  }

  if (document.readyState !== 'loading') boot();
  else document.addEventListener('DOMContentLoaded', boot);
})();
