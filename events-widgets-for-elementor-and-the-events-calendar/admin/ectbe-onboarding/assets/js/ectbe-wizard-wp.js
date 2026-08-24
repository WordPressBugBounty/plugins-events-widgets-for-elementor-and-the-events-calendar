/* WordPress integration for the Events Widgets onboarding wizard. */
(function () {
  'use strict';

  var draftPage = null;

  function cfg() {
    return window.ECTBE_ONBOARDING || null;
  }

  function wizardApi() {
    return window.__ECTBE_WIZARD_API || null;
  }

  function i18n(key, fallback) {
    var c = cfg();
    return (c && c.i18n && c.i18n[key]) || fallback;
  }

  function proInactive() {
    var c = cfg();
    return !!(c && c.pro && c.pro.installedInactive && c.pro.init);
  }

  function applySpbWizardUrl() {
    var c = cfg();
    if (!c || !c.spbWizardUrl || !window.ECTBE_ONBOARDING_CONFIG) {
      return;
    }
    window.ECTBE_ONBOARDING_CONFIG.URLS.SPB_WIZARD = c.spbWizardUrl;
    window.__ECTBE_SPB_WIZARD_URL = c.spbWizardUrl;

    var openBtn = document.querySelector('[data-promo-open]');
    if (openBtn) {
      openBtn.setAttribute('href', c.spbWizardUrl);
      openBtn.removeAttribute('role');
    }
  }

  /**
   * When Events Widgets Pro is on disk but inactive, swap Upgrade CTAs to Activate Pro.
   * Matches the ESB free onboarding pattern (template-events-calendar).
   */
  function applyWidgetsProUpgradeButtons() {
    var c = cfg();
    var pro = c && c.pro;
    var inactive = proInactive();

    // Pro already active → unlock wizard (existing data-tier=pro gate in ectbe-onboarding.js).
    if (pro && pro.active) {
      document.body.setAttribute('data-tier', 'pro');
    }

    document.querySelectorAll('[data-step2-nav-variant="upgrade"]').forEach(function (el) {
      var label = el.querySelector('[data-upgrade-label]') || el.querySelector('span:not(.dashicons)');
      if (inactive) {
        el.setAttribute('href', '#');
        el.removeAttribute('target');
        el.removeAttribute('rel');
        if (label) label.textContent = i18n('activatePro', 'Activate Pro');
      } else if (pro && pro.proUrl) {
        el.setAttribute('href', pro.proUrl);
        el.setAttribute('target', '_blank');
        el.setAttribute('rel', 'noopener');
        if (label) label.textContent = i18n('upgradePro', 'Upgrade to Pro');
      }
    });

    var desc = document.querySelector('[data-pro-notice-desc]');
    if (desc) {
      desc.textContent = inactive
        ? i18n('activateDesc', 'You already own Events Widgets Pro — activate it to unlock everything you\'ve selected.')
        : i18n('upgradeDesc', 'Upgrade to Events Widgets Pro to unlock everything you\'ve selected. Or pick free options to keep going.');
    }
  }

  function setBusy(btn, busy, labelText) {
    if (!btn) return;
    if (busy) {
      btn.setAttribute('data-ectbe-busy', '1');
      btn.classList.add('is-busy');
      btn.setAttribute('aria-disabled', 'true');
      var label = btn.querySelector('[data-upgrade-label]') || btn.querySelector('span:not(.dashicons)');
      if (label && labelText) {
        btn.setAttribute('data-ectbe-prev-label', label.textContent || '');
        label.textContent = labelText;
      }
    } else {
      btn.removeAttribute('data-ectbe-busy');
      btn.classList.remove('is-busy');
      btn.removeAttribute('aria-disabled');
      var prev = btn.getAttribute('data-ectbe-prev-label');
      var labelRestore = btn.querySelector('[data-upgrade-label]') || btn.querySelector('span:not(.dashicons)');
      if (labelRestore && prev !== null) {
        labelRestore.textContent = prev;
        btn.removeAttribute('data-ectbe-prev-label');
      }
    }
  }

  function setFailed(btn, message) {
    setBusy(btn, false);
    var label = btn.querySelector('[data-upgrade-label]') || btn.querySelector('span:not(.dashicons)');
    if (label) label.textContent = message || i18n('failedRetry', 'Failed — retry');
  }

  function activateWidgetsPro(init) {
    var c = cfg();
    if (!c || !c.ajaxUrl || !c.nonceActivate) {
      return Promise.reject(new Error('Missing ECTBE_ONBOARDING'));
    }
    var fd = new FormData();
    fd.append('action', 'ectbe_onboarding_activate_pro');
    fd.append('nonce', c.nonceActivate);
    fd.append('init', init);
    return fetch(c.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) {
          throw new Error((res && res.data && res.data.message) || 'Activation failed.');
        }
        return res.data || {};
      });
  }

  function parseCategories(raw) {
    if (!raw || raw === 'all') {
      return ['all'];
    }
    if (Array.isArray(raw)) {
      return raw.filter(Boolean);
    }
    return String(raw).split(',').map(function (v) {
      return v.trim();
    }).filter(Boolean);
  }

  function collectPayload() {
    var api = wizardApi();
    var state = api && api.getState ? api.getState() : null;
    var selections = (state && state.selections) || {};

    var layout = selections.layout || 'list';
    var type = selections.time || selections.style || 'upcoming';

    var catInput = document.querySelector('.ectbe-wizard-step[data-step="layout-query"] [data-wizard-input="category"]');
    // Prefer the live multiselect value; persisted wizard state can lag behind.
    var categoryRaw = (catInput && catInput.value) || selections.category || 'all';
    var categories = parseCategories(categoryRaw);

    var layoutLabel = layout;
    var card = document.querySelector('[data-required-selection="layout"] [data-value="' + layout + '"] .ectbe-layout-card__name');
    if (card) {
      layoutLabel = card.textContent.trim();
    }

    return {
      layout: layout,
      type: type,
      categories: categories,
      title: 'Events — ' + layoutLabel
    };
  }

  function setDraftLinks(data) {
    draftPage = data || null;
    var preview = document.querySelector('[data-draft-preview]');
    var edit = document.querySelector('[data-draft-edit]');
    if (preview) {
      preview.href = data && (data.previewUrl || data.viewUrl) ? (data.previewUrl || data.viewUrl) : '#';
      if (data && preview.href !== '#') {
        preview.setAttribute('target', '_blank');
      }
    }
    if (edit) {
      edit.href = data && data.editUrl ? data.editUrl : '#';
      if (data && edit.href !== '#') {
        edit.setAttribute('target', '_blank');
      }
    }
  }

  function createDraftPage() {
    var c = cfg();
    return new Promise(function (resolve, reject) {
      if (!c || !c.ajaxUrl) {
        reject(new Error('Missing ECTBE_ONBOARDING'));
        return;
      }

      saveOnboardingPreferences()
        .then(function () {
          var payload = collectPayload();
          var fd = new FormData();
          fd.append('action', 'ectbe_onboarding_create_page');
          fd.append('nonce', c.nonceCreatePage);
          fd.append('layout', payload.layout);
          fd.append('type', payload.type);
          fd.append('categories', JSON.stringify(payload.categories));
          fd.append('title', payload.title);
          fd.append('telemetry', isTelemetryAccepted() ? '1' : '0');

          return fetch(c.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
              if (!res || !res.success || !res.data) {
                reject(new Error((res && res.data && res.data.message) || 'Create failed'));
                return;
              }
              setDraftLinks(res.data);
              if (window.__ECTBE && window.__ECTBE.setPageCreated) {
                window.__ECTBE.setPageCreated(true);
              }
              resolve(res.data);
            });
        })
        .catch(reject);
    });
  }

  function buildSelectionsPayload() {
    var selections = {};
    try {
      if (window.ECTBE_WIZARD_RUNTIME && typeof window.ECTBE_WIZARD_RUNTIME.getState === 'function') {
        var st = window.ECTBE_WIZARD_RUNTIME.getState();
        if (st && st.selections) {
          selections = Object.assign({}, st.selections);
        }
      }
    } catch (_) { }

    var dm = document.body.getAttribute('data-display-mode');
    if (dm) selections.displayMode = dm;
    return selections;
  }

  function isTelemetryAccepted() {
    var t = window.ECTBE_ONBOARDING && window.ECTBE_ONBOARDING.telemetry;
    if (t && !t.show) return true;

    var wrap = document.querySelector('.ectbe-telemetry');
    if (wrap && wrap.hidden) return true;

    var box = document.querySelector('[data-wizard-telemetry]');
    if (box) return !!box.checked;

    try {
      if (window.ECTBE_WIZARD_RUNTIME && typeof window.ECTBE_WIZARD_RUNTIME.getState === 'function') {
        var st = window.ECTBE_WIZARD_RUNTIME.getState();
        if (st) return !!st.telemetryAccepted;
      }
    } catch (_) { }

    return true;
  }

  function applyTelemetryFromPhp() {
    var t = window.ECTBE_ONBOARDING && window.ECTBE_ONBOARDING.telemetry;
    if (!t) return;

    var wrap = document.querySelector('.ectbe-telemetry');
    var box = document.querySelector('[data-wizard-telemetry]');

    if (!t.show) {
      if (wrap) wrap.hidden = true;
      if (window.ECTBE_WIZARD_RUNTIME && typeof window.ECTBE_WIZARD_RUNTIME.setTelemetryAccepted === 'function') {
        window.ECTBE_WIZARD_RUNTIME.setTelemetryAccepted(true);
      } else if (box) {
        box.checked = true;
      }
      return;
    }

    if (wrap) wrap.hidden = false;
    if (window.ECTBE_WIZARD_RUNTIME && typeof window.ECTBE_WIZARD_RUNTIME.setTelemetryAccepted === 'function') {
      window.ECTBE_WIZARD_RUNTIME.setTelemetryAccepted(!!t.checked);
    } else if (box) {
      box.checked = !!t.checked;
    }
  }

  function saveOnboardingPreferences() {
    var c = cfg();
    if (!c || !c.ajaxUrl || !c.nonceSavePrefs) {
      return Promise.resolve();
    }

    var fd = new FormData();
    fd.append('action', 'ectbe_onboarding_save_preferences');
    fd.append('nonce', c.nonceSavePrefs);
    fd.append('telemetry', isTelemetryAccepted() ? '1' : '0');
    fd.append('selections', JSON.stringify(buildSelectionsPayload()));

    return fetch(c.ajaxUrl, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.success && res.data && res.data.telemetry) {
          window.ECTBE_ONBOARDING.telemetry = res.data.telemetry;
          applyTelemetryFromPhp();
        }
      })
      .catch(function () { /* non-blocking */ });
  }

  function completeWizard() {
    var c = cfg();
    if (!c || !c.ajaxUrl) {
      return Promise.resolve();
    }
    var fd = new FormData();
    fd.append('action', 'ectbe_onboarding_complete');
    fd.append('nonce', c.nonceComplete);
    return fetch(c.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function () { });
  }

  function isCreateDraftCta(btn) {
    return btn
      && btn.hasAttribute('data-wizard-next')
      && btn.hasAttribute('data-step-primary-cta')
      && btn.closest('.ectbe-wizard-step[data-step="layout-query"]');
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-wizard-next][data-step-primary-cta]');
    if (!isCreateDraftCta(btn) || btn.hasAttribute('disabled')) {
      return;
    }

    if (!cfg()) {
      return;
    }

    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    if (btn.classList.contains('is-busy')) {
      return;
    }

    btn.classList.add('is-busy');

    createDraftPage()
      .then(function () {
        var api = wizardApi();
        if (api && api.next) {
          api.next();
          return;
        }
        throw new Error('Wizard navigation unavailable.');
      })
      .catch(function (err) {
        window.alert(err && err.message ? err.message : 'Could not create draft page.');
      })
      .finally(function () {
        btn.classList.remove('is-busy');
      });
  }, true);

  // Pro installed but inactive → Activate Pro instead of opening the buy URL.
  document.addEventListener('click', function (e) {
    var upgrade = e.target.closest('[data-step2-nav-variant="upgrade"]');
    if (!upgrade || !proInactive()) {
      return;
    }

    e.preventDefault();
    e.stopPropagation();

    if (upgrade.getAttribute('data-ectbe-busy') === '1') {
      return;
    }

    var c = cfg();
    var init = c && c.pro && c.pro.init;
    if (!init) {
      return;
    }

    setBusy(upgrade, true, i18n('activating', 'Activating…'));
    activateWidgetsPro(init)
      .then(function (data) {
        if (c.pro) {
          c.pro.installedInactive = false;
        }
        var redirect = (data && data.redirect) || (c.pro && c.pro.setupUrl) || c.dashboardUrl;
        if (redirect) {
          window.location.href = redirect;
          return;
        }
        applyWidgetsProUpgradeButtons();
      })
      .catch(function (err) {
        setFailed(upgrade, (err && err.message) || i18n('failedRetry', 'Failed — retry'));
      });
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-wizard-finish]')) {
      return;
    }
    completeWizard();
  });

  applySpbWizardUrl();
  applyWidgetsProUpgradeButtons();

  if (window.ECTBE_WIZARD_RUNTIME) {
    window.ECTBE_WIZARD_RUNTIME.beforeNext = function (stepId, proceed) {
      if (stepId !== 'layout-query') return;
      saveOnboardingPreferences().then(proceed).catch(function () { proceed(); });
      return false;
    };
  }

  function bootTelemetry() {
    applyTelemetryFromPhp();
    applyWidgetsProUpgradeButtons();
  }
  if (document.readyState !== 'loading') bootTelemetry();
  else document.addEventListener('DOMContentLoaded', bootTelemetry);

  document.addEventListener('ectbe:wizard-step', function (e) {
    if (e.detail && e.detail.stepId === 'layout-query') {
      applyTelemetryFromPhp();
      applyWidgetsProUpgradeButtons();
    }
  });

  // Keep Activate/Upgrade label correct when Pro options are toggled.
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.ectbe-layout-card, .ectbe-style-card, .ectbe-toggle-option')) return;
    requestAnimationFrame(applyWidgetsProUpgradeButtons);
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applySpbWizardUrl);
  }
})();
