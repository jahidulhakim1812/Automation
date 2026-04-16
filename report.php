<?php
session_start();

// Admin session check
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function: Send block notification email
function sendBlockEmail($to_email, $student_name) {
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
        return false;
    }
}

// Handle Actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["student_id"]) && isset($_POST["new_paid_fee"])) {
    $student_id = $conn->real_escape_string($_POST["student_id"]);
    $new_paid_fee = floatval($_POST["new_paid_fee"]);
    $conn->query("UPDATE students SET paid_fee = '$new_paid_fee', last_updated = NOW() WHERE student_id = '$student_id'");
    echo "<script>alert('Fee updated successfully!'); window.location.href='report.php';</script>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_student_id"])) {
    $delete_id = $conn->real_escape_string($_POST["delete_student_id"]);
    $conn->query("DELETE FROM students WHERE student_id = '$delete_id'");
    echo "<script>alert('Student deleted successfully!'); window.location.href='report.php';</script>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["set_course_status"])) {
    $student_id = $conn->real_escape_string($_POST["student_id"]);
    $new_status = $conn->real_escape_string($_POST["set_course_status"]);
    $conn->query("UPDATE students SET course_status='$new_status', last_updated=NOW() WHERE student_id='$student_id'");
    echo "<script>alert('Course status updated to $new_status!'); window.location.href='report.php';</script>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["block_student_id"])) {
    $student_id = $conn->real_escape_string($_POST["block_student_id"]);
    $info = $conn->query("SELECT name, email FROM students WHERE student_id = '$student_id'");
    if ($info && $info->num_rows > 0) {
        $student = $info->fetch_assoc();
        $student_name = $student['name'];
        $student_email = $student['email'];
        $conn->query("UPDATE students SET is_blocked = 1, last_updated = NOW() WHERE student_id = '$student_id'");
        if (!empty($student_email)) {
            if (sendBlockEmail($student_email, $student_name)) {
                echo "<script>alert('Student blocked and email sent successfully!');</script>";
            } else {
                echo "<script>alert('Student blocked but email could not be sent.');</script>";
            }
        } else {
            echo "<script>alert('Student blocked, but no email address found.');</script>";
        }
    } else {
        echo "<script>alert('Student not found.');</script>";
    }
    echo "<script>window.location.href='report.php';</script>";
}

// Fetch records
$sql = "SELECT student_id, name, email, course_fee, paid_fee, 
               (course_fee - paid_fee) AS due_fee, last_updated, course_status, is_blocked
        FROM students 
        ORDER BY last_updated DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Account Report — AR TECH SOLUTION</title>
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

/* TABLE CARD */
.table-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 20px;
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
th, td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid var(--glass-border);
    vertical-align: middle;
}
th {
    background: rgba(0,0,0,0.3);
    color: var(--accent);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 11px;
}
td {
    color: var(--text);
}
tr:hover td {
    background: rgba(255,255,255,0.03);
}
input, select, button {
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid var(--glass-border);
    background: rgba(255,255,255,0.08);
    color: var(--text);
    font-size: 12px;
    outline: none;
}
input:focus, select:focus {
    border-color: var(--accent);
}
button {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
    border: none;
}
button:hover { opacity: 0.85; }
.delete-btn { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; }
.edit-btn { background: linear-gradient(135deg, #3498db, #2980b9); color: white; }
.print-btn { background: linear-gradient(135deg, #27ae60, #219150); color: white; }
.block-btn { background: linear-gradient(135deg, #ff9800, #f39c12); color: white; }
.status-blocked { color: #ff6b6b; font-weight: bold; }

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
    th, td { padding: 8px 6px; font-size: 11px; }
    input, select, button { padding: 4px 6px; font-size: 10px; }
}

@media print {
    .topnav, .sidebar, .sidebar-toggle-pill, .footer, button, select, .block-btn, .delete-btn, .edit-btn, .print-btn {
        display: none !important;
    }
    .main { margin: 0; padding: 0; background: white; }
    .table-card { background: white; backdrop-filter: none; border: none; padding: 0; }
    th { background: #ddd; color: black; }
    td { color: black; }
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
    <div class="section-title">📄 Student Account Report</div>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th><th>Name</th><th>Email</th><th>Total Fee</th><th>Paid Fee</th>
                        <th>Due Fee</th><th>Payment Date</th><th>Course Status</th><th>Block Status</th>
                        <th>Update Fee</th><th>Delete</th><th>Edit</th><th>Print</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $sid = htmlspecialchars($row["student_id"]);
                        $email = htmlspecialchars($row["email"]);
                        $is_blocked = $row["is_blocked"];
                    ?>
                        <tr>
                            <td><?php echo $sid; ?></td>
                            <td><?php echo htmlspecialchars($row["name"]); ?></td>
                            <td><?php echo $email; ?></td>
                            <td>৳ <?php echo number_format($row["course_fee"], 2); ?></td>
                            <td>৳ <?php echo number_format($row["paid_fee"], 2); ?></td>
                            <td>৳ <?php echo number_format($row["due_fee"], 2); ?></td>
                            <td><?php echo htmlspecialchars($row["last_updated"]); ?></td>
                            <td>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="student_id" value="<?php echo $sid; ?>">
                                    <select name="set_course_status" onchange="this.form.submit()">
                                        <option value="ongoing" <?php echo ($row["course_status"] == "ongoing") ? "selected" : ""; ?>>Ongoing</option>
                                        <option value="finished" <?php echo ($row["course_status"] == "finished") ? "selected" : ""; ?>>Finished</option>
                                        <option value="incomplete" <?php echo ($row["course_status"] == "incomplete") ? "selected" : ""; ?>>Incomplete</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <?php if ($is_blocked == 1): ?>
                                    <span class="status-blocked">Blocked</span>
                                <?php else: ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to block this student? An email notification will be sent.');" style="margin:0;">
                                        <input type="hidden" name="block_student_id" value="<?php echo $sid; ?>">
                                        <button type="submit" class="block-btn">Block</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="student_id" value="<?php echo $sid; ?>">
                                    <input type="number" name="new_paid_fee" step="0.01" placeholder="Amount" required style="width:100px;">
                                    <button type="submit">Update</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this student permanently?');" style="margin:0;">
                                    <input type="hidden" name="delete_student_id" value="<?php echo $sid; ?>">
                                    <button type="submit" class="delete-btn">Delete</button>
                                </form>
                            </td>
                            <td>
                                <form method="GET" action="edit.php" style="margin:0;">
                                    <input type="hidden" name="student_id" value="<?php echo $sid; ?>">
                                    <button type="submit" class="edit-btn">Edit</button>
                                </form>
                            </td>
                            <td>
                                <form method="GET" action="print.php" target="_blank" style="margin:0;">
                                    <input type="hidden" name="student_id" value="<?php echo $sid; ?>">
                                    <button type="submit" class="print-btn">Print</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="13" style="text-align:center;">No student records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
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