<?php
session_start();

// ✅ Admin session check
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

// ✅ Load PHPMailer (for email notification)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// ✅ Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ====================================================
// ⚙️ Function: sendUnblockEmail
// ====================================================
function sendUnblockEmail($to_email, $student_name) {
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
        $mail->Subject = 'Account Unblocked Notification';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif;'>
                <h3>Dear $student_name,</h3>
                <p>Your account has been <strong>unblocked</strong> by the administrator.</p>
                <p>You can now access all features again.</p>
                <br>
                <p>Regards,<br>AR Tech Solution</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false; // error: $mail->ErrorInfo
    }
}

// ====================================================
// ✅ Handle Unblock Action
// ====================================================
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["unblock_student_id"])) {
    $student_id = $_POST["unblock_student_id"];

    // Fetch student details for email
    $info = $conn->query("SELECT name, email FROM students WHERE student_id = '$student_id'");
    if ($info && $info->num_rows > 0) {
        $student = $info->fetch_assoc();
        $student_name = $student['name'];
        $student_email = $student['email'];

        // Update database: set is_blocked = 0
        $conn->query("UPDATE students SET is_blocked = 0, last_updated = NOW() WHERE student_id = '$student_id'");

        // Send email notification (if email exists)
        if (!empty($student_email)) {
            if (sendUnblockEmail($student_email, $student_name)) {
                $message = "<div class='status-msg success'>✅ Student unblocked and email sent successfully!</div>";
            } else {
                $message = "<div class='status-msg warning'>⚠️ Student unblocked but email could not be sent. Check SMTP settings.</div>";
            }
        } else {
            $message = "<div class='status-msg warning'>⚠️ Student unblocked, but no email address found.</div>";
        }
    } else {
        $message = "<div class='status-msg error'>❌ Student not found.</div>";
    }
}

// ====================================================
// ✅ Fetch only blocked students (is_blocked = 1)
// ====================================================
$search_category = '';
if (isset($_GET['category']) && !empty(trim($_GET['category']))) {
    $search_category = $conn->real_escape_string(trim($_GET['category']));
    $sql = "SELECT student_id, name, email, phone_number, course_status, course_category, is_blocked
            FROM students
            WHERE is_blocked = 1 AND course_category LIKE '%$search_category%'
            ORDER BY name ASC";
} else {
    $sql = "SELECT student_id, name, email, phone_number, course_status, course_category, is_blocked
            FROM students
            WHERE is_blocked = 1
            ORDER BY name ASC";
}

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blocked Students List</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f9; }

        /* Header/Navbar */
        .navbar {
            background-color: #1a1a1a;
            color: white;
            padding: 15px 30px;
            font-size: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        .logout-btn {
            position: absolute;
            right: 80px;
            background: linear-gradient(135deg, #ff4d4d, #cc0000);
            color: white;
            padding: 8px 15px;
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

        /* Side Navigation */
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
            overflow-y: auto;
            transition: transform 0.3s ease;
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
        .side-nav a:hover, .menu-toggle:hover { background-color: #34495e; border-left: 4px solid #1abc9c; }
        .menu-group { width: 100%; }
        .submenu { display: none; flex-direction: column; background-color: #34495e; }
        .submenu a { color: white; padding: 10px 40px; font-weight: normal; transition: background 0.3s ease; }
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

        /* Main Content */
        .container {
            margin-left: 240px;
            padding: 130px 30px 100px;
            transition: margin-left 0.3s ease;
        }
        .container.collapsed { margin-left: 30px; }

        h2 { text-align: center; margin-bottom: 20px; }

        /* Status Message */
        .status-msg { padding: 15px; margin: 20px 0; border-radius: 5px; font-weight: bold; }
        .status-msg.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-msg.warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-msg.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
        }
        th {
            background: #e74c3c; /* red theme for blocked page */
            color: #fff;
        }
        tr:nth-child(even) { background: #f9f9f9; }

        .search-form {
            text-align: center;
            margin: 20px 0;
        }
        .search-input {
            padding: 8px;
            width: 200px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .search-button {
            padding: 8px 12px;
            border: none;
            background: #3498db;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
        .search-button:hover { background: #2980b9; }

        .unblock-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .unblock-btn:hover { background: #219150; }

        /* Footer */
        .footer {
            background-color: #1a1a1a;
            color: white;
            text-align: center;
            padding: 15px;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            font-weight: bold;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<!-- Header/Navbar -->
<div class="navbar">
    <strong>AR TECH SOLUTION</strong>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<!-- Side Navigation (same as your other pages) -->
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
            <a href="completed_students.php">Completed Students</a>
            <a href="incomplete_students.php">Incomplete Students</a>
            <a href="ongoing_students.php">Ongoing Students</a>
            <a href="blocked_students_list.php" style="background:#c0392b;">🚫 Blocked Students</a> <!-- current page -->
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

<!-- Toggle Button -->
<div class="toggle-arrow" id="toggleBtn">◀</div>

<!-- Main Content -->
<div class="container" id="mainContent">
    <h2>🚫 Blocked Students List</h2>

    <?= $message ?? '' ?>

    <!-- Search by Category -->
    <div class="search-form">
        <form method="get" action="">
            <input type="text" name="category" class="search-input" placeholder="Filter by Course Category" value="<?= htmlspecialchars($search_category) ?>">
            <button type="submit" class="search-button">Search</button>
        </form>
    </div>

    <!-- Students Table -->
    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Course Category</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['student_id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone_number']) ?></td>
                <td><?= htmlspecialchars($row['course_category']) ?></td>
                <td><span style="color:red; font-weight:bold;">Blocked</span></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to unblock this student? An email notification will be sent.');">
                        <input type="hidden" name="unblock_student_id" value="<?= $row['student_id'] ?>">
                        <button type="submit" class="unblock-btn">🔓 Unblock</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="text-align:center; color:#555;">✅ No blocked students found.</p>
    <?php endif; ?>
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