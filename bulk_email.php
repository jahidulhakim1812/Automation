<?php
// ====================================================
// PREVENT TIMEOUTS – allow long execution
// ====================================================
set_time_limit(0);               // No time limit
ignore_user_abort(true);          // Continue even if browser closes
ob_implicit_flush(true);          // Flush output automatically
while (ob_get_level() > 0) ob_end_clean(); // Clear output buffers

session_start();

// ✅ Admin session check
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

// ✅ Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// ✅ Database Connection
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
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $my_email;
        $mail->Password   = $app_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($my_email, 'AR Tech Admin');
        $mail->addAddress($to_email, $student_name);

        if ($attachment_tmp && $attachment_name) {
            $mail->addAttachment($attachment_tmp, $attachment_name);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ====================================================
// ✅ HANDLE FORM SUBMISSION (Bulk Email)
// ====================================================
$message_status = "";
$filter_category = "";
$progress_html = "";

if (isset($_POST['send_bulk_emails'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $filter_category = $conn->real_escape_string($_POST['filter_category'] ?? '');

    if (empty($subject) || empty($message)) {
        $message_status = "<div class='alert alert-error'>❌ Subject and message cannot be empty.</div>";
    } else {
        $attachment_tmp = null;
        $attachment_name = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $file_type = mime_content_type($_FILES['attachment']['tmp_name']);
            $file_size = $_FILES['attachment']['size'];
            $max_size = 25 * 1024 * 1024;

            if (!in_array($file_type, $allowed)) {
                $message_status = "<div class='alert alert-error'>❌ Invalid file type. Only JPG, PNG, GIF, PDF allowed.</div>";
            } elseif ($file_size > $max_size) {
                $message_status = "<div class='alert alert-error'>❌ File size exceeds 25 MB limit.</div>";
            } else {
                $attachment_tmp = $_FILES['attachment']['tmp_name'];
                $attachment_name = basename($_FILES['attachment']['name']);
            }
        }

        if (strpos($message_status, 'error') === false) {
            $sql = "SELECT name, email FROM students WHERE email IS NOT NULL AND email != ''";
            if (!empty($filter_category)) {
                $sql .= " AND course_category LIKE '%$filter_category%'";
            }
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                $total = $result->num_rows;
                $sent_count = 0;
                $fail_count = 0;

                echo "<div id='progress' style='background:rgba(255,209,102,0.15); border:1px solid #ffd166; padding:15px; margin-bottom:20px; border-radius:12px;'>";
                echo "📧 Sending emails... Please wait.<br>";
                echo "Total recipients: $total<br>";
                echo '<div id="counter">Sent: 0</div>';
                echo '<div id="failed" style="color:#ff6b6b;"></div>';
                echo "</div>";
                flush();

                while ($row = $result->fetch_assoc()) {
                    $personalized_body = "<p>Dear " . htmlspecialchars($row['name']) . ",</p>" . $message;
                    if (sendCustomEmail($row['email'], $row['name'], $subject, $personalized_body, $attachment_tmp, $attachment_name)) {
                        $sent_count++;
                    } else {
                        $fail_count++;
                    }
                    echo "<script>document.getElementById('counter').innerHTML = 'Sent: $sent_count (failed: $fail_count)';</script>";
                    flush();
                    usleep(500000);
                }

                $message_status = "<div class='alert alert-success'>✅ Bulk email completed: $sent_count successful, $fail_count failed.</div>";
            } else {
                $message_status = "<div class='alert alert-error'>❌ No students found with email addresses" . (!empty($filter_category) ? " in this category" : "") . ".</div>";
            }
        }
    }
}

// Fetch categories for dropdown
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bulk Email — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg: rgba(8,12,24,0.82);
    --glass: rgba(255,255,255,0.07);
    --glass-border: rgba(255,255,255,0.13);
    --glass-hover: rgba(255,255,255,0.13);
    --accent: #00e5c8;
    --accent2: #7b5ea7;
    --accent3: #ff6b6b;
    --accent4: #ffd166;
    --accent5: #06d6a0;
    --text: #e8eaf0;
    --muted: rgba(200,210,230,0.55);
    --card-radius: 18px;
    --sans: 'Plus Jakarta Sans', sans-serif;
    --mono: 'Space Grotesk', sans-serif;
    --nav-h: 64px;
    --sidebar-w: 230px;
    --shadow: 0 8px 32px rgba(0,0,0,0.35);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: var(--sans);
    color: var(--text);
    min-height: 100vh;
    background: url('uploads/banner.jpg') no-repeat center center fixed;
    background-size: cover;
    overflow-x: hidden;
}

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg,rgba(8,10,30,0.88) 0%,rgba(15,20,50,0.78) 50%,rgba(5,15,35,0.85) 100%);
    z-index: 0;
    pointer-events: none;
}

