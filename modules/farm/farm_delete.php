<?php
include '../../database/db.php';
$user_id = 1;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "DELETE FROM farms WHERE farm_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $user_id);

if ($stmt->execute()) {
  header("Location: ./index.php");
  exit;
} else {
  echo "Delete failed: " . $stmt->error;
}
?>
