<?php
$conn = new mysqli("localhost","root","","freelancing");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$invoice = null;
$student = null;

if(isset($_POST['invoice_no'])){
    $invoice_no = $_POST['invoice_no'];

    // Fetch invoice
    $stmt = $conn->prepare("SELECT * FROM invoices WHERE invoice_no=?");
    $stmt->bind_param("s",$invoice_no);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){
        $invoice = $res->fetch_assoc();

        // Fetch student details
        $stmt2 = $conn->prepare("SELECT * FROM students WHERE student_id=?");
        $stmt2->bind_param("s",$invoice['student_id']);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $student = $result2->fetch_assoc();
        $stmt2->close();
    } else { echo "<script>alert('Invoice not found');</script>"; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Invoice</title>
<style>
 * { box-sizing: border-box; }
 body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; }

/* HEADER */
.navbar {
  background-color: #333;
  color: white;
  padding: 15px 20px;
  font-size: 24px;
  display: flex;
  justify-content: center;
  align-items: center;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 1000;
}
.logout-btn {
  position: absolute;
  right: 20px;
  background: linear-gradient(135deg, #ff4d4d, #cc0000);
  color: white;
  padding: 8px 20px;
  text-decoration: none;
  border-radius: 25px;
  font-size: 15px;
  box-shadow: 0 3px 6px rgba(0,0,0,0.2);
  transition: all 0.3s ease;
}
.logout-btn:hover {
  background: linear-gradient(135deg, #ff6666, #e60000);
  transform: scale(1.05);
}

/* SIDE NAVBAR */
.side-nav {
  position: fixed;
  top: 60px;
  left: 0;
  width: 220px;
  height: calc(100% - 60px);
  background-color: #2c3e50;
  padding-top: 20px;
  z-index: 999;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  box-shadow: 2px 0 5px rgba(0,0,0,0.2);
  transition: transform 0.3s ease;
  overflow-y: auto;
}
.side-nav.collapsed { transform: translateX(-220px); }
.side-nav a, .menu-toggle {
  color: white;
  text-decoration: none;
  padding: 12px 25px;
  width: 100%;
  font-weight: bold;
  transition: background 0.3s ease;
  border-left: 4px solid transparent;
  cursor: pointer;
}
.side-nav a:hover, .menu-toggle:hover {
  background-color: #34495e;
  border-left: 4px solid #1abc9c;
}
.menu-group { width: 100%; }
.submenu {
  display: none;
  flex-direction: column;
  background-color: #34495e;
}
.submenu a {
  color: white;
  padding: 10px 40px;
  text-decoration: none;
  font-weight: normal;
  transition: background 0.3s ease;
}
.submenu a:hover { background-color: #3d566e; }
.menu-group.active .submenu { display: flex; }

/* TOGGLE BUTTON */
.toggle-arrow {
  position: fixed;
  top: 70px;
  left: 220px;
  background-color: #1abc9c;
  color: white;
  padding: 6px 12px;
  border-radius: 0 5px 5px 0;
  cursor: pointer;
  font-size: 18px;
  transition: left 0.3s ease;
  z-index: 1001;
}
.toggle-arrow.collapsed { left: 0; }

/* FOOTER */
.footer {
  background-color: #333;
  color: white;
  text-align: center;
  padding: 15px;
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100vw;
  font-weight: bold;
}

/* CONTAINER */
.container { width:800px; margin:100px auto 50px auto; background:white; padding:30px; box-shadow:0 0 15px rgba(0,0,0,0.2);}
input[type="text"] {padding:8px; width:200px; border-radius:5px; border:1px solid #ccc;}
button {padding:8px 15px; border-radius:5px; border:none; background:#1abc9c; color:white; cursor:pointer;}
button:hover{background:#16a085;}
.invoice-box {padding:20px; border:1px solid #ddd; margin-top:20px;}
.invoice-box table {width:100%; border-collapse:collapse; margin-top:20px;}
.invoice-box th, .invoice-box td {border:1px solid #ddd; padding:10px;}
.invoice-box th {background:#1abc9c; color:white;}
.print-btn {margin-top:20px; padding:10px 20px; background:#3498db; color:white; border:none; cursor:pointer; border-radius:5px;}
.print-btn:hover{background:#2980b9;}

@media print{
  .navbar, .side-nav, .toggle-arrow, .footer, button { display:none !important; }
  .invoice-box { margin:0; border:none; }
}
</style>
</head>
<body>
<div class="navbar">
    <strong>AR TECH SOLUTION</strong>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="side-nav" id="sidebar">
  <a href="dashboard.php">📊 Dashboard</a>
  <div class="menu-group">
    <div class="menu-toggle">💵 Account ▾</div>
    <div class="submenu">
      <a href="account.php">Account Overview</a>
      <a href="account_report.php">Account Report</a>
      <a href="change_password.php">Change Password</a>
    </div>
  </div>
  <div class="menu-group">
    <div class="menu-toggle">👤 Student Information ▾</div>
    <div class="submenu">
      <a href="insert.php">Add Student</a>
      <a href="student_list.php">Total Student List</a>
      <a href="form_view.php">Student Form</a>
    </div>
  </div>
  <a href="delete.php">🗑️ Delete</a>
  <a href="report.php">📄 Report</a>
  <div class="menu-group">
    <div class="menu-toggle">💵 Payment ▾</div>
    <div class="submenu">
      <a href="invoice.php">Print Invoice</a>
      <a href="view_invoice.php">Verify Invoice</a>
      <a href="input_payment.php">Add Payment</a>
    </div>
  </div>
  <div class="menu-group">
    <div class="menu-toggle">📆 Attendance ▾</div>
    <div class="submenu">
      <a href="attendance.php">Take Attendance</a>
      <a href="attendance_report.php">View Attendance Report</a>
    </div>
  </div>
  <div class="menu-group">
    <div class="menu-toggle">📜 Certificate ▾</div>
    <div class="submenu">
      <a href="upload_certificate.php">Upload Certificate</a>
      <a href="certificate_list.php">View Certificate</a>
    </div>
  </div>
  <a href="routine_generator.php">🕒 Routine</a>
</div>

<div class="toggle-arrow" id="toggleBtn">◀</div>

<div class="container">
<h1>View Invoice</h1>
<form method="POST">
  <input type="text" name="invoice_no" placeholder="Enter Invoice Number" required>
  <button type="submit">Search Invoice</button>
</form>

<?php if($invoice && $student): ?>
<div class="invoice-box">
  <h2>Invoice: <?php echo $invoice['invoice_no'];?></h2>
  <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student['name']);?></p>
  <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']);?></p>
  <p><strong>Course Name:</strong> <?php echo htmlspecialchars($student['course_category']);?></p>
  <table>
    <tr><th>Description</th><th>Amount (৳)</th></tr>
    <tr><td>Total Course Fee</td><td><?php echo number_format($student['course_fee'],2);?></td></tr>
    <tr><td>Paid Fee</td><td><?php echo number_format($student['paid_fee'],2);?></td></tr>
    <tr><td>Due Fee</td><td><?php echo number_format($student['course_fee']-$student['paid_fee'],2);?></td></tr>
  </table>
  <p class="total">Last Updated: <?php echo $student['last_updated'];?></p>
  <button class="print-btn" onclick="window.print()">🖨️ Print Invoice</button>
</div>
<?php endif; ?>
</div>

<div class="footer">© 2025 AR TECH SOLUTION</div>

<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggleBtn');

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('collapsed');
  toggleBtn.classList.toggle('collapsed');
  toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
});

document.querySelectorAll('.menu-toggle').forEach(toggle => {
  toggle.addEventListener('click', () => {
    toggle.parentElement.classList.toggle('active');
  });
});
</script>
</body>
</html>
