<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Email sending function (reusable)
function sendNoticeEmail($to_email, $to_name, $subject, $body, $attachment_tmp = null, $attachment_name = null) {
    $my_email    = 'artechsolution.online@gmail.com';
    $app_password = 'giwr wrcr mnyi lkpf'; // Your 16-digit Google App Password

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
        $mail->addAddress($to_email, $to_name);

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

$message = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['send_notice'])) {
    $recipient_type = $_POST['recipient_type'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $message_body = trim($_POST['message'] ?? '');
    $category_filter = trim($_POST['category_filter'] ?? '');
    $attachment_tmp = null;
    $attachment_name = null;

    if (empty($subject) || empty($message_body)) {
        $error = "Subject and message cannot be empty.";
    } elseif (!in_array($recipient_type, ['students', 'customers', 'both'])) {
        $error = "Please select a valid recipient type.";
    } else {
        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $file_type = mime_content_type($_FILES['attachment']['tmp_name']);
            $file_size = $_FILES['attachment']['size'];
            $max_size = 25 * 1024 * 1024; // 25 MB

            if (!in_array($file_type, $allowed)) {
                $error = "Invalid file type. Only JPG, PNG, GIF, PDF allowed.";
            } elseif ($file_size > $max_size) {
                $error = "File size exceeds 25 MB limit.";
            } else {
                $attachment_tmp = $_FILES['attachment']['tmp_name'];
                $attachment_name = basename($_FILES['attachment']['name']);
            }
        }

        if (empty($error)) {
            // Build recipient list
            $recipients = [];
            $students_found = false;
            $customers_found = false;

            // Fetch students
            if ($recipient_type === 'students' || $recipient_type === 'both') {
                $sql = "SELECT name, email FROM students WHERE email IS NOT NULL AND email != ''";
                if (!empty($category_filter)) {
                    $category_filter = $conn->real_escape_string($category_filter);
                    $sql .= " AND course_category LIKE '%$category_filter%'";
                }
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $recipients[] = ['type' => 'Student', 'name' => $row['name'], 'email' => $row['email']];
                    }
                    $students_found = true;
                }
            }

            // Fetch customers
            if ($recipient_type === 'customers' || $recipient_type === 'both') {
                $sql = "SELECT name, email FROM customers WHERE email IS NOT NULL AND email != ''";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $recipients[] = ['type' => 'Customer', 'name' => $row['name'], 'email' => $row['email']];
                    }
                    $customers_found = true;
                }
            }

            if (empty($recipients)) {
                $error = "No recipients found with valid email addresses.";
            } else {
                // Start sending
                $total = count($recipients);
                $sent_count = 0;
                $fail_count = 0;

                // Show progress UI
                echo "<!DOCTYPE html><html><head><title>Sending Notices</title><style>
                        body{font-family:Arial;background:#1e2a3a;color:#fff;text-align:center;padding:50px;}
                        .progress-box{background:rgba(255,255,255,0.1);padding:20px;border-radius:10px;max-width:500px;margin:auto;}
                        .counter{font-size:24px;margin:15px 0;color:#00e5c8;}
                        </style></head><body>
                        <div class='progress-box'>
                        <h2>📧 Sending Notices</h2>
                        <p>Total recipients: <strong>$total</strong></p>
                        <div class='counter' id='counter'>Sent: 0 (Failed: 0)</div>
                        </div>
                        <script>
                        function updateCounter(sent, failed) {
                            document.getElementById('counter').innerHTML = 'Sent: ' + sent + ' (Failed: ' + failed + ')';
                        }
                        </script>";
                flush();

                foreach ($recipients as $recipient) {
                    $personalized_body = "<p>Dear " . htmlspecialchars($recipient['name']) . ",</p>" . $message_body;
                    if (sendNoticeEmail($recipient['email'], $recipient['name'], $subject, $personalized_body, $attachment_tmp, $attachment_name)) {
                        $sent_count++;
                    } else {
                        $fail_count++;
                    }
                    echo "<script>updateCounter($sent_count, $fail_count);</script>";
                    flush();
                    usleep(300000); // 0.3 sec delay
                }

                echo "<script>alert('✅ Notice sent: $sent_count successful, $fail_count failed.'); window.location.href='notices.php';</script>";
                exit;
            }
        }
    }
}

// Fetch course categories for filter dropdown
$courses = $conn->query("SELECT DISTINCT course_category FROM students WHERE course_category IS NOT NULL AND course_category != '' ORDER BY course_category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Notices — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    body {
        background-image: url('<?php echo $bg_image; ?>');
        background-size: cover;
        background-attachment: fixed;
        background-position: center;
        font-family: var(--sans);
    }
    body.dark-mode {
        --bg: rgba(0,0,0,0.9);
        --glass: rgba(0,0,0,0.5);
        --glass-border: rgba(255,255,255,0.1);
        --text: #e0e0e0;
    }
    body.dark-mode::before {
        background: rgba(0,0,0,0.85);
    }
