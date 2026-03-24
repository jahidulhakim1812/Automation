<?php
session_start();

// Admin session check (optional, adjust to your login system)
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

// Include PHPMailer manually (same as before)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";          // your MySQL password
$dbname = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ----------------------------------------------------------------------
// FUNCTION: Send block notification email
// ----------------------------------------------------------------------
function sendBlockEmail($to_email, $student_name) {
    // 🔴 USE YOUR GMAIL DETAILS HERE 🔴
    $my_email    = 'artechsolution.online@gmail.com';
    $app_password = 'giwr wrcr mnyi lkpf'; // Your 16‑digit Google App Password

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $my_email;
        $mail->Password   = $app_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($my_email, 'AR Tech Admin');
        $mail->addAddress($to_email, $student_name);

        $mail->isHTML(true);
        $mail->Subject = 'Account Blocked Notification';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif;'>
                <h3>Dear $student_name,</h3>
                <p>Your account has been <strong>blocked</strong> by the administrator.</p>
                <p>If you believe this is an error, please contact the administration.</p>
                <br>
                <p>Regards,<br>AR Tech Solution</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false; // error: $mail->ErrorInfo
    }
}

// ----------------------------------------------------------------------
// Handle Actions
// ----------------------------------------------------------------------
$message = ''; // for feedback (optional)

// 1. Update Paid Fee
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["student_id"]) && isset($_POST["new_paid_fee"])) {
    $student_id = $_POST["student_id"];
    $new_paid_fee = $_POST["new_paid_fee"];
    $update = "UPDATE students SET paid_fee = '$new_paid_fee', last_updated = NOW() WHERE student_id = '$student_id'";
    $conn->query($update);
    echo "<script>alert('Fee updated successfully!'); window.location.href='report.php';</script>";
}

// 2. Delete Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_student_id"])) {
    $delete_id = $_POST["delete_student_id"];
    $delete = "DELETE FROM students WHERE student_id = '$delete_id'";
    $conn->query($delete);
    echo "<script>alert('Student deleted successfully!'); window.location.href='report.php';</script>";
}

// 3. Update Course Status (using dropdown)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["set_course_status"])) {
    $student_id = $_POST["student_id"];
    $new_status = $_POST["set_course_status"];
    $conn->query("UPDATE students SET course_status='$new_status', last_updated=NOW() WHERE student_id='$student_id'");
    echo "<script>alert('Course status updated to $new_status!'); window.location.href='report.php';</script>";
}

// 4. BLOCK STUDENT (new action)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["block_student_id"])) {
    $student_id = $_POST["block_student_id"];

    // Fetch student email and name for the email
    $info = $conn->query("SELECT name, email FROM students WHERE student_id = '$student_id'");
    if ($info && $info->num_rows > 0) {
        $student = $info->fetch_assoc();
        $student_name = $student['name'];
        $student_email = $student['email'];

        // Update the student as blocked
        $conn->query("UPDATE students SET is_blocked = 1, last_updated = NOW() WHERE student_id = '$student_id'");

        // Send email notification
        if (!empty($student_email)) {
            if (sendBlockEmail($student_email, $student_name)) {
                echo "<script>alert('Student blocked and email sent successfully!');</script>";
            } else {
                echo "<script>alert('Student blocked but email could not be sent. Check SMTP settings.');</script>";
            }
        } else {
            echo "<script>alert('Student blocked, but no email address found.');</script>";
        }
    } else {
        echo "<script>alert('Student not found.');</script>";
    }
    echo "<script>window.location.href='report.php';</script>";
}

// ----------------------------------------------------------------------
// Fetch Records (now including email and is_blocked)
// ----------------------------------------------------------------------
$sql = "SELECT student_id, name, email, course_fee, paid_fee, 
               (course_fee - paid_fee) AS due_fee, last_updated, course_status, is_blocked
        FROM students 
        ORDER BY last_updated DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Student Account Report</title>
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
#toggleBtn {
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
#toggleBtn.collapsed { left: 0; }

/* MAIN CONTENT */
.container {
  margin-left: 240px;
  padding: 80px 20px 100px;
  text-align: center;
  transition: margin-left 0.3s ease;
}
.container.collapsed { margin-left: 20px; }