/* TOP NAV */
.topnav {
    position: fixed; top: 0; left: 0; right: 0; height: var(--nav-h);
    background: rgba(8,10,28,0.85);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid var(--glass-border);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 24px;
    z-index: 1100;
}
.topnav-brand {
    display: flex; align-items: center; gap: 12px;
    font-family: var(--mono); font-size: 18px; font-weight: 700;
    letter-spacing: 0.5px; color: #fff;
}
.topnav-brand span { color: var(--accent); }
.brand-dot { width: 8px; height: 8px; background: var(--accent); border-radius: 50%; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.4)} }
.topnav-right { display: flex; align-items: center; gap: 14px; }
.topnav-time { font-family: var(--mono); font-size: 13px; color: var(--muted); }
.logout-btn {
    background: linear-gradient(135deg,#e74c3c,#c0392b);
    color: #fff; padding: 7px 20px; border-radius: 40px;
    text-decoration: none; font-size: 13px; font-weight: 700;
    transition: opacity .2s; border: none; cursor: pointer;
}
.logout-btn:hover { opacity: .85; }
.hamburger {
    background: none; border: none; color: var(--text);
    font-size: 22px; cursor: pointer; display: none; padding: 4px;
}

/* SIDEBAR */
.sidebar {
    position: fixed; top: var(--nav-h); left: 0;
    width: var(--sidebar-w); height: calc(100vh - var(--nav-h));
    background: #08121e;
    border-right: 1px solid var(--glass-border);
    overflow-y: auto; overflow-x: hidden;
    z-index: 1050;
    transition: transform .3s cubic-bezier(.4,0,.2,1);
    padding-bottom: 40px;
}
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: var(--glass-border); border-radius: 4px; }
.sidebar.collapsed { transform: translateX(-100%); }
.sidebar a, .menu-toggle {
    display: flex; align-items: center; gap: 10px;
    color: var(--muted); text-decoration: none;
    padding: 11px 20px; font-size: 13.5px; font-weight: 500;
    border-left: 3px solid transparent;
    transition: all .2s; cursor: pointer; user-select: none;
    white-space: nowrap;
}
.sidebar a:hover, .menu-toggle:hover { color: #fff; background: var(--glass); border-left-color: var(--accent); }
.sidebar a.active { color: var(--accent); border-left-color: var(--accent); background: rgba(0,229,200,0.07); }
.submenu { display: none; flex-direction: column; background: rgba(0,0,0,0.2); }
.submenu a { padding: 9px 20px 9px 38px; font-size: 13px; }
.menu-group.open .submenu { display: flex; }
.menu-arrow { margin-left: auto; font-size: 11px; transition: transform .25s; }
.menu-group.open .menu-arrow { transform: rotate(180deg); }
.sidebar-divider { height: 1px; background: var(--glass-border); margin: 10px 16px; }

/* SIDEBAR TOGGLE PILL */
.sidebar-toggle-pill {
    position: fixed; top: calc(var(--nav-h) + 16px); left: var(--sidebar-w);
    width: 24px; height: 44px; background: var(--accent);
    border-radius: 0 10px 10px 0;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 1060; font-size: 13px; color: #000;
    font-weight: 900; transition: left .3s cubic-bezier(.4,0,.2,1), background .2s;
}
.sidebar-toggle-pill:hover { background: #00c9b0; }
.sidebar-toggle-pill.collapsed { left: 0; }

/* MAIN CONTENT */
.main {
    margin-left: var(--sidebar-w);
    padding: calc(var(--nav-h) + 24px) 24px 80px;
    position: relative; z-index: 1;
    transition: margin-left .3s cubic-bezier(.4,0,.2,1);
    min-height: 100vh;
}
.main.collapsed { margin-left: 0; }

/* SECTION TITLE */
.section-title {
    font-family: var(--mono); font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 2px; color: var(--accent);
    margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
}
.section-title::after { content: ''; flex: 1; height: 1px; background: var(--glass-border); }

/* FORM CARD */
.form-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 28px;
    max-width: 800px;
    margin: 0 auto;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--muted);
    margin-bottom: 6px;
}
.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 12px 14px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    color: var(--text);
    font-family: var(--sans);
    font-size: 14px;
    outline: none;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--accent);
    background: rgba(255,255,255,0.12);
}
.form-group textarea {
    min-height: 150px;
    resize: vertical;
}
.submit-btn {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    font-weight: 700;
    padding: 12px;
    border: none;
    border-radius: 40px;
    font-size: 16px;
    cursor: pointer;
    transition: opacity 0.2s;
    width: 100%;
}
.submit-btn:hover { opacity: 0.85; }
.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 14px;
}
.alert-success {
    background: rgba(6,214,160,0.15);
    border: 1px solid var(--accent5);
    color: var(--accent5);
}
.alert-error {
    background: rgba(255,107,107,0.15);
    border: 1px solid var(--accent3);
    color: var(--accent3);
}
.note {
    margin-top: 20px;
    padding: 12px;
    background: rgba(255,209,102,0.1);
    border-radius: 12px;
    font-size: 12px;
    color: var(--muted);
    text-align: center;
}

