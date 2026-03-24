<?php
session_start();

// ✅ 1. Admin session check
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

// ✅ 2. Load PHPMailer Library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Make sure the PHPMailer folder is in the same directory as this file
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// ✅ 3. Database Connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ====================================================
// ⚙️ EMAIL CONFIGURATION FUNCTION (SMTP)
// ====================================================
function sendDueEmail($to_email, $student_name, $due_amount) {
    // 🔴 ENTER YOUR GMAIL DETAILS HERE 🔴
    $my_email    = 'artechsolution.online@gmail.com'; 
    $app_password = 'giwr wrcr mnyi lkpf'; // Your 16-digit Google App Password

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $my_email;
        $mail->Password   = $app_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom($my_email, 'AR Tech Admin');
        $mail->addAddress($to_email, $student_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Reminder: Due Balance Notification';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif;'>
                <h3>Dear $student_name,</h3>
                <p>This is a reminder that you have a remaining balance of:</p>
                <h2 style='color:red;'>$due_amount TK</h2>
                <p>Please clear your dues to avoid course interruption.</p>
                <br>
                <p>Regards,<br>AR Tech Solution</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false; // Error message: $mail->ErrorInfo
    }
}

// ====================================================
// ✅ 4. HANDLE FORM SUBMISSIONS (Send Email)
// ====================================================
$message_status = "";

// A. Handle Single Email Send
if (isset($_POST['send_single_email'])) {
    $email = $_POST['s_email'];
    $name  = $_POST['s_name'];
    $due   = $_POST['s_due'];

    if (sendDueEmail($email, $name, $due)) {
        $message_status = "<div class='status-msg success'>✅ Email sent successfully to <b>$name</b>.</div>";
    } else {
        $message_status = "<div class='status-msg error'>❌ Failed to send email to <b>$name</b>. Check Internet or SMTP settings.</div>";
    }
}

// B. Handle Bulk Email Send
if (isset($_POST['send_bulk_emails'])) {
    $filter_cat = $conn->real_escape_string($_POST['filter_category']);
    
    $email_sql = "SELECT name, email, course_fee, paid_fee FROM students 
                  WHERE course_fee > paid_fee 
                  AND (course_status = 'ongoing' OR course_status = 'finished')";
    
    if (!empty($filter_cat)) {
        $email_sql .= " AND course_category LIKE '%$filter_cat%'";
    }

    $email_result = $conn->query($email_sql);
    $sent_count = 0;
    
    if ($email_result->num_rows > 0) {
        while ($row = $email_result->fetch_assoc()) {
            $due = $row['course_fee'] - $row['paid_fee'];
            if (sendDueEmail($row['email'], $row['name'], $due)) {
                $sent_count++;
            }
        }
        $message_status = "<div class='status-msg success'>✅ Bulk Action: Sent $sent_count emails successfully.</div>";
    }
}

// ====================================================
// ✅ 5. SEARCH & DISPLAY LOGIC
// ====================================================
$search_category = '';
$sql_condition = "WHERE course_fee > paid_fee AND (course_status = 'ongoing' OR course_status = 'finished')";

if (isset($_GET['category']) && !empty(trim($_GET['category']))) {
    $search_category = $conn->real_escape_string(trim($_GET['category']));
    $sql_condition .= " AND course_category LIKE '%$search_category%'";
}

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
    <title>Payment Due Students</title>
    <style>
         * { box-sizing: border-box; }
     body { font-family: Arial, sans-serif; margin: 0; background-color: #f5f5f5; }
    /* Navbar & Sidebar Styles */
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

    /* Content Styles */
    .container { margin-left: 240px; padding: 130px 30px 100px; transition: margin-left 0.3s; }
    .container.collapsed { margin-left: 30px; }

    h2 { text-align: center; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 14px; }
    th { background: #e74c3c; color: #fff; }
    tr:nth-child(even) { background: #f9f9f9; }

    /* Action Bar */
    .action-bar { display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; }
    .search-input { padding: 8px; width: 250px; border: 1px solid #ccc; }
    .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; color: white; font-weight: bold; }
    .btn-search { background: #2c3e50; }
    .btn-bulk { background: #d35400; }
    
    /* Individual Send Button */
    .btn-single { background: #27ae60; padding: 5px 10px; font-size: 12px; border-radius: 3px; cursor: pointer; color: white; border: none; }
    .btn-single:hover { background: #219150; }

    .status-msg { padding: 15px; margin-bottom: 20px; text-align: center; border-radius: 5px; font-weight: bold; }
    .status-msg.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .status-msg.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    .footer { background-color: #1a1a1a; color: white; text-align: center; padding: 15px; position: fixed; bottom: 0; left: 0; width: 100%; }
    </style>
</head>
<body>

<div class="navbar"><span>AR TECH SOLUTION</span><a href="logout.php" class="logout-btn">Logout</a></div>

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
            <a href="completed_students.php">Course Complete</a>
            <a href="incomplete_students.php">Course Incomplete</a>
            <a href="ongoing_students.php">Ongoing</a>
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
            <a href="payment_due.php">Due Payment List</a>
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
    <h2>💰 Payment Due List & Email Sender</h2>

    <?= $message_status ?>

    <div class="action-bar">
        <form method="get">
            <input type="text" name="category" class="search-input" placeholder="Filter by Category" value="<?= htmlspecialchars($search_category) ?>">
            <button type="submit" class="btn btn-search">🔍 Search</button>
        </form>

        <?php if (!empty($rows)): ?>
            <form method="post" onsubmit="return confirm('Send emails to ALL <?= count($rows) ?> students?');">
                <input type="hidden" name="filter_category" value="<?= htmlspecialchars($search_category) ?>">
                <button type="submit" name="send_bulk_emails" class="btn btn-bulk">📧 Email All Visible</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($rows)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Due Amount</th>
                    <th>Action</th> </tr>
            </thead>
            <tbody>
                <?php foreach($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['course_category']) ?></td>
                    <td><?= ucfirst($row['course_status']) ?></td>
                    <td style="color:red; font-weight:bold;"><?= $row['due_amount'] ?> TK</td>
                    
                    <td>
                        <form method="post">
                            <input type="hidden" name="s_email" value="<?= $row['email'] ?>">
                            <input type="hidden" name="s_name" value="<?= $row['name'] ?>">
                            <input type="hidden" name="s_due" value="<?= $row['due_amount'] ?>">
                            <button type="submit" name="send_single_email" class="btn-single">📩 Send Email</button>
                        </form>
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
    toggle.addEventListener('click', () => toggle.parentElement.classList.toggle('active'));
});
</script>

</body>
</html>