table {
  width: 100%;
  border-collapse: collapse;
  background-color: white;
  margin-top: 20px;
}
th, td {
  border: 1px solid #ddd;
  padding: 10px;
  text-align: center;
}
th {
  background-color: #333;
  color: white;
}
input[type="number"] { width: 90px; padding: 5px; }
button { padding: 5px 10px; cursor: pointer; }

/* Block button style */
.block-btn { background: #ff9800; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
.block-btn:disabled { background: #ccc; cursor: not-allowed; }

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
  .header, .sidebar, .footer, #toggleBtn, button, select { display: none !important; }
  body { background: white; }
  table { border: 1px solid #000; }
  .container { margin-left: 0; padding: 0; }
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

<div id="toggleBtn">◀</div>

<div class="container" id="mainContent">
  <h2>Individual Account Details</h2>
  <table>
    <tr>
      <th>Student ID</th>
      <th>Name</th>
      <th>Email</th>
      <th>Total Fee</th>
      <th>Paid Fee</th>
      <th>Due Fee</th>
      <th>Payment Date</th>
      <th>Course Status</th>
      <th>Block Status</th>
      <th>Update Fee</th>
      <th>Delete</th>
      <th>Edit</th>
      <th>Print</th>
    </tr>
    <?php
    if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $sid = htmlspecialchars($row["student_id"]);
        $email = htmlspecialchars($row["email"]);
        $is_blocked = $row["is_blocked"];
        echo "<tr>";
        echo "<td>$sid</td>";
        echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
        echo "<td>" . $email . "</td>";
        echo "<td>৳ " . number_format($row["course_fee"], 2) . "</td>";
        echo "<td>৳ " . number_format($row["paid_fee"], 2) . "</td>";
        echo "<td>৳ " . number_format($row["due_fee"], 2) . "</td>";
        echo "<td>" . htmlspecialchars($row["last_updated"]) . "</td>";

        // Dropdown for Course Status
        echo "<td>
          <form method='POST'>
            <input type='hidden' name='student_id' value='$sid'>
            <select name='set_course_status' onchange='this.form.submit()'>
              <option value='ongoing'" . ($row["course_status"] == "ongoing" ? " selected" : "") . ">Ongoing</option>
              <option value='finished'" . ($row["course_status"] == "finished" ? " selected" : "") . ">Finished</option>
              <option value='incomplete'" . ($row["course_status"] == "incomplete" ? " selected" : "") . ">Incomplete</option>
            </select>
          </form>
        </td>";

        // Block Status and Button
        echo "<td>";
        if ($is_blocked == 1) {
            echo "<span style='color:red; font-weight:bold;'>Blocked</span>";
        } else {
            echo "<form method='POST' onsubmit=\"return confirm('Are you sure you want to block this student? An email notification will be sent.');\">
                    <input type='hidden' name='block_student_id' value='$sid'>
                    <button type='submit' class='block-btn'>Block</button>
                  </form>";
        }
        echo "</td>";

        // Update Fee
        echo "<td>
          <form method='POST'>
            <input type='hidden' name='student_id' value='$sid'>
            <input type='number' name='new_paid_fee' required>
            <button type='submit'>Update</button>
          </form>
        </td>";

        // Delete
        echo "<td>
          <form method='POST' onsubmit=\"return confirm('Are you sure you want to delete this record?');\">
            <input type='hidden' name='delete_student_id' value='$sid'>
            <button type='submit' style='background:red;color:white;'>Delete</button>
          </form>
        </td>";

        // Edit
        echo "<td>
          <form method='GET' action='edit.php'>
            <input type='hidden' name='student_id' value='$sid'>
            <button type='submit' style='background:#008CBA;color:white;'>Edit</button>
          </form>
        </td>";

        // Print
        echo "<td>
          <form method='GET' action='print.php' target='_blank'>
            <input type='hidden' name='student_id' value='$sid'>
            <button type='submit' style='background:green;color:white;'>Print</button>
          </form>
        </td>";

        echo "</tr>";
      }
    }
    ?>
  </table>
</div>

<div class="footer">&copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved</div>

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