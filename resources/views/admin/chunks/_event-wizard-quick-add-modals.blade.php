@php
    $wizOrganizerStore = route('admin.organizers.store');
    $wizCategoryStore = route('admin.event-categories.store');
    $wizSpeakerStore = route('admin.speakers.store');
@endphp
<div id="quick-add-modal-organizer" class="quick-add-modal fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/45" role="dialog" aria-modal="true" aria-labelledby="quick-add-organizer-title">
<div class="bg-white dark:bg-[#2b2930] rounded-2xl border border-outline-variant shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 relative text-on-surface">
<button type="button" class="quick-add-modal-close absolute top-4 right-4 p-1 rounded-lg text-on-surface-variant hover:bg-surface-container-low" aria-label="Close">&times;</button>
<h3 id="quick-add-organizer-title" class="text-headline-md font-bold mb-1">Add organizer</h3>
<p class="text-body-sm text-on-surface-variant mb-4">Creates a partner account you can assign to this event.</p>
<div id="quick-add-organizer-errors" class="hidden mb-4 rounded-lg border border-error/50 bg-error-container/30 text-error text-sm p-3 space-y-1"></div>
<form id="quick-add-organizer-form" class="space-y-4">
<input type="hidden" name="status" value="active"/>
<input type="hidden" name="auto_approve_events" value="0"/>
<input type="hidden" name="digest_notifications" value="1"/>
<div>
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Name</label>
<input name="name" type="text" required class="w-full px-3 py-2.5 rounded-lg border border-outline-variant text-sm outline-none focus:ring-2 focus:ring-primary/20" autocomplete="name"/>
</div>
<div>
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Company</label>
<input name="company_name" type="text" required class="w-full px-3 py-2.5 rounded-lg border border-outline-variant text-sm outline-none focus:ring-2 focus:ring-primary/20"/>
</div>
<div>
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Email</label>
<input name="email" type="email" required class="w-full px-3 py-2.5 rounded-lg border border-outline-variant text-sm outline-none focus:ring-2 focus:ring-primary/20" autocomplete="email"/>
</div>
<div>
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Password</label>
<input name="password" type="password" required minlength="8" class="w-full px-3 py-2.5 rounded-lg border border-outline-variant text-sm outline-none focus:ring-2 focus:ring-primary/20" autocomplete="new-password"/>
<p class="text-[11px] text-on-surface-variant mt-1">Minimum 8 characters. They can change it after login.</p>
</div>
<div class="flex justify-end gap-2 pt-2">
<button type="button" class="quick-add-modal-close px-4 py-2 rounded-lg border border-outline-variant font-semibold text-sm">Cancel</button>
<button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-bold text-sm hover:opacity-90">Create organizer</button>
</div>
</form>
</div>
</div>
<div id="quick-add-modal-category" class="quick-add-modal fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/45" role="dialog" aria-modal="true" aria-labelledby="quick-add-category-title">
<div class="bg-white dark:bg-[#2b2930] rounded-2xl border border-outline-variant shadow-xl max-w-md w-full p-6 relative text-on-surface">
<button type="button" class="quick-add-modal-close absolute top-4 right-4 p-1 rounded-lg text-on-surface-variant hover:bg-surface-container-low" aria-label="Close">&times;</button>
<h3 id="quick-add-category-title" class="text-headline-md font-bold mb-1">Add event category</h3>
<p class="text-body-sm text-on-surface-variant mb-4">Categories group events in listings and filters.</p>
<div id="quick-add-category-errors" class="hidden mb-4 rounded-lg border border-error/50 bg-error-container/30 text-error text-sm p-3 space-y-1"></div>
<form id="quick-add-category-form" class="space-y-4">
<div>
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Category name</label>
<input name="name" type="text" required maxlength="120" class="w-full px-3 py-2.5 rounded-lg border border-outline-variant text-sm outline-none focus:ring-2 focus:ring-primary/20"/>
</div>
<div class="flex justify-end gap-2 pt-2">
<button type="button" class="quick-add-modal-close px-4 py-2 rounded-lg border border-outline-variant font-semibold text-sm">Cancel</button>
<button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-bold text-sm hover:opacity-90">Create category</button>
</div>
</form>
</div>
</div>
<div id="quick-add-modal-speaker" class="quick-add-modal fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/45" role="dialog" aria-modal="true" aria-labelledby="quick-add-speaker-title">
<div class="bg-white dark:bg-[#2b2930] rounded-2xl border border-outline-variant shadow-xl max-w-md w-full p-6 relative text-on-surface">
<button type="button" class="quick-add-modal-close absolute top-4 right-4 p-1 rounded-lg text-on-surface-variant hover:bg-surface-container-low" aria-label="Close">&times;</button>
<h3 id="quick-add-speaker-title" class="text-headline-md font-bold mb-1">Add speaker</h3>
<p class="text-body-sm text-on-surface-variant mb-4">Adds them to the directory so you can assign them here.</p>
<div id="quick-add-speaker-errors" class="hidden mb-4 rounded-lg border border-error/50 bg-error-container/30 text-error text-sm p-3 space-y-1"></div>
<form id="quick-add-speaker-form" class="space-y-4">
<div>
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Name</label>
<input name="name" type="text" required class="w-full px-3 py-2.5 rounded-lg border border-outline-variant text-sm outline-none focus:ring-2 focus:ring-primary/20"/>
</div>
<div>
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Headline <span class="font-normal normal-case text-on-surface-variant">(optional)</span></label>
<input name="headline" type="text" class="w-full px-3 py-2.5 rounded-lg border border-outline-variant text-sm outline-none focus:ring-2 focus:ring-primary/20"/>
</div>
<input type="hidden" name="sort_order" value="0"/>
<div class="flex justify-end gap-2 pt-2">
<button type="button" class="quick-add-modal-close px-4 py-2 rounded-lg border border-outline-variant font-semibold text-sm">Cancel</button>
<button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-bold text-sm hover:opacity-90">Create speaker</button>
</div>
</form>
</div>
</div>
<script>
(function () {
  var csrf = document.querySelector('meta[name="csrf-token"]');
  var token = csrf ? csrf.getAttribute('content') : '';
  var routes = {
    organizer: @json($wizOrganizerStore),
    category: @json($wizCategoryStore),
    speaker: @json($wizSpeakerStore)
  };

  function showErrors(el, errors) {
    if (!el) return;
    el.innerHTML = '';
    if (!errors || typeof errors !== 'object') {
      el.classList.add('hidden');
      return;
    }
    var msgs = [];
    Object.keys(errors).forEach(function (k) {
      var arr = errors[k];
      if (Array.isArray(arr)) arr.forEach(function (m) { msgs.push(m); });
    });
    if (msgs.length === 0) {
      el.classList.add('hidden');
      return;
    }
    msgs.forEach(function (m) {
      var p = document.createElement('p');
      p.textContent = m;
      el.appendChild(p);
    });
    el.classList.remove('hidden');
  }

  function openModal(id) {
    var m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  function closeModal(m) {
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
    var err = m.querySelector('[id$="-errors"]');
    if (err) showErrors(err, null);
    var form = m.querySelector('form');
    if (form) form.reset();
  }

  document.querySelectorAll('[data-quick-open-modal]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var which = btn.getAttribute('data-quick-open-modal');
      var map = { organizer: 'quick-add-modal-organizer', category: 'quick-add-modal-category', speaker: 'quick-add-modal-speaker' };
      if (map[which]) openModal(map[which]);
    });
  });

  document.querySelectorAll('.quick-add-modal').forEach(function (backdrop) {
    backdrop.addEventListener('click', function (e) {
      if (e.target === backdrop) closeModal(backdrop);
    });
    backdrop.querySelectorAll('.quick-add-modal-close').forEach(function (b) {
      b.addEventListener('click', function () { closeModal(backdrop); });
    });
  });

  function appendOrganizerOption(id, label) {
    var empty = document.getElementById('event-organizer-empty');
    var wrap = document.getElementById('event-organizer-select-wrap');
    if (empty) empty.classList.add('hidden');
    if (wrap) wrap.classList.remove('hidden');
    if (window.AdminSearchableSelect) {
      AdminSearchableSelect.appendOptionTo('event-organizer-select', id, label);
    }
  }

  function appendCategoryOption(id, name) {
    var empty = document.getElementById('event-category-empty');
    var wrap = document.getElementById('event-category-select-wrap');
    if (empty) empty.classList.add('hidden');
    if (wrap) wrap.classList.remove('hidden');
    if (window.AdminSearchableSelect) {
      AdminSearchableSelect.appendOptionTo('event-category-select', id, name);
    }
  }

  function appendSpeakerOption(id, label) {
    var empty = document.getElementById('event-speakers-directory-empty');
    var assignUi = document.getElementById('event-speakers-assign-ui');
    if (empty) empty.classList.add('hidden');
    if (assignUi) assignUi.classList.remove('hidden');
    if (window.AdminSearchableSelect) {
      AdminSearchableSelect.appendOption('event-speakers', id, label);
      var firstHidden = document.querySelector('#event-speakers-rows [data-searchable-value]');
      if (firstHidden && firstHidden.value === '') {
        AdminSearchableSelect.setSelected(firstHidden.closest('[data-searchable-select]'), id, label);
      }
    }
  }

  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(body)
    }).then(function (r) {
      return r.text().then(function (text) {
        var data = {};
        if (text) {
          try {
            data = JSON.parse(text);
          } catch (e) {
            data = { message: text.slice(0, 200) };
          }
        }
        return { ok: r.ok, status: r.status, data: data };
      });
    });
  }

  var orgForm = document.getElementById('quick-add-organizer-form');
  if (orgForm) {
    orgForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var errEl = document.getElementById('quick-add-organizer-errors');
      showErrors(errEl, null);
      var fd = new FormData(orgForm);
      var body = {
        name: fd.get('name'),
        company_name: fd.get('company_name'),
        email: fd.get('email'),
        password: fd.get('password'),
        status: fd.get('status'),
        auto_approve_events: fd.get('auto_approve_events') === '1',
        digest_notifications: fd.get('digest_notifications') === '1'
      };
      postJson(routes.organizer, body).then(function (res) {
        if (res.ok && res.data.organizer) {
          appendOrganizerOption(res.data.organizer.id, res.data.organizer.label);
          closeModal(document.getElementById('quick-add-modal-organizer'));
        } else {
          showErrors(errEl, (res.data && res.data.errors) || { _: [res.data.message || 'Could not create organizer.'] });
        }
      }).catch(function () {
        showErrors(errEl, { _: ['Network error. Try again.'] });
      });
    });
  }

  var catForm = document.getElementById('quick-add-category-form');
  if (catForm) {
    catForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var errEl = document.getElementById('quick-add-category-errors');
      showErrors(errEl, null);
      var fd = new FormData(catForm);
      postJson(routes.category, { name: fd.get('name') }).then(function (res) {
        if (res.ok && res.data.category) {
          appendCategoryOption(res.data.category.id, res.data.category.name);
          closeModal(document.getElementById('quick-add-modal-category'));
        } else {
          showErrors(errEl, (res.data && res.data.errors) || { _: [res.data.message || 'Could not create category.'] });
        }
      }).catch(function () {
        showErrors(errEl, { _: ['Network error. Try again.'] });
      });
    });
  }

  var spForm = document.getElementById('quick-add-speaker-form');
  if (spForm) {
    spForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var errEl = document.getElementById('quick-add-speaker-errors');
      showErrors(errEl, null);
      var fd = new FormData(spForm);
      var body = {
        name: fd.get('name'),
        headline: fd.get('headline') || null,
        sort_order: parseInt(fd.get('sort_order'), 10) || 0
      };
      if (body.headline === '') body.headline = null;
      postJson(routes.speaker, body).then(function (res) {
        if (res.ok && res.data.speaker) {
          appendSpeakerOption(res.data.speaker.id, res.data.speaker.label);
          closeModal(document.getElementById('quick-add-modal-speaker'));
        } else {
          showErrors(errEl, (res.data && res.data.errors) || { _: [res.data.message || 'Could not create speaker.'] });
        }
      }).catch(function () {
        showErrors(errEl, { _: ['Network error. Try again.'] });
      });
    });
  }
})();
</script>
