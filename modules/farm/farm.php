<?php
// modules/farm/farm.php
// Charts: Performance (kg), Revenue (RM), Cost (RM), Profit (RM).
// Stores kg/rev/cost inside tasks.description as "kg:..., rev:..., cost:...".

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../database/db.php';

$user_id = 1; // TODO: auth
$farm_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/* ----------------- helpers ----------------- */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function parse_metric_from_desc(?string $desc, string $key): float {
  if (!$desc) return 0.0;
  $pattern = '/(?:^|\s)'.preg_quote($key,'/').'\s*:\s*([0-9]+(?:\.[0-9]+)?)/i';
  if (preg_match($pattern, $desc, $m)) return (float)$m[1];
  return 0.0;
}
function parse_kg_from_desc(?string $d): float   { return parse_metric_from_desc($d, 'kg'); }
function parse_rev_from_desc(?string $d): float  { return parse_metric_from_desc($d, 'rev'); }
function parse_cost_from_desc(?string $d): float { return parse_metric_from_desc($d, 'cost'); }

function strip_metric_lines(string $desc): string {
  $desc = preg_replace('/(^|\s)(kg|rev|cost)\s*:\s*[0-9]+(?:\.[0-9]+)?\s*/i', ' ', $desc);
  return trim(preg_replace('/\s+/', ' ', $desc));
}
function inject_metrics_into_desc(?string $desc, $kg, $rev, $cost): string {
  $desc = (string)($desc ?? '');
  $desc = strip_metric_lines($desc);
  $parts = [];
  if ($kg   !== '' && is_numeric($kg)   && (float)$kg   > 0) $parts[] = 'kg:'.(0+$kg);
  if ($rev  !== '' && is_numeric($rev)  && (float)$rev  > 0) $parts[] = 'rev:'.(0+$rev);
  if ($cost !== '' && is_numeric($cost) && (float)$cost > 0) $parts[] = 'cost:'.(0+$cost);
  $header = implode(' ', $parts);
  return $header !== '' ? $header . ($desc ? ("\n".$desc) : '') : $desc;
}

/* ----------------- load farm ----------------- */
$sqlFarm = "SELECT * FROM farms WHERE farm_id = ? AND user_id = ? LIMIT 1";
$stmt = $conn->prepare($sqlFarm);
$stmt->bind_param("ii", $farm_id, $user_id);
$stmt->execute();
$farmRes = $stmt->get_result();
if (!$farmRes || $farmRes->num_rows === 0) { http_response_code(404); die("Farm not found."); }
$f = $farmRes->fetch_assoc();

$farmName   = h($f['name']);
$farmType   = h($f['farm_type']);
$farmStatus = h($f['status']);
$farmSize   = round(((float)$f['size_acres']));
$farmId     = (int)$f['farm_id'];

/* ----------------- actions ----------------- */
$ACTION = $_POST['action'] ?? $_GET['action'] ?? '';

if ($ACTION === 'create_task' && $_SERVER['REQUEST_METHOD']==='POST') {
  $title = trim($_POST['title'] ?? '');
  $date  = $_POST['date'] ?? null;
  $status = $_POST['status'] ?? 'pending';
  $notes  = $_POST['description'] ?? '';
  $kg     = $_POST['kg'] ?? '';
  $rev    = $_POST['rev'] ?? '';
  $cost   = $_POST['cost'] ?? '';

  if ($title !== '' && $date !== '') {
    $descToSave = inject_metrics_into_desc($notes, $kg, $rev, $cost);
    $sql = "INSERT INTO tasks (farm_id, title, description, status, date) VALUES (?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $farmId, $title, $descToSave, $status, $date);
    $stmt->execute();
  }
  header("Location: ./farm.php?id={$farmId}");
  exit;
}

