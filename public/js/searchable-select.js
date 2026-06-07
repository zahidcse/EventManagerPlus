window.AdminSearchableSelect = window.AdminSearchableSelect || (function () {
  var registry = {};
  var booted = false;

  function normalizeQuery(q) {
    return (q || '').toLowerCase().replace(/[_/]/g, ' ').trim();
  }

  function parseOptions(root) {
    var key = root.getAttribute('data-options-key');
    if (key && registry[key]) {
      return registry[key].slice();
    }
    var optionsId = root.getAttribute('data-searchable-options-id');
    if (optionsId) {
      var dataEl = document.getElementById(optionsId);
      if (dataEl && dataEl.textContent) {
        try {
          return JSON.parse(dataEl.textContent);
        } catch (e) {
          return [];
        }
      }
    }
    try {
      return JSON.parse(root.getAttribute('data-searchable-options') || '[]');
    } catch (e) {
      return [];
    }
  }

  function registerOptions(key, options) {
    registry[key] = options.slice();
    document.querySelectorAll('[data-searchable-select][data-options-key="' + key + '"]').forEach(function (root) {
      root._ssOptions = registry[key].slice();
      var inst = root._ssInstance;
      if (inst) {
        inst.options = registry[key].slice();
        inst.syncSelectedLabel();
      }
    });
  }

  function appendOption(key, value, label, group) {
    if (!registry[key]) {
      registry[key] = [];
    }
    var val = String(value);
    if (!registry[key].some(function (o) { return String(o.value) === val; })) {
      registry[key].push({ value: val, label: label, group: group || '' });
    }
    registerOptions(key, registry[key]);
  }

  function findRoot(idOrEl) {
    if (!idOrEl) return null;
    if (typeof idOrEl === 'string') {
      var byHidden = document.getElementById(idOrEl);
      if (byHidden && byHidden.closest('[data-searchable-select]')) {
        return byHidden.closest('[data-searchable-select]');
      }
      return document.querySelector('[data-searchable-select][data-searchable-id="' + idOrEl + '"]');
    }
    if (idOrEl.matches && idOrEl.matches('[data-searchable-select]')) return idOrEl;
    return idOrEl.closest ? idOrEl.closest('[data-searchable-select]') : null;
  }

  function setSelected(idOrEl, value, label) {
    var root = findRoot(idOrEl);
    if (!root) return;
    if (!root._ssInstance) init(root);
    if (!root._ssInstance) return;
    var opts = root._ssOptions || parseOptions(root);
    var match = opts.find(function (o) { return String(o.value) === String(value); });
    root._ssInstance.setValue(String(value), label || (match ? match.label : ''));
  }

  function appendOptionTo(idOrEl, value, label, group) {
    var root = findRoot(idOrEl);
    if (!root) return;
    var opts = parseOptions(root);
    var val = String(value);
    if (!opts.some(function (o) { return String(o.value) === val; })) {
      opts.push({ value: val, label: label, group: group || '' });
    }
    var optionsId = root.getAttribute('data-searchable-options-id');
    if (optionsId) {
      var dataEl = document.getElementById(optionsId);
      if (dataEl) {
        dataEl.textContent = JSON.stringify(opts);
      }
    } else {
      root.setAttribute('data-searchable-options', JSON.stringify(opts));
    }
    root._ssOptions = opts.slice();
    if (!root._ssInstance) init(root);
    else root._ssInstance.options = opts.slice();
    setSelected(root, val, label);
  }

  function init(root) {
    if (!root || root.dataset.ssReady === '1') return root._ssInstance || null;
    root.dataset.ssReady = '1';

    var options = parseOptions(root);
    root._ssOptions = options;

    var trigger = root.querySelector('.searchable-select-trigger');
    var triggerLabel = root.querySelector('.searchable-select-trigger-label');
    var search = root.querySelector('.searchable-select-search');
    var hidden = root.querySelector('[data-searchable-value]');
    var panel = root.querySelector('.searchable-select-panel');
    var list = root.querySelector('.searchable-select-options');
    if (!trigger || !hidden || !panel || !list || !triggerLabel) return null;

    var triggerPlaceholder = root.getAttribute('data-trigger-placeholder') || 'Select…';
    var selectedLabel = '';
    var isFrontend = root.getAttribute('data-searchable-frontend') === '1';
    if (isFrontend) {
      panel.classList.add('searchable-select-panel--ep');
      if (document.querySelector('main.ep-theme-classic, body.ep-theme-classic')) {
        panel.classList.add('searchable-select-panel--classic');
      }
    }

    function updateTriggerDisplay(label) {
      var show = label !== '' ? label : triggerPlaceholder;
      var isPlaceholder = label === '';
      triggerLabel.textContent = show;
      if (isFrontend) {
        triggerLabel.classList.toggle('is-placeholder', isPlaceholder);
      } else {
        triggerLabel.classList.toggle('text-on-surface-variant', isPlaceholder);
        triggerLabel.classList.toggle('text-on-surface', !isPlaceholder);
      }
    }

    function syncSelectedLabel() {
      var val = hidden.value;
      var found = (root._ssOptions || options).find(function (o) {
        return String(o.value) === String(val);
      });
      selectedLabel = found ? found.label : '';
      updateTriggerDisplay(selectedLabel);
    }

    function setValue(value, label) {
      hidden.value = value;
      selectedLabel = label;
      updateTriggerDisplay(label);
      closeList();
    }

    var onReposition = null;

    function positionPanel() {
      var rect = trigger.getBoundingClientRect();
      var gap = 4;
      var maxH = 320;
      var spaceBelow = window.innerHeight - rect.bottom - gap;
      var spaceAbove = rect.top - gap;
      var openUp = spaceBelow < 200 && spaceAbove > spaceBelow;
      var height = Math.min(maxH, openUp ? spaceAbove : spaceBelow);
      if (height < 160) height = Math.min(maxH, Math.max(spaceBelow, spaceAbove, 160));

      panel.style.position = 'fixed';
      panel.style.left = rect.left + 'px';
      panel.style.width = Math.max(rect.width, 220) + 'px';
      panel.style.maxHeight = height + 'px';
      if (openUp) {
        panel.style.top = 'auto';
        panel.style.bottom = (window.innerHeight - rect.top + gap) + 'px';
      } else {
        panel.style.bottom = 'auto';
        panel.style.top = (rect.bottom + gap) + 'px';
      }
    }

    function isOpen() {
      return panel.classList.contains('searchable-select-panel--open');
    }

    function closeList() {
      panel.hidden = true;
      panel.setAttribute('aria-hidden', 'true');
      panel.classList.remove('searchable-select-panel--open');
      trigger.setAttribute('aria-expanded', 'false');
      if (search) search.value = '';
      if (panel.parentNode === document.body) {
        root.appendChild(panel);
      }
      panel.style.position = '';
      panel.style.left = '';
      panel.style.top = '';
      panel.style.bottom = '';
      panel.style.width = '';
      panel.style.maxHeight = '';
      if (onReposition) {
        window.removeEventListener('scroll', onReposition, true);
        window.removeEventListener('resize', onReposition);
        onReposition = null;
      }
    }

    function openList() {
      if (panel.parentNode !== document.body) {
        document.body.appendChild(panel);
      }
      panel.hidden = false;
      panel.setAttribute('aria-hidden', 'false');
      panel.classList.add('searchable-select-panel--open');
      positionPanel();
      trigger.setAttribute('aria-expanded', 'true');
      if (!onReposition) {
        onReposition = function () {
          if (isOpen()) positionPanel();
        };
        window.addEventListener('scroll', onReposition, true);
        window.addEventListener('resize', onReposition);
      }
      if (search) {
        search.value = '';
        window.setTimeout(function () { search.focus(); }, 0);
      }
    }

    function isPanelTarget(target) {
      return panel === target || panel.contains(target);
    }

    function renderOptions(query) {
      var opts = root._ssOptions || options;
      var q = normalizeQuery(query);
      var matched = opts.filter(function (opt) {
        if (!q) return true;
        var hay = (String(opt.value) + ' ' + opt.label + ' ' + (opt.group || '')).toLowerCase().replace(/[_/]/g, ' ');
        return hay.indexOf(q) !== -1;
      });

      list.innerHTML = '';
      if (matched.length === 0) {
        var empty = document.createElement('li');
        empty.className = 'searchable-select-empty';
        empty.textContent = 'No matches found.';
        list.appendChild(empty);
        return;
      }

      var lastGroup = '';
      var limit = 80;
      matched.slice(0, limit).forEach(function (opt) {
        var grp = opt.group || '';
        if (grp && grp !== lastGroup) {
          lastGroup = grp;
          var heading = document.createElement('li');
          heading.className = 'searchable-select-group';
          heading.textContent = lastGroup;
          heading.setAttribute('role', 'presentation');
          list.appendChild(heading);
        }
        var item = document.createElement('li');
        item.className = 'searchable-select-option';
        item.setAttribute('role', 'option');
        item.dataset.value = opt.value;
        item.textContent = opt.label;
        if (String(opt.value) === String(hidden.value)) {
          item.classList.add('is-active');
        }
        item.addEventListener('mousedown', function (e) {
          e.preventDefault();
          setValue(opt.value, opt.label);
        });
        list.appendChild(item);
      });

      if (matched.length > limit) {
        var more = document.createElement('li');
        more.className = 'searchable-select-empty';
        more.textContent = 'Showing first ' + limit + ' matches — refine your search.';
        list.appendChild(more);
      }
    }

    function toggleList() {
      if (isOpen()) {
        closeList();
      } else {
        renderOptions('');
        openList();
      }
    }

    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      toggleList();
    });

    panel.addEventListener('mousedown', function (e) {
      e.stopPropagation();
    });

    if (search) {
      search.addEventListener('input', function () {
        if (!isOpen()) {
          renderOptions(search.value);
          openList();
        } else {
          renderOptions(search.value);
        }
      });
      search.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closeList();
          trigger.focus();
        }
      });
    }

    document.addEventListener('click', function (e) {
      if (!root.contains(e.target) && !isPanelTarget(e.target)) {
        closeList();
      }
    });

    syncSelectedLabel();
    closeList();

    var inst = {
      root: root,
      options: options,
      setValue: setValue,
      syncSelectedLabel: syncSelectedLabel,
      refreshOptions: function () {
        root._ssOptions = parseOptions(root);
        options = root._ssOptions;
        inst.options = options;
        syncSelectedLabel();
      }
    };
    root._ssInstance = inst;
    return inst;
  }

  function initAll(scope) {
    var root = scope && scope.querySelectorAll ? scope : document;
    root.querySelectorAll('[data-searchable-select]').forEach(function (el) {
      if (el.closest('template')) return;
      init(el);
    });
  }

  function boot() {
    initAll(document);
    booted = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  return {
    init: init,
    initAll: initAll,
    registerOptions: registerOptions,
    appendOption: appendOption,
    appendOptionTo: appendOptionTo,
    setSelected: setSelected,
    boot: boot
  };
})();
