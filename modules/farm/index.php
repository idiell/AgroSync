<?php
include '../../database/db.php'; // adjust the path if needed

// Hardcode user id until auth is built
$user_id = 1;

/* ---------------- Helpers for token parsing ---------------- */
function parse_metric_from_desc(?string $desc, string $key): float {
  if (!$desc) return 0.0;
  $pattern = '/(?:^|\s)'.preg_quote($key,'/').'\s*:\s*([0-9]+(?:\.[0-9]+)?)/i';
  if (preg_match($pattern, $desc, $m)) return (float)$m[1];
  return 0.0;
}

/* ---------------- Pull tasks for ALL farms of this user ---------------- */
$monthlyKg   = array_fill(0, 12, 0.0);
$monthlyRev  = array_fill(0, 12, 0.0);
$monthlyCost = array_fill(0, 12, 0.0);

$sqlTasks = "
  SELECT t.description, t.date
  FROM tasks t
  INNER JOIN farms f ON f.farm_id = t.farm_id
  WHERE f.user_id = ?
";
$stmt = $conn->prepare($sqlTasks);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resTasks = $stmt->get_result();

while ($row = $resTasks->fetch_assoc()) {
  $d = $row['date'] ?? null;
  if (!$d) continue;
  $ts = strtotime($d);
  if ($ts === false) continue;
  $m = (int)date('n', $ts) - 1; // 0..11

  $desc = $row['description'] ?? '';
  $kg   = parse_metric_from_desc($desc, 'kg');
  $rev  = parse_metric_from_desc($desc, 'rev');
  $cost = parse_metric_from_desc($desc, 'cost');

  $monthlyKg[$m]   += $kg;
  $monthlyRev[$m]  += $rev;
  $monthlyCost[$m] += $cost;
}

$monthlyProfit = [];
for ($i=0; $i<12; $i++) {
  $monthlyProfit[$i] = $monthlyRev[$i] - $monthlyCost[$i];
}

$kgJson     = json_encode(array_map(fn($v)=>round($v,2), $monthlyKg));
$revJson    = json_encode(array_map(fn($v)=>round($v,2), $monthlyRev));
$costJson   = json_encode(array_map(fn($v)=>round($v,2), $monthlyCost));
$profitJson = json_encode(array_map(fn($v)=>round($v,2), $monthlyProfit));

/* ---------------- Fetch farm list for table ---------------- */
$sql = "SELECT farm_id, name, farm_type, status, size_acres
        FROM farms
        WHERE user_id = $user_id 
        ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Farm Management | AgroSync</title>
  <link href="../../public/app.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    .card { background:#fff; border:1px solid rgb(226 232 240); border-radius:.75rem; }
  </style>
