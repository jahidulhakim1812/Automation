<?php
// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $file       = $_FILES['certificate']['name'];
    $targetDir  = "certificates/";
    $targetFile = $targetDir . basename($file);

    // Create folder if not exists
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    if (move_uploaded_file($_FILES['certificate']['tmp_name'], $targetFile)) {
        $stmt = $conn->prepare("INSERT INTO certificates (student_id, certificate_file) VALUES (?, ?)");
        $stmt->bind_param("ss", $student_id, $file);
        if ($stmt->execute()) {
            $message = "✅ Certificate uploaded successfully!";
        } else {
            $message = "❌ Database error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "❌ File upload failed.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Upload Certificate</title>
<style>
  * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; }

    /* Navbar */
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


    /* Sidebar */
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

    /* Toggle Button */
    .toggle-arrow {
      position: fixed;
      top: 70px;
      left: 220px;
      background-color: #1abc9c;
      color: white;
      padding: 6px 10px;
      border-radius: 0 5px 5px 0;
      cursor: pointer;
      z-index: 1001;
      font-size: 18px;
      transition: left 0.3s ease;
    }
    .toggle-arrow.collapsed { left: 0; }
.container {
  max-width: 500px;
  margin: 120px auto 80px auto; /* pushes it below navbar and above footer */
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  position: relative;
  z-index: 1;
}

h2 { text-align: center; color: #333; }
input, button { width: 100%; padding: 10px; margin: 10px 0; }
button { background: #1abc9c; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #16a085; }
.message { text-align: center; margin-top: 15px; }
/* Footer */
    .footer {
      background-color: #333;
      color: white;
      text-align: center;
      padding: 15px;
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      font-weight: bold;
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

  <div class="toggle-arrow" id="toggleBtn">◀</div>
<div class="container">
    <h2>📄 Upload Student Certificate</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="student_id" placeholder="Enter Student ID" required>
        <input type="file" name="certificate" accept=".pdf,.jpg,.png" required>
        <button type="submit">Upload</button>
    </form>
    <div class="message"><?= $message ?></div>
</div>
<!-- Footer -->
<div class="footer">
  &copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved
</div>

<!-- Script -->
<script>
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('toggleBtn');
  const mainContent = document.getElementById('mainContent');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    toggleBtn.classList.toggle('collapsed');
    mainContent.classList.toggle('collapsed');
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
