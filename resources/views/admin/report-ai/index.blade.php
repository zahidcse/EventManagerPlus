@extends('admin.layouts.app')

@section('title', 'AI reporting')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search…'])
<main class="mt-16 p-8 min-h-screen">
<div class="max-w-7xl mx-auto space-y-8">
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
<div>
<h2 class="text-display-lg font-bold text-on-surface">Natural language reporting</h2>
<p class="text-body-lg text-on-surface-variant">Ask analytics questions about bookings, gateways, revenue, and check-ins. The assistant generates read-only SQL, runs it in this sandbox, then shows results here.</p>
</div>
<a href="{{ route('admin.reports.index') }}" class="inline-flex items-center justify-center gap-2 border border-outline-variant px-6 py-3 rounded-xl font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
<span class="material-symbols-outlined text-[22px]">table_chart</span>
Registration reports
</a>
</div>

@if($reportAiConfigured ?? false)
<div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm space-y-4" id="report-ai-root">
<h3 class="text-headline-md font-semibold text-on-surface inline-flex items-center gap-2">
<span class="material-symbols-outlined text-primary-container">smart_toy</span>
Ask the assistant
</h3>
<p class="text-body-md text-on-surface-variant">
Questions run against the same read-only schema as configured under Settings → AI reports.
</p>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
<label class="lg:col-span-2 flex flex-col gap-2">
<span class="text-label-md font-medium text-on-surface-variant">Your question</span>
<textarea id="report-ai-question" rows="3"
class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright font-mono text-sm resize-y focus:outline-none focus:ring-2 focus:ring-primary/40"
placeholder="Example: registrations and ticket revenue totals for the past 6 months"
></textarea>
</label>
<div class="flex flex-col justify-end gap-2">
<button type="button" id="report-ai-run"
class="inline-flex justify-center items-center gap-2 px-6 py-3 bg-primary text-white rounded-xl font-semibold text-sm shadow-sm hover:shadow-md hover:bg-primary/90 active:scale-[0.98] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed group">
<span class="material-symbols-outlined text-[20px] group-hover:rotate-12 transition-transform">auto_awesome</span>
Generate Analysis
</button>
<p id="report-ai-status" class="text-body-sm text-on-surface-variant">&nbsp;</p>
</div>
</div>
<details class="text-body-sm"><summary class="cursor-pointer select-none font-medium text-on-surface-variant">Search Result</summary>
<pre id="report-ai-sql" class="mt-3 p-4 rounded-lg bg-surface-container-low text-xs overflow-auto max-h-52 font-mono text-on-surface"></pre>
</details>
<div id="report-ai-alert" role="alert" class="hidden text-body-sm px-4 py-3 rounded-lg bg-error-container text-error"></div>
<div class="flex flex-wrap items-center justify-end gap-2 min-h-[40px]">
<button type="button" id="report-ai-export-csv" disabled
class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-outline-variant text-body-sm font-semibold text-on-surface hover:bg-surface-container-low disabled:opacity-40 disabled:pointer-events-none">
<span class="material-symbols-outlined text-[20px]">download</span>
Download CSV
</button>
</div>
<div class="rounded-lg border border-outline-variant overflow-auto max-h-[min(70vh,720px)]" id="report-ai-table-wrapper">
<table id="report-ai-table" class="w-full border-collapse hidden text-body-sm"><thead></thead><tbody></tbody></table>
<p id="report-ai-empty" class="hidden px-6 py-10 text-center text-on-surface-variant">Nothing to show.</p>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var askUrl = @json(route('admin.report-ai.query'));
  var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  var question = document.getElementById('report-ai-question');
  var runBtn = document.getElementById('report-ai-run');
  var statusEl = document.getElementById('report-ai-status');
  var sqlEl = document.getElementById('report-ai-sql');
  var alertEl = document.getElementById('report-ai-alert');
  var tbl = document.getElementById('report-ai-table');
  var emptyEl = document.getElementById('report-ai-empty');
  var exportBtn = document.getElementById('report-ai-export-csv');
  var lastColumns = [];
  var lastRows = [];

  function setExportEnabled(on){
    if(exportBtn){ exportBtn.disabled = !on; }
  }

  function csvEscapeCell(val){
    var s = (val === null || val === undefined) ? '' : String(val);
    if(/[",\r\n]/.test(s)){
      return '"' + s.replace(/"/g, '""') + '"';
    }
    return s;
  }

  function buildCsv(columns, rows){
    var lines = [];
    lines.push(columns.map(csvEscapeCell).join(','));
    rows.forEach(function(row){
      lines.push(columns.map(function(col){ return csvEscapeCell(row[col]); }).join(','));
    });
    return '\ufeff' + lines.join('\r\n');
  }

  function triggerDownload(filename, text){
    var blob = new Blob([text], { type: 'text/csv;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.rel = 'noopener';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function escapeHtml(val){
    return String(val)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }
  function renderTable(columns, rows){
    lastColumns = Array.isArray(columns) ? columns.slice() : [];
    lastRows = Array.isArray(rows) ? rows.slice() : [];
    var thead = tbl.querySelector('thead');
    var tbody = tbl.querySelector('tbody');
    thead.innerHTML = '';
    tbody.innerHTML = '';
    if(!columns.length){
      tbl.classList.add('hidden');
      emptyEl.classList.remove('hidden');
      emptyEl.textContent = 'Zero rows.';
      setExportEnabled(false);
      return;
    }
    var trHead = document.createElement('tr');
    trHead.className = 'bg-surface-container-low border-b border-outline-variant';
    columns.forEach(function(col){
      var th = document.createElement('th');
      th.scope = 'col';
      th.className = 'px-4 py-2 text-left font-bold text-xs uppercase tracking-wide text-on-surface-variant';
      th.textContent = col;
      trHead.appendChild(th);
    });
    thead.appendChild(trHead);
    rows.forEach(function(row){
      var tr = document.createElement('tr');
      tr.className = 'even:bg-white odd:bg-surface-container-lowest/60 hover:bg-primary-fixed/40';
      columns.forEach(function(col){
        var td = document.createElement('td');
        td.className = 'px-4 py-2 border-t border-outline-variant text-on-surface';
        td.innerHTML = escapeHtml(row[col] ?? '');
        tr.appendChild(td);
      });
      tbody.appendChild(tr);
    });
    tbl.classList.remove('hidden');
    emptyEl.classList.add('hidden');
    setExportEnabled(true);
  }
  if(exportBtn){
    exportBtn.addEventListener('click', function(){
      if(!lastColumns.length){ return; }
      var stamp = new Date();
      var pad = function(n){ return (n < 10 ? '0' : '') + n; };
      var fname = 'ai-report-' + stamp.getFullYear() + '-' + pad(stamp.getMonth()+1) + '-' + pad(stamp.getDate())
        + '-' + pad(stamp.getHours()) + pad(stamp.getMinutes()) + pad(stamp.getSeconds()) + '.csv';
      triggerDownload(fname, buildCsv(lastColumns, lastRows));
    });
  }
  runBtn.addEventListener('click', function(){
    alertEl.classList.add('hidden');
    tbl.classList.add('hidden');
    emptyEl.classList.add('hidden');
    sqlEl.textContent = '';
    setExportEnabled(false);
    var q = (question.value||'').trim();
    if(!q){
      statusEl.textContent = 'Ask a reporting question.';
      statusEl.className = 'text-body-sm text-error';
      return;
    }
    runBtn.disabled = true;
    statusEl.className = 'text-body-sm text-on-surface-variant';
    statusEl.textContent = 'Contacting assistant…';
    fetch(askUrl, {
      method:'POST',
      credentials:'same-origin',
      headers: {
        'Content-Type':'application/json',
        'X-Requested-With':'XMLHttpRequest',
        'Accept':'application/json',
        'X-CSRF-TOKEN':token
      },
      body: JSON.stringify({question: q})
    })
    .then(function(res){return res.json().then(function(data){return {ok:res.ok,status:res.status,data:data};});})
    .then(function(bundle){
      var data = bundle.data || {};
      if(!bundle.ok){
        var httpMsg = (typeof data.message === 'string') ? data.message : 'Could not validate the request.';
        alertEl.textContent = httpMsg;
        alertEl.classList.remove('hidden');
        statusEl.textContent = (bundle.status >= 500 ? 'Server error.' : '');
        sqlEl.textContent = '';
        setExportEnabled(false);
        return;
      }
      if(!data.ok){
        alertEl.textContent = (typeof data.error === 'string') ? data.error : 'Assistant could not complete that.';
        alertEl.classList.remove('hidden');
        statusEl.textContent = bundle.status >= 500 ? 'Server error.' : 'Assistant reported an issue.';
        if(typeof data.summary === 'string' && data.summary.length){
          statusEl.textContent = data.summary.slice(0, 180);
        }
        if(data.sql_attempted){sqlEl.textContent = String(data.sql_attempted);}
        setExportEnabled(false);
        return;
      }
      alertEl.classList.add('hidden');
      statusEl.textContent = (typeof data.summary === 'string' && data.summary.length) ? data.summary : 'Results ready.';
      sqlEl.textContent = typeof data.sql === 'string' ? data.sql : '';
      renderTable(Array.isArray(data.columns)?data.columns:[], Array.isArray(data.rows)?data.rows:[]);
      if(data.sql && data.truncated){
        statusEl.textContent += ' (rows capped)';
      }
    }).catch(function(){
      alertEl.textContent = 'Network error.';
      alertEl.classList.remove('hidden');
      statusEl.textContent = '';
      setExportEnabled(false);
    }).finally(function(){
      runBtn.disabled = false;
    });
  });
});
</script>
</div>
@else
<div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm space-y-3">
<p class="text-body-md text-on-surface-variant">AI reporting is not enabled or API keys are missing. Configure it under <a href="{{ route('admin.settings.index', ['section' => 'ai_reports']) }}" class="text-primary font-medium underline underline-offset-2">Settings → AI reports</a>, then return here.</p>
</div>
@endif

</div>
</main>
@endsection
