<?php
session_start();
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "User") {
  header("Location: login.php");
  exit();
}
$conn = new mysqli("localhost", "root", "", "freelancing");
$email = $_SESSION["email"];

$get_id = $conn->query("SELECT student_id FROM students WHERE email = '$email'");
$student_id = ($get_id->num_rows) ? $get_id->fetch_assoc()["student_id"] : null;

$messages = [];
if ($student_id) {
  $msg_result = $conn->query("SELECT message, created_at FROM messages WHERE student_id = '$student_id' ORDER BY created_at DESC");
  while ($row = $msg_result->fetch_assoc()) {
    $messages[] = $row;
  }
}
$conn->close();
?>
<!DOCTYPE html>
<html><head><title>Inbox</title></head><body>
<h2>Your Messages</h2>
<?php if (empty($messages)): ?>
  <p>No messages yet.</p>
<?php else: ?>
  <ul>
    <?php foreach ($messages as $m): ?>
      <li><strong>[<?= $m["created_at"] ?>]</strong> - <?= htmlspecialchars($m["message"]) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
</body></html>
