<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, "", $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$student = null;
$message = "";

// Search by Student ID
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_id'])) {
    $student_id = $_POST['search_id'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        $student = $result->fetch_assoc();
    } else {
        $message = "Student ID not found!";
    }
}

// Update Payment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['payment_amount'])) {
    $student_id = $_POST['student_id'];
    $payment = floatval($_POST['payment_amount']);

    // Fetch current paid fee
    $stmt = $conn->prepare("SELECT paid_fee, course_fee FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $new_paid = $row['paid_fee'] + $payment;

    if($new_paid > $row['course_fee']){
        $message = "Payment exceeds total course fee!";
    } else {
        $stmt2 = $conn->prepare("UPDATE students SET paid_fee = ?, last_updated = NOW() WHERE student_id = ?");
        $stmt2->bind_param("ds", $new_paid, $student_id);
        $stmt2->execute();
        $stmt2->close();
        $message = "Payment updated successfully!";
        
        // Refresh student info
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Payment</title>
<style>
* { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; }

/* HEADER */
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
.toggle-btn {
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
.toggle-btn.collapsed { left: 0; }
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

@media print {
    .header, .sidebar, .footer, .toggle-btn, button { display: none !important; }
    body { background: white; }
    table { border: 1px solid #000; }
    .container { margin-left: 0; padding: 0; }
}
.container { width:600px; margin:50px auto; background:white; padding:30px; box-shadow:0 0 15px rgba(0,0,0,0.2);}
input[type=text], input[type=number] { padding:8px; width:200px; margin-right:10px; border-radius:5px; border:1px solid #ccc; }
button { padding:8px 15px; border-radius:5px; border:none; background:#1abc9c; color:white; cursor:pointer; }
button:hover { background:#16a085; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ddd; padding:10px; text-align:left; }
th { background:#1abc9c; color:white; }
.message { margin-top:15px; color:green; font-weight:bold; }
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
      <div class="submenu horizontal-submenu">
        <a href="insert.php">Add Student</a>
        <a href="student_list.php">Total Student List</a>
        <a href="form_view.php">Student Form</a>
      </div>
    </div>

    <a href="delete.php">🗑️ Delete</a>
    <a href="report.php">📄 Report</a>

    <div class="menu-group">
      <div class="menu-toggle">💵 Payment ▾</div>
      <div class="submenu horizontal-submenu">
        <a href="invoice.php">Print Invoice</a>
        <a href="view_invoice.php">Verify Invoice</a>
        <a href="input_payment.php">Add Payment</a>
      </div>
    </div>


    <div class="menu-group">
      <div class="menu-toggle">📆 Attendance▾</div>
      <div class="submenu">
        <a href="attendance.php">Take Attendance</a>
        <a href="attendance_report.php">View attendence Report</a>
        
      </div>
    </div>

     <div class="menu-group">
      <div class="menu-toggle">📜Certificate▾</div>
      <div class="submenu">
        <a href="upload_certificate.php">Upload Certificate</a>
        <a href="certificate_list.php">View Certificate</a>
        
      </div>
    </div>
    <a href="routine_generator.php">🕒 Routine</a>
  </div>

<div class="toggle-btn" id="toggleBtn">◀</div>
<div class="container">
<h1>Add Payment</h1>

<!-- Search Form -->
<form method="POST">
<input type="text" name="search_id" placeholder="Enter Student ID" required>
<button type="submit">Search</button>
</form>

<?php if($message) echo "<div class='message'>$message</div>"; ?>

<?php if($student): ?>
<!-- Student Details -->
<table>
<tr><th>Student Name</th><td><?php echo htmlspecialchars($student['name']); ?></td></tr>
<tr><th>Course Fee</th><td>৳ <?php echo number_format($student['course_fee'],2); ?></td></tr>
<tr><th>Paid Fee</th><td>৳ <?php echo number_format($student['paid_fee'],2); ?></td></tr>
<tr><th>Due Fee</th><td>৳ <?php echo number_format($student['course_fee']-$student['paid_fee'],2); ?></td></tr>
</table>

<!-- Payment Form -->
<form method="POST" style="margin-top:20px;">
<input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
<input type="number" name="payment_amount" placeholder="Enter Payment Amount" min="1" step="0.01" required>
<button type="submit">Add Payment</button>
</form>
<?php endif; ?>
</div>
<script>

const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggleBtn');

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('collapsed');
  toggleBtn.classList.toggle('collapsed');
  toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
});

// Dropdown menus
document.querySelectorAll('.menu-toggle').forEach(toggle => {
  toggle.addEventListener('click', () => {
    toggle.parentElement.classList.toggle('active');
  });
});

</script>
</body>
</html>
