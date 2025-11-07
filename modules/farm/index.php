<?php
include '../../database/db.php';

// Hardcode user id until auth is built
$user_id = 1;

/* ---------------- Helpers for token parsing ---------------- */
function parse_metric_from_desc(?string $desc, string $key): float {
    if (!$desc) return 0.0;
    $pattern = '/(?:^|\s)'.preg_quote($key,'/').'\s*:\s*([0-9]+(?:\.[0-9]+)?)/i';
    if (preg_match($pattern, $desc, $matches)) return (float)$matches[1];
    return 0.0;
}

/* ---------------- Status badge helper ---------------- */
function farm_status_badge(string $status): string {
    switch ($status) {
        case 'Active':
            return 'badge-success';
        case 'Seasonal':
            return 'badge-warning';
        default:
            return 'badge-ghost';
    }
}

/* ---------------- Monthly summary from ALL farms ---------------- */
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
for ($i = 0; $i < 12; $i++) {
    $monthlyProfit[$i] = $monthlyRev[$i] - $monthlyCost[$i];
}

$kgJson     = json_encode(array_map(fn($v)=>round($v,2), $monthlyKg));
$revJson    = json_encode(array_map(fn($v)=>round($v,2), $monthlyRev));
$costJson   = json_encode(array_map(fn($v)=>round($v,2), $monthlyCost));
$profitJson = json_encode(array_map(fn($v)=>round($v,2), $monthlyProfit));

/* ---------------- AJAX search handler ----------------
   This returns ONLY <tr> rows for the table body.
------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: text/html; charset=UTF-8');

    $q = trim($_POST['q'] ?? '');

    $sqlAjax = "
        SELECT farm_id, name, farm_type, status, size_acres
        FROM farms
        WHERE user_id = ?
    ";

    if ($q !== '') {
        $sqlAjax .= " AND (name LIKE ? OR farm_type LIKE ? OR status LIKE ?)";
    }

    $sqlAjax .= " ORDER BY created_at DESC";

    $stmtAjax = $conn->prepare($sqlAjax);

    if ($q !== '') {
        $like = "%{$q}%";
        $stmtAjax->bind_param("isss", $user_id, $like, $like, $like);
    } else {
        $stmtAjax->bind_param("i", $user_id);
    }

    $stmtAjax->execute();
    $resAjax = $stmtAjax->get_result();

    if ($resAjax->num_rows > 0) {
        $i = 1;
        while ($row = $resAjax->fetch_assoc()) {
            $acres = round((float)$row['size_acres']);
            $badgeClass = farm_status_badge($row['status']);

            echo '
            <tr class="hover">
              <td>'. $i .'</td>
              <td>
                <a class="link link-primary" href="./farm.php?id='. (int)$row['farm_id'] .'">'
                    . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') .
                '</a>
              </td>
              <td>'. htmlspecialchars($row['farm_type'], ENT_QUOTES, 'UTF-8') .'</td>
              <td>'. $acres .'</td>
              <td>
                <span class="badge '. $badgeClass .' badge-outline">'
                    . htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') .
                '</span>
              </td>
              <td class="space-x-2">
                <a class="btn btn-xs btn-outline btn-info"
                   href="./farm.php?id='. (int)$row['farm_id'] .'"
                   title="Open Dashboard">
                  <i class="bi bi-eye"></i>
                </a>
                <a class="btn btn-xs btn-outline btn-warning"
                   href="./farm_edit.php?id='. (int)$row['farm_id'] .'"
                   title="Edit">
                  <i class="bi bi-pencil-square"></i>
                </a>
                <a class="btn btn-xs btn-outline btn-error"
                   href="./farm_delete.php?id='. (int)$row['farm_id'] .'"
                   onclick="return confirm(\'Delete this farm?\')"
                   title="Delete">
                  <i class="bi bi-trash3"></i>
                </a>
              </td>
            </tr>';
            $i++;
        }
    } else {
        echo '<tr><td colspan="6" class="text-center py-8 text-slate-500">No farms found.</td></tr>';
    }
    exit;
}

/* ---------------- Initial farm list for full page load ---------------- */
$sql = "
  SELECT farm_id, name, farm_type, status, size_acres
  FROM farms
  WHERE user_id = $user_id
  ORDER BY created_at DESC
";
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
    .card {
      background:#fff;
      border:1px solid rgb(226 232 240);
      border-radius:.75rem;
    }
  </style>
</head>
<body class="flex h-screen font-inter bg-[#FAFAFA] text-slate-800">

<?php include '../../components/sidebar.php'; ?>

