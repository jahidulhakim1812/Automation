<?php
session_start();

// ✅ 1. Admin session check
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

// ✅ 2. Database Connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ====================================================
// ✅ 3. Helper function to format phone number for WhatsApp (Bangladesh)
// ====================================================
function formatWhatsAppNumber($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // If it starts with '0', remove the leading zero and add 880
    if (substr($phone, 0, 1) === '0') {
        $phone = '880' . substr($phone, 1);
    }
    // If it doesn't start with 880, assume local and add 880
    elseif (substr($phone, 0, 3) !== '880') {
        $phone = '880' . $phone;
    }
    return $phone;
}

// ====================================================
// ✅ 4. Handle category filter
// ====================================================
$search_category = '';
$sql_condition = "WHERE course_fee > paid_fee AND (course_status = 'ongoing' OR course_status = 'finished')";

if (isset($_GET['category']) && !empty(trim($_GET['category']))) {
    $search_category = $conn->real_escape_string(trim($_GET['category']));
    $sql_condition .= " AND course_category LIKE '%$search_category%'";
}

// ====================================================
// ✅ 5. Fetch students with due
// ====================================================
$sql = "SELECT student_id, name, email, phone_number, course_category, course_fee, paid_fee, course_status
        FROM students $sql_condition
        ORDER BY name ASC";

