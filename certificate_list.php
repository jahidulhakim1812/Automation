<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Search logic
$search = $_GET['search'] ?? '';
$sql = "SELECT c.id, c.student_id, s.name, c.certificate_file, c.uploaded_at 
        FROM certificates c 
        JOIN students s ON c.student_id = s.student_id";

if ($search != '') {
    $search = $conn->real_escape_string($search);
    $sql .= " WHERE c.student_id LIKE '%$search%' OR s.name LIKE '%$search%'";
}

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Certificate List</title>
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

/* Container (fixed with sidebar + footer spacing) */
.container {
  margin-top: 100px;  /* space below navbar */
  margin-left: 240px; /* space beside sidebar */
  margin-bottom: 80px; /* space above footer */
  max-width: calc(100% - 260px);
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
.container.collapsed {
  margin-left: 40px;
  max-width: calc(100% - 80px);
}

h2 { text-align: center; }
form { text-align: center; margin-bottom: 20px; }
input[type="text"] { padding: 8px; width: 250px; border: 1px solid #ccc; border-radius: 4px; }
button { padding: 8px 12px; border: none; background: #3498db; color: #fff; border-radius: 4px; cursor: pointer;}
button:hover { background: #2980b9; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
a { color: #3498db; text-decoration: none; }
a:hover { text-decoration: underline; }
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
<!-- Main Container -->
<div class="container" id="mainContent">
    <h2>📜 Uploaded Certificates</h2>

    <!-- Search Box -->
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search by Student ID or Name" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>Certificate</th>
            <th>Uploaded At</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['student_id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><a href="certificates/<?= htmlspecialchars($row['certificate_file']) ?>" target="_blank">View</a></td>
                <td><?= $row['uploaded_at'] ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No certificates found</td></tr>
        <?php endif; ?>
    </table>
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
<?php $conn->close(); ?>
