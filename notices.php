<?php
session_start();
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "User") {
  header("Location: login.php");
  exit();
}

$conn = new mysqli("localhost", "root", "", "freelancing");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$results = $conn->query("SELECT title, message, created_at FROM notices ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html><head><title>Student Notices</title></head><body>
<h2>📩 Notices from Admin</h2>
<?php if ($results->num_rows > 0): ?>
  <ul>
    <?php while ($n = $results->fetch_assoc()): ?>
      <li>
        <strong><?= htmlspecialchars($n["title"]) ?></strong><br>
        <?= htmlspecialchars($n["message"]) ?><br>
        <em>[<?= $n["created_at"] ?>]</em><br><br>
      </li>
    <?php endwhile; ?>
  </ul>
<?php else: ?>
  <p>No notices yet.</p>
<?php endif; ?>
</body></html>