<main class="flex-1 p-6 overflow-y-auto">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
    <div>
      <h1 class="text-2xl font-semibold">Farm Management</h1>
      <p class="text-sm text-slate-500">Overview of all your farms in AgroSync.</p>
    </div>
    <a href="./farm_create.php" class="btn btn-outline btn-primary gap-2">
      <i class="bi bi-plus-lg"></i> Add Farm
    </a>
  </div>

  <!-- Monthly Summary Chart -->
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
    <div class="w-full max-w-5xl h-52 mx-auto">
      <canvas id="summaryChart" class="w-full h-full"></canvas>
    </div>
  </section>

  <!-- Search + Farm Table -->
  <div class="px-4 pt-4 pb-2 flex items-center justify-between gap-3 mb-3">
       <i class="bi bi-search"></i>
       <div class="relative w-full max-w-xs">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
         
        </span>
        <input
          type="text"
          id="search"
          class="input input-bordered input-sm pl-8 w-full"          
          onkeyup="searchFarm()"
        >
      </div>
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
      <tbody id="farmTableBody">
        <?php
        if ($result && $result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                $acres = round((float)$row['size_acres']);
                $badgeClass = farm_status_badge($row['status']);
                ?>
                <tr class="hover">
                  <td><?= $i; ?></td>
                  <td>
                    <a class="link link-primary" href="./farm.php?id=<?= (int)$row['farm_id']; ?>">
                      <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($row['farm_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?= $acres; ?></td>
                  <td>
                    <span class="badge <?= $badgeClass; ?> badge-outline">
                      <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  </td>
                  <td class="space-x-2">
                    <a class="btn btn-xs btn-outline btn-info"
                       href="./farm.php?id=<?= (int)$row['farm_id']; ?>"
                       title="Open Dashboard">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a class="btn btn-xs btn-outline btn-warning"
                       href="./farm_edit.php?id=<?= (int)$row['farm_id']; ?>"
                       title="Edit">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                    <a class="btn btn-xs btn-outline btn-error"
                       href="./farm_delete.php?id=<?= (int)$row['farm_id']; ?>"
                       onclick="return confirm('Delete this farm?')"
                       title="Delete">
                      <i class="bi bi-trash3"></i>
                    </a>
                  </td>
                </tr>
                <?php
                $i++;
            }
        } else {
            echo '<tr><td colspan="6" class="text-center py-8 text-slate-500">No farms found.</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</main>

<script>
  /* ---------- AJAX Search ---------- */
  function searchFarm() {
    const keyword = document.getElementById("search").value;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
      if (xhr.status === 200) {
        document.getElementById("farmTableBody").innerHTML = xhr.responseText;
      }
    };
    xhr.send("ajax=1&q=" + encodeURIComponent(keyword));
  }

  /* ---------- Chart Data from PHP ---------- */
  const monthlyKg     = <?= $kgJson ?>;
  const monthlyRev    = <?= $revJson ?>;
  const monthlyCost   = <?= $costJson ?>;
  const monthlyProfit = <?= $profitJson ?>;
  const labels        = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  function niceMax(arr) {
    const max = Math.max(...arr, 0);
    if (max <= 0) return 10;
    const pow10 = Math.pow(10, Math.floor(Math.log10(max)));
    const unit = max / pow10;
    let niceUnit = 1;
    if (unit > 5) niceUnit = 10;
    else if (unit > 2) niceUnit = 5;
    else if (unit > 1) niceUnit = 2;
    return niceUnit * pow10;
  }

  function barOptions(yLabel, isRM=false, suggestedMax=undefined) {
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
          callbacks: isRM
            ? { label: ctx => `RM ${Number(ctx.parsed.y).toLocaleString()}` }
            : {}
        }
      },
      elements: {
        bar: {
          borderRadius: 6,
          borderSkipped: false,
          barThickness: 12,
          maxBarThickness: 16
        }
      }
    };
  }

  const ctx = document.getElementById('summaryChart');
  const metricSelect = document.getElementById('metricSelect');

  const datasets = {
    kg:     { data: monthlyKg,     label: 'Performance (kg)', yTitle: 'Kilograms',              isRM: false },
    rev:    { data: monthlyRev,    label: 'Revenue (RM)',     yTitle: 'Ringgit Malaysia (RM)', isRM: true  },
    cost:   { data: monthlyCost,   label: 'Cost (RM)',        yTitle: 'Ringgit Malaysia (RM)', isRM: true  },
    profit: { data: monthlyProfit, label: 'Profit (RM)',      yTitle: 'Ringgit Malaysia (RM)', isRM: true  },
  };

  function makeChartConfig(key) {
    const ds = datasets[key];
    return {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: ds.label,
          data: ds.data
        }]
      },
      options: barOptions(ds.yTitle, ds.isRM, niceMax(ds.data))
    };
  }

  let currentKey = 'kg';
  let chart = new Chart(ctx, makeChartConfig(currentKey));

  metricSelect.addEventListener('change', () => {
    currentKey = metricSelect.value;
    chart.destroy();
    chart = new Chart(ctx, makeChartConfig(currentKey));
  });
</script>

</body>
</html>
