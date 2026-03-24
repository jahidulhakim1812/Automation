<?php
$conn = new mysqli("localhost", "root", "", "freelancing");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

function formatTime($time) {
  return date("g:i A", strtotime($time));
}

$studentInfo = null;
$routine = [];

if (isset($_GET["student_id"])) {
  $id = $conn->real_escape_string($_GET["student_id"]);

  // Fetch student name
  $studentQuery = $conn->query("SELECT name FROM students WHERE student_id = '$id'");
  if ($studentQuery->num_rows > 0) {
    $studentInfo = $studentQuery->fetch_assoc();
  }

  // Fetch routine
  $routineQuery = $conn->query("
    SELECT * FROM student_routine
    WHERE student_id = '$id'
    ORDER BY FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time ASC
  ");

  while ($row = $routineQuery->fetch_assoc()) {
    $routine[] = $row;
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Routine Report</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 30px; }
    .report-box { background: white; padding: 30px; max-width: 900px; margin: auto; box-shadow: 0 0 10px #ccc; }
    h2 { text-align: center; margin-bottom: 20px; }
    .student-info { margin-bottom: 20px; font-size: 16px; }
    .student-info strong { display: inline-block; width: 130px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 10px; text-align: center; font-size: 15px; }
    .print-btn { margin-top: 20px; text-align: center; }
    .print-btn button {
      padding: 10px 20px;
      font-size: 16px;
      background: #0077cc;
      color: white;
      border: none;
      cursor: pointer;
      border-radius: 5px;
      margin: 0 10px;
    }
    .print-btn button:hover { background: #005fa3; }
    @media print {
      .print-btn { display: none; }
      body { background: white; padding: 0; }
      .report-box { box-shadow: none; margin: 0; }
    }
  </style>
  <script>
    function goBack() {
      if (document.referrer) {
        window.location.href = document.referrer;
      } else {
        window.location.href = "student_list.php"; // fallback page
      }
    }
  </script>
</head>
<body>

<div class="report-box" id="printArea">
  <h2>📋 Routine Report</h2>

  <?php if ($studentInfo): ?>
    <div class="student-info">
      <p><strong>Student Name:</strong> <?= htmlspecialchars($studentInfo["name"]) ?></p>
      <p><strong>Student ID:</strong> <?= htmlspecialchars($_GET["student_id"]) ?></p>
    </div>
  <?php endif; ?>

  <?php if (!empty($routine)): ?>
    <table>
      <tr>
        <th>Day</th>
        <th>Time</th>
        <th>Instructor</th>
        <th>Computer No</th>
      </tr>
      <?php foreach ($routine as $entry): ?>
        <tr>
          <td><?= $entry["day"] ?></td>
          <td><?= formatTime($entry["start_time"]) ?> - <?= formatTime($entry["end_time"]) ?></td>
          <td><?= $entry["instructor"] ?></td>
          <td><?= $entry["computer_no"] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p style="text-align:center; color:red;">No routine found.</p>
  <?php endif; ?>

  <div class="print-btn">
    <button onclick="goBack()">🔙 Back</button>
    <button onclick="window.print()">🖨️ Print / Download</button>
  </div>
</div>

</body>
</html>
