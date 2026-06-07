@extends('admin.layouts.app')

@section('title', 'AI assistance')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search…'])
<main class="mt-16 p-8 min-h-screen">
<div class="max-w-4xl mx-auto space-y-8">
<div>
<h2 class="text-display-lg font-bold text-on-surface">AI assistance</h2>
<p class="text-body-lg text-on-surface-variant mt-2">Describe creating an <strong>organizer</strong> (name, company, email, address…), a <strong>speaker</strong>, or an <strong>event</strong> (schedule, venue, tickets, add-ons). Events are saved as <strong>drafts</strong> with sensible defaults—open the edit screen to set category, public/private visibility, dates, tickets, and organizer if anything was left out.</p>
</div>

@if($assistantConfigured ?? false)
<div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm space-y-4" id="ev-assistant-root">
<h3 class="text-headline-md font-semibold text-on-surface inline-flex items-center gap-2">
<span class="material-symbols-outlined text-primary-container">smart_toy</span>
Create with AI
</h3>
<label class="flex flex-col gap-2">
<span class="text-label-md font-medium text-on-surface-variant">Instruction</span>
<textarea id="ev-assistant-instruction" rows="6"
class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright font-mono text-sm resize-y focus:outline-none focus:ring-2 focus:ring-primary/40"
placeholder="Examples:&#10;• Create organizer Acme Events, company Acme Ltd, email events@acme.test, phone +1 555-0100, city Austin, country US&#10;• Create speaker Jane Doe, headline Keynote architect&#10;• Create event &quot;Summer Gala&quot; with organizer_name Acme Ltd, Jul 15 2026 6pm to 11pm, venue_city Austin, venue_country US, one ticket type &quot;General&quot; price 75, 200 total seats, add-on Parking 15"
></textarea>
</label>
<div class="flex flex-wrap items-center gap-3">
<button type="button" id="ev-assistant-run"
class="inline-flex justify-center items-center gap-2 px-6 py-3 bg-primary text-white rounded-xl font-semibold text-sm shadow-sm hover:shadow-md hover:bg-primary/90 active:scale-[0.98] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed group">
<span class="material-symbols-outlined text-[20px] group-hover:rotate-12 transition-transform">auto_awesome</span>
Create with AI
</button>
<p id="ev-assistant-status" class="text-body-sm text-on-surface-variant">&nbsp;</p>
</div>
<div id="ev-assistant-alert" role="alert" class="hidden text-body-sm px-4 py-3 rounded-lg bg-error-container text-error"></div>
<div id="ev-assistant-success" class="hidden text-body-sm px-4 py-3 rounded-lg bg-primary-fixed text-on-primary-fixed space-y-2"></div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var runUrl = @json(route('admin.event-assistant.run'));
  var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  var ta = document.getElementById('ev-assistant-instruction');
  var runBtn = document.getElementById('ev-assistant-run');
  var statusEl = document.getElementById('ev-assistant-status');
  var alertEl = document.getElementById('ev-assistant-alert');
  var successEl = document.getElementById('ev-assistant-success');
  function clearOutput(){
    alertEl.classList.add('hidden');
    successEl.classList.add('hidden');
    successEl.innerHTML = '';
  }
  runBtn.addEventListener('click', function(){
    clearOutput();
    var text = (ta.value||'').trim();
    if(!text){
      statusEl.textContent = 'Enter an instruction.';
      statusEl.className = 'text-body-sm text-error';
      return;
    }
    runBtn.disabled = true;
    statusEl.className = 'text-body-sm text-on-surface-variant';
    statusEl.textContent = 'Working…';
    fetch(runUrl, {
      method:'POST',
      credentials:'same-origin',
      headers:{
        'Content-Type':'application/json',
        'X-Requested-With':'XMLHttpRequest',
        'Accept':'application/json',
        'X-CSRF-TOKEN':token
      },
      body: JSON.stringify({instruction:text})
    }).then(function(res){return res.json().then(function(data){return {ok:res.ok,data:data};});})
    .then(function(bundle){
      var data = bundle.data||{};
      if(!bundle.ok){
        alertEl.textContent = (typeof data.message==='string')?data.message:'Request failed.';
        alertEl.classList.remove('hidden');
        statusEl.textContent = '';
        return;
      }
      if(!data.ok){
        alertEl.textContent = typeof data.error==='string'?data.error:'Could not complete that.';
        if(data.hint){ alertEl.textContent += ' '+String(data.hint); }
        alertEl.classList.remove('hidden');
        statusEl.textContent = '';
        return;
      }
      statusEl.textContent = '';
      successEl.classList.remove('hidden');
      var msg = document.createElement('p');
      msg.textContent = data.message || 'Done.';
      successEl.appendChild(msg);
      if(typeof data.edit_url==='string' && data.edit_url){
        var a = document.createElement('a');
        a.href = data.edit_url;
        a.className = 'inline-flex items-center gap-2 font-semibold underline underline-offset-2';
        a.textContent = 'Review and Publish →';
        successEl.appendChild(a);
      }
    }).catch(function(){
      alertEl.textContent = 'Network error.';
      alertEl.classList.remove('hidden');
      statusEl.textContent = '';
    }).finally(function(){ runBtn.disabled = false; });
  });
});
</script>
</div>
@else
<div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm">
<p class="text-body-md text-on-surface-variant">Configure AI under <a href="{{ route('admin.settings.index', ['section' => 'ai_reports']) }}" class="text-primary font-medium underline underline-offset-2">Settings → AI reports</a>.</p>
</div>
@endif

</div>
</main>
@endsection
