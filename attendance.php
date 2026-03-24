<?php
$conn = new mysqli("localhost", "root", "", "freelancing");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

// ✅ Submit attendance
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_attendance"])) {
  $attendance_date = $_POST["attendance_date"];
  foreach ($_POST["attendance"] as $student_id => $data) {
    $status  = $data["status"] ?? '';
    $remarks = $conn->real_escape_string($data["remarks"] ?? '');
    $sql = "INSERT INTO attendance_records (student_id, attendance_date, status, remarks)
            VALUES ('$student_id', '$attendance_date', '$status', '$remarks')";
    $conn->query($sql);
  }
  $message = "✅ Attendance submitted successfully!";
}

// ✅ Filters
$search = isset($_GET["search"]) ? $conn->real_escape_string($_GET["search"]) : "";
$course_filter = isset($_GET["course"]) ? $conn->real_escape_string($_GET["course"]) : "";

// ✅ Fetch courses
$courses = $conn->query("SELECT DISTINCT course_category FROM students ORDER BY course_category ASC");

// ✅ Fetch filtered students
$sql = "SELECT student_id, name, course_category FROM students WHERE 1";
if ($search) {
  $sql .= " AND (student_id LIKE '%$search%' OR name LIKE '%$search%')";
}
if ($course_filter) {
  $sql .= " AND course_category = '$course_filter'";
}
$sql .= " ORDER BY name ASC";
$students = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance Portal</title>
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
      transition: margin-left 0.3s ease;
    }
    .main-content.collapsed {
      margin-left: 0; /* Content moves center when sidebar hidden */
    }

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

    .container {
      max-width: 1000px;
      margin: 120px auto 80px;
      background: #fff;
      padding: 25px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border-radius: 8px;
    }
    input[type="text"],
    input[type="date"],
    select,
    textarea {
      padding: 10px;
      margin: 5px 0;
      width: 100%;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 15px;
    }
    button {
      padding: 10px 20px;
      background: #34495e;
      color: white;
      border: none;
      cursor: pointer;
      border-radius: 5px;
      font-size: 16px;
      transition: background 0.3s ease;
    }
    button:hover {
      background: #2c3e50;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 12px;
      text-align: center;
      font-size: 14px;
    }
    th {
      background: #34495e;
      color: white;
    }
    tr:hover { background-color: #f9f9f9; }
    .message {
      color: green;
      font-weight: bold;
      margin-bottom: 10px;
      text-align: center;
      font-size: 16px;
    }
    .filter-box {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 20px;
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
<div id="mainContent" class="main-content">
  <div class="container">
    <h2>📝 Mark Attendance</h2>

    <?php if($message): ?>
      <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <!-- 🔍 Search & Filter -->
    <form method="GET" class="filter-box">
      <input type="text" name="search" placeholder="Search by ID or Name" value="<?= htmlspecialchars($search) ?>">
      <select name="course">
        <option value="">-- Filter by Course --</option>
        <?php while($row = $courses->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($row['course_category']) ?>" <?= $course_filter == $row['course_category'] ? "selected" : "" ?>>
            <?= htmlspecialchars($row['course_category']) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <button type="submit">🔍 Search</button>
    </form>

    <!-- ✅ Attendance Form -->
    <form method="POST">
      <label><b>Date:</b></label>
      <input type="date" name="attendance_date" value="<?= date('Y-m-d') ?>" required>
      <table>
        <tr>
          <th>Student ID</th>
          <th>Name</th>
          <th>Course</th>
          <th>Status</th>
          <th>Remarks</th>
        </tr>
        <?php if($students && $students->num_rows): ?>
          <?php while($row = $students->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row["student_id"]) ?></td>
              <td><?= htmlspecialchars($row["name"]) ?></td>
              <td><?= htmlspecialchars($row["course_category"]) ?></td>
              <td>
                <select name="attendance[<?= htmlspecialchars($row["student_id"]) ?>][status]">
                  <option value="Present">Present</option>
                  <option value="Absent">Absent</option>
                  <option value="Late">Late</option>
                </select>
              </td>
              <td><input type="text" name="attendance[<?= htmlspecialchars($row["student_id"]) ?>][remarks]" placeholder="Remarks"></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5">❌ No students found.</td></tr>
        <?php endif; ?>
      </table>
      <br>
      <button type="submit" name="submit_attendance">✅ Submit Attendance</button>
    </form>
  </div>
</div>

<!-- Footer -->
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
