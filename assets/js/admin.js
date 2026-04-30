(function () {
  var config = window.sArticlesThemeConfig || {};
  var themes = config.themes || ['evolight', 'evolightness', 'evodark', 'evodarkness'];
  var lightThemes = config.lightThemes || ['evolight', 'evolightness'];
  var darkThemes = config.darkThemes || ['evodark', 'evodarkness'];
  var defaultLight = config.defaultLight || 'evolight';
  var defaultDark = config.defaultDark || 'evodark';
  var root = document.documentElement;

  root.classList.add('sarticles-page');
  applyPlatformClasses();

  function readStorage(key, sourceWindow) {
    try {
      return (sourceWindow || window).localStorage.getItem(key);
    } catch (error) {
      return null;
    }
  }

  function normalizeTheme(theme) {
    if (!theme) {
      return null;
    }

    theme = String(theme).toLowerCase();

    if (theme === 'evodarknes') {
      theme = 'evodarkness';
    }
    if (theme === 'lightness') {
      theme = 'evolightness';
    }
    if (theme === 'darkness') {
      theme = 'evodarkness';
    }
    if (theme === 'light') {
      theme = defaultLight;
    }
    if (theme === 'dark') {
      theme = defaultDark;
    }

    return themes.indexOf(theme) !== -1 ? theme : null;
  }

  function themeGroup(theme) {
    return darkThemes.indexOf(theme) !== -1 ? 'dark' : 'light';
  }

  function platformName() {
    var platform = '';

    try {
      platform = (navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform || navigator.userAgent || '';
    } catch (error) {
      platform = '';
    }

    platform = String(platform).toLowerCase();

    if (platform.indexOf('mac') !== -1 || platform.indexOf('iphone') !== -1 || platform.indexOf('ipad') !== -1 || platform.indexOf('ipod') !== -1) {
      return 'mac';
    }
    if (platform.indexOf('win') !== -1) {
      return 'windows';
    }

    return 'other';
  }

  function applyPlatformClasses() {
    var platform = platformName();

    root.classList.toggle('sarticles-os-mac', platform === 'mac');
    root.classList.toggle('sarticles-os-windows', platform === 'windows');
    root.classList.toggle('sarticles-custom-scrollbars', platform === 'windows');

    if (document.body) {
      document.body.classList.toggle('sarticles-os-mac', platform === 'mac');
      document.body.classList.toggle('sarticles-os-windows', platform === 'windows');
      document.body.classList.toggle('sarticles-custom-scrollbars', platform === 'windows');
    }
  }

  function themeFromElement(element) {
    if (!element) {
      return null;
    }

    var attrTheme = normalizeTheme(element.getAttribute('data-theme'));
    if (attrTheme) {
      return attrTheme;
    }

    for (var i = 0; i < themes.length; i++) {
      if (element.classList.contains(themes[i])) {
        return themes[i];
      }
    }

    if (element.classList.contains('lightness')) {
      return 'evolightness';
    }
    if (element.classList.contains('darkness')) {
      return 'evodarkness';
    }
    if (element.classList.contains('light')) {
      return defaultLight;
    }
    if (element.classList.contains('dark')) {
      return defaultDark;
    }

    return null;
  }

  function readStoredTheme(sourceWindow) {
    var mode = readStorage('evo.mode', sourceWindow);
    var modeTheme = null;

    if (mode === 'dark') {
      modeTheme = normalizeTheme(readStorage('evo.theme.dark', sourceWindow));
    } else if (mode === 'light') {
      modeTheme = normalizeTheme(readStorage('evo.theme.light', sourceWindow));
    }

    return normalizeTheme(readStorage('evo.theme', sourceWindow))
      || modeTheme
      || normalizeTheme(readStorage('evo.blogDaisyui.theme', sourceWindow));
  }

  function readParentTheme() {
    try {
      if (!window.parent || window.parent === window || !window.parent.document) {
        return null;
      }

      var parentDocument = window.parent.document;
      return themeFromElement(parentDocument.documentElement)
        || themeFromElement(parentDocument.body)
        || readStoredTheme(window.parent);
    } catch (error) {
      return null;
    }
  }

  function fallbackTheme() {
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    return prefersDark ? defaultDark : defaultLight;
  }

  function applyTheme(theme) {
    theme = normalizeTheme(theme) || fallbackTheme();
    var mode = themeGroup(theme);

    root.setAttribute('data-theme', theme);
    root.setAttribute('data-theme-mode', mode);

    if (document.body) {
      applyPlatformClasses();
      document.body.setAttribute('data-theme', theme);
      document.body.setAttribute('data-theme-mode', mode);
      document.body.classList.add('sarticles-page');
      document.body.classList.toggle('dark', mode === 'dark');
      document.body.classList.toggle('darkness', theme === 'evodarkness');
      document.body.classList.toggle('light', theme === 'evolight');
      document.body.classList.toggle('lightness', theme === 'evolightness');
    }
  }

  function currentTheme() {
    return normalizeTheme(root.getAttribute('data-theme'));
  }

  function hydrateTheme() {
    applyTheme(readParentTheme() || readStoredTheme(window) || fallbackTheme());
  }

  hydrateTheme();

  document.addEventListener('DOMContentLoaded', function () {
    hydrateTheme();
    observeParentTheme();
  });

  window.addEventListener('message', function (event) {
    if (!event.data || event.data.type !== 'evo:theme') {
      return;
    }
    applyTheme(event.data.theme);
  });

  window.addEventListener('storage', function (event) {
    if (['evo.theme', 'evo.theme.light', 'evo.theme.dark', 'evo.mode', 'evo.blogDaisyui.theme'].indexOf(event.key) === -1) {
      return;
    }
    hydrateTheme();
  });

  function syncIfChanged() {
    var theme = readParentTheme() || readStoredTheme(window) || fallbackTheme();
    if (theme && theme !== currentTheme()) {
      applyTheme(theme);
    }
  }

  function observeParentTheme() {
    try {
      if (!window.parent || window.parent === window || !window.parent.document || !window.MutationObserver) {
        return;
      }

      var observer = new MutationObserver(syncIfChanged);
      observer.observe(window.parent.document.documentElement, {
        attributes: true,
        attributeFilter: ['class', 'data-theme', 'data-theme-mode']
      });

      if (window.parent.document.body) {
        observer.observe(window.parent.document.body, {
          attributes: true,
          attributeFilter: ['class', 'data-theme', 'data-theme-mode']
        });
      }
    } catch (error) {
    }
  }

  window.setInterval(syncIfChanged, 500);
})();

(function () {
  var sArticlesAdminConfig = window.sArticlesAdminConfig || {};
  var sArticlesDatePickerConfig = sArticlesAdminConfig.datePicker || {};

  function sArticlesMessage(key, fallback) {
    if (Object.prototype.hasOwnProperty.call(sArticlesAdminConfig, key) && sArticlesAdminConfig[key] !== null) {
      return sArticlesAdminConfig[key];
    }

    return fallback || '';
  }

  function sArticlesSiteUrl() {
    return sArticlesAdminConfig.siteUrl || '/';
  }

  window.evoRenderImageCheck = function (event) {
    var preview = document.getElementById('image_for_' + event.target.id);
    var image = new Image();

    if (!preview) {
      return;
    }

    if (event.target.value) {
      image.src = sArticlesSiteUrl() + event.target.value;
      image.onerror = function () {
        preview.style.backgroundImage = '';
        preview.setAttribute('data-image', '');
      };
      image.onload = function () {
        preview.style.backgroundImage = 'url(\'' + this.src + '\')';
        preview.setAttribute('data-image', this.src);
      };
    } else {
      preview.style.backgroundImage = '';
      preview.setAttribute('data-image', '');
    }
  };

  $(document).ready(function () {
      $('.select2').select2();
      $('.sortable').sortable();

      $(document).on("mouseenter", ".sarticles-admin table img", function () {
          var alt = $(this).attr("alt");
          if (alt && alt.length > 0) {
              $("#img-preview").attr("src", alt).show();
          }
      });

      $(document).on("mouseleave", ".sarticles-admin table img", function () {
          $("#img-preview").hide();
      });

      $('#confirmDelete').on('show.bs.modal', function (e) {
          $(this).find('#confirm-id').text($(e.relatedTarget).data('id'));
          $(this).find('#confirm-name').text($(e.relatedTarget).data('name'));
          $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
      });

      /* Delete item */
      $(document).on("click", "[data-delete]", function(e) {
          var _this = $(this);
          alertify
              .confirm(
                  sArticlesMessage('confirmDelete', 'Confirm deletion'),
                  sArticlesMessage('youSure', 'Are you sure?') + ' <b>' + _this.attr('data-name') + '</b> ' + sArticlesMessage('withId', 'ID') + ' <b>' + _this.attr('data-delete') + '</b>',
                  function() {
                      alertify.success(sArticlesMessage('deleted', 'Deleted'));
                      window.location.href = _this.attr('data-href');
                  },
                  function() {
                      alertify.error(sArticlesMessage('cancelLabel', 'Cancel'));
                  })
              .set('labels', {
                  ok: sArticlesMessage('deleteLabel', 'Delete'),
                  cancel: sArticlesMessage('cancelLabel', 'Cancel')
              })
              .set({transition:'zoom'});
          return false;
      });
  });

  function tabSave(starget) {
      if (documentDirty === true) {
          document.form.back.value = starget;
          saveForm('#form');
          return;
      }

      sArticlesLoadModuleView(sArticlesUrlFromBack(starget));
  }

  window.sArticlesDynamicStyleKeys = window.sArticlesDynamicStyleKeys || {};

  function sArticlesBackFromUrl(targetUrl) {
      var target = new URL(targetUrl, window.location.href);
      var params = new URLSearchParams(target.search);

      params.delete('a');
      params.delete('id');

      return params.toString() ? '&' + params.toString() : '';
  }

  function sArticlesUrlFromBack(starget) {
      var target = new URL(window.location.href);
      var base = new URLSearchParams(target.search);
      var extra = new URLSearchParams(String(starget || '').replace(/^&/, ''));

      Array.from(base.keys()).forEach(function (key) {
          if (key !== 'a' && key !== 'id') {
              target.searchParams.delete(key);
          }
      });

      extra.forEach(function (value, key) {
          target.searchParams.set(key, value);
      });

      return target.toString();
  }

  function sArticlesViewKey(targetUrl) {
      var target = new URL(targetUrl, window.location.href);
      var params = new URLSearchParams(target.search);
      var entries = [];

      params.delete('a');
      params.delete('id');

      params.forEach(function (value, key) {
          entries.push([key, value]);
      });

      entries.sort(function (left, right) {
          return (left[0] + '=' + left[1]).localeCompare(right[0] + '=' + right[1]);
      });

      return target.pathname + '?' + entries.map(function (entry) {
          return encodeURIComponent(entry[0]) + '=' + encodeURIComponent(entry[1]);
      }).join('&');
  }

  function sArticlesIsModuleUrl(targetUrl) {
      try {
          var target = new URL(targetUrl, window.location.href);
          var current = new URL(window.location.href);

          return target.origin === current.origin
              && target.pathname === current.pathname
              && target.searchParams.get('a') === current.searchParams.get('a')
              && target.searchParams.get('id') === current.searchParams.get('id');
      } catch (error) {
          return false;
      }
  }

  function sArticlesExecuteInlineScript(script) {
      var fresh = document.createElement('script');
      var type = script.getAttribute('type');

      if (type) {
          fresh.type = type;
      }

      fresh.text = script.textContent || '';
      document.head.appendChild(fresh).parentNode.removeChild(fresh);
  }

  function sArticlesInitTabPane(root) {
      sArticlesInitTabBars(root || document);
  }

  function sArticlesUpdateTabBar(tabbar) {
      var row = tabbar.querySelector('[data-sarticles-tabs-scroller]');
      var prev = tabbar.querySelector('[data-sarticles-tab-prev]');
      var next = tabbar.querySelector('[data-sarticles-tab-next]');

      if (!row || !prev || !next) {
          return;
      }

      var tabs = Array.prototype.slice.call(row.querySelectorAll('.sarticles-tabbar__tab'));
      var selected = row.querySelector('.sarticles-tabbar__tab.selected');
      var selectedIndex = selected ? tabs.indexOf(selected) : -1;

      prev.disabled = tabs.length < 2 || selectedIndex <= 0;
      next.disabled = tabs.length < 2 || selectedIndex === -1 || selectedIndex >= tabs.length - 1;
      prev.classList.toggle('is-disabled', prev.disabled);
      next.classList.toggle('is-disabled', next.disabled);
  }

  function sArticlesEnsureTabVisible(row, tab) {
      if (!row || !tab) {
          return;
      }

      var rowRect = row.getBoundingClientRect();
      var tabRect = tab.getBoundingClientRect();
      var offset = 0;

      if (tabRect.left < rowRect.left) {
          offset = tabRect.left - rowRect.left;
      } else if (tabRect.right > rowRect.right) {
          offset = tabRect.right - rowRect.right;
      }

      if (offset === 0) {
          return;
      }

      if (typeof row.scrollBy === 'function') {
          try {
              row.scrollBy({
                  left: offset,
                  behavior: 'smooth'
              });
              return;
          } catch (error) {
          }
      }

      row.scrollLeft += offset;
  }

  function sArticlesNavigateTabBar(button, direction) {
      var tabbar = button.closest('[data-sarticles-tabs]');
      var row = tabbar ? tabbar.querySelector('[data-sarticles-tabs-scroller]') : null;

      if (!tabbar || !row || button.disabled) {
          return;
      }

      var tabs = Array.prototype.slice.call(row.querySelectorAll('.sarticles-tabbar__tab'));
      var selected = row.querySelector('.sarticles-tabbar__tab.selected');
      var selectedIndex = selected ? tabs.indexOf(selected) : 0;
      var targetIndex = direction === 'prev' ? selectedIndex - 1 : selectedIndex + 1;
      var target = tabs[targetIndex];

      if (!target) {
          return;
      }

      sArticlesEnsureTabVisible(row, target);

      var link = target.querySelector('a[data-sarticles-tab-link], a[href]');
      if (link) {
          link.dispatchEvent(new MouseEvent('click', {
              bubbles: true,
              cancelable: true,
              view: window
          }));
      }

      window.setTimeout(function () {
          sArticlesUpdateTabBar(tabbar);
      }, 180);
  }

  function sArticlesInitTabBars(root) {
      root = root || document;

      root.querySelectorAll('[data-sarticles-tabs]').forEach(function (tabbar) {
          var row = tabbar.querySelector('[data-sarticles-tabs-scroller]');
          var prev = tabbar.querySelector('[data-sarticles-tab-prev]');
          var next = tabbar.querySelector('[data-sarticles-tab-next]');

          if (!row || !prev || !next) {
              return;
          }

          if (tabbar.dataset.sarticlesTabsBound !== 'true') {
              tabbar.dataset.sarticlesTabsBound = 'true';

              prev.addEventListener('click', function (event) {
                  event.preventDefault();
                  sArticlesNavigateTabBar(prev, 'prev');
              });

              next.addEventListener('click', function (event) {
                  event.preventDefault();
                  sArticlesNavigateTabBar(next, 'next');
              });

              row.addEventListener('scroll', function () {
                  sArticlesUpdateTabBar(tabbar);
              }, {passive: true});
          }

          var selected = row.querySelector('.sarticles-tabbar__tab.selected');
          if (selected) {
              sArticlesEnsureTabVisible(row, selected);
          }

          sArticlesUpdateTabBar(tabbar);
          window.setTimeout(function () {
              sArticlesUpdateTabBar(tabbar);
          }, 60);
      });
  }

  function sArticlesCleanupDelegatedHandlers() {
      if (!window.jQuery) {
          return;
      }

      var doc = jQuery(document);
      doc.off('click', '.sarticles-search__icon');
      doc.off('keydown', '[name="search"]');
      doc.off('dblclick', '.sarticles-editable-row');
      doc.off('click', '.js__publish_article');
      doc.off('click', '[data-filter-menu] .sarticles-filter-menu__toggle');
      doc.off('click', '.sarticles-filter-menu__panel');
      doc.off('input', '[data-filter-search]');
      doc.off('click', '[data-filter-select-all]');
      doc.off('click', '[data-filter-clear]');
      doc.off('click', '[data-filter-apply]');
      doc.off('click', '.articlesTab .sarticles-segmented__item, .articlesTab .sarticles-filter-group a, .articlesTab .paginator a');
      doc.off('click', '.js__comment_edit');
      doc.off('click', '.js__approve_modal');
      doc.off('click', '.js__approve_comment');
      doc.off('click', '.js_save_alias');
      doc.off('click', '.js_save_texts');
      doc.off('click', '.js_translate');
      doc.off('change', '.change_gender');
      doc.off('click', '.close-icon');
  }

  function sArticlesShouldSkipDynamicScript(script) {
      var text = script.textContent || '';

      return !text.trim()
          || script.closest('.sarticles-admin')
          || text.indexOf('window.sArticlesThemeConfig') !== -1
          || text.indexOf('function evoRenderImageCheck') !== -1
          || text.indexOf('function sArticlesLoadModuleView') !== -1
          || text.indexOf('function saveForm') !== -1
          || text.indexOf('var dpOffset') !== -1
          || text.indexOf('$(document).ready(function') !== -1;
  }

  function sArticlesApplyDynamicStyles(doc) {
      doc.querySelectorAll('style').forEach(function (style) {
          if (style.closest('.sarticles-admin')) {
              return;
          }

          var text = style.textContent || '';
          var key = text.replace(/\s+/g, ' ').trim().substring(0, 220) + ':' + text.length;

          if (!text.trim() || window.sArticlesDynamicStyleKeys[key]) {
              return;
          }

          window.sArticlesDynamicStyleKeys[key] = true;
          var fresh = document.createElement('style');
          fresh.textContent = text;
          document.head.appendChild(fresh);
      });
  }

  function sArticlesReplaceDynamicFragments(doc) {
      var currentSelector = [
          '[data-sarticles-dynamic-fragment]',
          'body > .draft-value',
          'body > .draft-element',
          'body > #addTag',
          'body > #editTagAlias',
          'body > #editTag',
          'body > #addAuthor',
          'body > #editAuthorAlias',
          'body > #editComment',
          'body > #confirmDelete'
      ].join(',');

      document.querySelectorAll(currentSelector).forEach(function (fragment) {
          fragment.remove();
      });

      doc.body.querySelectorAll('body > .draft-value, body > .draft-element, body > .modal[id]').forEach(function (fragment) {
          if (fragment.closest('[data-sarticles-admin]') || fragment.id === 'actions') {
              return;
          }

          var fresh = document.importNode(fragment, true);
          fresh.setAttribute('data-sarticles-dynamic-fragment', 'true');
          document.body.appendChild(fresh);
      });
  }

  function sArticlesExecuteDynamicScripts(doc) {
      sArticlesCleanupDelegatedHandlers();

      doc.querySelectorAll('script:not([src])').forEach(function (script) {
          if (!sArticlesShouldSkipDynamicScript(script)) {
              sArticlesExecuteInlineScript(script);
          }
      });
  }

  function sArticlesPrepareSaveButtons(root) {
      var dirty = documentDirty === true;

      if (!root || !root.querySelectorAll) {
          return;
      }

      root.querySelectorAll('#Button1, [onclick*="saveForm"]').forEach(function (button) {
          button.classList.toggle('is-disabled', !dirty);
          button.classList.toggle('disabled', !dirty);
          button.setAttribute('aria-disabled', dirty ? 'false' : 'true');
      });
  }

  function sArticlesReplaceActions(doc) {
      var currentActions = document.getElementById('actions');
      var freshActions = doc.getElementById('actions');

      if (freshActions) {
          sArticlesPrepareSaveButtons(freshActions);
      }

      if (currentActions && freshActions) {
          currentActions.replaceWith(freshActions);
      } else if (currentActions && !freshActions) {
          currentActions.remove();
      } else if (!currentActions && freshActions) {
          document.body.appendChild(freshActions);
      }
  }

  function sArticlesInitDynamic(root) {
      root = root || document;

      if (window.jQuery && jQuery.fn.select2) {
          jQuery(root).find('.select2').each(function () {
              var select = jQuery(this);
              if (select.data('select2')) {
                  select.select2('destroy');
              }
              select.select2();
          });
      }

      if (window.jQuery && jQuery.fn.sortable) {
          jQuery(root).find('.sortable').sortable();
      }

      var DatePickers = root.querySelectorAll ? root.querySelectorAll('input.DatePicker') : [];
      if (window.DatePicker && DatePickers.length) {
          for (var i = 0; i < DatePickers.length; i++) {
              let format = DatePickers[i].getAttribute("data-format");
              new DatePicker(DatePickers[i], {
                  yearOffset: dpOffset,
                  format: format !== null ? format : dpformat,
                  dayNames: dpdayNames,
                  monthNames: dpmonthNames,
                  startDay: dpstartDay
              });
          }
      }

      documentDirty = false;
      sArticlesSyncSaveState();
  }

  function sArticlesLoadModuleView(targetUrl, pushState) {
      pushState = pushState !== false;

      var target = new URL(targetUrl, window.location.href);
      var admin = document.querySelector('[data-sarticles-admin]');

      if (!admin || !sArticlesIsModuleUrl(target.toString())) {
          window.location.href = target.toString();
          return;
      }

      admin.classList.add('is-loading');

      fetch(target.toString(), {
          method: 'GET',
          cache: 'no-store',
          headers: {
              'X-Requested-With': 'XMLHttpRequest'
          }
      }).then(function (response) {
          return response.text();
      }).then(function (html) {
          var doc = new DOMParser().parseFromString(html, 'text/html');
          var freshAdmin = doc.querySelector('[data-sarticles-admin]');

          if (!freshAdmin) {
              window.location.href = target.toString();
              return;
          }

          sArticlesApplyDynamicStyles(doc);
          admin.replaceWith(freshAdmin);
          sArticlesReplaceActions(doc);
          sArticlesReplaceDynamicFragments(doc);
          sArticlesInitTabPane(freshAdmin);
          sArticlesExecuteDynamicScripts(doc);
          sArticlesInitDynamic(freshAdmin);

          window.sArticlesActiveViewKey = sArticlesViewKey(target.toString());

          if (pushState) {
              window.history.pushState({sarticlesView: true}, '', target.toString());
          } else {
              window.history.replaceState({sarticlesView: true}, '', target.toString());
          }
      }).catch(function (error) {
          console.error('Request failed', error, '.');
          window.location.href = target.toString();
      });
  }

  function sArticlesShouldHandleAjaxLink(link, event) {
      var rawHref = link ? (link.getAttribute('href') || '') : '';

      if (!link || !link.href || rawHref === '#' || rawHref.indexOf('javascript:') === 0) {
          return false;
      }
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.which === 2) {
          return false;
      }
      if (link.hasAttribute('data-toggle') || link.hasAttribute('data-target') || link.hasAttribute('onclick')) {
          return false;
      }
      if (link.closest('[data-filter-menu]') || link.hasAttribute('data-delete')) {
          return false;
      }
      if (link.matches('.articlesTab .sarticles-segmented__item, .articlesTab .sarticles-filter-group a')) {
          return false;
      }

      return link.hasAttribute('data-sarticles-tab-link')
          || (link.closest('.sarticles-admin, #actions') && sArticlesIsModuleUrl(link.href));
  }

  function sArticlesHandleAjaxClick(event) {
      var link = event.target && event.target.closest ? event.target.closest('a') : null;

      if (!sArticlesShouldHandleAjaxLink(link, event)) {
          return;
      }

      event.preventDefault();
      event.stopPropagation();

      if (documentDirty === true && document.form) {
          document.form.back.value = link.getAttribute('data-tab-back') || sArticlesBackFromUrl(link.href);
          saveForm('#form');
          return;
      }

      sArticlesLoadModuleView(link.href);
  }

  document.addEventListener('click', sArticlesHandleAjaxClick, true);

  window.sArticlesActiveViewKey = sArticlesViewKey(window.location.href);
  window.history.replaceState({sarticlesView: true}, '', window.location.href);
  window.addEventListener('popstate', function () {
      var current = window.location.href;
      var key = sArticlesViewKey(current);

      if (key !== window.sArticlesActiveViewKey) {
          sArticlesLoadModuleView(current, false);
      }
  });

  function sArticlesSaveButtons() {
      return $('#actions #Button1, #actions [onclick*="saveForm"]');
  }

  function sArticlesSyncSaveState() {
      var dirty = documentDirty === true;

      sArticlesSaveButtons()
          .toggleClass('is-disabled disabled', !dirty)
          .attr('aria-disabled', dirty ? 'false' : 'true');
  }

  function sArticlesMarkDirty() {
      documentDirty = true;
      sArticlesSyncSaveState();
  }

  document.addEventListener('DOMContentLoaded', function () {
      sArticlesInitTabPane(document);
      sArticlesSyncSaveState();

      document.addEventListener('input', function (event) {
          if (event.target && event.target.closest('.sarticles-admin form')) {
              sArticlesMarkDirty();
          }
      }, true);

      document.addEventListener('change', function (event) {
          if (event.target && event.target.closest('.sarticles-admin form')) {
              sArticlesMarkDirty();
          }
      }, true);

      setInterval(sArticlesSyncSaveState, 350);
  });

  // Form Validation and Saving
  function saveForm(selector) {
      if (documentDirty !== true) {
          sArticlesSyncSaveState();
          return false;
      }

      var errors = 0;
      var messages = "";
      var validates = $(selector + " [data-validate]");
      validates.each(function (k, v) {
          var rule = $(v).attr("data-validate").split(":");
          switch (rule[0]) {
              case "textNoEmpty": // Not an empty field
                  if ($(v).val().length < 1) {
                      messages = messages + $(v).parent().find(".error-text").text() + "<br/>";
                      $(v).parent().removeClass("is-valid").addClass("is-invalid");
                      errors = errors + 1;
                  } else {
                      $(v).parent().removeClass("is-invalid").addClass("is-valid");
                  }
                  break;
              case "textMustContainDefault": // Must contain the value of the default language
                  var _default = $(v).parents('tbody').find('[name^="s_lang_default"]').val();
                  _index = $(v).val().indexOf(_default);
                  if (_index >= $(v).val().length || _index < 0 || isNaN(_index)) {
                      messages = messages + $(v).parent().find(".error-text").text() + "<br/>";
                      $(v).parent().removeClass("is-valid").addClass("is-invalid");
                      errors = errors + 1;
                  } else {
                      $(v).parent().removeClass("is-invalid").addClass("is-valid");
                  }
                  break;
              case "textMustContainSiteLang": // Must contain site language list values
                  var _default = $(v).parents('tbody').find('[name^="s_lang_default"]').val();
                  var _config = $(v).parents('tbody').find('[name^="s_lang_config"]').val();
                  var _valid = 1;
                  _index = $(v).val().indexOf(_default);
                  $(v).val().forEach(function (val) {
                      if (_config.indexOf(val) < 0) {
                          return _valid = 0;
                      }
                  });
                  if (_index >= $(v).val().length || _index < 0 || isNaN(_index) || _valid < 1) {
                      messages = messages + $(v).parent().find(".error-text").text() + "<br/>";
                      $(v).parent().removeClass("is-valid").addClass("is-invalid");
                      errors = errors + 1;
                  } else {
                      $(v).parent().removeClass("is-invalid").addClass("is-valid");
                  }
                  break;
          }
      });
      if (errors == 0) {
          documentDirty = false;
          sArticlesSyncSaveState();
          $(selector).submit();
      } else {
          $('.notifier').addClass("notifier-error");
          $('.notifier').fadeIn(500);
          $('.notifier').find('.notifier-txt').html(messages);
          setTimeout(function () {
              $('.notifier').fadeOut(5000);
          }, 2000);
          setTimeout(function () {
              $('.notifier').removeClass("notifier-error");
          }, 5000);
      }
  }

  var dpOffset = Object.prototype.hasOwnProperty.call(sArticlesDatePickerConfig, 'yearOffset') ? sArticlesDatePickerConfig.yearOffset : -10;
  var dpformat = sArticlesDatePickerConfig.format || 'YYYY-mm-dd hh:mm:00';
  var dpdayNames = sArticlesDatePickerConfig.dayNames || [];
  var dpmonthNames = sArticlesDatePickerConfig.monthNames || [];
  var dpstartDay = Object.prototype.hasOwnProperty.call(sArticlesDatePickerConfig, 'startDay') ? sArticlesDatePickerConfig.startDay : 1;
  var DatePickers = document.querySelectorAll('input.DatePicker');
  if (DatePickers) {
      for (var i = 0; i < DatePickers.length; i++) {
          let format = DatePickers[i].getAttribute("data-format");
          new DatePicker(DatePickers[i], {
              yearOffset: dpOffset,
              format: format !== null ? format : dpformat,
              dayNames: dpdayNames,
              monthNames: dpmonthNames,
              startDay: dpstartDay
          });
      }
  }

  function changestate(el) {
      if (parseInt(el.value) === 1) {
          el.value = 0;
      } else {
          el.value = 1;
      }
      sArticlesMarkDirty();
  }

  var allowParentSelection = false;

  function enableParentSelection(b) {
      var plock = document.getElementById('plock');
      if(b) {
          parent.tree.ca = "parent";
          plock.className = "fa fa-folder-open";
          allowParentSelection = true;
      } else {
          parent.tree.ca = "open";
          plock.className = "fa fa-folder";
          allowParentSelection = false;
      }
  }

  function setParent(pId, pName) {
      sArticlesMarkDirty();
      document.form.parent.value = pId;
      var elm = document.getElementById('parentName');
      if (elm) {
          elm.innerHTML = (pId + " (" + pName + ")");
      }
  }

  window.tabSave = tabSave;
  window.sArticlesLoadModuleView = sArticlesLoadModuleView;
  window.sArticlesMarkDirty = sArticlesMarkDirty;
  window.sArticlesSyncSaveState = sArticlesSyncSaveState;
  window.saveForm = saveForm;
  window.changestate = changestate;
  window.enableParentSelection = enableParentSelection;
  window.setParent = setParent;

})();
