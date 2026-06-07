function resetAttendeeSelectMenuStyles(wrap) {
  if (!wrap) return;
  var menu = wrap.querySelector('.attendee-custom-select-menu');
  if (!menu) return;
  menu.classList.remove('is-portal', 'is-drop-up');
  menu.style.position = '';
  menu.style.left = '';
  menu.style.top = '';
  menu.style.bottom = '';
  menu.style.width = '';
  menu.style.minWidth = '';
  menu.style.maxHeight = '';
  menu.style.zIndex = '';
  menu.style.borderRadius = '';
}

function positionAttendeeSelectMenuForWrap(wrap) {
  var menu = wrap.querySelector('.attendee-custom-select-menu');
  var trigger = wrap.querySelector('.attendee-custom-select-trigger');
  if (!menu || !trigger) return;

  var pickerModal = wrap.closest('#seat-plan-picker-modal, #seat-plan-attendee-edit-modal');
  if (!pickerModal || pickerModal.hidden) {
    resetAttendeeSelectMenuStyles(wrap);
    return;
  }

  var rect = trigger.getBoundingClientRect();
  var maxH = 200;
  var spaceBelow = window.innerHeight - rect.bottom - 8;
  var spaceAbove = rect.top - 8;
  var dropUp = spaceBelow < 120 && spaceAbove > spaceBelow;

  menu.classList.add('is-portal');
  menu.classList.toggle('is-drop-up', dropUp);
  menu.style.position = 'fixed';
  menu.style.left = rect.left + 'px';
  menu.style.width = rect.width + 'px';
  menu.style.minWidth = rect.width + 'px';
  menu.style.zIndex = '1400';

  if (dropUp) {
    menu.style.top = 'auto';
    menu.style.bottom = (window.innerHeight - rect.top + 1) + 'px';
    menu.style.maxHeight = Math.min(maxH, spaceAbove) + 'px';
    menu.style.borderRadius = '8px 8px 0 0';
  } else {
    menu.style.bottom = 'auto';
    menu.style.top = (rect.bottom - 1) + 'px';
    menu.style.maxHeight = Math.min(maxH, spaceBelow) + 'px';
    menu.style.borderRadius = '0 0 8px 8px';
  }
}

function closeAttendeeSelectWrap(wrap) {
  if (!wrap) return;
  var menu = wrap.querySelector('.attendee-custom-select-menu');
  var trigger = wrap.querySelector('.attendee-custom-select-trigger');
  wrap.classList.remove('is-open');
  if (menu) menu.hidden = true;
  if (trigger) trigger.setAttribute('aria-expanded', 'false');
  resetAttendeeSelectMenuStyles(wrap);
}

function repositionOpenAttendeeSelectMenus() {
  document.querySelectorAll('.attendee-custom-select.is-open').forEach(function (wrap) {
    positionAttendeeSelectMenuForWrap(wrap);
  });
}

function createAttendeeSelectField(cfg, fieldName, selectedValue, required) {
  var wrap = document.createElement('div');
  wrap.className = 'attendee-custom-select';

  var native = document.createElement('select');
  native.className = 'attendee-custom-select-native';
  native.name = fieldName;
  if (required) native.required = true;
  native.setAttribute('aria-hidden', 'true');
  native.tabIndex = -1;

  var opts = Array.isArray(cfg.options) ? cfg.options : [{ value: '', label: 'Select' }];
  var value = selectedValue ? String(selectedValue) : '';
  opts.forEach(function (optVal) {
    var o = document.createElement('option');
    o.value = optVal.value;
    o.textContent = optVal.label;
    if (value === optVal.value) o.selected = true;
    native.appendChild(o);
  });

  var trigger = document.createElement('button');
  trigger.type = 'button';
  trigger.className = 'attendee-custom-select-trigger';
  trigger.setAttribute('aria-haspopup', 'listbox');
  trigger.setAttribute('aria-expanded', 'false');

  var menu = document.createElement('ul');
  menu.className = 'attendee-custom-select-menu';
  menu.setAttribute('role', 'listbox');
  menu.hidden = true;

  opts.forEach(function (optVal) {
    var li = document.createElement('li');
    li.className = 'attendee-custom-select-option';
    li.setAttribute('role', 'option');
    li.setAttribute('data-value', optVal.value);
    li.textContent = optVal.label;
    if (value === optVal.value) {
      li.classList.add('is-selected');
      li.setAttribute('aria-selected', 'true');
    }
    menu.appendChild(li);
  });

  function syncTriggerLabel() {
    var sel = native.options[native.selectedIndex];
    trigger.textContent = sel ? sel.textContent : 'Select';
    trigger.classList.toggle('is-placeholder', !native.value);
  }

  function closeMenu() {
    closeAttendeeSelectWrap(wrap);
  }

  function openMenu() {
    document.querySelectorAll('.attendee-custom-select.is-open').forEach(function (other) {
      if (other === wrap) return;
      closeAttendeeSelectWrap(other);
    });
    menu.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    wrap.classList.add('is-open');
    positionAttendeeSelectMenuForWrap(wrap);
  }

  function setValue(nextValue) {
    native.value = nextValue;
    menu.querySelectorAll('.attendee-custom-select-option').forEach(function (li) {
      var on = li.getAttribute('data-value') === nextValue;
      li.classList.toggle('is-selected', on);
      li.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    syncTriggerLabel();
  }

  trigger.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    if (wrap.classList.contains('is-open')) closeMenu();
    else openMenu();
  });

  menu.addEventListener('click', function (e) {
    var li = e.target.closest('.attendee-custom-select-option');
    if (!li) return;
    setValue(li.getAttribute('data-value'));
    closeMenu();
    native.dispatchEvent(new Event('change', { bubbles: true }));
  });

  syncTriggerLabel();
  wrap.appendChild(native);
  wrap.appendChild(trigger);
  wrap.appendChild(menu);
  return wrap;
}

if (!window.__attendeeCustomSelectDocBound) {
  window.__attendeeCustomSelectDocBound = true;
  document.addEventListener('click', function (e) {
    if (e.target.closest('.attendee-custom-select')) return;
    document.querySelectorAll('.attendee-custom-select.is-open').forEach(function (wrap) {
      closeAttendeeSelectWrap(wrap);
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.attendee-custom-select.is-open').forEach(function (wrap) {
      closeAttendeeSelectWrap(wrap);
    });
  });
  window.addEventListener('resize', repositionOpenAttendeeSelectMenus);
  document.addEventListener('scroll', repositionOpenAttendeeSelectMenus, true);
}
