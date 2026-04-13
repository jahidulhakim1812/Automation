<?php
session_start();
$conn = new mysqli("localhost", "root", "", "freelancing");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch students
$students = $conn->query("SELECT student_id, name FROM students ORDER BY name ASC");

// Handle routine submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_routine"])) {
    $student_id = $_POST["student_id"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    $apply_all = isset($_POST["apply_all"]) ? true : false;

    if ($apply_all) {
        $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $inserted = 0;
        foreach ($days as $day) {
            $check = $conn->query("SELECT id FROM student_routine WHERE student_id = '$student_id' AND day = '$day'");
            if ($check->num_rows == 0) {
                $conn->query("INSERT INTO student_routine (student_id, day, start_time, end_time) VALUES ('$student_id', '$day', '$start_time', '$end_time')");
                $inserted++;
            }
        }
        $msg = $inserted > 0 ? "$inserted days added (skipped existing days)." : "All days already have routines.";
        echo "<script>alert('$msg'); window.location.href='routine_generator.php?student_id=$student_id';</script>";
    } else {
        $day = $_POST["day"];
        $check = $conn->query("SELECT id FROM student_routine WHERE student_id = '$student_id' AND day = '$day'");
        if ($check->num_rows > 0) {
            echo "<script>alert('Routine already exists for this day. Please delete it first.'); window.location.href='routine_generator.php?student_id=$student_id';</script>";
            exit;
        }
        $conn->query("INSERT INTO student_routine (student_id, day, start_time, end_time) VALUES ('$student_id', '$day', '$start_time', '$end_time')");
        echo "<script>alert('Routine added successfully'); window.location.href='routine_generator.php?student_id=$student_id';</script>";
    }
    exit;
}

// Handle delete
if (isset($_GET["delete_id"])) {
    $delete_id = intval($_GET["delete_id"]);
    $student_id = $_GET["student_id"];
    $conn->query("DELETE FROM student_routine WHERE id = $delete_id");
    echo "<script>alert('Routine deleted'); window.location.href='routine_generator.php?student_id=$student_id';</script>";
    exit;
}

$selected_student = $_GET["student_id"] ?? null;
$student_info = null;
$routine_data = [];

