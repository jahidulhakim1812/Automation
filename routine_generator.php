<?php
$conn = new mysqli("localhost", "root", "", "freelancing");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch students
$students = $conn->query("SELECT student_id, name FROM students ORDER BY name ASC");

// Handle routine submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_routine"])) {
  $student_id = $_POST["student_id"];
  $day = $_POST["day"];
  $start_time = $_POST["start_time"];
  $end_time = $_POST["end_time"];
  $instructor = $conn->real_escape_string($_POST["instructor"]);
  $computer_no = $conn->real_escape_string($_POST["computer_no"]);

  // Conflict check
  $conflict = $conn->query("
    SELECT * FROM student_routine
    WHERE day = '$day'
      AND (
        ('$start_time' BETWEEN start_time AND end_time)
        OR ('$end_time' BETWEEN start_time AND end_time)
        OR (start_time BETWEEN '$start_time' AND '$end_time')
      )
      AND (instructor = '$instructor' OR computer_no = '$computer_no')
  ");

  if ($conflict->num_rows > 0) {
    echo "<script>alert('Conflict detected: Instructor or Computer already booked at this time.'); window.location.href='routine_generator.php?student_id=$student_id';</script>";
    exit;
  }

  // Insert routine
  $sql = "INSERT INTO student_routine (student_id, day, start_time, end_time, instructor, computer_no)
          VALUES ('$student_id', '$day', '$start_time', '$end_time', '$instructor', '$computer_no')";
  $conn->query($sql);
  echo "<script>alert('Routine added successfully'); window.location.href='routine_generator.php?student_id=$student_id';</script>";
  exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Routine Generator</title>
  <style>
    body { font-family: Arial; background: #f4f4f4; margin: 0; }
    .container { max-width: 700px; margin: auto; padding: 30px; background: white; box-shadow: 0 0 10px #ccc; margin-top: 30px; }
    h2 { text-align: center; }
    select, input, button { padding: 8px; font-size: 16px; margin: 10px 0; width: 100%; }
    .footer { text-align: center; margin-top: 40px; color: #666; }
    a.report-link { display: block; text-align: center; margin-top: 20px; font-weight: bold; color: #0077cc; text-decoration: none; }
    .back-btn {
      display: block;
      text-align: center;
      margin-bottom: 20px;
    }
    .back-btn button {
      padding: 10px 20px;
      font-size: 16px;
      background: #555;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .back-btn button:hover {
      background: #333;
    }
  </style>
  <script>
    function setEndTime() {
      const startInput = document.querySelector('input[name="start_time"]');
      const endInput = document.querySelector('input[name="end_time"]');
      const startTime = startInput.value;

      if (startTime) {
        let [hour, minute] = startTime.split(":").map(Number);
        hour = (hour + 1) % 24;
        endInput.value = hour.toString().padStart(2, '0') + ":" + minute.toString().padStart(2, '0');
      }
    }
  </script>
</head>
<body>

<div class="container">
  <h2>🧮 Routine Generator</h2>

  <div class="back-btn">
    <a href="dashboard.php">
      <button type="button">🔙 Back to Dashboard</button>
    </a>
  </div>

  <form method="GET">
    <label>Select Student:</label>
    <select name="student_id" required>
      <option value="">-- Select Student --</option>
      <?php while ($row = $students->fetch_assoc()): ?>
        <option value="<?= $row["student_id"] ?>" <?= isset($_GET["student_id"]) && $_GET["student_id"] == $row["student_id"] ? 'selected' : '' ?>>
          <?= $row["student_id"] ?> - <?= htmlspecialchars($row["name"]) ?>
        </option>
      <?php endwhile; ?>
    </select>
    <button type="submit">Load Form</button>
  </form>

  <?php if (isset($_GET["student_id"])): ?>
    <form method="POST">
      <input type="hidden" name="student_id" value="<?= $_GET["student_id"] ?>" />
      <label>Day:</label>
      <select name="day" required>
        <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): ?>
          <option value="<?= $day ?>"><?= $day ?></option>
        <?php endforeach; ?>
      </select>
      <label>Start Time:</label>
      <input type="time" name="start_time" required onchange="setEndTime()" />
      <label>End Time (auto 1hr later):</label>
      <input type="time" name="end_time" required readonly />
      <label>Instructor Name:</label>
      <input type="text" name="instructor" required />
      <label>Computer No:</label>
      <input type="text" name="computer_no" required />
      <button type="submit" name="submit_routine">Add Routine</button>
    </form>

    <a class="report-link" href="routine_report.php?student_id=<?= $_GET["student_id"] ?>" target="_blank">📄 View Routine Report</a>
  <?php endif; ?>
</div>

<div class="footer">
  &copy; <?= date("Y") ?> Routine Management System
</div>

</body>
</html>
