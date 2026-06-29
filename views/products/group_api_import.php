<div class="max-w-4xl mx-auto p-6">
  <div class="bg-white border rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold text-gray-800">Import missing products (all groups)</h2>
      <a href="?page=products&action=list" class="px-3 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50">Back</a>
    </div>
    <p class="text-sm text-gray-600 mb-4">
      Groups: <?= htmlspecialchars(implode(', ', $groups ?? [])) ?>.
      Fetches codes via <code>group-fetch</code>, skips existing item codes, imports the rest.
    </p>

    <div class="mb-4">
      <div class="flex justify-between text-sm mb-1">
        <span>Group: <strong id="curGroup">—</strong></span>
        <span id="pct">0%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
        <div id="bar" class="bg-amber-600 h-4 text-xs text-white text-center leading-4" style="width:0%">0%</div>
      </div>
    </div>

    <p class="text-sm mb-2">
      Seen <span id="seen">0</span> · Skipped <span id="skipped">0</span> ·
      Created <span id="created">0</span> · Failed <span id="failed">0</span>
    </p>
    <div id="log" class="text-sm text-gray-700 border rounded p-3 bg-gray-50 max-h-48 overflow-y-auto mb-4"></div>

    <div class="flex gap-2">
      <button type="button" id="startBtn" class="px-4 py-2 bg-green-600 text-white rounded text-sm font-semibold hover:bg-green-700">Start</button>
      <button type="button" id="resetBtn" class="px-4 py-2 bg-gray-600 text-white rounded text-sm font-semibold hover:bg-gray-700">Reset</button>
    </div>
  </div>
</div>
<script>
(function () {
  let running = false;

  function log(msg) {
    const el = document.getElementById('log');
    el.innerHTML += '<div>' + msg + '</div>';
    el.scrollTop = el.scrollHeight;
  }

  function apply(data) {
    const s = data.stats || {};
    document.getElementById('seen').textContent = s.seen || 0;
    document.getElementById('skipped').textContent = s.skipped || 0;
    document.getElementById('created').textContent = s.created || 0;
    document.getElementById('failed').textContent = s.failed || 0;
    document.getElementById('curGroup').textContent = data.current_group || '—';
    const p = data.progress_percent || 0;
    document.getElementById('pct').textContent = p + '%';
    const bar = document.getElementById('bar');
    bar.style.width = p + '%';
    bar.textContent = p + '%';
    if (data.message) log(data.message);
  }

  async function batch(body) {
    const res = await fetch('?page=products&action=group_api_import_batch', {
      method: 'POST',
      credentials: 'same-origin',
      body: body || null
    });
    return res.json();
  }

  async function loop() {
    if (!running) return;
    const data = await batch();
    apply(data);
    if (!data.success || data.finished || !data.should_continue) {
      running = false;
      if (data.finished) log('Finished.');
      return;
    }
    setTimeout(loop, 400);
  }

  document.getElementById('startBtn').addEventListener('click', function () {
    running = true;
    log('Started…');
    loop();
  });

  document.getElementById('resetBtn').addEventListener('click', async function () {
    running = false;
    const fd = new FormData();
    fd.append('reset', '1');
    const data = await batch(fd);
    document.getElementById('log').innerHTML = '';
    apply({ stats: { seen: 0, skipped: 0, created: 0, failed: 0 }, progress_percent: 0, message: 'Reset.' });
  });
})();
</script>