if ($selected_student) {
    $stmt = $conn->prepare("SELECT student_id, name, course_category FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $selected_student);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) $student_info = $result->fetch_assoc();
    $stmt->close();

    $stmt2 = $conn->prepare("SELECT id, day, start_time, end_time FROM student_routine WHERE student_id = ?");
    $stmt2->bind_param("s", $selected_student);
    $stmt2->execute();
    $rout_res = $stmt2->get_result();
    while ($row = $rout_res->fetch_assoc()) {
        $routine_data[$row["day"]] = [
            'id' => $row["id"],
            'time' => date("h:i A", strtotime($row["start_time"])) . " - " . date("h:i A", strtotime($row["end_time"]))
        ];
    }
    $stmt2->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routine Generator - AR TECH SOLUTION</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f7fa; }

        /* ========== NAVBAR (same as dashboard) ========== */
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 22px;
            font-weight: bold;
        }
        .navbar .brand {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
        }
        .logout-btn {
            position: absolute;
            right: 30px;
            background: #e74c3c;
            color: white;
            padding: 6px 18px;
            text-decoration: none;
            border-radius: 30px;
            font-size: 14px;
            font-weight: normal;
            transition: 0.2s;
        }
        .logout-btn:hover {
            background: #c0392b;
            transform: scale(1.02);
        }
        @media (max-width:600px) {
            .navbar { font-size: 16px; }
            .logout-btn { right: 10px; padding:5px 12px; font-size:12px; }
            .navbar .brand { font-size: 16px; }
        }

        /* ========== SIDEBAR (exactly like dashboard's navigation.php) ========== */
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
        .horizontal-submenu { flex-direction: row; flex-wrap: wrap; gap: 5px; }
        .horizontal-submenu a { padding: 10px 20px; white-space: nowrap; }

        /* Toggle button (identical to dashboard) */
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

        /* ========== MAIN CONTAINER - CENTERED (like dashboard but centered) ========== */
        .container {
            margin-left: 240px;
            padding: 100px 30px 80px;
            transition: margin-left 0.3s ease;
            display: flex;
            justify-content: center;
        }
        .container.collapsed { margin-left: 20px; }
        .content-wrapper {
            width: 100%;
            max-width: 900px;
        }

        /* Card styling */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        h2, h3 { color:#2c3e50; border-left:5px solid #1abc9c; padding-left:15px; margin-bottom:20px; }
        select, input, button {
            padding: 10px;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
            width: 100%;
            margin: 8px 0;
        }
        button {
            background: #1abc9c;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }
        button:hover { background: #16a085; }
        .back-btn button {
            background: #555;
            width: auto;
            padding: 8px 20px;
        }
        .back-btn button:hover { background: #333; }
        .delete-btn {
            background: #e74c3c;
            padding: 4px 10px;
            font-size: 12px;
            width: auto;
            margin-left: 10px;
        }
        .delete-btn:hover { background: #c0392b; }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            font-weight: normal;
        }
        .checkbox-label input {
            width: auto;
            margin: 0;
        }

        /* Routine Table */
        .routine-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .routine-table th, .routine-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .routine-table th {
            background: #1abc9c;
            color: white;
            font-weight: bold;
        }
        .routine-table tr:nth-child(even) { background: #f9f9f9; }
        .student-info {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .student-info p { margin: 5px 0; font-size: 16px; }
        .footer {
            background: #1a1a1a;
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
            .navbar, .side-nav, .toggle-arrow, .footer, .card:first-child { display: none; }
            .container { margin:0; padding:20px; justify-content: center; }
            .routine-table th { background: #ccc; color: black; }
        }
        @media (max-width: 768px) {
            .container { margin-left: 0; padding: 80px 15px 60px; }
            .side-nav { transform: translateX(-100%); }
            .toggle-arrow { left: 0; }
        }
    </style>
    <script>
        function setEndTime() {
            const startInput = document.getElementById('start_time');
            const endInput = document.getElementById('end_time');
            if (startInput.value) {
                let [hour, minute] = startInput.value.split(":").map(Number);
                hour = (hour + 1) % 24;
                endInput.value = hour.toString().padStart(2, '0') + ":" + minute.toString().padStart(2, '0');
            }
        }
        function toggleDaySelect() {
            const applyAll = document.getElementById('apply_all');
            const daySelect = document.getElementById('day_select');
            daySelect.disabled = applyAll.checked;
        }
    </script>
</head>
<body>

<!-- ========== NAVBAR (same as dashboard) ========== -->
<div class="navbar">
    <span class="brand">AR TECH SOLUTION</span>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<!-- ========== SIDEBAR (exact replica of dashboard's navigation.php) ========== -->
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

<div class="container" id="mainContent">
    <div class="content-wrapper">
        <div class="back-btn" style="margin-bottom:15px;">
            <a href="dashboard.php"><button type="button">🔙 Back to Dashboard</button></a>
        </div>

        <!-- Student selection card -->
        <div class="card">
            <h2>📅 Routine Generator</h2>
            <form method="GET">
                <label>Select Student:</label>
                <select name="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php while ($row = $students->fetch_assoc()): ?>
                        <option value="<?= $row["student_id"] ?>" <?= ($selected_student == $row["student_id"]) ? 'selected' : '' ?>>
                            <?= $row["student_id"] ?> - <?= htmlspecialchars($row["name"]) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">Load Student & Routine</button>
            </form>
        </div>

        <?php if ($selected_student && $student_info): ?>
            <!-- Add routine form with checkbox -->
            <div class="card">
                <h3>➕ Add Class Time for <?= htmlspecialchars($student_info["name"]) ?></h3>
                <form method="POST">
                    <input type="hidden" name="student_id" value="<?= $selected_student ?>">
                    
                    <label>Start Time:</label>
                    <input type="time" name="start_time" id="start_time" required onchange="setEndTime()">
                    
                    <label>End Time (auto 1 hour later):</label>
                    <input type="time" name="end_time" id="end_time" required readonly>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="apply_all" id="apply_all" onclick="toggleDaySelect()"> ✅ Apply to all days (Saturday to Friday)
                    </label>
                    
                    <div id="single_day_section">
                        <label>Day (if not applying to all):</label>
                        <select name="day" id="day_select">
                            <?php foreach (['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'] as $day): ?>
                                <option value="<?= $day ?>"><?= $day ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="submit_routine">➕ Add Routine</button>
                </form>
            </div>

            <!-- Display routine table -->
            <div class="card">
                <div class="student-info">
                    <p><strong>Student Name:</strong> <?= htmlspecialchars($student_info["name"]) ?></p>
                    <p><strong>ID:</strong> <?= htmlspecialchars($student_info["student_id"]) ?></p>
                    <p><strong>Course:</strong> <?= htmlspecialchars($student_info["course_category"]) ?></p>
                </div>
                <h3>📖 Class Routine</h3>
                <table class="routine-table">
                    <thead>
                        <tr><th>Day</th><th>Time</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $daysOfWeek = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                        foreach ($daysOfWeek as $day):
                            $hasRoutine = isset($routine_data[$day]);
                            $timeDisplay = $hasRoutine ? $routine_data[$day]['time'] : 'Close';
                            $deleteLink = $hasRoutine ? "<a href='?student_id=$selected_student&delete_id={$routine_data[$day]['id']}' onclick='return confirm(\"Delete this routine?\")'><button class='delete-btn' type='button'>🗑️ Delete</button></a>" : '';
                        ?>
                            <tr>
                                <td><?= $day ?></td>
                                <td><?= $timeDisplay ?></td>
                                <td><?= $deleteLink ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 20px; font-style: italic; font-size: 13px; color: #555;">
                    * This Routine can be changed if any emergency occurs. Students must have to follow this routine.<br>
                    If course instructor does any changes in the routine he will inform you.
                </p>
                <p style="margin-top: 15px; text-align: right; font-weight: bold;">
                    Md. Jahidul Hakim<br>Instructor<br>AR Tech Solution
                </p>
            </div>
        <?php elseif ($selected_student && !$student_info): ?>
            <div class="card" style="color:red; text-align:center;">
                ❌ Student ID not found. Please select a valid student.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="footer">
    &copy; <?= date("Y") ?> Freelancing Student Management System | All Rights Reserved
</div>

<script>
    // Sidebar toggle (identical to dashboard)
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
    // Initial toggle state for day select
    toggleDaySelect();
</script>
</body>
</html>