</style>
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
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg,rgba(8,10,30,0.88) 0%,rgba(15,20,50,0.78) 50%,rgba(5,15,35,0.85) 100%);
    z-index: 0;
    pointer-events: none;
}
/* TOP NAV (same as dashboard) */
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
    transition: opacity .2s;
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
    overflow-y: auto;
    z-index: 1050;
    transition: transform .3s cubic-bezier(.4,0,.2,1);
    padding-bottom: 40px;
}
.sidebar.collapsed { transform: translateX(-100%); }
.sidebar a, .menu-toggle {
    display: flex; align-items: center; gap: 10px;
    color: var(--muted); text-decoration: none;
    padding: 11px 20px; font-size: 13.5px; font-weight: 500;
    border-left: 3px solid transparent;
    transition: all .2s;
    cursor: pointer;
}
.sidebar a:hover, .menu-toggle:hover { color: #fff; background: var(--glass); border-left-color: var(--accent); }
.sidebar a.active { color: var(--accent); border-left-color: var(--accent); background: rgba(0,229,200,0.07); }
.submenu { display: none; flex-direction: column; background: rgba(0,0,0,0.2); }
.submenu a { padding: 9px 20px 9px 38px; font-size: 13px; }
.menu-group.open .submenu { display: flex; }
.menu-arrow { margin-left: auto; font-size: 11px; transition: transform .25s; }
.menu-group.open .menu-arrow { transform: rotate(180deg); }
.sidebar-divider { height: 1px; background: var(--glass-border); margin: 10px 16px; }
.sidebar-toggle-pill {
    position: fixed; top: calc(var(--nav-h) + 16px); left: var(--sidebar-w);
    width: 24px; height: 44px; background: var(--accent);
    border-radius: 0 10px 10px 0;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 1060; font-size: 13px; color: #000;
    font-weight: 900; transition: left .3s cubic-bezier(.4,0,.2,1);
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
    display: flex;
    justify-content: center;
}
.main.collapsed { margin-left: 0; }
/* FORM CARD */
.form-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 32px;
    max-width: 700px;
    width: 100%;
}
.form-card h2 {
    font-family: var(--mono);
    font-size: 22px;
    color: var(--accent);
    margin-bottom: 24px;
    text-align: center;
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
.form-group input[type="file"] {
    padding: 8px;
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
    width: 100%;
}
.submit-btn:hover { opacity: 0.85; }
.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
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
@media (max-width: 700px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
    .form-card { padding: 20px; }
}
</style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

<nav class="topnav">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" id="hamburgerBtn">☰</button>
        <div class="topnav-brand"><div class="brand-dot"></div><span>AR TECH</span> SOLUTION</div>
    </div>
    <div class="topnav-right">
        <div class="topnav-time" id="liveClock"></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<?php include 'navigation.php'; ?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<main class="main" id="mainContent">
    <div class="form-card">
        <h2>📢 Send Email Notices</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Recipients</label>
                <select name="recipient_type" required onchange="toggleCategoryFilter(this.value)">
                    <option value="">-- Select --</option>
                    <option value="students">📚 Students Only</option>
                    <option value="customers">👥 Customers Only</option>
                    <option value="both">🌍 Both Students & Customers</option>
                </select>
            </div>
            <div class="form-group" id="categoryGroup" style="display:none;">
                <label>Filter Students by Course (optional)</label>
                <select name="category_filter">
                    <option value="">-- All Courses --</option>
                    <?php if ($courses && $courses->num_rows > 0): ?>
                        <?php while ($cat = $courses->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($cat['course_category']); ?>"><?php echo htmlspecialchars($cat['course_category']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" required placeholder="Notice subject">
            </div>
            <div class="form-group">
                <label>Message (HTML allowed)</label>
                <textarea name="message" required placeholder="Write your notice here... Use HTML for formatting."></textarea>
                <small style="color:var(--muted);">The student/customer name will be automatically added at the beginning.</small>
            </div>
            <div class="form-group">
                <label>Attachment (Optional, max 25 MB)</label>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf">
            </div>
            <button type="submit" name="send_notice" class="submit-btn">📧 Send Notice</button>
        </form>
    </div>
</main>

<div class="footer">&copy; <?php echo date("Y"); ?> AR TECH SOLUTION — Freelancing Student Management System</div>

<script>
// Sidebar toggle
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
const hamburger = document.getElementById('hamburgerBtn');
if (hamburger) {
    hamburger.addEventListener('click', () => sidebar.classList.toggle('mobile-open'));
}
document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const group = toggle.closest('.menu-group');
        if (group) group.classList.toggle('open');
    });
});
function updateClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}
updateClock();
setInterval(updateClock, 1000);

function toggleCategoryFilter(val) {
    const catGroup = document.getElementById('categoryGroup');
    if (val === 'students' || val === 'both') {
        catGroup.style.display = 'block';
    } else {
        catGroup.style.display = 'none';
    }
}
</script>
</body>
</html>