<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, "", $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student = null;
if (isset($_GET["student_id"])) {
  $id = $_GET["student_id"];
  $result = $conn->query("SELECT * FROM students WHERE student_id = '$id'");
  if ($result->num_rows === 1) $student = $result->fetch_assoc();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Invoice - Student Payment Report</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; background: white; }
    .header {
      text-align: center;
      margin-bottom: 30px;
    }
    .header h1 {
      margin: 0;
      font-size: 28px;
    }
    .header p {
      margin: 5px 0;
      font-size: 16px;
      color: #555;
    }
    table {
      width: 60%;
      margin: 0 auto;
      border-collapse: collapse;
    }
    th, td {
      border: 1px solid #333;
      padding: 12px;
      text-align: left;
    }
    th {
      background: #333;
      color: white;
    }
    .footer {
      text-align: center;
      margin-top: 50px;
      font-size: 14px;
      color: #777;
    }
    .buttons {
      text-align: center;
      margin-top: 30px;
    }
    .buttons button {
      margin: 0 10px;
      padding: 10px 20px;
      font-size: 14px;
      cursor: pointer;
    }
    @media print {
      .buttons { display: none; }
    }
  </style>
</head>
<body>

<div class="header">
  <h1>AR TECH SOLUTION</h1>
  <p>House #64, Road #Shahid Siddique Road, Boardbazar, Gazipur-1704, Bangladesh</p>
  <p>Email: artechsolution.online@gmail.com | Phone: +880 1957-288638</p>
</div>

<?php if ($student): ?>
  <h2 style="text-align:center;">Student Payment Invoice</h2>
  <table>
    <tr><th>Student ID</th><td><?php echo $student["student_id"]; ?></td></tr>
    <tr><th>Name</th><td><?php echo htmlspecialchars($student["name"]); ?></td></tr>
    <tr><th>Course Category</th><td><?php echo htmlspecialchars($student["course_category"]); ?></td></tr>
    <tr><th>Total Course Fee</th><td>৳ <?php echo number_format($student["course_fee"], 2); ?></td></tr>
    <tr><th>Paid Fee</th><td>৳ <?php echo number_format($student["paid_fee"], 2); ?></td></tr>
    <tr><th>Due Fee</th><td>৳ <?php echo number_format($student["course_fee"] - $student["paid_fee"], 2); ?></td></tr>
    <tr><th>Last Payment</th><td><?php echo $student["last_updated"]; ?></td></tr>
  </table>

  <div class="buttons">
    <button onclick="window.print()">🖨️ Print Invoice</button>
    <button onclick="window.location.href='report.php'">← Back</button>

  </div>

  <div class="footer">
    &copy; <?php echo date("Y"); ?> Freelancing Students Management System
  </div>
<?php else: ?>
  <p style="text-align:center; color:red;">Student not found.</p>
<?php endif; ?>

</body>
</html>
