<?php
include '../../database/db.php';
$user_id = 1;
$message = "";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch current data
$sql = "SELECT * FROM farms WHERE farm_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$farm = $result->fetch_assoc();

if (!$farm) { die("Farm not found."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = $_POST['name'];
  $farm_type = $_POST['farm_type'];
  $status = $_POST['status'];
  $size_hectare = $_POST['size_acres'];

  $sql = "UPDATE farms 
          SET name=?, farm_type=?, status=?, size_acres=?
          WHERE farm_id=? AND user_id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sssdddii", $name, $farm_type, $status, $size_acres, $id, $user_id);

  if ($stmt->execute()) {
    header("Location: ./index.php");
    exit;
  } else {
    $message = "Update failed: " . $stmt->error;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Farm</title>
  <link href="../../public/app.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css">
</head>
<body class="flex h-screen bg-[#FAFAFA] text-slate-800">
  <?php include '../../components/sidebar.php'; ?>
  <main class="flex-1 p-6 overflow-y-auto">
    <h1 class="text-2xl font-semibold mb-4">Edit Farm</h1>
    <?php if($message): ?><p class="text-red-500 mb-2"><?= $message ?></p><?php endif; ?>

    <form method="post" class="grid gap-4 max-w-xl">
      <label>Name <input name="name" class="input input-bordered w-full" value="<?= htmlspecialchars($farm['name']) ?>" required></label>
      <label>Type
        <select name="farm_type" class="select select-bordered w-full">
          <?php
          $types = ['Oil Palm','Paddy','Vegetables','Fruits','Rubber'];
          foreach($types as $type) {
            $sel = ($type == $farm['farm_type']) ? "selected" : "";
            echo "<option $sel>$type</option>";
          }
          ?>
        </select>
      </label>
      <label>Status
        <select name="status" class="select select-bordered w-full">
          <?php
          $statuses = ['Active','Inactive','Seasonal'];
          foreach($statuses as $s) {
            $sel = ($s == $farm['status']) ? "selected" : "";
            echo "<option $sel>$s</option>";
          }
          ?>
        </select>
      </label>
      <label>Size (acres)
        <input type="number" step="0.01" name="size_acres" class="input input-bordered w-full" value="<?= htmlspecialchars($farm['size_hectare']) ?>">
      </label>
      <div class="flex gap-2">
        <a href="./index.php" class="btn">Cancel</a>
        <button class="btn btn-primary">Update</button>
      </div>
    </form>
  </main>
</body>
</html>