if ($ACTION === 'update_task' && $_SERVER['REQUEST_METHOD']==='POST') {
  $task_id = intval($_POST['task_id'] ?? 0);
  $title   = trim($_POST['title'] ?? '');
  $date    = $_POST['date'] ?? null;
  $status  = $_POST['status'] ?? 'pending';
  $notes   = $_POST['description'] ?? '';
  $kg      = $_POST['kg'] ?? '';
  $rev     = $_POST['rev'] ?? '';
  $cost    = $_POST['cost'] ?? '';

  $chk = $conn->prepare("SELECT task_id FROM tasks WHERE task_id=? AND farm_id=? LIMIT 1");
  $chk->bind_param("ii", $task_id, $farmId);
  $chk->execute();
  $own = $chk->get_result()->fetch_assoc();

  if ($own && $title !== '' && $date !== '') {
    $descToSave = inject_metrics_into_desc($notes, $kg, $rev, $cost);
    $up = $conn->prepare("UPDATE tasks SET title=?, description=?, status=?, date=? WHERE task_id=? AND farm_id=?");
    $up->bind_param("ssssii", $title, $descToSave, $status, $date, $task_id, $farmId);
    $up->execute();
  }
  header("Location: ./farm.php?id={$farmId}");
  exit;
}

if ($ACTION === 'delete_task') {
  $task_id = intval($_GET['task_id'] ?? 0);
  $del = $conn->prepare("DELETE FROM tasks WHERE task_id=? AND farm_id=?");
  $del->bind_param("ii", $task_id, $farmId);
  $del->execute();
  header("Location: ./farm.php?id={$farmId}");
  exit;
}

/* ----------------- fetch tasks + aggregates ----------------- */
$tasks = [];
$res = $conn->prepare("SELECT task_id, title, description, status, date FROM tasks WHERE farm_id=? ORDER BY date ASC, task_id ASC");
$res->bind_param("i", $farmId);
$res->execute();
$r = $res->get_result();

$monthlyKg     = array_fill(0, 12, 0.0);
$monthlyRev    = array_fill(0, 12, 0.0);
$monthlyCost   = array_fill(0, 12, 0.0);
$monthlyProfit = array_fill(0, 12, 0.0); // rev - cost

while ($row = $r->fetch_assoc()) {
  $kg   = parse_kg_from_desc($row['description'] ?? '');
  $rev  = parse_rev_from_desc($row['description'] ?? '');
  $cost = parse_cost_from_desc($row['description'] ?? '');

  $row['kg']   = $kg;
  $row['rev']  = $rev;
  $row['cost'] = $cost;

  $date = $row['date'] ?: null;
  if ($date) {
    $time = strtotime($date);
    if ($time !== false) {
      $m = (int)date('n', $time) - 1;
      $monthlyKg[$m]   += $kg;
      $monthlyRev[$m]  += $rev;
      $monthlyCost[$m] += $cost;
    }
  }
  $tasks[] = $row;
}

for ($i=0; $i<12; $i++) {
  $monthlyProfit[$i] = $monthlyRev[$i] - $monthlyCost[$i];
}

