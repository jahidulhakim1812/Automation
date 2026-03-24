<?php
session_start();

// Optional: restrict access to logged-in users only
if (!isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student ID from URL, or use a default for testing
$student_id = isset($_GET['student_id']) ? $conn->real_escape_string($_GET['student_id']) : '250803';

// Fetch student details
$student_query = "SELECT name, course_category FROM students WHERE student_id = '$student_id'";
$student_result = $conn->query($student_query);
if ($student_result->num_rows == 0) {
    die("Student not found.");
}
$student = $student_result->fetch_assoc();

// Fetch routine for this student
$routine_query = "SELECT day, time_slot FROM routines WHERE student_id = '$student_id' ORDER BY FIELD(day, 
                  'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')";
$routine_result = $conn->query($routine_query);

// If no routine found, use default data (optional fallback)
$days_order = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$default_times = [
    'Saturday'   => 'Close',
    'Sunday'     => 'Close',
    'Monday'     => 'Close',
    'Tuesday'    => 'Close',
    'Wednesday'  => 'Close',
    'Thursday'   => '2.30 pm to 4.30 pm',
    'Friday'     => '3.30 pm to 5.30 pm'
];

$routine_data = [];
if ($routine_result->num_rows > 0) {
    while ($row = $routine_result->fetch_assoc()) {
        $routine_data[$row['day']] = $row['time_slot'];
    }
} else {
    // fallback to default image data
    $routine_data = $default_times;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Class Routine</title>
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

        /* Side Navigation (copy from your other pages) */
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

        /* Routine Card – exactly as in the image */
        .routine-card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid #ddd;
        }
        .student-info {
            text-align: left;
            margin-bottom: 25px;
            font-size: 16px;
            line-height: 1.6;
        }
        .student-info p {
            margin: 5px 0;
        }
        .student-info strong {
            display: inline-block;
            width: 60px;
        }
        .routine-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 16px;
        }
        .routine-table th, .routine-table td {
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
        }
        .routine-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .routine-table td {
            background-color: #fff;
        }
        .note {
            margin-top: 20px;
            font-style: italic;
            color: #555;
            border-top: 1px dashed #aaa;
            padding-top: 15px;
        }
        .instructor {
            margin-top: 20px;
            font-weight: bold;
            text-align: right;
        }
        .instructor span {
            display: block;
            font-weight: normal;
            margin-top: 5px;
        }

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

        @media print {
            .navbar, .side-nav, .toggle-arrow, .footer, .logout-btn { display: none; }
            .container { margin-left: 0; padding: 20px; }
            .routine-card { box-shadow: none; border: 1px solid #000; }
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="navbar">
    <strong>AR TECH SOLUTION</strong>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<!-- Sidebar (same as your other pages) -->
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
            <a href="blocked_students_list.php">Blocked Students</a>
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
    <div class="routine-card">
        <div class="student-info">
            <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
            <p><strong>ID :</strong> <?php echo htmlspecialchars($student_id); ?></p>
            <p><strong>Course :</strong> <?php echo htmlspecialchars($student['course_category']); ?></p>
        </div>

        <h3 style="text-align:center; margin-bottom:10px;">Class Routine</h3>

        <table class="routine-table">
            <tr>
                <th>Day</th>
                <th>Time</th>
            </tr>
            <?php
            $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            foreach ($days as $day):
                $time = isset($routine_data[$day]) ? $routine_data[$day] : 'Close';
            ?>
            <tr>
                <td><?php echo $day; ?></td>
                <td><?php echo htmlspecialchars($time); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="note">
            This Routine can be changed if any emergency occurs. Students must have to follow this routine. If course instructor do any changes in the routine he will informed you.
        </div>

        <div class="instructor">
            Md. Jahidul Hakim<br>
            <span>Instructor<br>AR Tech Solution</span>
        </div>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    &copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved
</div>

<script>
    // Sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
    const mainContent = document.getElementById('mainContent');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        toggleBtn.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
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