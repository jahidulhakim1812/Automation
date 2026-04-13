<?php
session_start();
$conn = new mysqli("localhost", "root", "", "freelancing");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';
$error_message = '';
$student = null;
$routine_data = [];

if (!empty($student_id)) {
    $stmt = $conn->prepare("SELECT student_id, name, course_category FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $error_message = "Student ID not found. Please check and try again.";
    } else {
        $student = $result->fetch_assoc();
        $stmt2 = $conn->prepare("SELECT day, start_time, end_time FROM student_routine WHERE student_id = ?");
        $stmt2->bind_param("s", $student_id);
        $stmt2->execute();
        $routine_result = $stmt2->get_result();
        $days_order = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($days_order as $day) { $routine_data[$day] = 'Close'; }
        while ($row = $routine_result->fetch_assoc()) {
            $start = date("h:i A", strtotime($row['start_time']));
            $end = date("h:i A", strtotime($row['end_time']));
            $routine_data[$row['day']] = "$start - $end";
        }
        $stmt2->close();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routine Report - AR TECH SOLUTION</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }

        /* ========== NAVBAR (no logo, only brand center and logout) ========== */
        .navbar {
            background: linear-gradient(135deg, #1e2a3a, #0f1722);
            color: white;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 0 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .brand {
            font-size: 22px;
            font-weight: bold;
        }
        .logout-btn {
            position: absolute;
            right: 20px;
            background: #e74c3c;
            color: white;
            padding: 6px 18px;
            border-radius: 30px;
            text-decoration: none;
            transition: 0.2s;
        }
        .logout-btn:hover { background: #c0392b; transform: scale(1.02); }
        @media (max-width: 700px) {
            .brand { font-size: 16px; }
        }

        /* ========== SIDEBAR (identical to dashboard) ========== */
        .side-nav {
            position: fixed;
            top: 60px;
            left: 0;
            width: 220px;
            height: calc(100% - 60px);
            background-color: #2c3e50;
            padding-top: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        .side-nav.collapsed {
            transform: translateX(-100%);
        }
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

        /* Toggle button */
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

        /* ========== MAIN CONTAINER ========== */
        .container {
            margin-left: 240px;
            padding: 80px 30px 60px;
            transition: margin-left 0.3s ease;
        }
        .container.collapsed { margin-left: 20px; }

        /* Report card */
        .report-card {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 25px 30px;
        }

        /* ===== REPORT HEADER with logo on left ===== */
        .report-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1abc9c;
            flex-wrap: wrap;
        }
        .header-logo img {
            height: 70px;
            width: auto;
            max-width: 120px;
            object-fit: contain;
        }
        .header-text {
            flex: 1;
            text-align: center;
        }
        .institute-name {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        .address, .contact {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
        }
        @media (max-width: 600px) {
            .report-header {
                flex-direction: column;
                text-align: center;
            }
            .header-text {
                text-align: center;
            }
        }

        /* Search box, student info, table */
        .search-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .search-form {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .search-form input {
            padding: 10px 15px;
            width: 250px;
            border-radius: 30px;
            border: 1px solid #ccc;
        }
        .search-form button {
            background: #1abc9c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .student-info {
            background: #f1f3f5;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .student-info p { margin: 5px 0; }
        .student-info strong { display: inline-block; width: 100px; }
        .routine-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .routine-table th, .routine-table td {
            border: 1px solid #212529;
            padding: 10px;
            text-align: left;
        }
        .routine-table th {
            background: #2c3e50;
            color: white;
        }
        .routine-table tr:nth-child(even) { background: #f8f9fa; }
        .note {
            margin-top: 20px;
            font-style: italic;
            font-size: 13px;
            border-top: 1px dashed #adb5bd;
            padding-top: 15px;
        }
        .signature {
            margin-top: 30px;
            text-align: right;
            font-weight: bold;
        }
        .signature span {
            display: block;
            font-weight: normal;
            margin-top: 5px;
        }
        .print-btn {
            text-align: center;
            margin-top: 25px;
        }
        .print-btn button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            cursor: pointer;
        }
        .footer {
            background: #1a1a1a;
            color: white;
            text-align: center;
            padding: 12px;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            font-size: 14px;
            z-index: 999;
        }

        /* Print styles */
        @media print {
            .navbar, .side-nav, .toggle-arrow, .footer, .search-box, .print-btn {
                display: none !important;
            }
            .container {
                margin: 0 !important;
                padding: 0 !important;
            }
            .report-card {
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
            }
            .report-header {
                border-bottom: 1px solid #000;
            }
            .header-logo img {
                height: 50px;
            }
            .institute-name {
                font-size: 20pt;
            }
            .routine-table th {
                background: #eee !important;
                color: black;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page {
                size: A4;
                margin: 1.5cm;
            }
        }
        @media (max-width: 768px) {
            .container {
                margin-left: 0;
                padding: 70px 15px 50px;
            }
            .side-nav {
                transform: translateX(-100%);
            }
            .toggle-arrow {
                left: 0;
            }
        }
    </style>
</head>
<body>

<!-- ========== TOP NAVBAR (no logo, only brand centered + logout) ========== -->
<div class="navbar">
    <div class="brand">AR TECH SOLUTION</div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<!-- ========== SIDEBAR (identical to dashboard) ========== -->
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
            <a href="completed_students.php">Course Complete</a>
            <a href="incomplete_students.php">Course Incomplete</a>
            <a href="blocked_students_list.php">Blocked student List</a>
            <a href="ongoing_students.php">Ongoing</a>
        </div>
    </div>
    <a href="delete.php">🗑️ Delete</a>
    <a href="report.php">📄 Report</a>
    <a href="bulk_email.php">✉️ Bulk Email</a>
    <div class="menu-group">
        <div class="menu-toggle">💵 Payment ▾</div>
        <div class="submenu">
            <a href="invoice.php">Print Invoice</a>
            <a href="view_invoice.php">Verify Invoice</a>
            <a href="input_payment.php">Add Payment</a>
            <a href="payment_due.php">Due Payment List</a>
            <a href="whatsapp_due.php">Send Whatsapp Message</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">🛠️ Services ▾</div>
        <div class="submenu">
            <a href="services.php">Manage Services</a>
            <a href="service_categories.php">Service Categories</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">📆 Attendance ▾</div>
        <div class="submenu">
            <a href="attendance.php">Take Attendance</a>
            <a href="attendance_report.php">View attendance Report</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">📜 Certificate ▾</div>
        <div class="submenu">
            <a href="upload_certificate.php">Upload Certificate</a>
            <a href="certificate_list.php">View Certificate</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">🎬 Video ▾</div>
        <div class="submenu">
            <a href="upload_video.php">Upload Video</a>
            <a href="view_videos.php">View Videos</a>
        </div>
    </div>
    <a href="routine_generator.php">🕒 Routine</a>
    <a href="rutine_form.php">🕒 Routine Form</a>
    <a href="account_info.php">🕒 Account</a>
</div>

<div class="toggle-arrow" id="toggleBtn">◀</div>

<!-- ========== MAIN CONTENT ========== -->
<div class="container" id="mainContent">
    <div class="report-card">
        <!-- Report header with logo on left -->
        <div class="report-header">
            <div class="header-logo">
                <img src="uploads/logo.png" alt="Institute Logo" onerror="this.style.display='none'">
            </div>
            <div class="header-text">
                <div class="institute-name">AR TECH SOLUTION</div>
                <div class="address">Address:South Khailkur,Shahid Siddique road ,Boardbazar, Gazipur-1704.</div>
                <div class="contact">📞 Mobile: +880 1957-288638 | ✉️ artechsolution.online@gmail.com</div>
            </div>
        </div>

        <!-- Search box (screen only) -->
        <div class="search-box">
            <h3>🔍 Search Student Routine by ID</h3>
            <form method="GET" class="search-form">
                <input type="text" name="student_id" placeholder="Enter Student ID (e.g., 250803)" value="<?= htmlspecialchars($student_id) ?>" required>
                <button type="submit">Load Routine</button>
            </form>
            <?php if ($error_message): ?>
                <div class="error-msg">❌ <?= $error_message ?></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($student_id) && $student): ?>
            <div class="student-info">
                <p><strong>Student Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                <p><strong>ID :</strong> <?= htmlspecialchars($student['student_id']) ?></p>
                <p><strong>Course :</strong> <?= htmlspecialchars($student['course_category']) ?></p>
            </div>

            <h3 style="text-align:center;">📖 Class Routine</h3>
            <table class="routine-table">
                <thead><tr><th>Day</th><th>Time</th></tr></thead>
                <tbody>
                    <?php foreach (['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'] as $day): ?>
                        <tr>
                            <td><?= $day ?></td>
                            <td><?= htmlspecialchars($routine_data[$day] ?? 'Close') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="note">
                This Routine can be changed if any emergency occurs. Students must have to follow this routine. 
                If course instructor does any changes in the routine, he will inform you.
            </div>
            <div class="signature">
                Md. Jahidul Hakim<br>
                <span>Instructor<br>AR Tech Solution</span>
            </div>

            <div class="print-btn">
                <button onclick="window.print()">🖨️ Print Routine (A4)</button>
            </div>
        <?php elseif (!empty($student_id) && !$student): ?>
            <div class="error-msg">❌ Student ID not found. Please try again.</div>
        <?php endif; ?>
    </div>
</div>

<div class="footer">
    &copy; <?= date("Y") ?> Freelancing Student Management System | All Rights Reserved
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