$tasks_json         = json_encode($tasks, JSON_UNESCAPED_UNICODE);
$monthlyKgJson      = json_encode(array_map(fn($v)=>round($v,2), $monthlyKg));
$monthlyRevJson     = json_encode(array_map(fn($v)=>round($v,2), $monthlyRev));
$monthlyCostJson    = json_encode(array_map(fn($v)=>round($v,2), $monthlyCost));
$monthlyProfitJson  = json_encode(array_map(fn($v)=>round($v,2), $monthlyProfit));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $farmName ?> | Farm Dashboard</title>
  <link href="../../public/app.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    .stack-col { display:grid; grid-template-columns: 1fr; gap: 1rem; }
    .card { background:#fff; border:1px solid rgb(226 232 240); border-radius:.75rem; }
  </style>
</head>
<body class="flex h-screen font-inter bg-[#FAFAFA] text-slate-800">
  <?php include '../../components/sidebar.php'; ?>

  <main class="flex-1 p-6 overflow-y-auto">
    <div class="mb-4 flex items-start justify-between gap-3">
      <div>
        <div class="text-sm breadcrumbs mb-1">
          <ul>
            <li><a href="./index.php">Farms</a></li>
            <li><?= $farmName ?></li>
          </ul>
        </div>
        <h1 class="text-2xl font-semibold"><?= $farmName ?></h1>
        <p class="text-sm text-slate-600">
          Type: <b><?= $farmType ?></b> •
          Size: <b><?= $farmSize ?> acres</b> •
          Status: <b><?= $farmStatus ?></b> •
          ID: <b>#<?= $farmId ?></b>
        </p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-primary" onclick="openCreate()">+ Add Task</button>
        <a class="btn btn-outline" href="./index.php"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="stack-col">
      <!-- Calendar & Upcoming -->
      <section class="card p-3">
        <div class="flex items-center justify-between mb-2">
          <h2 class="text-lg font-semibold">Calendar & Tasks</h2>
          <div class="flex items-center gap-2">
            <button class="btn btn-xs" onclick="calendar.today()">Today</button>
            <div class="join">
              <button class="btn btn-xs join-item" onclick="calendar.prev()"><i class="bi bi-chevron-left"></i></button>
              <button class="btn btn-xs join-item" onclick="calendar.next()"><i class="bi bi-chevron-right"></i></button>
            </div>
          </div>
        </div>
        <div id="calendar"></div>
        <div class="mt-4">
          <h3 class="font-semibold mb-1 text-2xl">Task:</h3>
          <ul id="upcomingList" class="text-sm space-y-2"></ul>
        </div>
      </section>

      <!-- Performance chart (kg) -->
      <section class="card p-4">
        <div class="flex items-center justify-between mb-2">
          <h2 class="text-lg font-semibold">Farm Performance (kg)</h2>
          <div class="text-xs text-slate-500">Sum of task “kg” per month</div>
        </div>
        <div class="mx-auto w-full max-w-4xl h-64">
          <canvas id="kgChart" class="w-full h-full"></canvas>
        </div>
      </section>

      <!-- Revenue chart (RM) -->
      <section class="card p-4">
        <div class="flex items-center justify-between mb-2">
          <h2 class="text-lg font-semibold">Farm Revenue (RM)</h2>
          <div class="text-xs text-slate-500">Sum of task “rev” per month</div>
        </div>
        <div class="mx-auto w-full max-w-4xl h-64">
          <canvas id="revChart" class="w-full h-full"></canvas>
        </div>
      </section>

      <!-- Cost chart (RM) -->
      <section class="card p-4">
        <div class="flex items-center justify-between mb-2">
          <h2 class="text-lg font-semibold">Farm Cost (RM)</h2>
          <div class="text-xs text-slate-500">Sum of task “cost” per month</div>
        </div>
        <div class="mx-auto w-full max-w-4xl h-64">
          <canvas id="costChart" class="w-full h-full"></canvas>
        </div>
      </section>

      <!-- Profit chart (RM) -->
      <section class="card p-4">
        <div class="flex items-center justify-between mb-2">
          <h2 class="text-lg font-semibold">Farm Profit (RM)</h2>
          <div class="text-xs text-slate-500">Revenue − Cost, per month</div>
        </div>
        <div class="mx-auto w-full max-w-4xl h-64">
          <canvas id="profitChart" class="w-full h-full"></canvas>
        </div>
      </section>
    </div>
  </main>

  <!-- CREATE / EDIT MODAL -->
  <dialog id="taskModal" class="modal">
    <div class="modal-box max-w-lg">
      <form method="post" class="space-y-3" id="taskForm">
        <h3 class="font-bold text-lg" id="taskModalTitle">Add Task</h3>
        <input type="hidden" name="action" value="create_task" id="formAction">
        <input type="hidden" name="task_id" id="taskIdField">

        <label class="form-control">
          <span class="label-text">Title</span>
          <input name="title" id="titleField" class="input input-bordered w-full" required>
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <label class="form-control sm:col-span-1">
            <span class="label-text">Date</span>
            <input type="date" name="date" id="dateField" class="input input-bordered w-full" required>
          </label>
          <label class="form-control sm:col-span-1">
            <span class="label-text">Status</span>
            <select name="status" id="statusField" class="select select-bordered w-full">
              <option value="pending">pending</option>
              <option value="in_progress">in progress</option>
              <option value="done">done</option>
              <option value="cancelled">cancelled</option>
            </select>
          </label>
          <label class="form-control sm:col-span-1">
            <span class="label-text">Quantity (kg)</span>
            <input type="number" step="0.01" name="kg" id="kgField" class="input input-bordered w-full" placeholder="e.g. 1200">
          </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <label class="form-control sm:col-span-1">
            <span class="label-text">Revenue (RM)</span>
            <input type="number" step="0.01" name="rev" id="revField" class="input input-bordered w-full" placeholder="e.g. 3500">
          </label>
          <label class="form-control sm:col-span-1">
            <span class="label-text">Cost (RM)</span>
            <input type="number" step="0.01" name="cost" id="costField" class="input input-bordered w-full" placeholder="e.g. 2200">
          </label>
          <div class="sm:col-span-1"></div>
        </div>

        <label class="form-control">
          <span class="label-text">Notes</span>
          <textarea name="description" id="descField" class="textarea textarea-bordered w-full" rows="3" placeholder="Notes (optional)"></textarea>
        </label>

        <div class="modal-action">
          <a class="btn" onclick="closeModal()">Cancel</a>
          <button class="btn btn-error hidden" id="deleteBtn" type="button" onclick="confirmDelete()">Delete</button>
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </dialog>

  <script>
    const tasks         = <?= $tasks_json ?> || [];
    const monthlyKg     = <?= $monthlyKgJson ?>;
    const monthlyRev    = <?= $monthlyRevJson ?>;
    const monthlyCost   = <?= $monthlyCostJson ?>;
    const monthlyProfit = <?= $monthlyProfitJson ?>;
    const farmId = <?= (int)$farmId ?>;

    // ---- Calendar ----
    let calendar;
    document.addEventListener('DOMContentLoaded', () => {
      const calendarEl = document.getElementById('calendar');
      calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: { left:'', center:'title', right:'' },
        events: tasks.map(t => {
          let suffix = [];
          if (t.kg && t.kg>0)     suffix.push(`${t.kg} kg`);
          if (t.rev && t.rev>0)   suffix.push(`RM ${Number(t.rev).toLocaleString()}`);
          if (t.cost && t.cost>0) suffix.push(`Cost RM ${Number(t.cost).toLocaleString()}`);
          return {
            id: String(t.task_id),
            title: t.title + (suffix.length ? ` (${suffix.join(' | ')})` : ''),
            start: t.date,
            extendedProps: { status: t.status, description: t.description, kg: t.kg, rev: t.rev, cost: t.cost }
          };
        }),
        dateClick: (info) => { openCreate(info.dateStr); },
        eventClick: (info) => {
          const e = info.event;
          openEdit({
            task_id: e.id,
            title: e.title.replace(/\s+\((.*?)\)$/, ''),
            date: e.startStr,
            status: e.extendedProps.status,
            description: e.extendedProps.description || '',
            kg: e.extendedProps.kg || '',
            rev: e.extendedProps.rev || '',
            cost: e.extendedProps.cost || ''
          });
        }
      });
      calendar.render();
      renderUpcoming();
      renderKgChart();
      renderRevChart();
      renderCostChart();
      renderProfitChart();
    });

    // ===========================
    // UPDATED: Upcoming Task List
    // Only show tasks with status 'pending' or 'in_progress' and date >= today
    // ===========================
    function renderUpcoming() {
      const list = document.getElementById('upcomingList');
      list.innerHTML = '';
      const today = new Date(); today.setHours(0,0,0,0);

      const allowedStatuses = new Set(['pending', 'in_progress']);

      const evs = tasks
        .filter(t => t.date && allowedStatuses.has((t.status || '').toLowerCase()))
        .map(t => ({...t, d: new Date(t.date)}))
        .filter(t => !isNaN(t.d) && t.d >= today)
        .sort((a,b)=>a.d-b.d)
        .slice(0,8);

      if (!evs.length) {
        list.innerHTML = '<li class="text-slate-500">No upcoming pending/in-progress tasks.</li>';
        return;
      }
      evs.forEach(t=>{
        const parts = [t.status];
        if (t.kg && t.kg>0)     parts.push(`${t.kg} kg`);
        if (t.rev && t.rev>0)   parts.push(`RM ${Number(t.rev).toLocaleString()}`);
        if (t.cost && t.cost>0) parts.push(`Cost RM ${Number(t.cost).toLocaleString()}`);
        const meta = parts.join(' • ');
        const li = document.createElement('li');
        li.innerHTML = `
          <div class="flex items-start justify-between gap-2">
            <div>
              <div class="font-medium">${escapeHtml(t.title)}</div>
              <div class="text-xs text-slate-500">${t.date} • ${meta}</div>
            </div>
            <a class="btn btn-ghost btn-xs" title="Edit" href="javascript:void(0)"
               onclick='openEdit(${JSON.stringify(t)})'><i class="bi bi-pencil"></i></a>
          </div>`;
        list.appendChild(li);
      });
    }

    function escapeHtml(s){return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m]));}

    // ---- Charts ----
    function baseBarOptions(yTitle, fmtRM=false){
      return {
        responsive:true, maintainAspectRatio:false, layout:{padding:8},
        scales:{ y:{beginAtZero:true, title:{display:true,text:yTitle}, grid:{color:'rgba(0,0,0,0.06)'}},
                 x:{title:{display:true,text:'Month'}, grid:{display:false}} },
        plugins:{ legend:{display:false}, tooltip:{enabled:true,
          callbacks: fmtRM ? { label: c => `RM ${Number(c.parsed.y).toLocaleString()}` } : {}
        } }
      };
    }
    const labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    function renderKgChart(){
      const ctx = document.getElementById('kgChart');
      new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label:'kg', data: monthlyKg, borderWidth:1, barThickness:22, maxBarThickness:28, borderRadius:6, borderSkipped:false }] },
        options: baseBarOptions('Kilograms')
      });
    }
    function renderRevChart(){
      const ctx = document.getElementById('revChart');
      new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label:'RM', data: monthlyRev, borderWidth:1, barThickness:22, maxBarThickness:28, borderRadius:6, borderSkipped:false }] },
        options: baseBarOptions('Ringgit Malaysia (RM)', true)
      });
    }
    function renderCostChart(){
      const ctx = document.getElementById('costChart');
      new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label:'RM', data: monthlyCost, borderWidth:1, barThickness:22, maxBarThickness:28, borderRadius:6, borderSkipped:false }] },
        options: baseBarOptions('Cost (RM)', true)
      });
    }
    function renderProfitChart(){
      const ctx = document.getElementById('profitChart');
      new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label:'RM', data: monthlyProfit, borderWidth:1, barThickness:22, maxBarThickness:28, borderRadius:6, borderSkipped:false }] },
        options: baseBarOptions('Profit (RM)', true)
      });
    }

    // ---- Modal helpers ----
    const modal = document.getElementById('taskModal');
    const formAction = document.getElementById('formAction');
    const taskIdField = document.getElementById('taskIdField');
    const titleField = document.getElementById('titleField');
    const dateField = document.getElementById('dateField');
    const statusField = document.getElementById('statusField');
    const descField = document.getElementById('descField');
    const kgField = document.getElementById('kgField');
    const revField = document.getElementById('revField');
    const costField = document.getElementById('costField');
    const deleteBtn = document.getElementById('deleteBtn');
    const modalTitle = document.getElementById('taskModalTitle');

    function openCreate(dateStr){
      modalTitle.textContent = 'Add Task';
      formAction.value = 'create_task';
      taskIdField.value = '';
      titleField.value = '';
      dateField.value = dateStr || '';
      statusField.value = 'pending';
      descField.value = '';
      kgField.value = '';
      revField.value = '';
      costField.value = '';
      deleteBtn.classList.add('hidden');
      modal.showModal();
    }
    function openEdit(task){
      modalTitle.textContent = 'Edit Task';
      formAction.value = 'update_task';
      taskIdField.value = task.task_id;
      titleField.value = task.title || '';
      dateField.value = (task.date || '').slice(0,10);
      statusField.value = task.status || 'pending';
      const stripped = (task.description || '').replace(/(^|\s)(kg|rev|cost)\s*:\s*[0-9]+(?:\.[0-9]+)?/gi,'').trim();
      descField.value = stripped;
      kgField.value = task.kg || '';
      revField.value = task.rev || '';
      costField.value = task.cost || '';
      deleteBtn.classList.remove('hidden');
      deleteBtn.setAttribute('data-id', task.task_id);
      modal.showModal();
    }
    function closeModal(){ modal.close(); }
    function confirmDelete(){
      const id = deleteBtn.getAttribute('data-id');
      if (!id) return;
      if (confirm('Delete this task?')) {
        window.location.href = `./farm.php?id=${farmId}&action=delete_task&task_id=${id}`;
      }
    }
    window.openCreate=openCreate; window.openEdit=openEdit; window.closeModal=closeModal; window.confirmDelete=confirmDelete;
  </script>
</body>
</html>