/* FOOTER */
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(6,8,20,0.9);
    backdrop-filter: blur(10px);
    border-top: 1px solid var(--glass-border);
    text-align: center;
    padding: 12px;
    font-size: 12.5px;
    color: var(--muted);
    z-index: 900;
}

/* RESPONSIVE */
@media (max-width: 700px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
}
</style>
</head>
<body>

<!-- TOP NAVIGATION -->
<nav class="topnav">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" id="hamburgerBtn">☰</button>
        <div class="topnav-brand">
            <div class="brand-dot"></div>
            <span>AR TECH</span> SOLUTION
        </div>
    </div>
    <div class="topnav-right">
        <div class="topnav-time" id="liveClock"></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<!-- SIDEBAR (modern dashboard) -->
<?php
include 'navigation.php';
?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="section-title">✉️ Bulk Email to Students</div>

    <?php if (!empty($message_status)) echo $message_status; ?>

    <div class="form-card">
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Filter by Course Category (optional)</label>
                <select name="filter_category">
                    <option value="">-- All Students (No Filter) --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($filter_category == $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" required placeholder="Enter email subject">
            </div>

            <div class="form-group">
                <label>Message (HTML allowed)</label>
                <textarea name="message" required placeholder="Write your greeting or offer here..."></textarea>
                <p style="font-size:11px; color:var(--muted); margin-top:5px;">The student's name will be automatically added at the beginning (e.g., "Dear [Name],").</p>
            </div>

            <div class="form-group">
                <label>Attach File (JPG, PNG, GIF, PDF up to 25 MB)</label>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf">
            </div>

            <button type="submit" name="send_bulk_emails" class="submit-btn">📧 Send to Selected Students</button>
        </form>

        <div class="note">
            <strong>Note:</strong> Sending many emails may take several minutes. The page will show live progress. 
            Please do not close the browser until finished. A 0.5-second delay is added between emails to avoid SMTP rate limits.
        </div>
    </div>
</main>

<div class="footer">
    &copy; <?php echo date("Y"); ?> AR TECH SOLUTION — Freelancing Student Management System
</div>

<script>
// Sidebar toggle (desktop)
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
const mainContent = document.getElementById('mainContent');

if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        toggleBtn.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
        toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
    });
}

// Hamburger (mobile)
const hamburger = document.getElementById('hamburgerBtn');
if (hamburger) {
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
    });
}

// Submenu toggles
document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const group = toggle.closest('.menu-group');
        if (group) group.classList.toggle('open');
    });
});

// Live clock
function updateClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
}
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>