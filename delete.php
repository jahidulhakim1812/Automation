<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, "", $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student = null;
$message = "";

// Search student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["search_id"])) {
    $search_id = $_POST["search_id"];
    $sql = "SELECT * FROM students WHERE student_id = '$search_id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        $message = "Student not found.";
    }
}

// Delete student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    $delete_id = $_POST["delete_id"];
    $sql = "DELETE FROM students WHERE student_id = '$delete_id'";
    if ($conn->query($sql) === TRUE) {
        $message = "✅ Student deleted successfully.";
        $student = null;
    } else {
        $message = "❌ Error deleting record: " . $conn->error;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delete Student</title>
<style>
body {
  font-family: Arial, sans-serif;
  margin: 0;
  background: #f4f6f8;
}

/* Navbar */
.navbar {
  background-color: #333;
  color: white;
  padding: 15px 20px;
  font-size: 22px;
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
  right: 50px;
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
  display: flex;
  flex-direction: column;
  box-shadow: 2px 0 5px rgba(0,0,0,0.2);
  overflow-y: auto;
  transition: transform 0.3s ease;
}
.side-nav.collapsed {
  transform: translateX(-100%);
}
.side-nav a, .menu-toggle {
  color: white;
  text-decoration: none;
  padding: 12px 20px;
  width: 100%;
  font-weight: bold;
  transition: background 0.3s ease;
  cursor: pointer;
}
.side-nav a:hover, .menu-toggle:hover {
  background-color: #34495e;
}
.submenu {
  display: none;
  flex-direction: column;
  background-color: #34495e;
}
.submenu a {
  padding: 10px 40px;
  font-weight: normal;
}
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

/* Main content */
.main-content {
  margin-left: 220px;
  padding: 100px 20px 80px;
  transition: margin-left 0.3s ease;
}
.main-content.collapsed {
  margin-left: 0;
}
.container {
  max-width: 800px;
  margin: auto;
  padding: 30px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.15);
  text-align: center;
}
.container h2 { margin-bottom: 20px; }
.container input, .container button {
  padding: 10px;
  margin: 8px;
  border-radius: 5px;
  border: 1px solid #ccc;
}
.container button {
  background: #1abc9c;
  color: white;
  border: none;
  cursor: pointer;
}
.container button:hover { background: #16a085; }
.container form button[style*="red"] { background: #e74c3c; }
.container form button[style*="red"]:hover { background: #c0392b; }

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

<!-- Main Content -->
<div class="main-content" id="mainContent">
  <div class="container">
    <h2>Find Student to Delete</h2>
    <form method="POST">
      <input type="number" name="search_id" placeholder="Enter Student ID" required />
      <button type="submit">Search</button>
    </form>

    <?php if ($student): ?>
      <hr />
      <p><strong>ID:</strong> <?= htmlspecialchars($student["student_id"]) ?></p>
      <p><strong>Name:</strong> <?= htmlspecialchars($student["name"]) ?></p>
      <p><strong>Course:</strong> <?= htmlspecialchars($student["course_category"]) ?></p>
      <form method="POST" onsubmit="return confirm('Are you sure you want to delete this student?');">
        <input type="hidden" name="delete_id" value="<?= $student["student_id"] ?>" />
        <button type="submit" style="background:red;">Delete Permanently</button>
      </form>
    <?php endif; ?>

    <?php if ($message): ?>
      <p><strong><?= $message ?></strong></p>
    <?php endif; ?>
  </div>
</div>

<!-- Footer -->
<div class="footer">
  &copy; <?= date("Y"); ?> Freelancing Students Management System | All Rights Reserved
</div>

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
