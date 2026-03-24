<?php
session_start();
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "User") {
  header("Location: login.php");
  exit();
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing";

// DB connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// get logged-in student id
$email = $_SESSION["email"];
$student = $conn->query("SELECT student_id, name FROM students WHERE email='$email' LIMIT 1")->fetch_assoc();
if (!$student) {
    die("❌ Student record not found.");
}
$student_id = $student["student_id"];
$student_name = $student["name"];

// ✅ if user requests PDF download
if (isset($_GET['download']) && $_GET['download'] === "pdf") {
    require('fpdf/fpdf.php');
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,"Attendance Report",0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,"Student: $student_name ($student_id)",0,1,'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(40,10,"Date",1);
    $pdf->Cell(40,10,"Status",1);
    $pdf->Ln();

    $result = $conn->query("SELECT * FROM attendance WHERE student_id='$student_id' ORDER BY date ASC");
    $pdf->SetFont('Arial','',12);
    while($row = $result->fetch_assoc()) {
        $pdf->Cell(40,10,$row["date"],1);
        $pdf->Cell(40,10,$row["status"],1);
        $pdf->Ln();
    }
    $pdf->Output("D","attendance_report.pdf");
    exit;
}

// ✅ Fetch attendance for table display
$attendance = [];
$sql = "SELECT * FROM attendance WHERE student_id='$student_id' ORDER BY date ASC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $attendance[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Attendance Report</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f6f9; margin:0; padding:0; }
.navbar { background: #222; color:#fff; padding:15px; text-align:center; font-size:18px; }
.container { max-width:800px; margin:40px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.2);}
h2 { text-align:center; }
table { width:100%; border-collapse:collapse; margin-top:20px;}
th, td { border:1px solid #ccc; padding:10px; text-align:center;}
button, a.btn { display:inline-block; margin-top:15px; padding:10px 15px; background:#3498db; color:white; border:none; border-radius:5px; cursor:pointer; text-decoration:none;}
button:hover, a.btn:hover { background:#217dbb; }
</style>
</head>
<body>

<div class="navbar">📅 My Attendance Report</div>

<div class="container">
  <h2><?= htmlspecialchars($student_name) ?> (<?= htmlspecialchars($student_id) ?>)</h2>

  <?php if (count($attendance) > 0): ?>
    <table>
      <tr>
        <th>Date</th>
        <th>Status</th>
      </tr>
      <?php foreach($attendance as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row["date"]) ?></td>
        <td><?= htmlspecialchars($row["status"]) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="text-align:center;">
      <a href="?download=pdf" class="btn">⬇️ Download PDF</a>
    </div>
  <?php else: ?>
    <p style="text-align:center; color:red;">❌ No attendance records found.</p>
  <?php endif; ?>
</div>

</body>
</html>
<?php $conn->close(); ?>
