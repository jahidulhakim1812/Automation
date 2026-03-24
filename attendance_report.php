<?php
// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing"; // Your DB name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Initialize variables
$selectedId = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$selectedStudent = null;
$total = $present = $absent = $late = 0;
$percentage = 0;
$remarksData = [];

if ($selectedId) {
    // Fetch student info
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $selectedId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $selectedStudent = $res->fetch_assoc();

        // Fetch attendance records with remarks
        $attStmt = $conn->prepare("SELECT status, remarks, COUNT(*) as count 
                                   FROM attendance_records 
                                   WHERE student_id = ? 
                                   GROUP BY status, remarks");
        $attStmt->bind_param("s", $selectedId);
        $attStmt->execute();
        $attRes = $attStmt->get_result();
        while ($row = $attRes->fetch_assoc()) {
            if ($row['status'] === 'Present') $present += $row['count'];
            if ($row['status'] === 'Absent') $absent += $row['count'];
            if ($row['status'] === 'Late') $late += $row['count'];

            // Store remarks info
            $remarksData[] = [
                'status'  => $row['status'],
                'remarks' => $row['remarks'],
                'count'   => $row['count']
            ];
        }
        $attStmt->close();

        // Total & percentage
        $total = $present + $absent + $late;
        $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Report</title>
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
.toggle-btn {
  position: fixed;
  top: 70px;
  left: 220px;
  background-color: #1abc9c;
  color: white;
  padding: 6px 10px;
  border-radius: 0 5px 5px 0;
  cursor: pointer;
  font-size: 18px;
  transition: left 0.3s ease;
  z-index: 1001;
}
.toggle-btn.collapsed { left: 0; }

/* MAIN CONTENT */
.main-content {
  margin-left: 220px;
  padding: 20px;
  transition: margin-left 0.3s ease;
}
.main-content.collapsed { margin-left: 0; }

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

.container { max-width: 900px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
h2 { text-align: center; color: #333;}
input[type=text], button { padding: 8px; margin: 10px 0; font-size: 14px;}
button { cursor: pointer; border-radius: 5px; border: none; background: #1abc9c; color: #fff;}
button:hover { background: #16a085;}
.report { margin-top: 20px; padding: 20px; border: 1px solid #ddd; border-radius: 6px; background: #fafafa;}
.report h3 { margin-bottom: 15px;}
table { width: 100%; border-collapse: collapse; margin-top: 15px;}
table, th, td { border: 1px solid #ccc; padding: 8px; text-align: center;}
.print-btn { background: #3498db; margin-top: 15px;}
.print-btn:hover { background: #2980b9;}
@media print {
    .navbar, .side-nav, .toggle-btn, .footer, button { display: none !important; }
    body { background: white; }
    .report { margin: 0; box-shadow: none; border: none; }
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

  <!-- Main content -->
  <div class="main-content" id="mainContent">
    <div class="container">
      <h2>📊 Attendance Report</h2>
      <form method="get">
        <label for="student_id">Enter Student ID:</label>
        <input type="text" name="student_id" id="student_id" placeholder="Student ID" required
               value="<?= htmlspecialchars($selectedId ?? '') ?>">
        <button type="submit">Search</button>
      </form>

      <?php if ($selectedStudent): ?>
      <div class="report" id="report">
          <h3>Attendance Report for <?= htmlspecialchars($selectedStudent["name"]) ?> (ID: <?= htmlspecialchars($selectedStudent["student_id"]) ?>)</h3>
          <table>
              <tr>
                  <th>Total Classes</th>
                  <th>Present</th>
                  <th>Absent</th>
                  <th>Late</th>
                  <th>Attendance %</th>
              </tr>
              <tr>
                  <td><?= $total ?></td>
                  <td><?= $present ?></td>
                  <td><?= $absent ?></td>
                  <td><?= $late ?></td>
                  <td><?= $percentage ?>%</td>
              </tr>
          </table>

          <h3>📌 Remarks Details</h3>
          <table>
              <tr>
                  <th>Status</th>
                  <th>Remarks</th>
                  <th>Count</th>
              </tr>
              <?php if (!empty($remarksData)): ?>
                  <?php foreach ($remarksData as $remark): ?>
                      <tr>
                          <td><?= htmlspecialchars($remark['status']) ?></td>
                          <td><?= htmlspecialchars($remark['remarks'] ?? 'N/A') ?></td>
                          <td><?= $remark['count'] ?></td>
                      </tr>
                  <?php endforeach; ?>
              <?php else: ?>
                  <tr><td colspan="3">No remarks available</td></tr>
              <?php endif; ?>
          </table>
      </div>
      <button class="print-btn" onclick="window.print()">🖨 Print Report</button>
      <?php elseif ($selectedId): ?>
      <p style="color:red; margin-top:20px;">Student not found for ID: <?= htmlspecialchars($selectedId) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="footer">
    &copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved
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
