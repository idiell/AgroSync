<?php
include '../../database/db.php';

$user_id = 1; // temporary until auth is built
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = $_POST['name'];
  $farm_type = $_POST['farm_type'];
  $status = $_POST['status'];
  $size_acres = $_POST['size_acres'];

  $sql = "INSERT INTO farms (user_id, name, farm_type, status, size_acres)
          VALUES (?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("isssd", $user_id, $name, $farm_type, $status, $size_acres);

  if ($stmt->execute()) {
    header("Location: ./index.php");
    exit;
  } else {
    $message = "Error: " . $stmt->error;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Farm</title>
  <link href="../../public/app.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css">
</head>
<body class="flex h-screen bg-[#FAFAFA] text-slate-800">
  <?php include '../../components/sidebar.php'; ?>
  <main class="flex-1 p-6 overflow-y-auto">
    <h1 class="text-2xl font-semibold mb-4">Add Farm</h1>
    <?php if($message): ?><p class="text-red-500 mb-2"><?= $message ?></p><?php endif; ?>

    <form method="post" class="grid gap-4 max-w-xl">
      <label>Name <input name="name" class="input input-bordered w-full" required></label>
      <label>Type
        <select name="farm_type" class="select select-bordered w-full">
          <option>Oil Palm</option><option>Paddy</option><option>Vegetables</option>
          <option>Fruits</option><option>Rubber</option>
        </select>
      </label>
      <label>Status
        <select name="status" class="select select-bordered w-full">
          <option>Active</option><option>Inactive</option><option>Seasonal</option>
        </select>
      </label>
      <label>Size (acres)
        <input type="number" step="0.01" name="size_hectare" class="input input-bordered w-full" value="0.00">
      </label>
      <div class="flex gap-2">
        <a href="./index.php" class="btn">Cancel</a>
        <button class="btn btn-primary">Save</button>
      </div>
    </form>
  </main>
</body>
</html>