</head>
<body class="flex h-screen font-inter bg-[#FAFAFA] text-slate-800">

  <?php include '../../components/sidebar.php'; ?>

  <main class="flex-1 p-6 overflow-y-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
      <h1 class="text-2xl font-semibold">Farm Management</h1>
      <a href="./farm_create.php" class="btn btn-outline btn-primary gap-2">
        <i class="bi bi-plus-lg"></i> Add Farm
      </a>
    </div>

    <!-- Summary Chart -->
    <section class="card p-4 mb-6">
      <div class="flex items-center justify-between gap-3 mb-2">
        <div>
          <h2 class="text-lg font-semibold">Monthly Summary</h2>
          <p class="text-xs text-slate-500">Aggregate across all farms (Jan–Dec)</p>
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm text-slate-600">Metric</label>
          <select id="metricSelect" class="select select-bordered select-sm">
            <option value="kg">Performance (kg)</option>
            <option value="rev">Revenue (RM)</option>
            <option value="cost">Cost (RM)</option>
            <option value="profit">Profit (RM)</option>
          </select>
        </div>
      </div>
      <!-- smaller height -->
      <div class="w-full max-w-5xl h-52 mx-auto">        
        <canvas id="summaryChart" class="w-full h-full"></canvas>
      </div>
    </section>
      <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow-md">
          <i class="bi bi-search"></i>
            <input type="text" id="search" class="form-control" placeholder="Search..." onkeyup="searchFarm()">
            <div id="farmResults" class="row" ></div>
      </div>
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
      <table class="table table-zebra w-full">
        <thead class="bg-slate-100">
          <tr class="text-slate-600 text-sm">
            <th>#</th>
            <th>Name</th>
            <th>Type</th>
            <th>Size (acres)</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($result && $result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
              $acres = round($row['size_acres']);
              $badgeClass = ($row['status'] == 'Active') ? 'badge-success' :
                            (($row['status'] == 'Seasonal') ? 'badge-warning' : 'badge-ghost');
              echo "
              <tr class='hover'>
                <td>{$i}</td>
                <td><a class='link link-primary' href='./farm.php?id={$row['farm_id']}'>" . htmlspecialchars($row['name']) . "</a></td>
                <td>" . htmlspecialchars($row['farm_type']) . "</td>
                <td>{$acres}</td>
                <td><span class='badge {$badgeClass} badge-outline'>" . htmlspecialchars($row['status']) . "</span></td>
                <td class='space-x-2'>
                  <a class='btn btn-xs btn-outline btn-info' href='./farm.php?id={$row['farm_id']}' title='Open Dashboard'><i class='bi bi-eye'></i></a>
                  <a class='btn btn-xs btn-outline btn-warning' href='./farm_edit.php?id={$row['farm_id']}' title='Edit'><i class='bi bi-pencil-square'></i></a>
                  <a class='btn btn-xs btn-outline btn-error' href='./farm_delete.php?id={$row['farm_id']}' onclick='return confirm(\"Delete this farm?\")'><i class='bi bi-trash3'></i></a>
                </td>
              </tr>";
              $i++;
            }
          } else {
            echo "<tr><td colspan='6' class='text-center py-8 text-slate-500'>No farms found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </main>

  <script>
  function searchFarm(){
    const keyword = document.getElementById("search").value;
    const xhr = new XMLHttpRequest();
    xhr.open("POST","",true);
    xhr.setRequestHeader("Comtent-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
      if (xhr.status === 200) {
        document.getElementById("farmResults").innerHTML = xhr.responseText;
      }
    };
    xhr.send("ajax=1&q=" + encodeURIComponent(keyword));
  }

    // PHP → JS data
    const monthlyKg     = <?= $kgJson ?>;
    const monthlyRev    = <?= $revJson ?>;
    const monthlyCost   = <?= $costJson ?>;
    const monthlyProfit = <?= $profitJson ?>;

    const labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    // Find a nice suggested max so single big months don't look gigantic
    function niceMax(arr) {
      const max = Math.max(...arr, 0);
      if (max <= 0) return 10;
      // round up to 1, 2, 5 × 10^n
      const pow10 = Math.pow(10, Math.floor(Math.log10(max)));
      const unit = max / pow10;
      let niceUnit = 1;
      if (unit > 5) niceUnit = 10;
      else if (unit > 2) niceUnit = 5;
      else if (unit > 1) niceUnit = 2;
      return niceUnit * pow10;
    }

    // Common Chart options (compact)
    function barOptions(yLabel, isRM=false, suggestedMax=undefined){
      return {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 6 },
        scales: {
          y: {
            beginAtZero: true,
            suggestedMax,
            title: { display: true, text: yLabel },
            grid: { color: 'rgba(0,0,0,0.06)' },
            ticks: { maxTicksLimit: 6 }
          },
          x: {
            title: { display: true, text: 'Month' },
            grid: { display: false },
            ticks: { maxRotation: 0 }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            enabled: true,
            callbacks: isRM ? { label: ctx => `RM ${Number(ctx.parsed.y).toLocaleString()}` } : {}
          }
        },
        elements: {
          bar: {
            borderRadius: 6,
            borderSkipped: false,
            barThickness: 12,
            maxBarThickness: 16
          }
        },
        datasets: {
          bar: {
            categoryPercentage: 0.55,
            barPercentage: 0.75
          }
        }
      };
    }

    const ctx = document.getElementById('summaryChart');
    const metricSelect = document.getElementById('metricSelect');

    const datasets = {
      kg:     { data: monthlyKg,     label: 'Performance (kg)', yTitle: 'Kilograms',               isRM: false },
      rev:    { data: monthlyRev,    label: 'Revenue (RM)',     yTitle: 'Ringgit Malaysia (RM)',  isRM: true  },
      cost:   { data: monthlyCost,   label: 'Cost (RM)',        yTitle: 'Ringgit Malaysia (RM)',  isRM: true  },
      profit: { data: monthlyProfit, label: 'Profit (RM)',      yTitle: 'Ringgit Malaysia (RM)',  isRM: true  },
    };

    function makeChartConfig(key){
      const ds = datasets[key];
      return {
        type: 'bar',
        data: { labels, datasets: [{ label: ds.label, data: ds.data }] },
        options: barOptions(ds.yTitle, ds.isRM, niceMax(ds.data))
      };
    }

    let currentKey = 'kg';
    let chart = new Chart(ctx, makeChartConfig(currentKey));

    metricSelect.addEventListener('change', () => {
      currentKey = metricSelect.value;
      chart.destroy(); // rebuild to apply suggestedMax cleanly
      chart = new Chart(ctx, makeChartConfig(currentKey));
    });
  </script>
</body>
</html>