$result = $conn->query($sql);
$rows = [];
$total_due = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['due_amount'] = $row['course_fee'] - $row['paid_fee'];
        $total_due += $row['due_amount'];
        $rows[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WhatsApp Due Reminder</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f5f5f5; }
        .navbar { background-color: #1a1a1a; color: white; padding: 15px 30px; font-size: 22px; display: flex; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
        .logout-btn { position: absolute; right: 10px; background: #c0392b; color: white; padding: 8px 15px; text-decoration: none; border-radius: 25px; font-size: 15px; }

        .side-nav { position: fixed; top: 60px; left: 0; width: 220px; height: calc(100% - 60px); background-color: #2c3e50; padding-top: 20px; z-index: 999; display: flex; flex-direction: column; overflow-y: auto; transition: transform 0.3s; }
        .side-nav.collapsed { transform: translateX(-220px); }
        .side-nav a, .menu-toggle { color: white; text-decoration: none; padding: 12px 25px; width: 100%; font-weight: bold; cursor: pointer; display:block; }
        .side-nav a:hover, .menu-toggle:hover { background-color: #34495e; border-left: 4px solid #1abc9c; }
        .menu-group { width: 100%; }
        .submenu { display: none; flex-direction: column; background-color: #34495e; }
        .submenu a { font-weight: normal; padding-left: 40px; }
        .menu-group.active .submenu { display: flex; }

        .toggle-arrow { position: fixed; top: 70px; left: 220px; background-color: #1abc9c; color: white; padding: 6px 10px; cursor: pointer; z-index: 1001; transition: left 0.3s; }
        .toggle-arrow.collapsed { left: 0; }

        .container { margin-left: 240px; padding: 130px 30px 100px; transition: margin-left 0.3s; }
        .container.collapsed { margin-left: 30px; }

        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 14px; }
        th { background: #e74c3c; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }

        .action-bar { display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .search-input { padding: 8px; width: 250px; border: 1px solid #ccc; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; color: white; font-weight: bold; }
        .btn-search { background: #2c3e50; }
        .btn-wa { background: #25D366; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; display: inline-block; font-weight: bold; border: none; cursor: pointer; }
        .btn-wa:hover { background: #128C7E; }
        .btn-bulk-wa { background: #075e54; }

        .footer { background-color: #1a1a1a; color: white; text-align: center; padding: 15px; position: fixed; bottom: 0; left: 0; width: 100%; }
        .wa-note { background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 15px 0; text-align: center; }
        .checkbox-col { width: 30px; }
        .select-all-bar { display: flex; align-items: center; gap: 10px; margin: 10px 0; }
    </style>
</head>
<body>

<div class="navbar"><span>AR TECH SOLUTION</span><a href="logout.php" class="logout-btn">Logout</a></div>

<!-- Sidebar -->
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
            <a href="ongoing_students.php">Ongoing</a>
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
            <a href="payment_due.php">Due Payment List</a>
            <a href="whatsapp_due.php"><strong>📱 WhatsApp Due Reminder</strong></a>
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
    <a href="account_info.php">🕒 Account</a>
</div>

<div class="toggle-arrow" id="toggleBtn">◀</div>

<div class="container" id="mainContent">
    <h2>📱 Send WhatsApp Reminder (Due Payments)</h2>
    <p class="wa-note">
        ⚡ Click the <strong>WhatsApp button</strong> to open a pre‑written message with full invoice details.
        Use the checkboxes to select specific students and click <strong>Send to Selected</strong>.
    </p>

    <!-- Filter and Bulk Actions -->
    <div class="action-bar">
        <form method="get">
            <input type="text" name="category" class="search-input" placeholder="Filter by Category" value="<?= htmlspecialchars($search_category) ?>">
            <button type="submit" class="btn btn-search">🔍 Search</button>
        </form>
        <a href="whatsapp_due.php" class="btn btn-search">Reset</a>
        <button class="btn btn-wa btn-bulk-wa" id="sendSelectedBtn">📤 Send to Selected</button>
        <button class="btn btn-wa" id="sendAllBtn">📤 Send to All Visible</button>
    </div>

    <?php if (!empty($rows)): ?>
        <div class="select-all-bar">
            <label>
                <input type="checkbox" id="selectAllCheckbox"> Select All
            </label>
        </div>

        <table id="studentTable">
            <thead>
                <tr>
                    <th class="checkbox-col">Select</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Due Amount</th>
                    <th>WhatsApp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rows as $index => $row): 
                    $raw_phone = $row['phone_number'] ?? '';
                    $wa_phone = formatWhatsAppNumber($raw_phone);
                    
                    // Build detailed message with invoice info
                    $student_name = $row['name'];
                    $course_name  = $row['course_category'];
                    $total_fee    = $row['course_fee'];
                    $paid_fee     = $row['paid_fee'];
                    $due          = $row['due_amount'];
                    
                    $message = "Dear {$student_name},\n\n";
                    $message .= "This is a reminder that your account has been *blocked* due to non‑payment.\n\n";
                    $message .= "📌 *Invoice Details:*\n";
                    $message .= "• Course: {$course_name}\n";
                    $message .= "• Total Fee: {$total_fee} TK\n";
                    $message .= "• Paid: {$paid_fee} TK\n";
                    $message .= "• Due: {$due} TK\n\n";
                    $message .= "Please clear the due amount immediately to reactivate your account.\n\n";
                    $message .= "Thank you for your prompt attention.\n";
                    $message .= "AR Tech Solution";
                    
                    $encoded_message = urlencode($message);
                    $wa_link = "https://wa.me/{$wa_phone}?text={$encoded_message}";
                ?>
                <tr data-wa-link="<?= htmlspecialchars($wa_link) ?>" data-phone="<?= htmlspecialchars($raw_phone) ?>">
                    <td class="checkbox-col">
                        <input type="checkbox" class="student-checkbox" data-index="<?= $index ?>">
                    </td>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($raw_phone) ?></td>
                    <td><?= htmlspecialchars($row['course_category']) ?></td>
                    <td><?= ucfirst($row['course_status']) ?></td>
                    <td style="color:red; font-weight:bold;"><?= $row['due_amount'] ?> TK</td>
                    <td>
                        <?php if (!empty($raw_phone)): ?>
                            <a href="<?= $wa_link ?>" target="_blank" class="btn-wa" style="padding:5px 8px; font-size:12px;">📱 Send</a>
                        <?php else: ?>
                            <span style="color:gray;">No phone</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3 style="text-align:right; color:#c0392b;">Total Due: <?= number_format($total_due) ?> TK</h3>
    <?php else: ?>
        <p style="text-align:center;">✅ No payment due students found.</p>
    <?php endif; ?>
</div>

<div class="footer">&copy; <?= date("Y") ?> Freelancing Management System</div>

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

// Submenu toggle
document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', () => toggle.parentElement.classList.toggle('active'));
});

// Select All checkbox
const selectAllCheckbox = document.getElementById('selectAllCheckbox');
const studentCheckboxes = document.querySelectorAll('.student-checkbox');

if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        studentCheckboxes.forEach(cb => cb.checked = this.checked);
    });
}

// Function to open WhatsApp links with delay
function openWhatsAppLinks(links) {
    if (links.length === 0) {
        alert('No valid phone numbers selected.');
        return;
    }
    const delay = 1500; // 1.5 seconds between tabs to help browser not block
    links.forEach((link, index) => {
        setTimeout(() => {
            window.open(link, '_blank');
        }, index * delay);
    });
}

// Send to Selected
document.getElementById('sendSelectedBtn')?.addEventListener('click', function() {
    const selectedRows = Array.from(document.querySelectorAll('.student-checkbox:checked'))
        .map(cb => cb.closest('tr'))
        .filter(tr => tr.dataset.waLink && tr.dataset.phone.trim() !== '');
    
    const links = selectedRows.map(tr => tr.dataset.waLink);
    if (links.length === 0) {
        alert('No students selected or selected students have no phone number.');
        return;
    }
    if (confirm(`Send WhatsApp messages to ${links.length} selected student(s)?`)) {
        openWhatsAppLinks(links);
    }
});

// Send to All Visible
document.getElementById('sendAllBtn')?.addEventListener('click', function() {
    const allRows = Array.from(document.querySelectorAll('#studentTable tbody tr'))
        .filter(tr => tr.dataset.waLink && tr.dataset.phone.trim() !== '');
    
    const links = allRows.map(tr => tr.dataset.waLink);
    if (links.length === 0) {
        alert('No students with valid phone numbers found.');
        return;
    }
    if (confirm(`Send WhatsApp messages to ALL ${links.length} visible student(s)?`)) {
        openWhatsAppLinks(links);
    }
});
</script>

</body>
</html>