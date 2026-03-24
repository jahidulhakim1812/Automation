<?php
// ====================================================
// PREVENT TIMEOUTS – allow long execution
// ====================================================
set_time_limit(0);               // No time limit
ignore_user_abort(true);          // Continue even if browser closes
ob_implicit_flush(true);          // Flush output automatically
while (ob_get_level() > 0) ob_end_clean(); // Clear output buffers

session_start();

// ✅ 1. Admin session check
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

// ✅ 2. Load PHPMailer manually
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
function sendCustomEmail($to_email, $student_name, $subject, $body, $attachment_tmp = null, $attachment_name = null) {
    // 🔴 USE YOUR GMAIL DETAILS HERE 🔴
    $my_email    = 'artechsolution.online@gmail.com'; 
    $app_password = 'giwr wrcr mnyi lkpf'; // Your 16‑digit Google App Password

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

        // Attachment (if provided)
        if ($attachment_tmp && $attachment_name) {
            $mail->addAttachment($attachment_tmp, $attachment_name);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body; // Already personalized

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false; // error: $mail->ErrorInfo
    }
}

// ====================================================
// ✅ 4. HANDLE FORM SUBMISSION (Bulk Email)
// ====================================================
$message_status = "";
$filter_category = "";
$progress_html = ""; // Will hold live progress

if (isset($_POST['send_bulk_emails'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $filter_category = $conn->real_escape_string($_POST['filter_category'] ?? '');

    // Validate subject and message
    if (empty($subject) || empty($message)) {
        $message_status = "<div class='status-msg error'>❌ Subject and message cannot be empty.</div>";
    } else {
        // Handle file upload with 25 MB limit
        $attachment_tmp = null;
        $attachment_name = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $file_type = mime_content_type($_FILES['attachment']['tmp_name']);
            $file_size = $_FILES['attachment']['size'];
            $max_size = 25 * 1024 * 1024; // 25 MB

            if (!in_array($file_type, $allowed)) {
                $message_status = "<div class='status-msg error'>❌ Invalid file type. Only JPG, PNG, GIF, PDF allowed.</div>";
            } elseif ($file_size > $max_size) {
                $message_status = "<div class='status-msg error'>❌ File size exceeds 25 MB limit.</div>";
            } else {
                $attachment_tmp = $_FILES['attachment']['tmp_name'];
                $attachment_name = basename($_FILES['attachment']['name']);
            }
        }

        // If no file error, proceed
        if (strpos($message_status, 'error') === false) {
            // Build query: fetch all students with email (optionally filtered by category)
            $sql = "SELECT name, email FROM students WHERE email IS NOT NULL AND email != ''";
            if (!empty($filter_category)) {
                $sql .= " AND course_category LIKE '%$filter_category%'";
            }
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                $total = $result->num_rows;
                $sent_count = 0;
                $fail_count = 0;

                // Start progress output
                echo "<div id='progress' style='background:#fff3cd; padding:15px; margin-bottom:20px; border-radius:5px; font-weight:bold;'>";
                echo "📧 Sending emails... Please wait.<br>";
                echo "Total recipients: $total<br>";
                echo '<div id="counter">Sent: 0</div>';
                echo '<div id="failed" style="color:#721c24;"></div>';
                echo "</div>";
                flush();

                while ($row = $result->fetch_assoc()) {
                    // Prepend student name to message
                    $personalized_body = "<p>Dear " . htmlspecialchars($row['name']) . ",</p>" . $message;

                    if (sendCustomEmail($row['email'], $row['name'], $subject, $personalized_body, $attachment_tmp, $attachment_name)) {
                        $sent_count++;
                    } else {
                        $fail_count++;
                    }

                    // Update progress
                    echo "<script>document.getElementById('counter').innerHTML = 'Sent: $sent_count (failed: $fail_count)';</script>";
                    flush();

                    // Small delay to avoid hitting Gmail rate limits
                    usleep(500000); // 0.5 seconds
                }

                // Final message
                $message_status = "<div class='status-msg success'>✅ Bulk email completed: $sent_count successful, $fail_count failed.</div>";
            } else {
                $message_status = "<div class='status-msg error'>❌ No students found with email addresses" . (!empty($filter_category) ? " in this category" : "") . ".</div>";
            }
        }
    }
}

// ====================================================
// ✅ 5. FETCH CATEGORIES FOR DROPDOWN
// ====================================================
$cat_sql = "SELECT DISTINCT course_category FROM students WHERE course_category IS NOT NULL AND course_category != '' ORDER BY course_category";
$cat_result = $conn->query($cat_sql);
$categories = [];
if ($cat_result) {
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat['course_category'];
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Email - AR Tech</title>
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

        /* Form Styles */
        .email-form { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: bold; display: block; margin-bottom: 5px; }
        .form-group input[type="text"], .form-group textarea, .form-group select, .form-group input[type="file"] {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;
        }
        .form-group textarea { min-height: 150px; resize: vertical; }

        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; color: white; }
        .btn-primary { background: #27ae60; }
        .btn-primary:hover { background: #219150; }

        .status-msg { padding: 15px; margin-bottom: 20px; text-align: center; border-radius: 5px; font-weight: bold; }
        .status-msg.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-msg.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .footer { background-color: #1a1a1a; color: white; text-align: center; padding: 15px; position: fixed; bottom: 0; left: 0; width: 100%; }

        .php-note { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 10px; border-radius: 4px; margin-top: 20px; font-size: 13px; }
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
            <a href="bulk_email.php">✉️ Bulk Email</a>
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
    <h2>✉️ Send Bulk Email to Students</h2>

    <?= $message_status ?>

    <div class="email-form">
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="filter_category">Filter by Course Category (optional):</label>
                <select name="filter_category" id="filter_category">
                    <option value="">-- All Students (No Filter) --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= ($filter_category == $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" name="subject" id="subject" required placeholder="Enter email subject">
            </div>

            <div class="form-group">
                <label for="message">Message (HTML allowed):</label>
                <textarea name="message" id="message" required placeholder="Write your greeting or offer here..."></textarea>
                <p style="font-size:12px; color:#777;">The student's name will be automatically added at the beginning (e.g., "Dear [Name],").</p>
            </div>

            <div class="form-group">
                <label for="attachment">Attach Photo or File (JPG, PNG, GIF, PDF up to <strong>25 MB</strong>):</label>
                <input type="file" name="attachment" id="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf">
            </div>

            <button type="submit" name="send_bulk_emails" class="btn btn-primary">📧 Send to Selected Students</button>
        </form>

        <div class="php-note">
            <strong>Note:</strong> Sending many emails may take several minutes. The page will show live progress. 
            Please do not close the browser until finished. A 0.5-second delay is added between emails to avoid SMTP rate limits.
        </div>
    </div>
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